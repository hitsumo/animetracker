<?php
/**
 * Anime Tracker - Spoiler Guard Preference Endpoint (1.1.33)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that toggles the "hide the synopsis of a later entry
 * while its earlier entries are unwatched" preference. Called by the
 * checkbox in list_settings.php (a tiny CSRF-protected form posting
 * here), exactly like set_adult_pref.php does for adult content.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   enabled     - '1' to keep the guard on, anything else = off
 *
 * The choice is written to the user_pref table under the key
 * 'spoiler_guard' (created on first use, same runtime-key family as
 * show_adult_content / chrono_display_mode). No migration needed.
 *
 * Default is ON: when the key is absent the guard applies, so a fresh
 * install - and every anonymous visitor, who has no preference row at
 * all - is protected without touching a setting. Turning it OFF is the
 * deliberate opt-out. Note the inversion against set_adult_pref.php:
 * there the safe default is '0', here it is '1'. A missing or tampered
 * field therefore lands on '1' (guard stays on), which is the safe side
 * for THIS preference.
 *
 * Why a POST endpoint instead of GET: same reasoning as set_adult_pref.php
 * and set_language.php - per KARARLAR Bolum 1 state-changing operations
 * must be CSRF protected, and a GET handler would let any external link
 * flip the preference for the user.
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

// The form posts a hidden '0' before the checkbox, so an unchecked box
// still arrives as '0'. Anything that is not exactly '0' keeps the
// guard on (see the default note above).
$enabled = (($_POST['enabled'] ?? '1') === '0') ? '0' : '1';
// spoiler_guard is a per-user preference (user_pref, 1.0.1 family).
set_user_pref($pdo, current_user_id(), 'spoiler_guard', $enabled);

// Redirect back to the page that triggered the toggle, with the same
// same-host Referer hardening as set_adult_pref.php / set_language.php.
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
