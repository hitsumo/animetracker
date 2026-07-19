<?php

/**
 * Anime Tracker - Country Helpers (animes.country ISO code -> UI label / options)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.17 with the animes.country column and the
 * country filter on index.php.
 *
 * WHY A CODE AND NOT FREE TEXT: animes is the CENTRAL CATALOG - every
 * member sees the same rows. A free-text field would let "Japonya",
 * "Japan" and "japonya" describe one country as three separate filter
 * values, and no translation would be possible. So the DB stores an
 * ISO 3166-1 alpha-2 code and this file maps it to a localized name.
 * The user never types or sees a code: the add/edit form shows a
 * dropdown of country NAMES, and every render surface goes through
 * country_label().
 *
 * This mirrors the broadcast_status_label / _options pair - same shape,
 * same reasoning (single source of truth instead of an inlined
 * if/elseif at each surface).
 *
 * Loaded via the functions.php loader.
 */

/**
 * Map an ISO 3166-1 alpha-2 country code to a localized country name.
 *
 * Uses the country.* keys defined in lang/tr.php and lang/en.php, so the
 * English UI gets "Japan" where the Turkish one gets "Japonya".
 *
 * Returns an EMPTY STRING for null / empty / unknown codes. That is
 * deliberate and different from broadcast_status_label(), which echoes
 * an unmapped value back: country is an OPTIONAL field, so the common
 * case here is "not filled in yet", and every render surface guards with
 * a non-empty check before printing. Echoing a raw "XX" back would put a
 * meaningless code on the page.
 *
 * @param string|null $code Two-letter country code, any case.
 * @return string           Localized country name, or '' if unmapped.
 */
function country_label($code) {
    if ($code === null || $code === '') {
        return '';
    }
    $code = strtoupper((string)$code);
    $keys = country_codes();
    if (!isset($keys[$code])) {
        return '';
    }
    return t($keys[$code]);
}

/**
 * The canonical code => i18n key map. Single place to extend.
 *
 * To add a country: add one line here plus the matching country.* key in
 * lang/tr.php and lang/en.php. Nothing else needs to change - the form
 * dropdown, the filter whitelist and the filter dropdown all derive from
 * this map.
 *
 * Kept deliberately short: these are the production countries that
 * actually appear in the catalog. A full 250-entry ISO list would bury
 * the five that matter inside a dropdown nobody wants to scroll.
 *
 * @return array Associative array: ISO code => i18n key.
 */
function country_codes() {
    return [
        'JP' => 'country.jp',
        'CN' => 'country.cn',
        'KR' => 'country.kr',
        'TW' => 'country.tw',
        'US' => 'country.us',
        'FR' => 'country.fr',
    ];
}

/**
 * Build a sort key that orders a string by the TURKISH alphabet.
 *
 * Why this exists: PHP's byte comparison puts "Çin" AFTER "Tayvan",
 * because Ç is a two-byte UTF-8 sequence starting at 0xC3 - far above
 * every ASCII letter. sort($a, SORT_LOCALE_STRING) does not fix it
 * either: it follows the process locale, which is "C" on a default
 * PHP/Apache install, so it falls back to the same byte comparison.
 * The intl extension's Collator would do this properly but is NOT
 * guaranteed to be enabled (it is off on the deployment host), and the
 * app has no other intl dependency - so a small local mapping it is.
 *
 * How: each letter of the Turkish alphabet is remapped onto a
 * sequential ASCII letter (a b c ç d ... -> a b c d e ...). The mapping
 * is strictly increasing, so a plain strcmp() on the mapped string
 * yields Turkish order. Pure-ASCII strings keep their normal order
 * (a < b < c < ...), which is why the English UI needs no special case.
 *
 * Only used for display ordering; never stored or shown.
 *
 * @param string $s Localized country name.
 * @return string   Comparison key for strcmp().
 */
function country_sort_key($s) {
    // Turkish-aware lowercasing first: I -> ı and İ -> i, which the
    // generic mb_strtolower gets wrong for Turkish.
    $s = str_replace(['I', 'İ'], ['ı', 'i'], $s);
    $s = mb_strtolower($s, 'UTF-8');

    // Turkish alphabet -> sequential ASCII. Order:
    // a b c ç d e f g ğ h ı i j k l m n o ö p r s ş t u ü v y z
    static $map = [
        'a' => 'a', 'b' => 'b', 'c' => 'c', 'ç' => 'd', 'd' => 'e',
        'e' => 'f', 'f' => 'g', 'g' => 'h', 'ğ' => 'i', 'h' => 'j',
        'ı' => 'k', 'i' => 'l', 'j' => 'm', 'k' => 'n', 'l' => 'o',
        'm' => 'p', 'n' => 'q', 'o' => 'r', 'ö' => 's', 'p' => 't',
        'r' => 'u', 's' => 'v', 'ş' => 'w', 't' => 'x', 'u' => 'y',
        'ü' => 'z', 'v' => '{', 'y' => '|', 'z' => '}',
    ];
    return strtr($s, $map);
}

/**
 * Return the country options for a <select>, sorted by LOCALIZED name.
 *
 * Sorting is done on the translated label, not on the code, so the
 * Turkish UI reads Amerika Birleşik Devletleri / Çin / Fransa / Güney
 * Kore / Japonya / Tayvan and the English UI reads China / France /
 * Japan / South Korea / Taiwan / United States - each in its own
 * alphabet. See country_sort_key() for why this is not a plain asort().
 *
 * Use as:
 *   foreach (country_options() as $code => $label) { ... }
 *
 * @return array Associative array: ISO code => localized country name.
 */
function country_options() {
    $options = [];
    foreach (country_codes() as $code => $key) {
        $options[$code] = t($key);
    }
    uasort($options, function ($a, $b) {
        return strcmp(country_sort_key($a), country_sort_key($b));
    });
    return $options;
}

/**
 * Is this a country code we recognise?
 *
 * Used as the whitelist for the ?country_filter= GET parameter on
 * index.php. The value still goes into the query as a bound parameter -
 * this check is about not building a filter UI state for a code that can
 * never match, not about SQL safety.
 *
 * @param string|null $code Candidate code, any case.
 * @return bool
 */
function is_valid_country_code($code) {
    if ($code === null || $code === '') {
        return false;
    }
    return isset(country_codes()[strtoupper((string)$code)]);
}
