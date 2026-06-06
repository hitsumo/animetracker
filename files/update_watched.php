<?php

/**
 * Anime Tracker - Quick Watched-Episode Update Endpoint (0.5.5 / 0.5.6 / 0.5.7)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by index.php when the user clicks the "+" or
 * "-" button in the "Izlenen Bolum" cell of the list view. Adjusts
 * the user's watched_episodes by +1 or -1 without opening the full
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
 * 0.5.6 - watch_status forward automation (only on delta=+1):
 *   Rule 1: if current watch_status is 'PlanToWatch', + flips it
 *           to 'Watching'. The act of incrementing is treated as a
 *           "started watching" signal. Also handles the "paused -> back"
 *           case (user manually set PlanToWatch again, then comes back
 *           months later and hits + - jumps back to Watching).
 *   Rule 2: if the new watched count equals the ceiling and the status
 *           is not already 'Watched', flip to 'Watched'. Ceiling-known
 *           anime only - for unknown-ceiling animes the + is refused
 *           before reaching this point.
 *   The two rules apply sequentially in the same request, so the edge
 *   case "PlanToWatch + 11/12 -> +1 -> 12/12" yields 'Watched' in one
 *   click (PlanToWatch -> Watching -> Watched via the same call).
 *
 * 0.5.7 - watch_status reverse automation (only on delta=-1):
 *   Rule 3: if the new watched count is below the ceiling AND the
 *           current watch_status is 'Watched', flip to 'Watching'.
 *           Symmetric reverse of Rule 2: stepping out of "watched all"
 *           back into "watching". Ceiling-known animes only - if the
 *           ceiling is unknown (no total, no aired) the rule does not
 *           fire and any manual 'Watched' is preserved as-is.
 *   Rule 4: if the new watched count is 0 AND the current watch_status
 *           is 'Watching', flip to 'PlanToWatch'. Symmetric reverse of
 *           Rule 1: stepping all the way back to zero count while in
 *           "watching" mode is read as "haven't started yet". Triggers
 *           on absolute zero, not on a relative ceiling check, so it
 *           works for ceiling-unknown animes too.
 *   Rules 3 and 4 apply sequentially in the same request, so the edge
 *   case "Watched + 1/12 -> -1 -> 0/12" yields 'PlanToWatch' in one
 *   click (Watched -> Watching -> PlanToWatch via the same call - the
 *   symmetric mirror of the Rule 1+2 edge case on the +1 side).
 *   "-" leaves watch_status alone in cases not covered by Rule 3 or 4
 *   ('Watching' + - with new > 0 stays 'Watching'; 'PlanToWatch' + -
 *   stays 'PlanToWatch'). Rules 1 and 2 do not fire on delta=-1; Rules
 *   3 and 4 do not fire on delta=+1.
 *
 * 0.6 - 'OnHold' support + Rule 5 + ASCII enum migration:
 *   Schema change: watch_status enum was migrated from TR labels to
 *   ASCII values ('Watched','Watching','PlanToWatch','OnHold'). UI text
 *   stays Turkish via watch_status_label() in functions.php. A 4th
 *   value 'OnHold' (TR label "Izleme Ertelendi") was added: semantics
 *   "I started watching, took a break, keep my progress". Distinct from
 *   'PlanToWatch' ("haven't started") - 'OnHold' preserves
 *   watched_episodes intact when the user toggles into it from the edit
 *   form (see edit_anime.php toggleWatchedEpisodes JS).
 *
 *   Rule 5: if current watch_status is 'OnHold' AND delta=+1, flip to
 *           'Watching'. Resume signal, analogous to Rule 1 but from a
 *           different source (paused mid-series rather than not started).
 *           Combined with Rule 1 in the code (both produce 'Watching');
 *           the subsequent Rule 2 chain still applies, so an OnHold
 *           anime at (ceiling-1)/ceiling + 1 lands on 'Watched' in one
 *           click via Rule 5 + Rule 2.
 *
 *   "-" while OnHold does not trigger any rule (mirror of PlanToWatch +
 *   -). Status stays 'OnHold', watched_episodes simply decrements. This
 *   is intentional: a user pausing mid-series and then "undoing" one
 *   episode is rare; if they want to roll back further they switch
 *   status manually from the edit form.
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
 *     "ceiling":              24,           // null if no known ceiling
 *     "at_min":               false,        // watched == 0  -> client disables "-"
 *     "at_max":               false,        // watched == ceiling -> disables "+"
 *     "watch_status_changed": true,         // 0.5.6 - did the rules fire?
 *     "watch_status_new":     "Watched",    // 0.5.6 - new ASCII value, or null
 *     "watch_status_label":   "Izlendi"     // 0.6 - new TR UI label, or null
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
 * progress. As of 1.0.1 (Faz 2, Milestone 1) they live in the per-user
 * user_anime table, not on the shared animes row. This endpoint reads the
 * ceiling (total_episodes / aired_episodes) from animes (catalog) and the
 * personal watched/status from user_anime via ua_get_state(), and writes
 * back with ua_set_state(), both keyed by current_user_id(). With
 * MULTI_USER_MODE off, current_user_id() is 1, so behaviour is unchanged.
 * The 'Dropped' watch_status value exists in the user_anime enum but is
 * not produced by the +/- rules below (parked for the personal-state
 * milestone); the four-value automation here is unchanged.
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

// Watched progress is personal, so a logged-in user is required (online
// only; no-op in self-host). JSON denial so the AJAX client sees success:false.
require_login(true);

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
//
// Ceiling fields (total_episodes / aired_episodes) are CATALOG data and
// live on animes; the personal watched_episodes / watch_status live in
// user_anime (per user). Read each from its own home.

$uid = current_user_id();

$catStmt = $pdo->prepare(
    "SELECT total_episodes, aired_episodes
       FROM animes
      WHERE id = ?"
);
$catStmt->execute([$animeId]);
$catRow = $catStmt->fetch();

if (!$catRow) {
    uw_respond([
        'success' => false,
        'error'   => 'Anime bulunamadi.',
        'code'    => 'not_found',
    ]);
}

// Personal state (defaults to PlanToWatch / 0 if this user has no row yet).
$state = ua_get_state($pdo, $uid, $animeId);

$old      = (int)$state['watched_episodes'];
// Match index.php's !empty() display rule exactly: a 0 (or NULL)
// total/aired counts as "not set". This keeps the client-computed
// ceiling and the server-enforced ceiling in lockstep.
$total    = !empty($catRow['total_episodes']) ? (int)$catRow['total_episodes'] : null;
$aired    = !empty($catRow['aired_episodes']) ? (int)$catRow['aired_episodes'] : null;

// 0.5.6 - current watch_status, will be compared against target below.
$current_watch_status = (string)$state['watch_status'];

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

// --- watch_status target (0.5.6 + 0.5.7 + 0.6) ---------------------------
//
// Two-way automation, five rules total. Forward rules (1, 2, 5) fire only
// on delta=+1; reverse rules (3, 4) fire only on delta=-1. Rules within
// each direction apply sequentially, which is what makes the two-step
// edge cases work:
//   - +1 edge: PlanToWatch + 11/12 -> +1 -> 12/12 fires Rule 1 then Rule
//     2 in one call (PlanToWatch -> Watching -> Watched). Same shape for
//     OnHold via Rule 5 + Rule 2.
//   - -1 edge: Watched + 1/12 -> -1 -> 0/12 fires Rule 3 then Rule 4 in
//     one call (Watched -> Watching -> PlanToWatch). Symmetric mirror of
//     the +1 edge.
//
// Rule 1 (0.5.6): 'PlanToWatch' + (any +) -> 'Watching'. The "+" press
//         is the "started watching" signal. Covers the first time the
//         user opens an anime.
// Rule 2 (0.5.6): new watched == ceiling AND status not already
//         'Watched' -> 'Watched'. Ceiling-known animes only; "+" above
//         ceiling was already rejected above so this is safe to evaluate
//         here.
// Rule 3 (0.5.7): new watched < ceiling AND status === 'Watched'
//         -> 'Watching'. Symmetric reverse of Rule 2. Ceiling-known
//         animes only; if the ceiling is unknown the rule is skipped
//         and a manual 'Watched' state is preserved.
// Rule 4 (0.5.7): new watched == 0 AND status === 'Watching'
//         -> 'PlanToWatch'. Symmetric reverse of Rule 1. Triggers on
//         absolute zero (not a ceiling-relative comparison), so it works
//         for ceiling-unknown animes too. Reads Rule 3's just-set
//         target, which is what makes the Watched -> Watching ->
//         PlanToWatch chain on the -1 edge work in one click.
// Rule 5 (0.6):   'OnHold' + (any +) -> 'Watching'. Resume signal -
//         analogous to Rule 1 but from a different source (paused
//         mid-series rather than not started). Combined with Rule 1 in
//         code (same target). OnHold + (ceiling-1)/ceiling + 1 chain
//         lands on 'Watched' via Rule 5 then Rule 2 in one click.
//
// Deliberate asymmetries still left as user-controlled (post-rules):
//   - 'Watching' + - with new > 0 stays 'Watching' (Rule 4 needs
//     new === 0; a partial step back is not "give up").
//   - 'PlanToWatch' + - stays 'PlanToWatch' (no rule needs to fire;
//     the pause intent is preserved across decrement attempts).
//   - 'OnHold' + - stays 'OnHold' (mirror of PlanToWatch + -; pause
//     intent preserved, watched_episodes simply decrements).
//   - 'Watched' + + below ceiling stays 'Watched' (a stuck-Watched
//     state is not auto-corrected on +; the user can manually edit).
//
// Enum values are matched verbatim against schema.sql (0.6 ASCII):
//   watch_status enum('Watched','Watching','PlanToWatch','OnHold')

$target_watch_status = $current_watch_status;
if ($delta === 1) {
    // Rule 1 (0.5.6) + Rule 5 (0.6): 'PlanToWatch' or 'OnHold' + (+) ->
    // 'Watching'. The "+" press is the "started / resumed watching"
    // signal. Rule 1 covers the first time the user opens an anime;
    // Rule 5 covers resuming after a manual OnHold pause. Both produce
    // 'Watching' so they are combined here; the subsequent Rule 2 can
    // still fire in the same call if the new count hits the ceiling
    // (single-click chain like OnHold + 11/12 -> +1 -> 12/12 lands on
    // 'Watched' via Rule 5 then Rule 2).
    if ($target_watch_status === 'PlanToWatch'
        || $target_watch_status === 'OnHold') {
        $target_watch_status = 'Watching';
    }
    if ($ceiling !== null && $new === $ceiling
        && $target_watch_status !== 'Watched') {
        $target_watch_status = 'Watched';
    }
} elseif ($delta === -1) {
    if ($ceiling !== null && $new < $ceiling
        && $target_watch_status === 'Watched') {
        $target_watch_status = 'Watching';
    }
    if ($new === 0 && $target_watch_status === 'Watching') {
        $target_watch_status = 'PlanToWatch';
    }
}
$watch_status_changed = ($target_watch_status !== $current_watch_status);

// --- Write ---------------------------------------------------------------
//
// Both columns are personal, so they go to user_anime for the current
// user. ua_set_state() upserts: it creates the row if this user does not
// have one yet, otherwise updates it in place. Writing the same
// watch_status value is harmless.

$ok = ua_set_state($pdo, $uid, $animeId, [
    'watched_episodes' => $new,
    'watch_status'     => $target_watch_status,
]);

if (!$ok) {
    // The write failed (the cause is logged inside ua_set_state). Report
    // it so the UI does not show a change that was not persisted.
    uw_respond([
        'success' => false,
        'error'   => 'Guncelleme kaydedilemedi. Tekrar deneyin.',
        'code'    => 'write_failed',
    ]);
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
    // 0.5.6 - ASCII internal value, or null if unchanged.
    'watch_status_new'     => $watch_status_changed ? $target_watch_status : null,
    // 0.6 - TR UI label for the same value. The client writes this into
    // the DOM so the user sees Turkish; the ASCII field stays for
    // programmatic consumers (debugging, integration tests).
    'watch_status_label'   => $watch_status_changed ? watch_status_label($target_watch_status) : null,
]);
