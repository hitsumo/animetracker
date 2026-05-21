<?php

/**
 * Anime Tracker - Quick Watched-Episode Update Endpoint (0.5.5 / 0.5.6)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by index.php when the user clicks the "+" or
 * "-" button in the "Izlenen Bolum" cell of the list view. Adjusts
 * animes.watched_episodes by +1 or -1 without opening the full
 * "Duzenle" form.
 *
 * Why a dedicated endpoint instead of reusing edit_anime.php:
 *   edit_anime.php's POST handler rewrites ~26 columns at once. Sending
 *   a one-field delta through it would mean replaying the entire form
 *   payload. A focused endpoint that touches only watched_episodes is
 *   simpler, safer, and keeps the list-view interaction snappy. This
 *   mirrors fetch_aired_episodes.php (0.5.x), which is the same AJAX
 *   shape: POST + csrf_token + anime_id, JSON reply.
 *
 * Bound enforcement (server side - JS does the same, this is the
 * authoritative copy; see proje_durumu_07 Bolum 3 kararlari):
 *   - Lower bound: watched_episodes can never go below 0. A "-" that
 *     would push it negative is rejected.
 *   - Upper bound (tavan): if total_episodes is set, the ceiling is
 *     total_episodes; otherwise it is aired_episodes. A "+" that would
 *     exceed the ceiling is rejected.
 *   - If BOTH total_episodes and aired_episodes are empty there is no
 *     known ceiling. In that case "+" is refused here and the buttons
 *     are hidden/disabled client side - the user is told to sync /
 *     enter episode data first. "-" still works down to 0.
 *
 * 0.5.6 - watch_status one-way automation (only on delta=+1):
 *   Rule 1: if current watch_status is 'Izlenme Planlandi', + flips it
 *           to 'Izleniyor'. The act of incrementing is treated as a
 *           "started watching" signal. Also handles the "paused -> back"
 *           case (user manually set Planlandi again, then comes back
 *           months later and hits + - jumps back to Izleniyor).
 *   Rule 2: if the new watched count equals the ceiling and the status
 *           is not already 'Izlendi', flip to 'Izlendi'. Ceiling-known
 *           anime only - for unknown-ceiling animes the + is refused
 *           before reaching this point.
 *   The two rules apply sequentially in the same request, so the edge
 *   case "Planlandi + 11/12 -> +1 -> 12/12" yields 'Izlendi' in one
 *   click (Planlandi -> Izleniyor -> Izlendi via the same call).
 *   "-" never touches watch_status. The reverse transition (watched
 *   dropping below ceiling -> back to Izleniyor) is deferred to 0.5.7.
 *
 * Request:
 *   POST update_watched.php
 *     csrf_token=<token>
 *     anime_id=<int>
 *     delta=<1 | -1>
 *
 * Response (success):
 *   {
 *     "success":              true,
 *     "watched_episodes":     12,
 *     "old_value":            11,
 *     "ceiling":              24,         // null if no known ceiling
 *     "at_min":               false,      // watched == 0  -> client disables "-"
 *     "at_max":               false,      // watched == ceiling -> disables "+"
 *     "watch_status_changed": true,       // 0.5.6 - did the rules fire?
 *     "watch_status_new":     "Izlendi"   // 0.5.6 - new value, or null if unchanged
 *   }
 *
 * Response (error):
 *   {
 *     "success": false,
 *     "error":   "User-facing Turkish message",
 *     "code":    "raw_code"   // for client-side branching
 *   }
 *
 * Note: watched_episodes AND watch_status are both personal watch
 * progress. In Faz 2 (multi-user) this endpoint must become user-scoped
 * (write to the per-user table, not the shared animes row) for BOTH
 * columns. Logged in the Faz 2 "tasinacaklar" list (KARARLAR.md Bolum
 * 5) alongside chronology_default_view.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function uw_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    uw_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
        'code'    => 'method',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    uw_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
        'code'    => 'csrf',
    ]);
}

// --- Input ---------------------------------------------------------------

$animeId = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
if ($animeId <= 0) {
    uw_respond([
        'success' => false,
        'error'   => 'Gecersiz anime ID.',
        'code'    => 'bad_id',
    ]);
}

$delta = isset($_POST['delta']) ? (int)$_POST['delta'] : 0;
if ($delta !== 1 && $delta !== -1) {
    uw_respond([
        'success' => false,
        'error'   => 'Gecersiz islem. Sadece +1 veya -1 kabul edilir.',
        'code'    => 'bad_delta',
    ]);
}

// --- Load current state --------------------------------------------------

$stmt = $pdo->prepare(
    "SELECT watched_episodes, total_episodes, aired_episodes, watch_status
       FROM animes
      WHERE id = ?"
);
$stmt->execute([$animeId]);
$row = $stmt->fetch();

if (!$row) {
    uw_respond([
        'success' => false,
        'error'   => 'Anime bulunamadi.',
        'code'    => 'not_found',
    ]);
}

$old      = (int)$row['watched_episodes'];
// Match index.php's !empty() display rule exactly: a 0 (or NULL)
// total/aired counts as "not set". This keeps the client-computed
// ceiling and the server-enforced ceiling in lockstep.
$total    = !empty($row['total_episodes']) ? (int)$row['total_episodes'] : null;
$aired    = !empty($row['aired_episodes']) ? (int)$row['aired_episodes'] : null;

// 0.5.6 - current watch_status, will be compared against target below.
$current_watch_status = (string)$row['watch_status'];

// Ceiling: total wins if set, else aired, else unknown (null).
$ceiling = ($total !== null) ? $total : (($aired !== null) ? $aired : null);

$new = $old + $delta;

// --- Bound enforcement (authoritative copy) ------------------------------

if ($new < 0) {
    uw_respond([
        'success' => false,
        'error'   => 'Izlenen bolum 0 in altina inemez.',
        'code'    => 'below_min',
    ]);
}

if ($delta === 1) {
    if ($ceiling === null) {
        // No total and no aired -> we have no idea what the cap is.
        // Refuse the increment; the client also hides/disables the
        // buttons in this state and points the user at sync / data
        // entry.
        uw_respond([
            'success' => false,
            'error'   => 'Bu anime icin toplam veya yayinlanan bolum '
                       . 'sayisi bilinmiyor. Once Senkronize Et veya '
                       . 'bolum bilgisini girin.',
            'code'    => 'no_ceiling',
        ]);
    }
    if ($new > $ceiling) {
        uw_respond([
            'success' => false,
            'error'   => 'Izlenen bolum tavan degerini ('
                       . $ceiling . ') asamaz.',
            'code'    => 'above_max',
        ]);
    }
}

// --- watch_status target (0.5.6) -----------------------------------------
//
// One-way automation, fires only on delta=+1. Two sequential rules; the
// edge case Planlandi + 11/12 -> +1 -> 12/12 needs both to apply in the
// same call (Planlandi -> Izleniyor -> Izlendi).
//
// Rule 1: 'Izlenme Planlandi' + (any +) -> 'Izleniyor'. The "+" press
//         is the "started / resumed watching" signal. Covers both the
//         first time the user opens an anime and the case where they
//         manually set status back to Planlandi (pause) and later
//         resume.
// Rule 2: new watched == ceiling AND status not already 'Izlendi'
//         -> 'Izlendi'. Ceiling-known animes only; "+" above ceiling
//         was already rejected above so this is safe to evaluate here.
//
// "-" (delta=-1) never touches watch_status. The reverse transition
// (Izlendi -> Izleniyor when watched drops below ceiling) is the 0.5.7
// stage, intentionally out of scope here.
//
// Enum values are matched verbatim against schema.sql:
//   watch_status enum('Izlendi','Izleniyor','Izlenme Planlandi')

$target_watch_status = $current_watch_status;
if ($delta === 1) {
    if ($target_watch_status === 'İzlenme Planlandı') {
        $target_watch_status = 'İzleniyor';
    }
    if ($ceiling !== null && $new === $ceiling
        && $target_watch_status !== 'İzlendi') {
        $target_watch_status = 'İzlendi';
    }
}
$watch_status_changed = ($target_watch_status !== $current_watch_status);

// --- Write ---------------------------------------------------------------
//
// Single UPDATE for both columns. watched_episodes always changes (delta
// is +-1 and we already rejected out-of-bounds), so rowCount() should
// report 1 row affected; watch_status may or may not be different from
// the current value but writing the same value is a no-op at the row
// level and does not affect rowCount logic here.

$upd = $pdo->prepare(
    "UPDATE animes
        SET watched_episodes = ?,
            watch_status     = ?
      WHERE id = ?"
);
$upd->execute([$new, $target_watch_status, $animeId]);

if ($upd->rowCount() === 0) {
    // Row exists (we SELECTed it) but nothing changed. Almost always a
    // concurrent identical write; treat the stored state as truth and
    // report it back so the UI stays consistent.
    error_log('[anime_tracker] update_watched no-op id=' . $animeId
        . ' old=' . $old . ' new=' . $new);
}

// --- Reply ---------------------------------------------------------------

uw_respond([
    'success'              => true,
    'watched_episodes'     => $new,
    'old_value'            => $old,
    'ceiling'              => $ceiling,
    'at_min'               => ($new <= 0),
    'at_max'               => ($ceiling !== null && $new >= $ceiling),
    'watch_status_changed' => $watch_status_changed,
    'watch_status_new'     => $watch_status_changed ? $target_watch_status : null,
]);
