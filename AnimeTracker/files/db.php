<?php

/**
 * Anime Tracker - Shared Database Connection
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * ---
 *
 * This file provides a shared PDO connection used by all application
 * modules. It loads config.php (created by setup.php during install)
 * and exposes a $pdo variable.
 *
 * If config.php does not exist, the user is redirected to setup.php
 * so the WordPress-style installer can run.
 *
 * Usage in other modules:
 *     require_once __DIR__ . '/db.php';
 *     // $pdo is now available
 */

// All date/time calculations use UTC internally. The database stores
// UTC, PHP compares UTC, and the browser converts to the user's local
// timezone for display. This prevents offset bugs caused by the
// server's default timezone (e.g. XAMPP shipping with Europe/Berlin).
date_default_timezone_set('UTC');

// Start the PHP session so modules can use $_SESSION (CSRF tokens,
// future auth state, etc.). We start it here centrally so every page
// that includes db.php gets a session - no module has to remember
// to call session_start() on its own.
//
// Cookie hardening (set BEFORE session_start, since cookie params only
// apply to a not-yet-started session). These flags are safe in both modes
// and do not change self-host behaviour:
//   - HttpOnly: the session cookie is not readable from JavaScript (XSS
//     mitigation). The app never reads it from JS, so nothing breaks.
//   - SameSite=Lax: the cookie is not sent on cross-site POSTs (CSRF defense
//     in depth, alongside csrf_verify()). Same-site navigation is unaffected.
//   - Secure: only when the request is itself HTTPS, so plain-http localhost
//     (XAMPP) still receives the cookie and self-host keeps working. Behind a
//     TLS-terminating proxy that does not set $_SERVER['HTTPS'], a forwarded-
//     proto check would be needed; the production host sets HTTPS directly.
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (
        (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
    );
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $is_https,
    ]);
    session_start();
}

// Step 1: Make sure the application has been installed.
// If config.php is missing, send the user to the setup wizard.
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: setup.php');
    exit;
}

require_once __DIR__ . '/config.php';

// Multi-user mode default (backward compatibility).
// config.php files created before multi-user support do not define
// MULTI_USER_MODE. Default it to false here so existing single-user
// installs keep working with no config change. Deliberately NOT added
// to the $required list below, because old configs legitimately lack it.
if (!defined('MULTI_USER_MODE')) {
    define('MULTI_USER_MODE', false);
}

// current_user_id() - the single source of truth for "who is the current
// user". Every endpoint that reads or writes personal data must call this
// instead of hard-coding 1, so that flipping MULTI_USER_MODE switches the
// whole application between the two behaviours.
//
//   - Single-user / self-host (MULTI_USER_MODE = false): always returns 1,
//     the seeded "owner" row, so every user_id FK resolves. Today's
//     behaviour is preserved exactly.
//   - Multi-user / online (MULTI_USER_MODE = true): returns the logged-in
//     user's id from the session. Until the auth milestone lands there is
//     no login yet, so this returns null when no session user is set;
//     endpoints enforce login separately.
//
// Defined here in db.php (not in functions/) on purpose: every page loads
// db.php but not every page loads functions.php, and this is a foundational
// identity function that must always be available. This avoids the
// "Call to undefined function" fatal class (cf. the statistics.php lesson).
if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        if (!MULTI_USER_MODE) {
            return 1;
        }
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
}

// Step 2: Make sure config.php defined everything we need.
// This catches the case where config.php exists but is incomplete
// or corrupted (for example, a half-written file from a failed setup).
$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $constant) {
    if (!defined($constant)) {
        die(
            'Configuration error: ' . htmlspecialchars($constant) .
            ' is not defined in config.php. ' .
            'Please re-run setup.php to regenerate the configuration.'
        );
    }
}

// Step 3: Open the PDO connection.
// - charset=utf8mb4 is required for Turkish characters and emoji
// - ERRMODE_EXCEPTION makes failures throw instead of silently returning false
// - EMULATE_PREPARES = false forces real prepared statements (safer against SQLi)
// - DB_HOST supports "host:port" format (e.g. "127.0.0.1:3307" or "localhost:3308")
try {
    $dsn_host = DB_HOST;
    $dsn_port = '';
    if (strpos(DB_HOST, ':') !== false) {
        list($dsn_host, $dsn_port) = explode(':', DB_HOST, 2);
    }
    $dsn = 'mysql:host=' . $dsn_host
         . ($dsn_port !== '' ? ';port=' . $dsn_port : '')
         . ';dbname=' . DB_NAME
         . ';charset=utf8mb4';

    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // For now (offline use) we show the raw error to help debugging.
    // For the future online version, this should be replaced with a
    // generic message and the real error should be written to a log file.
    die('Database connection error: ' . htmlspecialchars($e->getMessage()));
}

// Step 4: Run any pending database migrations.
// This is a no-op when the schema is already up to date, so the cost
// on every page load is minimal (one SELECT and one filesystem scan).
// New migrations are added under migration/{version}/upgrade.sql and
// will be detected automatically on the next request.
require_once __DIR__ . '/migration_manager.php';
try {
    MigrationManager::run($pdo);
} catch (Exception $e) {
    error_log('[anime_tracker] Migration failed: ' . $e->getMessage());
    http_response_code(500);

    // Try to report a helpful log location. ini_get('error_log') returns
    // the explicit path if one is configured; otherwise (empty string)
    // PHP writes to the web server's default error log, which varies
    // between Apache/Nginx/IIS and operating systems.
    $logPath = ini_get('error_log');
    $logHint = !empty($logPath)
        ? '<code>' . htmlspecialchars($logPath, ENT_QUOTES, 'UTF-8') . '</code>'
        : 'web sunucusunun hata gunlugu';

    die(
        '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">' .
        '<title>Veritabani Guncelleme Hatasi</title></head><body>' .
        '<h1>Veritabani guncelleme islemi basarisiz oldu</h1>' .
        '<p>Detay icin sunucu loglarini kontrol edin (' . $logHint . ').</p>' .
        '</body></html>'
    );
}
