<?php

/**
 * Anime Tracker - watch_status Helpers (ASCII enum -> UI label / options / CSS class)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

   
/**
 * Map an internal watch_status ENUM value to a user-facing label.
 *
 * Since 0.6, the DB enum stores ASCII values ('Watched', 'Watching',
 * 'PlanToWatch', 'OnHold'). The user-facing UI text remains Turkish.
 * This helper is the single source of truth for the translation.
 *
 * Adding a new language: extend the $map with a new lang key.
 * Adding a new status:   add an entry under each lang.
 *
 * Falls back to the raw status if the value is unknown (defensive -
 * a stray enum value never produces an empty cell).
 *
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold'.
 * @param string $lang   'tr' (default) or 'en'.
 * @return string        Localized label, or $status itself if unmapped.
 */
function watch_status_label($status, $lang = null) {
    // Default to the active UI language. Passing $lang explicitly still
    // overrides this - useful for tests, admin scripts, or any spot that
    // needs a specific language regardless of the user's UI choice.
    if ($lang === null) {
        $lang = current_lang();
    }
    static $map = [
        'tr' => [
            'Watched'     => 'İzlendi',
            'Watching'    => 'İzleniyor',
            'PlanToWatch' => 'İzlenme Planlandı',
            'OnHold'      => 'İzleme Ertelendi',
        ],
        'en' => [
            'Watched'     => 'Watched',
            'Watching'    => 'Watching',
            'PlanToWatch' => 'Plan to Watch',
            'OnHold'      => 'On Hold',
        ],
    ];
    return $map[$lang][$status] ?? $status;
}

/**
 * Return the watch_status options for a dropdown, in display order.
 *
 * Order: Watched, Watching, PlanToWatch, OnHold.
 *   - First three preserve the existing UI order from 0.5.x (filter
 *     dropdown in index.php was Watched / Watching / PlanToWatch).
 *   - OnHold is appended at the end as the newest, least-used value -
 *     keeps existing user muscle memory intact.
 *
 * Use as:
 *   foreach (watch_status_options() as $value => $label) {
 *       echo "<option value=\"{$value}\">{$label}</option>";
 *   }
 *
 * @param string $lang 'tr' (default) or 'en'.
 * @return array       Associative array: ASCII value => localized label.
 */
function watch_status_options($lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    $order = ['Watched', 'Watching', 'PlanToWatch', 'OnHold'];
    $options = [];
    foreach ($order as $status) {
        $options[$status] = watch_status_label($status, $lang);
    }
    return $options;
}

/**
 * Map an internal watch_status ENUM value to a stable CSS class suffix.
 *
 * Pre-0.6, classes were built ad-hoc from the TR enum value via
 * strtolower(str_replace(' ', '-', $status)), which produced names with
 * the Turkish "ı" character (e.g. ws-izlenme-planlandı). The 0.6 ASCII
 * migration moved DB values to English, which would now produce names
 * like ws-watched / ws-plantowatch - English in the markup and a clash
 * with the KARARLAR Bolum 1 convention "UI Turkish, internals English".
 *
 * Resolution: a stable, language-neutral suffix per enum value. CSS in
 * style.css (0.6 adim 8) targets these exact names. The suffix is also
 * ASCII-clean and case-insensitive friendly.
 *
 *   Watched     -> watched
 *   Watching    -> watching
 *   PlanToWatch -> plantowatch
 *   OnHold      -> onhold
 *
 * Unknown values fall back to 'unknown' so a stray DB value never
 * produces an empty class attribute.
 *
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold'.
 * @return string        CSS suffix (no prefix). Caller adds its own prefix.
 */
function watch_status_css_class($status) {
    static $map = [
        'Watched'     => 'watched',
        'Watching'    => 'watching',
        'PlanToWatch' => 'plantowatch',
        'OnHold'      => 'onhold',
    ];
    return $map[$status] ?? 'unknown';
}
