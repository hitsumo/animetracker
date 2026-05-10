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
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Step 1: Make sure the application has been installed.
// If config.php is missing, send the user to the setup wizard.
if (!file_exists(__DIR__ . '/config.php')) {
    header('Location: setup.php');
    exit;
}

require_once __DIR__ . '/config.php';

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
