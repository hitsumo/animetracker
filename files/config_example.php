<?php

/**
 * Anime Tracker - Configuration Template
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
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

// AnimeSchedule API key (optional).
// Used by the "fetch from AnimeSchedule" button on the add/edit forms to
// pull synopsis, titles, broadcast info, etc. Get a free key at
// https://animeschedule.net (account -> API), then uncomment the line
// below and paste your key. If this constant is missing or empty, the
// feature is simply disabled - the rest of the application works normally.
// define('ANIMESCHEDULE_API_KEY', '');

// Multi-user mode.
// false (default) - single-user / self-host: no login, the application
//   behaves exactly as it always has. Leave this as-is for a personal
//   install.
// true            - online / multi-user: login is required and each user
//   sees their own list. Only set this on a hosted, multi-user server.
//
// If this constant is missing (older config.php files), db.php defaults
// it to false, so existing single-user installs keep working unchanged.
define('MULTI_USER_MODE', false);

// Public address of this installation (optional, 1.1.30).
// Only used for the absolute URLs that search engines require: the
// canonical link, the Open Graph tags and the sitemap entries. Everything
// else in the application links relatively and never needs this.
//
// LEAVE IT COMMENTED OUT unless you need it. Without it the address is
// rebuilt from the request itself (scheme + Host header + the directory
// the application lives in), which is correct for a normal install.
//
// Set it when the request does NOT reveal the public address - typically
// behind a reverse proxy or a CDN that forwards a different Host header.
// Write the root of the application, with the scheme and WITHOUT a
// trailing slash:
// define('SITE_URL', 'https://example.com');

// IndexNow key (optional, 1.1.32).
// IndexNow is the ping protocol Bing and Yandex share: when a page is
// added, changed or deleted, the site tells them so instead of waiting to
// be re-crawled. (Google does not take part; the sitemap covers it.)
//
// It only does anything in online mode (MULTI_USER_MODE = true) - a
// self-host install publishes no sitemap and announces nothing.
//
// Setup:
//   1. php indexnow_ping.php --genkey        (prints a fresh key)
//   2. uncomment the line below and paste it in
//   3. define SITE_URL above - the command-line ping cannot work out the
//      public address on its own, and refuses to run without it
//   4. open https://<your site>/<the key>.txt - it must return the key
//   5. schedule indexnow_ping.php (hourly is plenty)
//
// Without this constant nothing is queued and nothing is sent; the rest of
// the application is unaffected.
// define('INDEXNOW_KEY', '');
