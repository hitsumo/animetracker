<?php
/**
 * Anime Tracker - Default List Preference Endpoint (1.1.13)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that sets which list (General / Personal) is selected by
 * default on the main list page (index.php). Called by the small <select>
 * in list_settings.php (a tiny CSRF-protected form posting here).
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   view        - 'personal' to default to the Personal List; anything else
 *                 (incl. 'all' or missing/tampered) means the General List.
 *
 * The choice is written to the per-user user_pref table under the key
 * 'list_view_default' (created on first use, same user_pref family as
 * display_title_english / show_adult_content). No migration needed.
 *
 * index.php reads this default with get_user_pref(); an explicit ?view= in
 * the URL (e.g. a tab click) always overrides it, so the preference only
 * decides the initial landing tab.
 *
 * Why a POST endpoint instead of GET: same reasoning as set_title_pref.php /
 * set_language.php - per KARARLAR Bolum 1 state-changing operations must be
 * CSRF protected, and a GET handler would let any external link flip the
 * preference for the user.
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

// Normalize to a strict 'personal' / 'all'. Any value other than 'personal'
// falls back to the General List, so a missing or tampered field is safe.
$view = (($_POST['view'] ?? '') === 'personal') ? 'personal' : 'all';
// list_view_default is a per-user preference (user_pref, 1.0.1 family).
set_user_pref($pdo, current_user_id(), 'list_view_default', $view);

// Redirect back to the page that triggered the change, with the same
// same-host Referer hardening as set_title_pref.php / set_language.php.
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
