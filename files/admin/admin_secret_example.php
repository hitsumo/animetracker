<?php
/**
 * Anime Tracker - Admin HMAC Secret — EXAMPLE FILE
 *
 * This file holds the shared secret used to sign admin push requests.
 * The same secret MUST be set in the server's admin_push_config.php
 * as ADMIN_SECRET.
 *
 * Setup:
 *   1. Copy this file:  cp admin/admin_secret_example.php admin/admin_secret.php
 *   2. Generate a secret:
 *        Linux/Mac:   openssl rand -hex 32
 *        Windows:     C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32));"
 *   3. Paste the generated string below (at least 64 hex characters)
 *   4. Set the SAME string in admin_push_config.php on the server
 *
 * NEVER commit admin_secret.php to git — only this example file.
 */

define('ADMIN_PUSH_SECRET', 'REPLACE_WITH_YOUR_64_CHAR_HEX_SECRET');
