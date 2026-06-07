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

/**
 * ONLINE (multi-user) instances only.
 *
 * Set the central catalog server's admin_push.php URL here as well. When
 * it is defined, approving a pending anime in admin_pending.php will
 * automatically push the catalog to the server (server-to-server, signed
 * with the secret above) - no localhost gate, no manual step. This is how
 * anime added on the online instance reach offline/self-host clients.
 *
 * Self-host (single-user) installs can leave this commented out: they push
 * manually with admin_sync.php instead, which has its own ADMIN_PUSH_URL.
 * A separate name (CATALOG_PUSH_URL) is used on purpose so admin_sync.php's
 * own ADMIN_PUSH_URL const never collides with this define.
 */
// define('CATALOG_PUSH_URL', 'https://yourdomain.com/admin_push.php');
