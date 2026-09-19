<?php
/**
 * Anime Tracker - Install counter endpoint (1.1.43)
 * https://animetracker.sicakcikolata.com/ping.php
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Two jobs, both GET, both JSON:
 *
 *   ping.php?id=<32 hex>&v=<version>&m=single|multi
 *       Records "this install exists". One row per install id (INSERT
 *       IGNORE - a retry or a restored backup with the same id changes
 *       nothing). Stores the three values plus first_seen. Does NOT store
 *       the IP. Sent ONCE per installation by every install that has not
 *       switched the counter off (files/functions/install_ping_helpers.php).
 *
 *   ping.php?stats=1
 *       Public totals, numbers only - never an id. CORS-open so the admin
 *       dashboard of any install can read it straight from the browser
 *       (files/admin/admin.php "Install Counter" card).
 *
 * Why so little validation ceremony: this is a vanity counter. Nothing
 * downstream trusts it; a forged ping can only make the number bigger.
 * What IS guarded against is cost - a random-id flood filling the table -
 * with a per-IP file throttle (one accepted ping per 10 s per IP). That
 * is enough to make bulk inflation slow and boring.
 *
 * Table (create by hand on the catalog host, once):
 *
 *   CREATE TABLE IF NOT EXISTS installs (
 *     install_id  CHAR(32)     NOT NULL,
 *     first_seen  DATETIME     NOT NULL,
 *     version     VARCHAR(20)  NOT NULL DEFAULT '',
 *     mode        ENUM('single','multi') NOT NULL DEFAULT 'single',
 *     PRIMARY KEY (install_id)
 *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 *
 * Config: reuses ../private/admin_push_config.php because this endpoint
 * WRITES and anime_api_config.php is documented as a read-only user. Only
 * the DB_* constants are used; ADMIN_SECRET is loaded but never touched.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$configPath = __DIR__ . '/../private/admin_push_config.php';
if (!file_exists($configPath)) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Service configuration missing']);
    error_log('[ping] config not found: ' . $configPath);
    exit;
}
require_once $configPath;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'GET required']);
    exit;
}

// --- DB ---------------------------------------------------------------

$dbHost = DB_HOST;
$dbPort = 3306;
if (strpos($dbHost, ':') !== false) {
    list($dbHost, $dbPort) = explode(':', $dbHost, 2);
    $dbPort = (int)$dbPort;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database unavailable']);
    error_log('[ping] DB connection failed: ' . $e->getMessage());
    exit;
}

// --- stats ------------------------------------------------------------

if (isset($_GET['stats'])) {
    // Numbers only. Readable from any origin: the admin card fetches this
    // from the browser, and the totals are public by decision (there is
    // nothing in them to protect).
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: public, max-age=3600');

    try {
        $total = (int)$pdo->query("SELECT COUNT(*) FROM installs")->fetchColumn();

        $byMode = ['single' => 0, 'multi' => 0];
        foreach ($pdo->query("SELECT mode, COUNT(*) AS c FROM installs GROUP BY mode") as $row) {
            $byMode[$row['mode']] = (int)$row['c'];
        }

        // Version each install STARTED on (the ping is one-time, so this is
        // "which release brought people in", not "who is on what today").
        $byVersion = [];
        $stmt = $pdo->query(
            "SELECT version, COUNT(*) AS c FROM installs
              GROUP BY version ORDER BY c DESC, version DESC LIMIT 20"
        );
        foreach ($stmt as $row) {
            $byVersion[$row['version'] !== '' ? $row['version'] : 'unknown'] = (int)$row['c'];
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Query failed']);
        error_log('[ping] stats query failed: ' . $e->getMessage());
        exit;
    }

    echo json_encode([
        'ok'           => true,
        'generated_at' => gmdate('Y-m-d\TH:i:s\Z'),
        'total'        => $total,
        'by_mode'      => $byMode,
        'by_version'   => $byVersion,
    ], JSON_PRETTY_PRINT);
    exit;
}

// --- ping -------------------------------------------------------------

$id      = isset($_GET['id']) ? (string)$_GET['id'] : '';
$version = isset($_GET['v'])  ? trim((string)$_GET['v']) : '';
$mode    = isset($_GET['m'])  ? (string)$_GET['m'] : 'single';

if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid id']);
    exit;
}
// Version is stored as sent but only if it looks like one; anything odd
// becomes '' rather than a 400 (an old or forked client should still be
// counted).
if ($version === '' || strlen($version) > 20 || !preg_match('/^\d+(\.\d+)*$/', $version)) {
    $version = '';
}
if ($mode !== 'multi') {
    $mode = 'single';
}

// --- Per-IP throttle --------------------------------------------------
// One accepted ping per 10 seconds per IP. A legitimate install sends one
// request in its lifetime; the only thing that trips this is a flood. Bookkeeping
// is a zero-byte file per IP hash under private/rate_limit/ (the directory
// admin_push.php used to use), touched on each accepted ping. The IP is
// hashed so the throttle files do not become an address log.
$rlDir = __DIR__ . '/../private/rate_limit';
if (!is_dir($rlDir)) {
    @mkdir($rlDir, 0700, true);
}
$ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile = $rlDir . '/ping_' . hash('sha256', $ip);
if (is_file($rlFile) && (time() - (int)filemtime($rlFile)) < 10) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many requests']);
    exit;
}
@touch($rlFile);

// Opportunistic cleanup of stale throttle files so the directory does not
// grow forever: roughly one request in 200 sweeps files older than a day.
if (mt_rand(1, 200) === 1) {
    foreach ((array)@glob($rlDir . '/ping_*') as $f) {
        if (time() - (int)@filemtime($f) > 86400) {
            @unlink($f);
        }
    }
}

// --- Record -----------------------------------------------------------

// INSERT IGNORE: a known id (the client retried because it never saw our
// 200, or a backup was restored elsewhere) is simply not a new install.
try {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO installs (install_id, first_seen, version, mode)
         VALUES (?, UTC_TIMESTAMP(), ?, ?)"
    );
    $stmt->execute([$id, $version, $mode]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Write failed']);
    error_log('[ping] insert failed: ' . $e->getMessage());
    exit;
}

echo json_encode(['ok' => true]);
