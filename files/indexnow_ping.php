<?php

/**
 * Anime Tracker - CLI IndexNow queue drain (1.1.32)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Command-line entry point for the IndexNow announcements. Pages never
 * call the endpoint themselves; adding, editing or deleting catalog
 * content only writes a row into indexnow_queue, and this script sends
 * what has accumulated. See functions/indexnow_helpers.php for why the
 * work is split that way.
 *
 * The two CLI rules of sync_aired.php apply here for the same reasons:
 *
 *   - No HTTP context: there is no session and no logged-in user, so this
 *     script does NOT call can($pdo, 'moderate'). What it does is
 *     catalog-level and per-installation, not per-user, and cron is
 *     already a trusted environment.
 *   - Path-independent loading: __DIR__ resolves config.php / db.php /
 *     functions.php no matter which directory cron invokes it from.
 *     config.php is also where INDEXNOW_KEY and SITE_URL are defined.
 *
 * SITE_URL IS REQUIRED HERE, unlike everywhere else in the application.
 * Over HTTP the public address is rebuilt from the request itself, but a
 * command line has no Host header - so without SITE_URL every submitted
 * address would come out as "http://localhost/..." and be rejected for not
 * belonging to the host. The script refuses to run rather than send those.
 *
 * SETUP (once)
 *
 *   1. Pick a key:            php indexnow_ping.php --genkey
 *   2. Put it in config.php:  define('INDEXNOW_KEY', '<the key>');
 *      and, in the same file: define('SITE_URL', 'https://example.com');
 *   3. Check the key file is publicly readable:
 *      https://example.com/<the key>.txt  must return the key as text.
 *      (files/.htaccess rewrites that address to indexnow_key.php. On a
 *      server without mod_rewrite, serve the same address some other way -
 *      the protocol reads the file, it does not care what generated it.)
 *   4. Schedule this script.
 *
 * Usage:
 *   php indexnow_ping.php              send what is queued
 *   php indexnow_ping.php --status     report configuration and queue, send nothing
 *   php indexnow_ping.php --dry-run    print the URLs of the next batch, send nothing
 *   php indexnow_ping.php --retry      clear attempt counters, then send
 *   php indexnow_ping.php --genkey     print a fresh random key, do nothing else
 *
 * Cron (Linux) - hourly, log appended:
 *   0 * * * * php /path/to/anime_tracker/indexnow_ping.php >> /var/log/anime_indexnow.log 2>&1
 *
 * Windows Task Scheduler - run program:
 *   C:\xampp\php\php.exe
 *   with argument:
 *   C:\xampp\htdocs\anime_tracker\indexnow_ping.php
 *
 * Hourly is a deliberate choice, not a minimum. IndexNow promises
 * "minutes", not "seconds", and batching an hour of edits into one request
 * is both cheaper and closer to what the protocol asks of submitters.
 *
 * Exit codes: 0 = nothing to do or everything accepted, 1 = configuration
 * error or a rejected submission, so cron and monitoring can tell them
 * apart.
 */

// Refuse to run over HTTP. This script is for the command line only;
// without this guard a browser request to the file could drain the queue.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

// --genkey is answered before anything else is loaded: it is the step a
// site owner takes BEFORE there is a key to configure, so it must not
// depend on the feature being configured (or on the database being
// reachable at all).
$argv = isset($argv) ? $argv : [];
if (in_array('--genkey', $argv, true)) {
    // 32 hex characters. Well inside the protocol's 8-128 range, and hex
    // keeps it copy-paste safe into config.php and into a file name.
    $fresh = bin2hex(random_bytes(16));
    fwrite(STDOUT, "IndexNow key generated:\n\n");
    fwrite(STDOUT, "  " . $fresh . "\n\n");
    fwrite(STDOUT, "Add this line to config.php:\n\n");
    fwrite(STDOUT, "  define('INDEXNOW_KEY', '" . $fresh . "');\n\n");
    fwrite(STDOUT, "SITE_URL must also be defined there, e.g.:\n\n");
    fwrite(STDOUT, "  define('SITE_URL', 'https://example.com');\n\n");
    fwrite(STDOUT, "Then check that https://example.com/" . $fresh . ".txt\n");
    fwrite(STDOUT, "returns the key as plain text before scheduling this script.\n");
    exit(0);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$status = in_array('--status', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);
$retry  = in_array('--retry', $argv, true);

// ---------------------------------------------------------------------
// --status: what is configured, what is waiting. Sends nothing.
// ---------------------------------------------------------------------
if ($status) {
    $key  = indexnow_key();
    $base = indexnow_base_url();

    fwrite(STDOUT, "indexnow status\n");
    fwrite(STDOUT, "  mode:          " . (seo_indexing_allowed()
        ? 'online (indexable)' : 'self-host (nothing is announced)') . "\n");
    fwrite(STDOUT, "  key:           " . ($key !== ''
        ? substr($key, 0, 4) . '... (' . strlen($key) . ' chars)'
        : 'MISSING or malformed - define INDEXNOW_KEY in config.php') . "\n");
    fwrite(STDOUT, "  site url:      " . ($base !== null
        ? $base : 'MISSING - define SITE_URL in config.php') . "\n");
    fwrite(STDOUT, "  key file:      " . ($key !== '' && $base !== null
        ? indexnow_key_location($base) : '-') . "\n");
    fwrite(STDOUT, "  curl:          " . (function_exists('curl_init') ? 'yes' : 'NO') . "\n");
    fwrite(STDOUT, "  queued:        " . indexnow_queue_size($pdo) . "\n");
    fwrite(STDOUT, "  stuck:         " . indexnow_stuck_count($pdo)
        . " (attempts >= " . INDEXNOW_MAX_ATTEMPTS . "; use --retry)\n");
    $lastPing = get_setting($pdo, 'last_indexnow_ping');
    fwrite(STDOUT, "  last ping:     "
        . ($lastPing === null ? 'never' : (string)$lastPing . ' UTC') . "\n");
    fwrite(STDOUT, "  last count:    "
        . (string)get_setting($pdo, 'last_indexnow_count', '0') . "\n");

    exit(indexnow_enabled() ? 0 : 1);
}

// ---------------------------------------------------------------------
// --retry: give exhausted rows one more chance before draining.
// ---------------------------------------------------------------------
if ($retry) {
    $reset = indexnow_reset_attempts($pdo);
    fwrite(STDOUT, "attempt counters cleared on " . $reset . " row(s)\n");
}

// ---------------------------------------------------------------------
// Drain.
// ---------------------------------------------------------------------
$result = indexnow_flush($pdo, ['dry_run' => $dryRun]);

if ($dryRun) {
    if (empty($result['preview'])) {
        fwrite(STDOUT, "dry run: queue is empty, nothing would be sent\n");
    } else {
        fwrite(STDOUT, "dry run: would send " . count($result['preview'])
            . " url(s) to " . INDEXNOW_ENDPOINT . "\n");
        foreach ($result['preview'] as $url) {
            fwrite(STDOUT, "  " . $url . "\n");
        }
    }
}

// Short, plain ASCII summary so cron logs stay useful - same shape as the
// aired sync's summary line.
fwrite(STDOUT, sprintf(
    "indexnow done: sent=%d batches=%d left=%d stuck=%d%s\n",
    (int)$result['sent'],
    (int)$result['batches'],
    (int)$result['left'],
    (int)$result['stuck'],
    empty($result['errors']) ? '' : ' errors=' . implode(' | ', $result['errors'])
));

exit(empty($result['ok']) ? 1 : 0);
