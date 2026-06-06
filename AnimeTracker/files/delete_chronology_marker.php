<?php
/**
 * Anime Tracker - Delete Chronology Marker
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that deletes a chronology marker from the
 * chronology_markers table. Called from the marker list on
 * anime_details.php (the small X button next to each marker).
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   marker_id   - The chronology_markers.id to delete
 *   anime_id    - Used for redirect after deletion
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

$marker_id = (int)($_POST['marker_id'] ?? 0);
$anime_id  = (int)($_POST['anime_id'] ?? 0);

if ($marker_id <= 0) {
    die('Gecersiz marker ID.');
}

// Delete the marker. If it does not exist (already deleted or wrong ID),
// the DELETE simply affects 0 rows — no error, no harm.
try {
    $stmt = $pdo->prepare("DELETE FROM chronology_markers WHERE id = ?");
    $stmt->execute([$marker_id]);
} catch (PDOException $e) {
    error_log('[anime_tracker] delete_chronology_marker failed: ' . $e->getMessage());
    die('Kronoloji notu silinirken bir hata olustu.');
}

// Redirect back to the anime detail page
$redirectId = $anime_id > 0 ? $anime_id : '';
header('Location: anime_details.php?id=' . $redirectId);
exit;
