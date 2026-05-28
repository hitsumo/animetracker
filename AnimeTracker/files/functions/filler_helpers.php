<?php

/**
 * Anime Tracker - Filler Helpers (filler type -> label / options / CSS class)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Added in 0.7 (per-episode filler tracking). Mirrors the emotion and
 * watch_status helper families: the DB stores stable ASCII type keys, the
 * UI shows localized labels, and a separate map gives each type a stable
 * language-neutral CSS suffix. functions.php (single source of truth) is
 * the only place the canonical type list lives; endpoints validate posted
 * values against filler_type_options() the same way update_emotion.php
 * validates against emotion_options(). Loaded via the functions.php loader.
 *
 * Data model (KARARLAR Bolum 8 - filler bolum izleme):
 *   filler_episodes(anime_id, episode_no, type). One row per marked
 *   episode. An UNMARKED episode (no row) means "assume canon" - the
 *   default - so only exceptions are stored. The four types below are the
 *   exceptions a curator can record.
 */

/**
 * Map an internal filler type to its localized UI label.
 *
 * Internal values are ASCII identifiers (MangaCanon, AnimeCanon, Mixed,
 * Filler). The UI label is localized; same idea as emotion_label and
 * watch_status_label. Falls back to $type itself if unmapped, so a stray
 * DB value never produces an empty cell.
 *
 * @param string $type ASCII internal value.
 * @param string $lang 'tr' (default via current_lang) or 'en'.
 * @return string      Localized label, or $type itself if unmapped.
 */
function filler_type_label($type, $lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    static $map = [
        'tr' => [
            'MangaCanon' => 'Manga Canon',
            'AnimeCanon' => 'Anime Canon',
            'Mixed'      => 'Karışık',
            'Filler'     => 'Dolgu',
        ],
        'en' => [
            'MangaCanon' => 'Manga Canon',
            'AnimeCanon' => 'Anime Canon',
            'Mixed'      => 'Mixed',
            'Filler'     => 'Filler',
        ],
    ];
    return $map[$lang][$type] ?? $type;
}

/**
 * Return the filler type options in display / cycle order.
 *
 * The order here is also the grid cell cycle order in filler_edit.php:
 * a click advances unset -> MangaCanon -> AnimeCanon -> Mixed -> Filler
 * -> unset. The list itself is the single source of truth for which type
 * values are valid; update_filler.php validates each posted type with
 * array_key_exists() against this map, the same way the emotion endpoint
 * validates its values (the enum column constrains the DB, this constrains
 * the input layer and keeps the two in step).
 *
 * @param string $lang 'tr' (default via current_lang) or 'en'.
 * @return array       Associative array: ASCII value => localized label.
 */
function filler_type_options($lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    $order = [
        'MangaCanon',
        'AnimeCanon',
        'Mixed',
        'Filler',
    ];
    $options = [];
    foreach ($order as $type) {
        $options[$type] = filler_type_label($type, $lang);
    }
    return $options;
}

/**
 * Map an internal filler type to a stable CSS class suffix.
 *
 * Stable, language-neutral, ASCII-clean. css/filler.css targets these
 * exact suffixes (.filler-cell-mangacanon, etc.) for the traffic-light
 * colouring decided in KARARLAR Bolum 8: the two canon types render
 * green, Mixed amber, Filler red, and an unmarked cell stays neutral
 * (no type -> caller uses the plain .filler-cell with no suffix).
 *
 *   MangaCanon -> mangacanon
 *   AnimeCanon -> animecanon
 *   Mixed      -> mixed
 *   Filler     -> filler
 *
 * Unknown values fall back to 'unknown' so a stray DB value never
 * produces an empty class attribute.
 *
 * @param string $type ASCII internal value.
 * @return string      CSS suffix (no prefix).
 */
function filler_type_css_class($type) {
    static $map = [
        'MangaCanon' => 'mangacanon',
        'AnimeCanon' => 'animecanon',
        'Mixed'      => 'mixed',
        'Filler'     => 'filler',
    ];
    return $map[$type] ?? 'unknown';
}

/**
 * Collapse a per-episode filler list into a compact human-readable summary.
 *
 * Input is the raw rows for one anime: a list of [episode_no, type] in any
 * order. Output groups consecutive same-type episode numbers into ranges
 * and returns one summary string per type that has episodes, in
 * filler_type_options() order, e.g.:
 *
 *   "Dolgu: 5-6, 18 | Karışık: 11"
 *
 * Only the non-default (non-canon-by-omission) view matters to the reader,
 * but every recorded type is shown - including explicit MangaCanon /
 * AnimeCanon marks if a curator added them. Returns '' when there is
 * nothing to show, which the details page uses to hide the summary row
 * entirely (empty-state per KARARLAR Bolum 8).
 *
 * Range derivation is a DISPLAY concern only (KARARLAR: "Range SADECE
 * gosterimde turetilir"); the table stays per-episode.
 *
 * @param array  $rows Each item ['episode_no' => int, 'type' => string]
 *                     (or a [episode_no, type] indexed pair).
 * @param string $lang 'tr' (default via current_lang) or 'en'.
 * @return string      Compact summary, or '' if there is nothing to show.
 */
function filler_summary($rows, $lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    if (empty($rows)) {
        return '';
    }

    // Bucket episode numbers by type.
    $byType = [];
    foreach ($rows as $row) {
        $ep   = (int)($row['episode_no'] ?? $row[0] ?? 0);
        $type = (string)($row['type'] ?? $row[1] ?? '');
        if ($ep <= 0 || $type === '') {
            continue;
        }
        $byType[$type][] = $ep;
    }
    if (empty($byType)) {
        return '';
    }

    // Walk the canonical type order so the output order is stable, then
    // group each bucket's sorted episode numbers into "a-b" / "a" ranges.
    $parts = [];
    foreach (array_keys(filler_type_options($lang)) as $type) {
        if (empty($byType[$type])) {
            continue;
        }
        $eps = $byType[$type];
        sort($eps, SORT_NUMERIC);
        $eps = array_values(array_unique($eps));

        $ranges = [];
        $start = $prev = $eps[0];
        $count = count($eps);
        for ($i = 1; $i <= $count; $i++) {
            $cur = $eps[$i] ?? null;
            if ($cur !== null && $cur === $prev + 1) {
                $prev = $cur;
                continue;
            }
            // Close the current run.
            $ranges[] = ($start === $prev) ? (string)$start : ($start . '-' . $prev);
            if ($cur !== null) {
                $start = $prev = $cur;
            }
        }

        $parts[] = filler_type_label($type, $lang) . ': ' . implode(', ', $ranges);
    }

    return implode(' | ', $parts);
}

/* =====================================================================
   AnimeFillerList import (0.7)

   One-click import of per-episode filler/canon classification from
   animefillerlist.com. Mirrors the external-fetch pattern of
   animeschedule_helpers.php (cURL, SSL verify on, ASCII error_log, no
   throw - return an 'error' key the AJAX endpoint maps to a Turkish
   message). The parser does NOT depend on the site's CSS classes: it
   reads the stable "Quick List" text region (bounded by the literal
   markers "Quick List" and "Jump to") and the category labels
   "<Category> Episodes:", so layout changes are less likely to break it.

   Category -> type mapping (animefillerlist labels map 1:1 to our enum):
     Manga Canon        -> MangaCanon
     Anime Canon        -> AnimeCanon
     Mixed Canon/Filler -> Mixed
     Filler             -> Filler

   Episode classifications are facts (episode N is filler); we import only
   the number->type mapping, never titles or descriptions.
   ===================================================================== */

/**
 * Extract the show slug from an animefillerlist.com URL, or accept a bare
 * slug. Returns lowercase slug or null.
 *   https://www.animefillerlist.com/shows/detective-conan -> detective-conan
 */
function parseAnimeFillerListSlug($url) {
    if (empty($url) || !is_string($url)) {
        return null;
    }
    $url = trim($url);
    if (preg_match('#animefillerlist\.com/shows/([a-z0-9_-]+)#i', $url, $m)) {
        return strtolower($m[1]);
    }
    // Bare slug typed directly.
    if (preg_match('/^[a-z0-9_-]+$/i', $url)) {
        return strtolower($url);
    }
    return null;
}

/**
 * Fetch the show page HTML. Returns ['html' => string] on success, or
 * ['error' => code, ...] on failure (codes mirror the animeschedule
 * helper: bad_slug, curl, http_404, http_other).
 */
function fetchAnimeFillerListPage($slug) {
    if (empty($slug) || !preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        return ['error' => 'bad_slug'];
    }

    $url = 'https://www.animefillerlist.com/shows/' . rawurlencode($slug);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html',
            // Descriptive UA - be a polite, identifiable client.
            'User-Agent: AnimeTracker/0.7 (self-hosted; +https://www.sicakcikolata.com)',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log('[anime_tracker] AnimeFillerList cURL error: ' . $curlErr);
        return ['error' => 'curl', 'detail' => $curlErr];
    }
    if ($httpCode === 404) {
        return ['error' => 'http_404', 'http_code' => 404];
    }
    if ($httpCode !== 200) {
        return ['error' => 'http_other', 'http_code' => $httpCode];
    }
    return ['html' => $body];
}

/**
 * Parse the show page HTML into a per-episode classification map.
 *
 * Returns:
 *   ['episodes' => [ep_no => type, ...], 'counts' => [type => n], 'total' => n]
 * or
 *   ['error' => 'no_quicklist' | 'no_categories']
 *
 * Strategy: strip to text, isolate the region between "Quick List" and
 * "Jump to" (the condensed per-category range list), then for each known
 * category label capture the segment up to the next label and expand its
 * comma-separated numbers/ranges.
 */
function parseAnimeFillerList($html) {
    if (!is_string($html) || $html === '') {
        return ['error' => 'no_quicklist'];
    }

    // To text. Keep link texts (the ranges live inside <a> tags).
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    // Normalize nbsp + whitespace.
    $text = str_replace("\xC2\xA0", ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);

    // Bound to the Quick List region so the full episode table and the
    // "Jump to" navigation numbers can never leak into the parse.
    $start = stripos($text, 'Quick List');
    if ($start === false) {
        return ['error' => 'no_quicklist'];
    }
    $end = stripos($text, 'Jump to', $start);
    if ($end === false || $end <= $start) {
        $end = strlen($text);
    }
    $region = substr($text, $start, $end - $start);

    $labelMap = [
        'Manga Canon Episodes:'        => 'MangaCanon',
        'Anime Canon Episodes:'        => 'AnimeCanon',
        'Mixed Canon/Filler Episodes:' => 'Mixed',
        'Filler Episodes:'             => 'Filler',
    ];

    // Locate each present label inside the region.
    $found = [];
    foreach ($labelMap as $label => $type) {
        $pos = stripos($region, $label);
        if ($pos !== false) {
            $found[] = ['pos' => $pos, 'len' => strlen($label), 'type' => $type];
        }
    }
    if (empty($found)) {
        return ['error' => 'no_categories'];
    }
    usort($found, function ($a, $b) {
        return $a['pos'] - $b['pos'];
    });

    $episodes = [];
    $counts   = [];
    $n = count($found);
    for ($i = 0; $i < $n; $i++) {
        $segStart = $found[$i]['pos'] + $found[$i]['len'];
        $segEnd   = ($i + 1 < $n) ? $found[$i + 1]['pos'] : strlen($region);
        $segment  = substr($region, $segStart, $segEnd - $segStart);
        $type     = $found[$i]['type'];

        // Tokens: "A-B" (range) or "N" (single).
        if (preg_match_all('/(\d+)\s*-\s*(\d+)|(\d+)/', $segment, $m, PREG_SET_ORDER)) {
            foreach ($m as $tok) {
                if ($tok[1] !== '' && isset($tok[2]) && $tok[2] !== '') {
                    $a = (int)$tok[1];
                    $b = (int)$tok[2];
                    if ($b < $a) { $tmp = $a; $a = $b; $b = $tmp; }
                    for ($e = $a; $e <= $b; $e++) {
                        $episodes[$e] = $type;
                        $counts[$type] = ($counts[$type] ?? 0) + 1;
                    }
                } elseif (isset($tok[3]) && $tok[3] !== '') {
                    $e = (int)$tok[3];
                    $episodes[$e] = $type;
                    $counts[$type] = ($counts[$type] ?? 0) + 1;
                }
            }
        }
    }

    if (empty($episodes)) {
        return ['error' => 'no_categories'];
    }
    ksort($episodes);
    return ['episodes' => $episodes, 'counts' => $counts, 'total' => count($episodes)];
}

/**
 * Like filler_summary but shows only PER-TYPE COUNTS, not episode ranges.
 * Used on the details page, where the full range list would be far too
 * long for big shows (e.g. 1200-episode series). Types appear in
 * filler_type_options order; only types with at least one episode show.
 *
 *   "635 Manga Canon, 1 Anime Canon, 567 Filler"
 *
 * Returns '' when there is nothing to show (details page hides the row).
 */
function filler_count_summary($rows, $lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    if (empty($rows)) {
        return '';
    }
    $counts = [];
    foreach ($rows as $row) {
        $ep   = (int)($row['episode_no'] ?? $row[0] ?? 0);
        $type = (string)($row['type'] ?? $row[1] ?? '');
        if ($ep <= 0 || $type === '') {
            continue;
        }
        $counts[$type] = ($counts[$type] ?? 0) + 1;
    }
    if (empty($counts)) {
        return '';
    }
    $parts = [];
    foreach (array_keys(filler_type_options($lang)) as $type) {
        if (!empty($counts[$type])) {
            $parts[] = $counts[$type] . ' ' . filler_type_label($type, $lang);
        }
    }
    return implode(', ', $parts);
}
