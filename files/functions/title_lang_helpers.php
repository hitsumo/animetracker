<?php

/**
 * Anime Tracker - Title Language Helpers (alternative_titles <-> language tags)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.20, when the separate "English Title" form field was
 * folded into the alternative-titles list.
 *
 * WHAT CHANGED AND WHY: until 1.1.19 the add/edit form had TWO places to
 * type a name - a list of untagged alternative titles, and a dedicated
 * "English Title" box wired to animes.title_english. That box was a dead
 * end: it only ever described English. Wanting a Turkish or Japanese title
 * next would have meant a turkish_title / japanese_title column, a new
 * branch in display_title(), a new field on the catalog wire and a manual
 * ALTER on the central catalog host - once per language.
 *
 * So the language moved INTO the list. Each alternative title now carries
 * an optional two-letter tag, and adding a language is a one-line change
 * to title_lang_codes() below. No column, no migration, no server work.
 *
 * STORAGE FORMAT: animes.alternative_titles keeps the historical
 * pipe-separated text; an entry MAY start with a [xx] tag.
 *
 *     [en]My Neighbor Totoro|[ja]となりのトトロ|Totoro
 *
 * The column type does not change, so tagged text rides the existing
 * catalog sync untouched (catalog_server/ needs NO manual ALTER).
 *
 * WHY [xx] AND NOT xx: a bare "en:" prefix would misread real titles -
 * "Re:Zero kara Hajimeru Isekai Seikatsu" starts with exactly two letters
 * and a colon. The bracket form plus a whitelist check (parse_alt_titles()
 * only honours a tag whose code is in title_lang_codes()) means an
 * untagged title can never be mistaken for a tagged one.
 *
 * title_english IS GONE (1.1.21). In 1.1.20 that column survived as a
 * derived display shortcut, so the release could ship without touching a
 * single render surface. 1.1.21 completed the move: display_title() now
 * reads these tags directly, the "show English titles" checkbox became a
 * Title Language picker, and the column was dropped from animes and
 * catalog_requests (and from the catalog wire). This file is now the ONLY
 * place a title's language is recorded.
 *
 * Loaded via the functions.php loader.
 */

/**
 * The canonical language code => i18n key map. Single place to extend.
 *
 * To add a language: add one line here plus the matching title_lang.* key
 * in lang/tr.php and lang/en.php. The form dropdown, the parser whitelist
 * and the validation all derive from this map.
 *
 * ORDER IS DELIBERATE AND NOT SORTED - unlike country_options(), which
 * sorts by localized name. English is by far the most-used tag (it is the
 * one the old form had a whole field for), so it sits at the top of the
 * dropdown where it costs no scrolling; the rest follow by how often they
 * show up in the catalog.
 *
 * KEEP THIS AT 8 ENTRIES OR FEWER: select_enhance.js replaces any select
 * with MORE than 8 options with a custom dropdown, and it only runs once
 * at page load - the rows added later by addAlternativeTitle() would get
 * a native popup while the initial rows got the custom one. Seven entries
 * (the empty option plus six languages) stays safely under the line.
 *
 * @return array Associative array: language code => i18n key.
 */
function title_lang_codes() {
    return [
        'en' => 'title_lang.en',
        'ja' => 'title_lang.ja',
        'tr' => 'title_lang.tr',
        'zh' => 'title_lang.zh',
        'ko' => 'title_lang.ko',
        'fr' => 'title_lang.fr',
    ];
}

/**
 * Map a language code to a localized language name.
 *
 * Returns an EMPTY STRING for null / empty / unknown codes, matching
 * country_label(): the tag is OPTIONAL, so "no language given" is the
 * common case and every caller guards with a non-empty check before
 * printing. Echoing a raw "xx" back would put a meaningless code on the
 * page.
 *
 * @param string|null $code Two-letter language code, any case.
 * @return string           Localized language name, or '' if unmapped.
 */
function title_lang_label($code) {
    if ($code === null || $code === '') {
        return '';
    }
    $code = strtolower((string)$code);
    $keys = title_lang_codes();
    if (!isset($keys[$code])) {
        return '';
    }
    return t($keys[$code]);
}

/**
 * Is this a language code we recognise?
 *
 * Used both as the form-dropdown whitelist and as the parser guard - an
 * entry starting with an UNKNOWN [xx] is treated as plain text, never as
 * a tag, so a title that happens to open with brackets survives intact.
 *
 * @param string|null $code Candidate code, any case.
 * @return bool
 */
function is_valid_title_lang($code) {
    if ($code === null || $code === '') {
        return false;
    }
    return isset(title_lang_codes()[strtolower((string)$code)]);
}

/**
 * Return the language options for a <select>, in title_lang_codes() order.
 *
 * The empty "no language" option is NOT included - the form renders it
 * itself, the same way the country select renders its own "choose"
 * option.
 *
 * Use as:
 *   foreach (title_lang_options() as $code => $label) { ... }
 *
 * @return array Associative array: language code => localized name.
 */
function title_lang_options() {
    $options = [];
    foreach (title_lang_codes() as $code => $key) {
        $options[$code] = t($key);
    }
    return $options;
}

/**
 * Split a stored alternative_titles string into tagged rows.
 *
 * Accepts everything the column has ever held: pre-1.1.20 untagged text
 * parses fine, every row simply coming back with lang ''. Blank entries
 * are dropped (a trailing pipe is harmless).
 *
 * Parsing is forgiving on input - "[EN] Title" and "[en]Title" both work -
 * but build_alt_titles() always WRITES the tight lowercase form, so the
 * column stays canonical.
 *
 * @param string|null $raw The alternative_titles column value.
 * @return array           List of ['lang' => 'en'|'', 'title' => '...'].
 */
function parse_alt_titles($raw) {
    $rows = [];
    if ($raw === null || trim((string)$raw) === '') {
        return $rows;
    }

    foreach (explode('|', (string)$raw) as $entry) {
        $entry = trim($entry);
        if ($entry === '') {
            continue;
        }

        $lang = '';
        // Only a whitelisted code counts as a tag. "[TV] Something" or
        // "[Blu-ray] X" fall through as plain title text.
        if (preg_match('/^\[([A-Za-z]{2})\]\s*/', $entry, $m)
            && is_valid_title_lang($m[1])
        ) {
            $lang  = strtolower($m[1]);
            $entry = trim(substr($entry, strlen($m[0])));
        }

        if ($entry === '') {
            continue; // a lone tag with no name behind it
        }

        $rows[] = ['lang' => $lang, 'title' => $entry];
    }

    return $rows;
}

/**
 * Build the alternative_titles column value from posted form arrays.
 *
 * The two arrays are POSITIONAL TWINS: alternative_titles[i] is named by
 * alt_title_langs[i]. They stay aligned because the form emits both
 * inputs inside one .field-group and addAlternativeTitle() / removeField()
 * add and remove the whole group, so the browser can never post one
 * without the other.
 *
 * Input hygiene, in order:
 *   - A pipe typed into a name would invent a new entry, so it becomes a
 *     space. (This is not new in 1.1.20 - the old implode('|', ...) had
 *     the same hazard, just silently.)
 *   - A hand-typed "[en]" at the front of a name is stripped: the
 *     dropdown is the single authority on language, and leaving it would
 *     produce "[en][en]Title" on the next save.
 *   - An unknown or missing code degrades to "no language" rather than
 *     being written through, so the column can only ever hold codes that
 *     title_lang_codes() knows.
 *
 * Duplicate tags are ALLOWED and kept as typed - two [en] rows are not an
 * error worth rejecting a save over. Readers take the first (see
 * alt_title_for_lang()).
 *
 * @param array $titles Posted alternative_titles[] values.
 * @param array $langs  Posted alt_title_langs[] values, same indices.
 * @return string       Column value ('' when nothing was filled in).
 */
function build_alt_titles($titles, $langs) {
    $out = [];

    foreach ((array)$titles as $i => $title) {
        $title = trim(str_replace('|', ' ', (string)$title));

        if (preg_match('/^\[([A-Za-z]{2})\]\s*/', $title, $m)
            && is_valid_title_lang($m[1])
        ) {
            $title = trim(substr($title, strlen($m[0])));
        }

        if ($title === '') {
            continue; // empty row: the user added a field and left it blank
        }

        $lang = isset($langs[$i]) ? strtolower(trim((string)$langs[$i])) : '';
        if (!is_valid_title_lang($lang)) {
            $lang = '';
        }

        $out[] = ($lang !== '' ? '[' . $lang . ']' : '') . $title;
    }

    return implode('|', $out);
}

/**
 * Read the title stored under one language tag.
 *
 * Returns the FIRST match, so a row tagged twice resolves predictably to
 * whichever the curator put higher in the list.
 *
 * This is what display_title() calls once the user has picked a Title
 * Language other than Romaji:
 *     $shown = alt_title_for_lang($anime['alternative_titles'], 'ja');
 *
 * @param string|null $raw  The alternative_titles column value.
 * @param string      $code Language code to look for.
 * @return string           The title, or '' when that language is absent.
 */
function alt_title_for_lang($raw, $code) {
    $code = strtolower((string)$code);
    foreach (parse_alt_titles($raw) as $row) {
        if ($row['lang'] === $code) {
            return $row['title'];
        }
    }
    return '';
}

/*
 * NOTE (1.1.21): alt_titles_for_form() lived here in 1.1.20. It rebuilt the
 * form rows from parse_alt_titles() and then rescued an English name that
 * still lived ONLY in the title_english column, so that opening and saving
 * the edit form could not silently wipe it.
 *
 * That column is gone in 1.1.21 and the rescue moved into
 * migration/1.1.21/upgrade.sql, which tags every such row (in place when the
 * same string is already listed untagged, appended otherwise) BEFORE dropping
 * the column. With no column left to read, the helper reduced to a plain
 * parse_alt_titles() call, so edit_anime.php now calls that directly.
 */
