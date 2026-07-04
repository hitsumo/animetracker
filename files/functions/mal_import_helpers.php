<?php

/**
 * Anime Tracker - MAL Import Helpers (1.1.1)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Parse a MyAnimeList XML export into a normalized entry list, plus the
 * status/date mapping the import writer consumes. No DB access here - this
 * layer only turns raw file bytes into clean PHP arrays. Matching against
 * the local catalog and writing user_anime / catalog_requests happens in
 * list_settings.php (Faz 1), which reuses the existing match-or-add path.
 *
 * MAL export is a gzip'd XML in practice, but users often hand us the
 * already-decompressed .xml, so the parser accepts both: it sniffs the
 * gzip magic bytes and inflates only when needed.
 *
 * Loaded via the functions.php loader (helper-family convention, same as
 * watch_status_helpers / emotion_helpers).
 */

/**
 * Map a MAL my_status value (textual or numeric) to our watch_status enum.
 *
 * MAL numeric codes: 1 Watching, 2 Completed, 3 On-Hold, 4 Dropped,
 * 6 Plan to Watch (5 is unused). Returns null for anything unrecognized so
 * the caller can decide the fallback, never a wrong bucket.
 *
 * @param mixed $raw Textual ("Completed") or numeric ("2") MAL status.
 * @return string|null One of Watched/Watching/PlanToWatch/OnHold/Dropped, or null.
 */
function mal_status_to_enum($raw)
{
    if ($raw === null) {
        return null;
    }
    $s = trim((string)$raw);
    if ($s === '') {
        return null;
    }

    // Numeric form first.
    $numeric = [
        '1' => 'Watching',
        '2' => 'Watched',
        '3' => 'OnHold',
        '4' => 'Dropped',
        '6' => 'PlanToWatch',
    ];
    if (isset($numeric[$s])) {
        return $numeric[$s];
    }

    // Textual form: case-insensitive, tolerate spacing/underscore/hyphen
    // variants ("Plan to Watch", "on-hold", "On_Hold").
    $key = strtolower(preg_replace('/[\s_-]+/', '', $s));
    $text = [
        'watching'    => 'Watching',
        'completed'   => 'Watched',
        'onhold'      => 'OnHold',
        'dropped'     => 'Dropped',
        'plantowatch' => 'PlanToWatch',
    ];
    return $text[$key] ?? null;
}

/**
 * Normalize a MAL date.
 *
 * MAL writes '0000-00-00' (and sometimes an empty string) for "no date".
 * Returns a clean 'YYYY-MM-DD' string when the value is a real calendar
 * date, or null otherwise. No time zone math - MAL dates are day
 * granularity and map straight onto our DATE columns (watch_start_date /
 * watch_finish_date). Partial MAL dates like '2019-00-00' are rejected
 * (returned as null), matching the 1.1.0 rule that a partial date is free.
 *
 * @param mixed $raw
 * @return string|null 'YYYY-MM-DD' or null.
 */
function mal_normalize_date($raw)
{
    if ($raw === null) {
        return null;
    }
    $s = trim((string)$raw);
    if ($s === '' || $s === '0000-00-00') {
        return null;
    }
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        return null;
    }
    $y  = (int)$m[1];
    $mo = (int)$m[2];
    $d  = (int)$m[3];
    if (!checkdate($mo, $d, $y)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $y, $mo, $d);
}

/**
 * Parse raw MAL export bytes into a normalized entry list.
 *
 * Accepts gzip'd or plain XML. Returns:
 *   [ 'ok' => true,  'entries' => [ ... ] ]      on success, or
 *   [ 'ok' => false, 'error'   => 'parse'|'empty' ] on failure.
 *
 * Each entry:
 *   [ 'mal_id' => int|null, 'title' => string,
 *     'watch_status' => enum|null, 'watched_episodes' => int,
 *     'watch_start_date' => 'YYYY-MM-DD'|null,
 *     'watch_finish_date' => 'YYYY-MM-DD'|null,
 *     'notes' => string|null ]
 *
 * A row with neither a positive mal_id nor a title is dropped (nothing to
 * match on). An export that yields zero usable rows returns error 'empty'.
 *
 * @param string $bytes Raw uploaded file contents (possibly gzip'd).
 * @return array
 */
function mal_parse_export($bytes)
{
    if (!is_string($bytes) || $bytes === '') {
        return ['ok' => false, 'error' => 'parse'];
    }

    // Inflate when the gzip magic bytes (0x1f 0x8b) are present.
    if (strlen($bytes) > 2 && ord($bytes[0]) === 0x1f && ord($bytes[1]) === 0x8b) {
        $inflated = @gzdecode($bytes);
        if ($inflated === false) {
            error_log('[anime_tracker] mal import: gzdecode failed');
            return ['ok' => false, 'error' => 'parse'];
        }
        $bytes = $inflated;
    }

    // Parse without network access (LIBXML_NONET). libxml >= 2.9 does not
    // load external entities by default, so this is safe against XXE; the
    // flag is an extra belt-and-braces guard.
    $prev = libxml_use_internal_errors(true);
    $xml  = simplexml_load_string($bytes, 'SimpleXMLElement', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    if ($xml === false || !isset($xml->anime)) {
        return ['ok' => false, 'error' => 'parse'];
    }

    $entries = [];
    foreach ($xml->anime as $a) {
        $malId = (int)($a->series_animedb_id ?? 0);
        $title = trim((string)($a->series_title ?? ''));
        if ($malId <= 0 && $title === '') {
            continue;
        }
        $notes = trim((string)($a->my_comments ?? ''));
        $entries[] = [
            'mal_id'            => $malId > 0 ? $malId : null,
            'title'             => $title,
            'watch_status'      => mal_status_to_enum($a->my_status ?? null),
            'watched_episodes'  => max(0, (int)($a->my_watched_episodes ?? 0)),
            'watch_start_date'  => mal_normalize_date($a->my_start_date ?? null),
            'watch_finish_date' => mal_normalize_date($a->my_finish_date ?? null),
            'notes'             => $notes !== '' ? $notes : null,
        ];
    }

    if (empty($entries)) {
        return ['ok' => false, 'error' => 'empty'];
    }
    return ['ok' => true, 'entries' => $entries];
}

/**
 * Build the ua_set_state payload for one normalized MAL entry.
 *
 * watch_status falls back to PlanToWatch when MAL gave an unmappable value
 * (defensive - MAL always sets a status), matching the JSON import default.
 * status + watched_episodes are always carried. Optional fields (notes,
 * dates) are included ONLY when MAL actually filled them: ua_set_state
 * writes every key present (including null), so omitting an absent field is
 * what keeps an overwrite from erasing notes/dates the user already had.
 *
 * @param array $e A normalized entry from mal_parse_export().
 * @return array Payload for ua_set_state().
 */
function mal_ua_payload(array $e)
{
    $status = $e['watch_status'] ?? null;
    $valid  = ['Watched', 'Watching', 'PlanToWatch', 'OnHold', 'Dropped'];
    if (!in_array($status, $valid, true)) {
        $status = 'PlanToWatch';
    }

    $payload = [
        'watch_status'     => $status,
        'watched_episodes' => max(0, (int)($e['watched_episodes'] ?? 0)),
    ];

    if (($e['notes'] ?? null) !== null) {
        $payload['notes'] = $e['notes'];
    }
    if (($e['watch_start_date'] ?? null) !== null) {
        $payload['watch_start_date'] = $e['watch_start_date'];
    }
    if (($e['watch_finish_date'] ?? null) !== null) {
        $payload['watch_finish_date'] = $e['watch_finish_date'];
    }

    return $payload;
}
