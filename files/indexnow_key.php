<?php

/**
 * Anime Tracker - IndexNow key file
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.32.
 *
 * Served as /{key}.txt through the rewrite in files/.htaccess.
 *
 * WHAT THE PROTOCOL WANTS
 *
 * IndexNow proves that whoever submits a URL controls the host, and it
 * does that the cheapest way possible: the submitter puts a text file
 * named {key}.txt on the host, containing that same key and nothing else.
 * The engine fetches it once, sees the two match, and accepts the
 * submissions. The key is therefore NOT a secret - it is published on
 * purpose. What it protects against is a stranger announcing URLs on a
 * host they do not own.
 *
 * WHY A PHP FILE AND NOT A REAL .txt
 *
 * The same reasoning as robots.php in 1.1.30, and it applies twice over:
 *
 *   1. files/.htaccess denies EVERY .txt file, so a real key file dropped
 *      next to index.php would answer 403 to the very crawler that has to
 *      read it - and the submissions would be rejected with nothing in the
 *      site's own logs to explain why. Rewriting to a .php target means
 *      the deny list is evaluated against indexnow_key.php and the .txt
 *      rule never applies, so that rule can stay exactly as strict as it
 *      is. (The 1.1.30 notes predicted an .htaccess exception would be
 *      needed here; the rewrite is what makes it unnecessary.)
 *   2. The key lives in config.php. A generated file cannot fall out of
 *      sync with it, and there is no second place to edit when the key is
 *      rotated - change the constant, and the published file changes with
 *      it.
 *
 * The address still ENDS in .txt, which is what the protocol asks for; only
 * the handler behind it is PHP.
 *
 * WHY THE NAME IS CHECKED
 *
 * The rewrite matches any 16-128 character alphanumeric name, not just the
 * configured one, because .htaccess cannot read config.php. So this file
 * does the comparing: only the exact configured key answers 200, every
 * other name answers 404. Without that check the site would publish its
 * key at every conceivable .txt address, which is untidy and would make a
 * key rotation look like it had not taken effect.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');

$key = indexnow_key();

// Which name was actually requested? SCRIPT_NAME would say
// "indexnow_key.php" (the rewrite target), so the ORIGINAL address is read
// from REQUEST_URI, with the query string and any directory prefix cut off.
$requested = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
$requested = (string)parse_url($requested, PHP_URL_PATH);
$requested = basename($requested);

// Not configured, self-host mode, or a different file name: there is no
// such document here. 404 rather than 403 - a crawler drops a 404 from its
// schedule instead of retrying it.
if ($key === '' || !seo_indexing_allowed() || $requested !== $key . '.txt') {
    http_response_code(404);
    echo "Not found.\n";
    exit;
}

// The whole document: the key, and nothing else.
echo $key . "\n";
