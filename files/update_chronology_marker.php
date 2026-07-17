<?php
/**
 * Anime Tracker - Update Chronology Marker (story point) - 1.1.15
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that sets (or clears) the STORY-order point of an
 * existing chronology marker. Added in 1.1.15 so a curator can attach a
 * story_after_episode to markers that already carry a release-order
 * after_episode, WITHOUT deleting and re-adding them (markers only had
 * add + delete before). Called from the inline field next to each marker
 * on anime_details.php.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   marker_id   - The chronology_markers.id to update
 *   anime_id    - Host anime id, used for redirect
 *   field       - Which axis this box edits: 'release' updates
 *                 after_episode (required), 'story' updates
 *                 story_after_episode (empty clears to NULL). So the
 *                 release-section box and the story-section box on
 *                 anime_details.php edit their own value independently.
 *   episode     - The episode number. For 'release' it is required and
 *                 must be >= 1; for 'story' an empty value clears it.
 *
 * On success: redirects back to anime_details.php?id={anime_id}
 * On error: dies with an error message
 *
 * Mirrors add_chronology_marker.php: moderator+ gate (shared series
 * structure) and the same central-catalog push contract, so the story
 * point reaches the shared chronology the same way a new marker does.
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

$marker_id = (int)($_POST['marker_id'] ?? 0);
$anime_id  = (int)($_POST['anime_id'] ?? 0);
// Which axis this inline box edits (1.1.15): 'release' = after_episode
// (required, never NULL), 'story' = story_after_episode (empty clears to
// NULL / "same as release"). Default 'story' for safety.
$field   = (($_POST['field'] ?? 'story') === 'release') ? 'release' : 'story';
$epRaw   = trim($_POST['episode'] ?? '');

if ($marker_id <= 0) {
    die('Gecersiz marker ID.');
}

// Fetch the marker with its host anime's episode count for bounds checking.
$stmt = $pdo->prepare("
    SELECT cm.id, cm.anime_id, a.total_episodes
    FROM chronology_markers cm
    JOIN animes a ON a.id = cm.anime_id
    WHERE cm.id = ?
");
$stmt->execute([$marker_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    die('Kronoloji notu bulunamadi.');
}

$total = ($row['total_episodes'] !== null) ? (int)$row['total_episodes'] : null;

try {
    if ($field === 'release') {
        // Release point is required and must be a valid episode number.
        $ep = (int)$epRaw;
        if ($ep <= 0) {
            die('Yayin bolum numarasi 1 veya daha buyuk olmali.');
        }
        if ($total !== null && $ep > $total) {
            die('Yayin bolum numarasi toplam bolum sayisindan (' . $total . ') buyuk olamaz.');
        }
        $upd = $pdo->prepare("UPDATE chronology_markers SET after_episode = ? WHERE id = ?");
        $upd->execute([$ep, $marker_id]);
    } else {
        // Story point: empty clears it back to NULL ("same as release").
        $ep = ($epRaw === '') ? null : (int)$epRaw;
        if ($ep !== null) {
            if ($ep <= 0) {
                die('Hikaye bolum numarasi 1 veya daha buyuk olmali.');
            }
            if ($total !== null && $ep > $total) {
                die('Hikaye bolum numarasi toplam bolum sayisindan (' . $total . ') buyuk olamaz.');
            }
        }
        $upd = $pdo->prepare("UPDATE chronology_markers SET story_after_episode = ? WHERE id = ?");
        $upd->execute([$ep, $marker_id]);
    }
} catch (PDOException $e) {
    // 23000 = UNIQUE (anime_id, after_episode, related_anime_id) collision when
    // moving a release point onto an existing marker's slot.
    if ($e->getCode() == '23000') {
        die('Bu bolum icin zaten bir kronoloji notu var.');
    }
    error_log('[anime_tracker] update_chronology_marker failed: ' . $e->getMessage());
    die('Kronoloji notu guncellenirken bir hata olustu.');
}

// Online: re-push the shared chronology so the story point reaches the
// central catalog. Same failure contract as add_chronology_marker.php - a
// failed push NEVER rolls back the local update; the detail is logged and
// the user is sent to index.php with the shared warning band. Any later
// catalog save retries the push (the payload carries ALL markers).
$pushFailed = false;
if (MULTI_USER_MODE) {
    $pushHelper = __DIR__ . '/admin/catalog_push.php';
    if (is_file($pushHelper)) {
        require_once $pushHelper;
        $push = catalog_push_to_server($pdo);
        if (!empty($push['ok'])) {
            try {
                $promote = $pdo->prepare("UPDATE chronology_markers SET source = 'catalog' WHERE id = ?");
                $promote->execute([$marker_id]);
            } catch (PDOException $e) {
                error_log('[anime_tracker] update_chronology_marker promote failed: ' . $e->getMessage());
            }
        } else {
            $pushFailed = true;
            error_log('[anime_tracker] update_chronology_marker catalog push failed: '
                . (isset($push['message']) ? $push['message'] : 'unknown'));
        }
    } else {
        $pushFailed = true;
        error_log('[anime_tracker] update_chronology_marker catalog push skipped: helper missing');
    }
}

$redirectId = $anime_id > 0 ? $anime_id : (int)$row['anime_id'];
if ($pushFailed) {
    header('Location: index.php?catalog_push=failed');
} else {
    header('Location: anime_details.php?id=' . $redirectId);
}
exit;
