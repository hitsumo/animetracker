<?php
/**
 * Anime Tracker - Chronology Display Mode Toggle (1.1.15)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint behind the single "cycle" button that switches how
 * chronology markers are ordered on the detail page and the chronology
 * timeline: release -> story -> both -> release.
 *
 * Unlike the saved default in list settings (user_pref
 * 'chrono_display_mode'), this writes an EPHEMERAL override into the
 * SESSION, so cycling changes the current view for this session only and
 * NEVER overwrites the persistent default. chrono_current_mode() reads
 * the session first, then falls back to the saved pref, then 'release'.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   mode        - Target mode ('release' | 'story' | 'both'). Anything
 *                 else falls back to 'release'.
 *
 * Optional POST fields:
 *   persist     - '1' writes the PERSISTENT per-user default (user_pref
 *                 'chrono_display_mode') and clears the ephemeral session
 *                 override, so the new default takes effect immediately.
 *                 This is how the list-settings <select> saves the default.
 *                 Anything else (the cycle button) sets only the session
 *                 override, leaving the saved default untouched.
 *
 * Redirects back to the triggering page via same-host Referer (so it works
 * from both anime_details.php and chronology.php), same POST+CSRF+Referer
 * hardening as set_list_view_pref.php / set_language.php.
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

// Validate the requested mode against the canonical list; unknown -> release.
$mode = (string)($_POST['mode'] ?? 'release');
if (!in_array($mode, chrono_display_modes(), true)) {
    $mode = 'release';
}

if (($_POST['persist'] ?? '') === '1') {
    // List-settings default: write the persistent pref and drop any session
    // override so the freshly-saved default governs the next view.
    set_user_pref($pdo, current_user_id(), 'chrono_display_mode', $mode);
    unset($_SESSION['chrono_display_mode']);
} else {
    // Cycle button: ephemeral session override only.
    $_SESSION['chrono_display_mode'] = $mode;
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
