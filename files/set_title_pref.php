<?php
/**
 * Anime Tracker - Title-language Preference Endpoint (0.7.2, reworked 1.1.21)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that sets the "Title Language" preference. Called by the
 * <select> in list_settings.php (a tiny CSRF-protected form posting here).
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   lang        - a language code from title_lang_codes() ('en', 'ja', ...),
 *                 or '' for Romaji (the default)
 *
 * The choice is written to user_pref under the key 'display_title_lang'.
 *
 * 1.1.21: this used to take a BOOLEAN 'enabled' field and write
 * 'display_title_english' ('1'/'0'), because the only alternative to Romaji
 * was the dedicated title_english column. That column is gone - titles now
 * carry [xx] language tags inside alternative_titles, so ANY language can be
 * the display language. The old pref rows are migrated in 1.1.21 ('1' -> 'en').
 *
 * This preference is INDEPENDENT of the UI language: it only decides WHICH
 * language's title is shown. See anime_helpers.php display_title() /
 * display_title_lang().
 *
 * Why a POST endpoint instead of GET: same reasoning as set_language.php -
 * per KARARLAR Bolum 1 state-changing operations must be CSRF protected, and
 * a GET handler would let any external link flip the preference for the user.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Gate 1: POST only. A GET arrival means a bookmark or direct URL -
// send the user to the list page rather than acting.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Gate 2: CSRF. Same pattern as the other POST endpoints.
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// Whitelist against title_lang_codes(). Anything unrecognised - a missing
// field, a hand-crafted POST, or a language later removed from the map -
// stores '' and therefore falls back to Romaji, so the preference can never
// hold a code that display_title() would not be able to resolve.
$lang = strtolower(trim((string)($_POST['lang'] ?? '')));
if (!is_valid_title_lang($lang)) {
    $lang = '';
}
// display_title_lang is a per-user preference (user_pref, 1.0.1).
set_user_pref($pdo, current_user_id(), 'display_title_lang', $lang);

// Redirect back to the page that triggered the toggle, with the same
// same-host Referer hardening as set_language.php.
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
        $path  = $parts['path']  ?? '/';
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $target = $path . $query;
    }
}

header('Location: ' . $target);
exit;
