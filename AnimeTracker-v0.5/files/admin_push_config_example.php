<?php

/**
 * Anime Tracker - Admin Push Config (SERVER SIDE)
 *
 * This file lives OUTSIDE public_html so it's never web-accessible.
 * Place at: /domains/animetracker.sicakcikolata.com/private/admin_push_config.php
 *
 * Contains:
 *   - ADMIN_SECRET: shared secret used by admin_push.php to verify HMAC
 *     signatures on incoming pushes. MUST match the ADMIN_PUSH_SECRET
 *     defined in the admin's local config.php.
 *   - DB credentials: used by admin_push.php to write to the anime DB.
 *     Typically the same as anime_api_config.php but with write privileges.
 *
 * Generating a strong secret:
 *   On Linux/Mac:   openssl rand -hex 32
 *   On Windows:     php -r "echo bin2hex(random_bytes(32));"
 *
 * The secret should be at least 32 hex characters (64 characters total).
 */

// Shared HMAC secret. Long random string. Keep this IDENTICAL to the
// ADMIN_PUSH_SECRET in the admin's local config.php.
define('ADMIN_SECRET', 'REPLACE_WITH_64_CHAR_HEX_FROM_openssl_rand_-hex_32');

// Database connection details for the catalog DB.
// Note that port format can be either plain "localhost" or "host:port"
// if your MariaDB listens on a non-standard port (e.g. "localhost:3308"
// on Plesk shared hosting).
define('DB_HOST', 'localhost:3308');
define('DB_NAME', 'sicakcik_animetracker');
define('DB_USER', 'sicakcik_animetracker');
define('DB_PASS', 'REPLACE_WITH_DB_PASSWORD');
