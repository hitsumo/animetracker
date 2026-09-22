<?php
/**
 * Anime Tracker - Default "Recently Updated" Tab Endpoint (1.1.44)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint behind the list-settings <select> that saves which tab
 * recent.php opens in: 'episodes' (anime whose aired episode count
 * changed most recently) or 'content' (anime added to / edited in the
 * catalog most recently).
 *
 * The tabs on recent.php itself do NOT post here - they are plain GET
 * links (?tab=...), and an explicit ?tab= always wins for that view.
 * This endpoint exists only for the PERSISTENT per-user default and
 * mirrors set_list_view_pref.php (1.1.13): same gates, same redirect.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   tab         - one of recent_tabs() ('episodes' / 'content' /
 *                 'watched' since 1.1.45); anything else (missing/tampered)
 *                 means the episode tab,
 *                 which is the shipped default.
 *
 * Written to user_pref under 'recent_default_tab' (created on first use,
 * same family as list_view_default). No migration needed.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Gate 1: POST only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Gate 2: CSRF.
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// Normalize to a strict 'content' / 'episodes'. Any value other than
// 'content' falls back to the episode tab, so a tampered field is safe.
// 1.1.46 - whitelist from recent_tabs(), not a hard-coded pair: 1.1.45 added
// the 'watched' option to the list-settings select but this line still knew
// only 'content', so choosing "Son Izlenenler" silently saved 'episodes'.
$tab = (string)($_POST['tab'] ?? '');
if (!in_array($tab, recent_tabs(), true)) {
    $tab = 'episodes';
}
set_user_pref($pdo, current_user_id(), 'recent_default_tab', $tab);

// Redirect back to the page that triggered the change (same-host only).
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
