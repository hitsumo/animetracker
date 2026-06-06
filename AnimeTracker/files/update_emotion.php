<?php

/**
 * Anime Tracker - Emotion Mark Toggle Endpoint (0.6.1)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called from anime_details.php (and potentially the list
 * view later) when the user clicks an emotion button. Toggles a single
 * (user_id, anime_id, emotion) row in the user_anime_emotion table.
 *
 * Why a dedicated endpoint instead of reusing edit_anime.php:
 *   Emotion marks live in a separate table (user_anime_emotion) with a
 *   triple primary key, not on the animes row. edit_anime.php is a full
 *   anime-row editor and would have to grow a parallel set of mark/unmark
 *   loops. A focused toggle endpoint is simpler and matches the existing
 *   shape used by update_watched.php and fetch_aired_episodes.php (POST
 *   + csrf_token + small payload, JSON reply).
 *
 * Toggle semantics:
 *   The same emotion submitted twice flips state. If the mark exists,
 *   this call removes it; if it does not exist, this call adds it
 *   (subject to the cap below). The client does not need to ask the
 *   server "is this marked already" before clicking - the server is the
 *   authoritative source for current state and returns the resulting
 *   set in every reply.
 *
 * Server-side cap (3 marks per anime per user):
 *   KARARLAR Bolum 8 v1 spec - the cap exists so the data does not get
 *   diluted by users marking all 9 options on every anime. The cap is
 *   enforced HERE (the authoritative copy); the client UI mirrors it for
 *   responsiveness but the server has the final say. Removing a mark is
 *   always allowed regardless of count.
 *
 * Validation chain (each step short-circuits with an error reply):
 *   1. POST method (405 if not).
 *   2. CSRF token verified against session.
 *   3. anime_id is a positive integer.
 *   4. emotion is in the canonical list (emotion_options() keys -
 *      functions.php is the single source of truth, the table column is
 *      VARCHAR so the DB itself does not constrain values).
 *   5. The referenced anime exists in the animes table (handles the
 *      stale-tab case where the anime was deleted between page load
 *      and click).
 *   6. If this is a "would-add" call, the user has fewer than 3 marks
 *      on this anime. The DB does not enforce this; we count rows here.
 *
 * Single-user mode:
 *   user_id is hard-coded to 1 (the admin/owner). When Faz 2 introduces
 *   multi-user, this becomes $_SESSION['user_id']. The table is shared
 *   between modes - existing rows simply belong to user 1 (KARARLAR Yol
 *   4 - "ayni tablo paylasilir, migration gerekmez").
 *
 * Concurrency:
 *   The read-current-state and write-new-state happen inside a
 *   transaction. The cap check uses the snapshot read inside the
 *   transaction; for a single-user install the race window is essentially
 *   nil (no second simultaneous client). INSERT IGNORE swallows a
 *   1062 (duplicate primary key) if a perfectly-simultaneous duplicate
 *   sneaks through; the reply still reports the consistent post-state.
 *
 * Request:
 *   POST update_emotion.php
 *     csrf_token=<token>
 *     anime_id=<int>
 *     emotion=<ASCII identifier from emotion_options() keys>
 *
 * Response (success):
 *   {
 *     "success":          true,
 *     "action":           "added" | "removed",
 *     "anime_id":         123,
 *     "emotion":          "Huzunlendirdi",     // ASCII value just toggled
 *     "emotion_label":    "Huzunlendirdi" (TR), // for toast / feedback
 *     "current_emotions": ["Huzunlendirdi", "MotiveEtti"],
 *                         // all marks now set on this anime, ASCII values,
 *                         // unsorted (client renders in canonical order
 *                         // via emotion_options()).
 *     "count":            2,                    // length of current_emotions
 *     "at_max":           false                 // count === 3
 *   }
 *
 * Response (error):
 *   {
 *     "success": false,
 *     "error":   "User-facing Turkish message",
 *     "code":    "raw_code"   // for client-side branching
 *   }
 *
 * Error codes:
 *   method        - request was not POST
 *   csrf          - csrf_token missing or invalid
 *   bad_id        - anime_id missing or <= 0
 *   bad_emotion   - emotion missing or not in canonical list
 *   not_found     - no animes row with that id (stale tab)
 *   limit_reached - already 3 marks for this (user, anime), refuse add
 *   db_error      - transaction rolled back unexpectedly
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function ue_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Current user --------------------------------------------------------
//
// Every row written by this endpoint belongs to the current user, resolved
// via current_user_id() (1.0.x data model). A variable, not a const, because
// a const expression cannot hold a function call. Single-user mode returns 1
// (behaviour unchanged); multi-user mode returns the session user.

$ue_user_id = current_user_id();

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ue_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
        'code'    => 'method',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    ue_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
        'code'    => 'csrf',
    ]);
}

// Emotion marks are personal, so a logged-in user is required (online only;
// no-op in self-host). JSON denial so the AJAX client sees success:false.
require_login(true);

// --- Input ---------------------------------------------------------------

$animeId = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
if ($animeId <= 0) {
    ue_respond([
        'success' => false,
        'error'   => 'Gecersiz anime ID.',
        'code'    => 'bad_id',
    ]);
}

$emotion = isset($_POST['emotion']) ? (string)$_POST['emotion'] : '';
// Validate against the canonical list maintained in functions.php.
// emotion_options() keys are the ASCII identifiers; this is the single
// source of truth - the VARCHAR column itself does not constrain values.
$canonical = emotion_options();
if ($emotion === '' || !array_key_exists($emotion, $canonical)) {
    ue_respond([
        'success' => false,
        'error'   => 'Gecersiz duygu etiketi.',
        'code'    => 'bad_emotion',
    ]);
}

// --- Verify anime exists -------------------------------------------------
//
// Catches the stale-tab case: the anime was deleted after the page was
// rendered, before the user clicked. Without this check the INSERT would
// fail with a FK violation (less helpful error) or - in a hypothetical
// non-FK schema - quietly orphan the row.

$stmt = $pdo->prepare("SELECT 1 FROM animes WHERE id = ? LIMIT 1");
$stmt->execute([$animeId]);
if (!$stmt->fetchColumn()) {
    ue_respond([
        'success' => false,
        'error'   => 'Anime bulunamadi.',
        'code'    => 'not_found',
    ]);
}

// --- Toggle inside a transaction -----------------------------------------
//
// Read current marks, decide add vs remove, write, commit. Wrapping the
// read and the write in one transaction means the cap check sees a
// consistent snapshot. For single-user installs the race window is
// negligible but the structure is correct for the Faz 2 multi-user
// follow-up.

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT emotion
           FROM user_anime_emotion
          WHERE user_id = ? AND anime_id = ?"
    );
    $stmt->execute([$ue_user_id, $animeId]);
    $current = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $exists = in_array($emotion, $current, true);

    if ($exists) {
        // Toggle off - removal is always allowed regardless of count.
        $del = $pdo->prepare(
            "DELETE FROM user_anime_emotion
              WHERE user_id = ? AND anime_id = ? AND emotion = ?"
        );
        $del->execute([$ue_user_id, $animeId, $emotion]);
        $action = 'removed';
        // Recompute current list (cheaper than re-querying).
        $current = array_values(array_filter(
            $current,
            function ($e) use ($emotion) { return $e !== $emotion; }
        ));
    } else {
        // Toggle on - enforce cap before INSERT.
        if (count($current) >= 3) {
            $pdo->rollBack();
            ue_respond([
                'success' => false,
                'error'   => 'Bir anime icin en fazla 3 duygu '
                           . 'isaretleyebilirsiniz. Birini kaldirip '
                           . 'tekrar deneyin.',
                'code'    => 'limit_reached',
            ]);
        }
        $ins = $pdo->prepare(
            "INSERT IGNORE INTO user_anime_emotion
                (user_id, anime_id, emotion)
             VALUES (?, ?, ?)"
        );
        $ins->execute([$ue_user_id, $animeId, $emotion]);
        $action = 'added';
        $current[] = $emotion;
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[anime_tracker] update_emotion db_error anime_id='
        . $animeId . ' emotion=' . $emotion
        . ' msg=' . $e->getMessage());
    ue_respond([
        'success' => false,
        'error'   => 'Veritabani hatasi olustu. Lutfen tekrar deneyin.',
        'code'    => 'db_error',
    ]);
}

// --- Reply ---------------------------------------------------------------

ue_respond([
    'success'          => true,
    'action'           => $action,
    'anime_id'         => $animeId,
    'emotion'          => $emotion,
    'emotion_label'    => emotion_label($emotion),
    'current_emotions' => $current,
    'count'            => count($current),
    'at_max'           => (count($current) >= 3),
]);
