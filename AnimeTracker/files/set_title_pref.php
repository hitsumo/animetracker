<?php
/**
 * Anime Tracker - English-title Preference Endpoint (0.7.2)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * POST endpoint that toggles the "show English titles" preference.
 * Called by the small checkbox/toggle in list_settings.php (a tiny
 * CSRF-protected form posting to this file).
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   enabled     - '1' to prefer English titles, anything else = off
 *
 * The choice is written to the settings table under the key
 * 'display_title_english' (created on first use, same runtime-key
 * family as display_language / last_aired_sync). No migration needed.
 *
 * This preference is INDEPENDENT of the UI language: it only decides
 * whether title_english (when present) is shown instead of the Romaji
 * title. See anime_helpers.php display_title() / show_english_titles().
 *
 * Why a POST endpoint instead of GET: same reasoning as
 * set_language.php - per KARARLAR Bolum 1 state-changing operations
 * must be CSRF protected, and a GET handler would let any external
 * link flip the preference for the user.
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

// Normalize to a strict '1' / '0'. Any value other than '1' turns the
// preference off, so a missing or tampered field defaults to Romaji.
$enabled = (($_POST['enabled'] ?? '') === '1') ? '1' : '0';
set_setting($pdo, 'display_title_english', $enabled);

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
