<?php
/**
 * Anime Tracker - Language Switcher Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that switches the UI language. Called by the small
 * TR / EN switcher in the page header (each option is a tiny CSRF-
 * protected form posting to this file).
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   lang        - 'tr' or 'en' (any other value is silently rejected)
 *
 * The chosen language is written to the settings table under the
 * 'display_language' key (created on first switch, see schema.sql
 * "missing rows are created on demand"). No migration is needed
 * because the row is a runtime-created key in the same family as
 * last_aired_sync and last_catalog_sync.
 *
 * After writing, the user is redirected back to the page that
 * triggered the switch (taken from Referer with a same-host check
 * to prevent open redirects). If Referer is missing or off-host,
 * the fallback target is index.php.
 *
 * Why a POST endpoint instead of GET '?lang=en':
 *   - Per KARARLAR Bolum 1, state-changing operations must be CSRF
 *     protected. GET cannot carry a CSRF token in a way that resists
 *     accidental link sharing or bot crawling.
 *   - A GET handler would let any external link change the user's
 *     language for them, which is mildly annoying but technically
 *     a CSRF vector.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Gate 1: POST only. A GET arrival means either a bookmark or a direct
// URL access - send the user to the list page rather than acting.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Gate 2: CSRF. Same pattern as the other POST endpoints in this
// project (delete_chronology_marker.php, update_emotion.php, etc).
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// Gate 3: language whitelist. Server is authoritative - any value
// outside the allowed set is silently dropped (no DB write, no
// error message). The UI only ever sends 'tr' or 'en', so a value
// outside this set means either an old cached form or tampering.
$allowed = ['tr', 'en'];
$lang    = $_POST['lang'] ?? '';

if (in_array($lang, $allowed, true)) {
    set_setting($pdo, 'display_language', $lang);
}

// Redirect back to the page that triggered the switch. We accept
// Referer only if it points at the same host as the current request -
// any other value is treated as missing, and the user lands on the
// list page. This is a small open-redirect hardening so a malicious
// link cannot bounce users through this endpoint to a third party.
$target = 'index.php';
$ref    = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref !== '') {
    $parts = parse_url($ref);
    if (
        is_array($parts)
        && isset($parts['host'])
        && isset($_SERVER['HTTP_HOST'])
        && strcasecmp($parts['host'], $_SERVER['HTTP_HOST']) === 0
    ) {
        // Keep only path + query so a full URL cannot smuggle a scheme
        // or host change into the Location header.
        $path  = $parts['path']  ?? '/';
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $target = $path . $query;
    }
}

header('Location: ' . $target);
exit;
