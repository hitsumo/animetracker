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
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold', 'Dropped'.
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
    // 1.0.10: NULL / '' (no user_anime row, or an explicit "not
    // selected" row) folds to the '__unselected__' pseudo-status so
    // every render surface shows the same label without repeating the
    // null check. The strings deliberately match the
    // 'index.watch_status.unselected' entries in lang/tr.php and
    // lang/en.php - change them together.
    if ($status === null || $status === '') {
        $status = '__unselected__';
    }
    static $map = [
        'tr' => [
            'Watched'        => 'İzlendi',
            'Watching'       => 'İzleniyor',
            'PlanToWatch'    => 'İzlenme Planlandı',
            'OnHold'         => 'İzleme Ertelendi',
            'Dropped'        => 'İzleme Bırakıldı',
            '__unselected__' => 'Seçim Yapılmamış',
        ],
        'en' => [
            'Watched'        => 'Watched',
            'Watching'       => 'Watching',
            'PlanToWatch'    => 'Plan to Watch',
            'OnHold'         => 'On Hold',
            'Dropped'        => 'Dropped',
            '__unselected__' => 'Not Selected',
        ],
    ];
    return $map[$lang][$status] ?? $status;
}

/**
 * Return the watch_status options for a dropdown, in display order.
 *
 * Order: Watched, Watching, PlanToWatch, OnHold, Dropped.
 *   - First three preserve the existing UI order from 0.5.x (filter
 *     dropdown in index.php was Watched / Watching / PlanToWatch).
 *   - OnHold and Dropped are appended at the end as the newest,
 *     least-used values - keeps existing user muscle memory intact.
 *     Dropped (1.0.10) was born in the user_anime enum at 1.0.1 but
 *     had no label/option until now.
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
    $order = ['Watched', 'Watching', 'PlanToWatch', 'OnHold', 'Dropped'];
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
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold', 'Dropped'.
 * @return string        CSS suffix (no prefix). Caller adds its own prefix.
 */
function watch_status_css_class($status) {
    // 1.0.10: NULL / '' folds to the unselected pseudo-status, same
    // normalization as watch_status_label().
    if ($status === null || $status === '') {
        $status = '__unselected__';
    }
    static $map = [
        'Watched'        => 'watched',
        'Watching'       => 'watching',
        'PlanToWatch'    => 'plantowatch',
        'OnHold'         => 'onhold',
        'Dropped'        => 'dropped',
        '__unselected__' => 'unselected',
    ];
    return $map[$status] ?? 'unknown';
}

/**
 * Build the ORDER BY expression used when the list is sorted by the
 * watch status column (index.php "Durum").
 *
 * 1.0.10: the column used to sort on the raw ASCII enum value
 * (COALESCE(ua.watch_status, 'PlanToWatch')), which is alphabetical in
 * English only and silently folded the "not selected" state (no
 * user_anime row) into PlanToWatch. Instead, sort by the LOCALIZED
 * label, alphabetically in the active UI language; the virtual "not
 * selected" entry takes its own alphabetical place like any other
 * label. Switching the UI language changes the order with it.
 *
 * Implementation: the label order is computed in PHP and baked into a
 * FIELD() expression. NULL watch_status (no user_anime row) is folded
 * to the sentinel '__unselected__' so it can participate in FIELD().
 * Every FIELD() argument is an internal ASCII enum value from this
 * file plus that sentinel - no user input, safe to inline. A DB value
 * missing from the list (defensive case) makes FIELD() return 0 and
 * sort first.
 *
 * Collation: with the intl extension, Collator gives correct Turkish
 * alphabet order. Without intl, raw UTF-8 byte order is wrong for
 * Turkish (e.g. 'S' < dotted capital I byte-wise), so a manual sort
 * key mapping is used for TR; EN falls back to strcasecmp.
 *
 * DESC simply reverses the whole FIELD() order - the "not selected"
 * group is not pinned to either end.
 *
 * @return string SQL ORDER BY expression (no direction suffix).
 */
function watch_status_sort_expr() {
    $labels = watch_status_options();
    $labels['__unselected__'] = watch_status_label('__unselected__');

    if (class_exists('Collator')) {
        $collator = new Collator(current_lang() === 'tr' ? 'tr_TR' : 'en_US');
        uasort($labels, function ($a, $b) use ($collator) {
            return $collator->compare($a, $b);
        });
    } elseif (current_lang() === 'tr') {
        // No intl: build byte-comparable sort keys that follow the
        // Turkish alphabet. '~' (0x7E) sorts after 'z' (0x7A), so a
        // mapped pair like 'c~' lands after every plain 'c*' word and
        // before 'd*' - exactly where the corresponding Turkish letter
        // belongs. Undotted i maps to 'h~' (between 'h*' and 'i*').
        $trKey = function ($s) {
            $s = strtr($s, [
                'Ç' => 'ç', 'Ğ' => 'ğ', 'I' => 'ı', 'İ' => 'i',
                'Ö' => 'ö', 'Ş' => 'ş', 'Ü' => 'ü',
            ]);
            $s = mb_strtolower($s, 'UTF-8');
            return strtr($s, [
                'ç' => 'c~', 'ğ' => 'g~', 'ı' => 'h~',
                'ö' => 'o~', 'ş' => 's~', 'ü' => 'u~',
            ]);
        };
        uasort($labels, function ($a, $b) use ($trKey) {
            return strcmp($trKey($a), $trKey($b));
        });
    } else {
        uasort($labels, 'strcasecmp');
    }

    $quoted = [];
    foreach (array_keys($labels) as $value) {
        $quoted[] = "'" . $value . "'";
    }
    return "FIELD(COALESCE(ua.watch_status, '__unselected__'), "
         . implode(', ', $quoted) . ")";
}
