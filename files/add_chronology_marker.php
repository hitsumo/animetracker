<?php
/**
 * Anime Tracker - Add Chronology Marker
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that inserts a new chronology marker into the
 * chronology_markers table. Called from the marker form on
 * anime_details.php.
 *
 * Required POST fields:
 *   csrf_token       - CSRF protection token
 *   anime_id         - The host anime (e.g. Detective Conan S1)
 *   after_episode    - Episode number after which the related anime
 *                      should be watched (1-indexed)
 *   related_anime_id - The anime to watch at that point (e.g. Film 1)
 *
 * Optional POST fields:
 *   note             - Free text comment
 *
 * On success: redirects back to anime_details.php?id={anime_id}
 * On error: dies with an error message
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Gate: POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Gate: CSRF
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// Chronology markers are shared series structure, so a moderator+ is required
// (online only; no-op in self-host).
require_role($pdo, 'moderator');

// Read and validate inputs
$anime_id         = (int)($_POST['anime_id'] ?? 0);
$after_episode    = (int)($_POST['after_episode'] ?? 0);
$related_anime_id = (int)($_POST['related_anime_id'] ?? 0);
$note             = trim($_POST['note'] ?? '');
if ($note === '') { $note = null; }

// Basic validation
if ($anime_id <= 0) {
    die('Gecersiz anime ID.');
}
if ($after_episode <= 0) {
    die('Bolum numarasi 1 veya daha buyuk olmali.');
}
if ($related_anime_id <= 0) {
    die('Izlenecek anime secilmedi.');
}
if ($anime_id === $related_anime_id) {
    die('Bir anime kendisine kronoloji notu olusturamaz.');
}

// Verify that the host anime exists and check episode bounds
$hostStmt = $pdo->prepare("SELECT id, total_episodes, series_name FROM animes WHERE id = ?");
$hostStmt->execute([$anime_id]);
$hostAnime = $hostStmt->fetch(PDO::FETCH_ASSOC);

if (!$hostAnime) {
    die('Kaynak anime bulunamadi.');
}

// If total_episodes is known, after_episode must not exceed it
if ($hostAnime['total_episodes'] !== null && $after_episode > (int)$hostAnime['total_episodes']) {
    die('Bolum numarasi toplam bolum sayisindan (' . (int)$hostAnime['total_episodes'] . ') buyuk olamaz.');
}

// Verify that the related anime exists
$relatedStmt = $pdo->prepare("SELECT id, series_name FROM animes WHERE id = ?");
$relatedStmt->execute([$related_anime_id]);
$relatedAnime = $relatedStmt->fetch(PDO::FETCH_ASSOC);

if (!$relatedAnime) {
    die('Hedef anime bulunamadi.');
}

// Insert the marker. The UNIQUE KEY (anime_id, after_episode,
// related_anime_id) will prevent exact duplicates from a double-submit.
//
// source starts as 'user' (Karar 1B). The schema default is also 'user',
// but stating it here keeps the intent in the code: a locally-created
// marker must never be wiped by catalog_import.php, which only deletes
// WHERE source='catalog'. After a SUCCESSFUL central push below the row is
// promoted to 'catalog' (method B); on push failure - or on self-host -
// it stays 'user', so import never wipes an unsynced marker.
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO chronology_markers (anime_id, after_episode, related_anime_id, note, source)
        VALUES (?, ?, ?, ?, 'user')
    ");
    $insertStmt->execute([$anime_id, $after_episode, $related_anime_id, $note]);
    $markerId = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    // Error code 23000 = integrity constraint violation (duplicate entry)
    if ($e->getCode() == '23000') {
        die('Bu kronoloji notu zaten mevcut.');
    }
    error_log('[anime_tracker] add_chronology_marker failed: ' . $e->getMessage());
    die('Kronoloji notu eklenirken bir hata olustu.');
}

// Online: a new chronology marker is pushed to the central catalog
// automatically. This is the FOURTH trigger of the Karar 14 / 1.0.11 push
// contract (the other three are add_anime, edit_anime and admin_pending
// promote). Runs only in MULTI_USER_MODE; on self-host the block never
// executes and behavior is unchanged. admin/catalog_push.php is not in the
// self-host package, so the require is lazy and guarded by is_file.
//
// Failure contract (same as edit_anime / promote): a failed push NEVER rolls
// back the local insert. The detail is written to error_log (no emoji) and
// the user is sent to index.php with the shared warning band. Any later
// catalog save (edit_anime / add_anime / promote) retries the push, because
// the push payload carries ALL markers.
//
// Method B: on a CONFIRMED push the marker is promoted to source='catalog'.
// This immediately clears the "not synced" warning in list_settings.php and
// makes the local state reflect reality - the moderator is the curator of the
// shared chronology. On failure the row stays 'user' (import-safe) and the
// warning correctly remains.
$pushFailed = false;
if (MULTI_USER_MODE) {
    $pushHelper = __DIR__ . '/admin/catalog_push.php';
    if (is_file($pushHelper)) {
        require_once $pushHelper;
        $push = catalog_push_to_server($pdo);
        if (!empty($push['ok'])) {
            // Promote ONLY this marker after a successful push. Promotion is
            // cosmetic (clears the unsynced warning); a failure here must not
            // break the flow - the marker is already on the central catalog
            // and reconverges to 'catalog' on the next pull anyway.
            try {
                $promote = $pdo->prepare("UPDATE chronology_markers SET source = 'catalog' WHERE id = ?");
                $promote->execute([$markerId]);
            } catch (PDOException $e) {
                error_log('[anime_tracker] add_chronology_marker promote failed: ' . $e->getMessage());
            }
        } else {
            $pushFailed = true;
            error_log('[anime_tracker] add_chronology_marker catalog push failed: '
                . (isset($push['message']) ? $push['message'] : 'unknown'));
        }
    } else {
        $pushFailed = true;
        error_log('[anime_tracker] add_chronology_marker catalog push skipped: helper missing');
    }
}

// On push failure, send the user to index.php with the shared warning band
// (same contract as edit_anime / add_anime). Otherwise return to the detail
// page so the moderator sees the new marker.
if ($pushFailed) {
    header('Location: index.php?catalog_push=failed');
} else {
    header('Location: anime_details.php?id=' . $anime_id);
}
exit;
