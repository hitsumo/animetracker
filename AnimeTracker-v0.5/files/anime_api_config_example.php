<?php
/**
 * Anime Tracker API - Database Configuration (EXAMPLE)
 *
 * This file provides read-only DB credentials for catalog.php.
 * It MUST stay outside public_html (e.g. ../private/) so it is
 * never directly accessible from the web.
 *
 * Setup:
 *   1. Copy this file:  cp anime_api_config_example.php anime_api_config.php
 *   2. Fill in your database credentials below
 *   3. Place the file outside public_html (e.g. /private/ directory)
 *
 * Security note:
 *   - Use a DB user with SELECT-only privileges for this config.
 *   - The admin_push_config.php uses a separate user with write access.
 *   - If your DB runs on a non-standard port (e.g. Plesk uses 3308),
 *     use the format 'localhost:3308' for DB_HOST.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_readonly_db_user');
define('DB_PASS', 'your_db_password');
