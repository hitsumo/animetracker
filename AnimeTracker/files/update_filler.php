<?php

/**
 * Anime Tracker - Filler Episodes Save Endpoint (0.6.8)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Batch endpoint called from filler_edit.php when the curator clicks
 * "Kaydet". Receives the FULL desired filler state for one anime and
 * writes it atomically: inside a transaction it deletes every existing
 * filler_episodes row for the anime and re-inserts the marked ones
 * (KARARLAR Bolum 8 - "silip yeniden yazar (atomik)"). This is a batch
 * POST, NOT per-tick AJAX - a long series produces one request, not 1000,
 * and a half-saved grid is impossible.
 *
 * Why a dedicated endpoint instead of edit_anime.php:
 *   Filler classification lives in a separate table (filler_episodes,
 *   composite key), not on the animes row. Same reasoning as
 *   update_emotion.php / update_watched.php: a focused endpoint with a
 *   small payload and a JSON reply is simpler than growing the 26-column
 *   anime-row editor a parallel save loop.
 *
 * Catalog-level data, not user-scoped:
 *   Unlike update_emotion.php there is no user_id here. Filler is
 *   curator-maintained catalog data shared by every user. In Faz 2/3 the
 *   write becomes "live edit + catalog_edit_log + moderator revert" (the
 *   AniDB hybrid model, KARARLAR Bolum 8/9); for now the single curator
 *   (admin) writes directly. Catalog sync (admin_push / catalog_import)
 *   is intentionally out of scope for this first cut.
 *
 * Validation chain (each step short-circuits with an error reply):
 *   1. POST method (405 if not).
 *   2. CSRF token verified against session.
 *   3. anime_id is a positive integer.
 *   4. The referenced anime exists (stale-tab guard).
 *   5. episodes is a valid JSON object {episode_no: type, ...}.
 *      - episode_no a positive integer, within the known episode count
 *        when one is available (total_episodes ?? aired_episodes).
 *      - type in the canonical list (filler_type_options() keys -
 *        functions.php is the single source of truth; the enum column
 *        also constrains it, this keeps the input layer in step).
 *      Any bad entry rejects the WHOLE payload (all-or-nothing - a grid
 *      save is one intent).
 *
 * Request:
 *   POST update_filler.php
 *     csrf_token=<token>
 *     anime_id=<int>
 *     episodes=<JSON object, e.g. {"5":"Filler","6":"Filler","11":"Mixed"}>
 *               (an empty object {} clears all filler for the anime)
 *
 * Response (success):
 *   {
 *     "success":     true,
 *     "anime_id":    123,
 *     "count":       3,                 // rows written
 *     "summary":     "Dolgu: 5-6 | Karışık: 11",  // localized, for the
 *                                                 // read-only details view
 *     "summary_empty": false            // true when nothing is marked
 *   }
 *
 * Response (error):
 *   {
 *     "success": false,
 *     "error":   "User-facing Turkish message",
 *     "code":    "raw_code"
 *   }
 *
 * Error codes:
 *   method     - request was not POST
 *   csrf       - csrf_token missing or invalid
 *   bad_id     - anime_id missing or <= 0
 *   not_found  - no animes row with that id (stale tab)
 *   bad_payload- episodes missing or not a JSON object
 *   bad_entry  - an episode number or type failed validation
 *   db_error   - transaction rolled back unexpectedly
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function uf_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    uf_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
        'code'    => 'method',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    uf_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
        'code'    => 'csrf',
    ]);
}

// --- Input: anime_id -----------------------------------------------------

$animeId = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
if ($animeId <= 0) {
    uf_respond([
        'success' => false,
        'error'   => 'Gecersiz anime ID.',
        'code'    => 'bad_id',
    ]);
}

// --- Verify anime exists + read episode count for bounds -----------------
//
// total_episodes ?? aired_episodes is the same count source the editor
// grid uses (KARARLAR Bolum 8). When known, it is the upper bound for a
// valid episode number; when both are NULL we fall back to a defensive
// ceiling so a malformed payload cannot insert millions of rows.

$stmt = $pdo->prepare(
    "SELECT total_episodes, aired_episodes FROM animes WHERE id = ? LIMIT 1"
);
$stmt->execute([$animeId]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$anime) {
    uf_respond([
        'success' => false,
        'error'   => 'Anime bulunamadi.',
        'code'    => 'not_found',
    ]);
}

$episodeCount = null;
if (!empty($anime['total_episodes'])) {
    $episodeCount = (int)$anime['total_episodes'];
} elseif (!empty($anime['aired_episodes'])) {
    $episodeCount = (int)$anime['aired_episodes'];
}
// Defensive ceiling when no count is known (editor guards against this
// case, but the endpoint must not trust the client).
const UF_MAX_EPISODE = 9999;
$upperBound = $episodeCount ?? UF_MAX_EPISODE;

// --- Input: episodes map -------------------------------------------------

$raw = $_POST['episodes'] ?? '';
$decoded = json_decode((string)$raw, true);
// An empty object {} is valid and means "clear everything"; reject only
// non-object payloads (null/scalar/array-list).
if (!is_array($decoded)) {
    uf_respond([
        'success' => false,
        'error'   => 'Gecersiz veri bicimi.',
        'code'    => 'bad_payload',
    ]);
}

$validTypes = filler_type_options();   // ASCII key => label
$clean = [];                           // episode_no(int) => type(string)
foreach ($decoded as $epKey => $type) {
    $ep   = (int)$epKey;
    $type = (string)$type;
    if ($ep < 1 || $ep > $upperBound || !array_key_exists($type, $validTypes)) {
        uf_respond([
            'success' => false,
            'error'   => 'Gecersiz bolum veya tip degeri.',
            'code'    => 'bad_entry',
        ]);
    }
    // Last write wins for a duplicated key (json_decode already dedupes
    // object keys, this is belt-and-suspenders).
    $clean[$ep] = $type;
}

// --- Atomic replace inside a transaction ---------------------------------
//
// Delete the anime's existing filler rows, then insert the new set. A
// single multi-row INSERT keeps it to two statements regardless of grid
// size. The whole thing is one transaction so a reader never sees a
// half-written grid.

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare("DELETE FROM filler_episodes WHERE anime_id = ?");
    $del->execute([$animeId]);

    if (!empty($clean)) {
        $placeholders = [];
        $values = [];
        foreach ($clean as $ep => $type) {
            $placeholders[] = '(?, ?, ?)';
            $values[] = $animeId;
            $values[] = $ep;
            $values[] = $type;
        }
        $ins = $pdo->prepare(
            "INSERT INTO filler_episodes (anime_id, episode_no, type) VALUES "
            . implode(', ', $placeholders)
        );
        $ins->execute($values);
    }

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[anime_tracker] update_filler db_error anime_id='
        . $animeId . ' count=' . count($clean)
        . ' msg=' . $e->getMessage());
    uf_respond([
        'success' => false,
        'error'   => 'Veritabani hatasi olustu. Lutfen tekrar deneyin.',
        'code'    => 'db_error',
    ]);
}

// --- Reply ---------------------------------------------------------------
//
// Build the same compact summary the details page shows, so the client can
// update the read-only view without a reload.

$rows = [];
foreach ($clean as $ep => $type) {
    $rows[] = ['episode_no' => $ep, 'type' => $type];
}
$summary = filler_summary($rows);

uf_respond([
    'success'       => true,
    'anime_id'      => $animeId,
    'count'         => count($clean),
    'summary'       => $summary,
    'summary_empty' => ($summary === ''),
]);
