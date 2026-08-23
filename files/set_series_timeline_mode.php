<?php
/**
 * Anime Tracker - Series Timeline Mode (1.1.23)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint behind the list-settings <select> that saves which tab
 * the series timeline (series_timeline.php) opens in: 'chain' (the
 * next_in_series walk) or 'airdate' (every anime sharing the series
 * name, by first air/release date).
 *
 * The tabs on series_timeline.php itself do NOT post here - they are
 * plain GET links; the page writes the EPHEMERAL session override
 * directly (view-only state, no side effect worth a token). This
 * endpoint exists for the PERSISTENT per-user default and mirrors
 * set_chrono_mode.php (1.1.15) so the two prefs age together.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   mode        - Target mode ('chain' | 'airdate'). Anything else
 *                 falls back to 'chain'.
 *
 * Optional POST fields:
 *   persist     - '1' writes the PERSISTENT per-user default (user_pref
 *                 'series_timeline_mode') and clears the ephemeral
 *                 session override, so the new default takes effect
 *                 immediately. This is how the list-settings <select>
 *                 saves. Anything else sets only the session override.
 *
 * Redirects back via same-host Referer, same POST+CSRF+Referer
 * hardening as set_chrono_mode.php / set_list_view_pref.php.
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

// Validate the requested mode against the canonical list; unknown -> chain.
$mode = (string)($_POST['mode'] ?? 'chain');
if (!in_array($mode, series_timeline_modes(), true)) {
    $mode = 'chain';
}

if (($_POST['persist'] ?? '') === '1') {
    // List-settings default: write the persistent pref and drop any session
    // override so the freshly-saved default governs the next view.
    set_user_pref($pdo, current_user_id(), 'series_timeline_mode', $mode);
    unset($_SESSION['series_timeline_mode']);
} else {
    // Ephemeral session override only.
    $_SESSION['series_timeline_mode'] = $mode;
}

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
