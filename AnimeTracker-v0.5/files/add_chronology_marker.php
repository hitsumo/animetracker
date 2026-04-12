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
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO chronology_markers (anime_id, after_episode, related_anime_id, note)
        VALUES (?, ?, ?, ?)
    ");
    $insertStmt->execute([$anime_id, $after_episode, $related_anime_id, $note]);
} catch (PDOException $e) {
    // Error code 23000 = integrity constraint violation (duplicate entry)
    if ($e->getCode() == '23000') {
        die('Bu kronoloji notu zaten mevcut.');
    }
    error_log('[anime_tracker] add_chronology_marker failed: ' . $e->getMessage());
    die('Kronoloji notu eklenirken bir hata olustu.');
}

// Redirect back to the anime detail page
header('Location: anime_details.php?id=' . $anime_id);
exit;
