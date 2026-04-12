<?php

/**
 * Anime Tracker - Configuration Template
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * This is a TEMPLATE file. Do not edit it directly.
 *
 * The real config.php is generated automatically by setup.php during
 * the first installation. If you prefer to set things up manually,
 * copy this file to config.php and fill in the values below.
 *
 * Once config.php exists, the application will use it on every request.
 * If config.php is missing, the user is redirected to setup.php.
 */

// Database server hostname.
// Local installs (XAMPP, WAMP, MAMP) usually use 'localhost'.
// On shared hosting, use the host provided by your hosting company.
define('DB_HOST', 'localhost');

// Database name.
// The setup wizard creates this database automatically if it does
// not already exist (and the DB user has permission to do so).
define('DB_NAME', 'anime_tracker');

// Database username.
// On local XAMPP / WAMP installs this is usually 'root'.
// On shared hosting, use the username provided by your hosting company.
define('DB_USER', 'root');

// Database password.
// On a default XAMPP install this is empty. WAMP often uses 'root'.
// On shared hosting, use the password provided by your hosting company.
define('DB_PASS', '');
