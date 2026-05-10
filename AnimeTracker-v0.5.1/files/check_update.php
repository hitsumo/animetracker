<?php
/**
 * Anime Tracker - Update Checker
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint that reports whether the installed version is behind
 * the latest version published on the project website.
 *
 * Returns JSON:
 * {
 *   "error": false,
 *   "current_version": "0.5",
 *   "latest_version": "0.5",
 *   "needs_update": false,
 *   "update_available": "Sistem guncel",
 *   "download_url": null
 * }
 *
 * On error:
 * {
 *   "error": true,
 *   "message": "..."
 * }
 *
 * Design notes:
 * - Loads config.php directly (not db.php) to avoid triggering the
 *   migration manager from an AJAX endpoint. Migration runs on regular
 *   page loads; an update-check request should be side-effect free.
 * - The current version is read from the settings table, which is kept
 *   in sync by migration_manager.php on every page load. The version.txt
 *   file is used as a last-resort fallback if the DB row is missing
 *   (shouldn't happen after a correct install).
 * - There is NO hardcoded version fallback. A missing version is an
 *   error we report, not a stale default we silently return.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

// Open a minimal PDO connection. We do not use db.php because db.php
// triggers the migration manager, which is heavier work than an update
// check should do and could produce HTML output on failure.
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[anime_tracker] check_update DB error: ' . $e->getMessage());
    echo json_encode([
        'error'   => true,
        'message' => 'Veritabani baglantisi basarisiz.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Read the installed version. Primary source: settings table (kept
// up to date by migration_manager on every page load). Fallback:
// version.txt file shipped with the source.
$currentVersion = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'version'");
    $currentVersion = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Settings table missing or query failed - fall through to file fallback.
    error_log('[anime_tracker] check_update settings query failed: ' . $e->getMessage());
}

if (!$currentVersion) {
    $versionFile = __DIR__ . '/version.txt';
    if (file_exists($versionFile)) {
        $currentVersion = trim(file_get_contents($versionFile));
    }
}

if (!$currentVersion) {
    echo json_encode([
        'error'   => true,
        'message' => 'Mevcut surum bulunamadi. Kurulum tamamlanmamis olabilir.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fetch the latest published version. On network failure we treat the
// installed version as the latest (i.e. "no update available").
$updateUrl = 'https://animetracker.sicakcikolata.com/version.txt';
$remote = @file_get_contents($updateUrl);
$latestVersion = ($remote !== false) ? trim($remote) : $currentVersion;

$needsUpdate = version_compare($currentVersion, $latestVersion, '<');

echo json_encode([
    'error'            => false,
    'current_version'  => $currentVersion,
    'latest_version'   => $latestVersion,
    'needs_update'     => $needsUpdate,
    'update_available' => $needsUpdate
        ? ('Yeni versiyon mevcut: ' . $latestVersion)
        : 'Sistem guncel',
    'download_url'     => $needsUpdate
        ? ('https://animetracker.sicakcikolata.com/updates/'
            . $latestVersion . '/AnimeTracker-v' . $latestVersion . '.exe')
        : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
