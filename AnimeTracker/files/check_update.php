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
 *   "update_available": "Sistem guncel"
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
 * - No download_url is returned. The actual upgrade path is the
 *   in-place ZIP auto-update handled by update.php (called by the UI
 *   via runUpdate() in list_settings.php). The old .exe download_url
 *   was a leftover from a pre-auto-update design: it pointed at an
 *   installer .exe that is no longer published on the server (the
 *   .exe is distributed out-of-band for fresh installs only). Nothing
 *   in the UI ever consumed download_url, so it was removed to avoid
 *   returning a dead link that 404s.
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

// Fetch the latest published version. On network failure we report
// an explicit error rather than silently claiming the system is up
// to date. Previously a 404 or network error returned "Sistem guncel"
// - a lie that masked 27 days of broken auto-update in 0.5.
$updateUrl = 'https://animetracker.sicakcikolata.com/version.txt';

$context = stream_context_create([
    'http'  => ['timeout' => 5, 'ignore_errors' => true],
    'https' => ['timeout' => 5, 'ignore_errors' => true],
]);

$remote = @file_get_contents($updateUrl, false, $context);

// $http_response_header is auto-populated when context has ignore_errors.
// Without ignore_errors, a 404/500 response would make file_get_contents
// return false and we would not know if the server is down or just
// missing the file.
$httpStatus = 0;
if (isset($http_response_header[0])
    && preg_match('#^HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
    $httpStatus = (int)$m[1];
}

if ($remote === false || $httpStatus !== 200) {
    error_log('[anime_tracker] check_update remote fetch failed: '
        . $updateUrl . ' (HTTP ' . $httpStatus . ')');
    echo json_encode([
        'error'           => true,
        'message'         => 'Surum sunucusuna ulasilamadi. '
                           . 'Internet baglantinizi kontrol edin veya '
                           . 'daha sonra tekrar deneyin.',
        'current_version' => $currentVersion,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$latestVersion = trim($remote);

// Defensive: empty body or non-version-shaped response should not
// reach version_compare (which would return weird results).
if ($latestVersion === '' || !preg_match('/^\d+(\.\d+)*$/', $latestVersion)) {
    error_log('[anime_tracker] check_update remote returned invalid version: '
        . var_export($latestVersion, true));
    echo json_encode([
        'error'           => true,
        'message'         => 'Surum sunucusu gecersiz cevap dondurdu.',
        'current_version' => $currentVersion,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$needsUpdate = version_compare($currentVersion, $latestVersion, '<');

echo json_encode([
    'error'            => false,
    'current_version'  => $currentVersion,
    'latest_version'   => $latestVersion,
    'needs_update'     => $needsUpdate,
    'update_available' => $needsUpdate
        ? ('Yeni versiyon mevcut: ' . $latestVersion)
        : 'Sistem guncel',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
