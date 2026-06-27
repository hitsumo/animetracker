<?php

/**
 * Anime Tracker - AnimeSchedule API + Timetable Helpers
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

// ============================================================================
// AnimeSchedule API Helpers
// ============================================================================
//
// These three functions handle the "Otomatik Doldur" button on add_anime.php
// and edit_anime.php. They are pure helpers - no DB access, no session, no
// HTTP response writing. The AJAX endpoint (fetch_animeschedule.php) calls
// them and wraps the result in JSON.
//
// Flow:
//   1. parseAnimeScheduleSlug() extracts the slug from a user-pasted URL
//   2. fetchAnimeScheduleData() makes the HTTP request to the API
//   3. mapAnimeScheduleToFormFields() turns the API JSON into our form values
//
// API key is loaded from config.php as ANIMESCHEDULE_API_KEY. If the constant
// is not defined the feature is disabled (the AJAX endpoint reports this back
// to the browser).

/**
 * Extract the slug from an AnimeSchedule URL.
 *
 * Accepts every reasonable variant we have seen users paste:
 *   - https://animeschedule.net/anime/akane-banashi
 *   - https://animeschedule.net/anime/akane-banashi/
 *   - http://animeschedule.net/anime/akane-banashi
 *   - animeschedule.net/anime/akane-banashi      (no scheme)
 *   - https://animeschedule.net/anime/akane-banashi?foo=bar
 *
 * Returns the slug string, or null if the URL is not an AnimeSchedule
 * anime URL. The slug is what we pass to the /api/v3/anime/{slug}
 * endpoint - lowercase letters, digits and dashes per the AnimeSchedule
 * URL convention.
 */
function parseAnimeScheduleSlug($url) {
    if (empty($url) || !is_string($url)) {
        return null;
    }
    // The slug character class is intentionally lenient (a-z, 0-9, dash,
    // underscore) so we don't reject valid slugs we have not seen yet.
    // The trailing group stops at /, ?, # or end of string.
    if (preg_match('#animeschedule\.net/anime/([a-z0-9_-]+)#i', $url, $m)) {
        return strtolower($m[1]);
    }
    return null;
}

/**
 * Fetch the JSON body for /api/v3/anime/{slug}.
 *
 * Returns an associative array on success. On any kind of failure
 * returns an array with an 'error' key so the caller can report a
 * Turkish message back to the user. We intentionally do not throw -
 * the AJAX endpoint converts these errors into JSON responses and
 * exceptions would force ugly HTTP 500 pages.
 *
 * Possible 'error' values:
 *   'no_key'      - ANIMESCHEDULE_API_KEY not defined in config.php
 *   'curl'        - network/cURL failure (timeout, DNS, no internet)
 *   'http_404'    - slug does not exist on AnimeSchedule
 *   'http_401'    - API token invalid or expired
 *   'http_403'    - API token lacks permission for this endpoint
 *   'http_429'    - rate limit hit
 *   'http_other'  - any other unexpected HTTP status
 *   'bad_json'    - response body was not valid JSON
 *
 * The 'http_code' key carries the raw HTTP status when applicable so
 * the caller can include it in the error message for debugging.
 */
function fetchAnimeScheduleData($slug) {
    if (!defined('ANIMESCHEDULE_API_KEY') || ANIMESCHEDULE_API_KEY === '') {
        return ['error' => 'no_key'];
    }
    if (empty($slug) || !preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        return ['error' => 'bad_slug'];
    }

    $url = 'https://animeschedule.net/api/v3/anime/' . rawurlencode($slug);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . ANIMESCHEDULE_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        // Force IPv4. Some hosts resolve animeschedule.net to an IPv6 (AAAA)
        // address but have no working IPv6 egress, so a default request first
        // tries IPv6 and stalls until timeout. IPv4 is reachable, so pin it.
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_FOLLOWLOCATION => false,
        // Cert verification on - we never want to talk to a MITM
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log('[anime_tracker] AnimeSchedule cURL error: ' . $curlErr);
        return ['error' => 'curl', 'detail' => $curlErr];
    }

    if ($httpCode === 404) {
        return ['error' => 'http_404', 'http_code' => 404];
    }
    if ($httpCode === 401) {
        return ['error' => 'http_401', 'http_code' => 401];
    }
    if ($httpCode === 403) {
        return ['error' => 'http_403', 'http_code' => 403];
    }
    if ($httpCode === 429) {
        return ['error' => 'http_429', 'http_code' => 429];
    }
    if ($httpCode !== 200) {
        return ['error' => 'http_other', 'http_code' => $httpCode];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'bad_json'];
    }

    return $data;
}

/**
 * Convert an AnimeSchedule API response into a flat array of form
 * field values our add/edit forms can consume.
 *
 * Returned keys (only ones we actually use - synopsis, names, links
 * are intentionally NOT mapped per the user's request):
 *
 *   broadcast_day        - 'Pazartesi'..'Pazar', or null
 *   broadcast_time       - 'HH:MM' (Asia/Tokyo local time), or null
 *   broadcast_timezone   - always 'Asia/Tokyo' if either day or time is set
 *   status               - 'Yayın Devam Ediyor' / 'Yayın Tamamlandı', or null
 *   total_episodes       - int (only when API has 'episodes' AND status finished)
 *   aired_episodes       - intentionally NOT set (the basic /anime endpoint
 *                          does not give a reliable aired count for ongoing
 *                          shows, see /timetables endpoint for future work)
 *
 * Any key whose value cannot be derived is OMITTED from the result -
 * the caller can then iterate the array and only fill empty form
 * fields, leaving anything else untouched.
 *
 * IMPORTANT: This function uses TWO different API fields for broadcast
 * info, because the API splits the data:
 *
 *   broadcast_day  comes from `premier` (the first episode's air date,
 *                  whose weekday equals the show's weekly broadcast day)
 *   broadcast_time comes from `jpnTime` (per the AnimeSchedule docs:
 *                  "only the hour and minute are relevant" - the date
 *                  part of jpnTime is unreliable, often points to an
 *                  announcement timestamp from months earlier)
 *
 * Why we cannot use jpnTime's weekday: real-world testing showed that
 * for Marriagetoxin (Spring 2026, broadcasts Tuesdays) jpnTime returned
 * a Friday in October 2025 - that timestamp matches the show's "Tuesday
 * Night Block" announcement, not its actual broadcast day. Using the
 * weekday from jpnTime gave wrong results for both Marriagetoxin and
 * Akane-banashi (the latter broadcasts Saturdays but jpnTime pointed
 * to a Thursday).
 *
 * The premier field, in contrast, is the actual first-episode air date.
 * Its weekday matches the show's weekly broadcast slot reliably.
 *
 * Both timestamps are converted from UTC to Asia/Tokyo before extracting
 * weekday/time, because that is the broadcast region the API normalises
 * to and the local form values must match what the user sees on
 * AnimeSchedule.net.
 */
function mapAnimeScheduleToFormFields($apiData) {
    $out = [];

    // --- Helper: parse an API datetime field, return DateTime in Tokyo
    // or null if the value is missing / null-marker / unparseable.
    $parseTokyo = function($value) {
        if (empty($value) || !is_string($value)) return null;
        // API uses "0001-01-01T00:00:00Z" as null marker
        if (strpos($value, '0001-01-01') === 0) return null;
        try {
            $dt = new DateTime($value, new DateTimeZone('UTC'));
            // Sanity check: anything before 1971 is the null marker or junk
            if ((int)$dt->format('Y') < 1971) return null;
            $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
            return $dt;
        } catch (Exception $e) {
            return null;
        }
    };

    $dayMap = [
        'Monday'    => 'Pazartesi',
        'Tuesday'   => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday'  => 'Perşembe',
        'Friday'    => 'Cuma',
        'Saturday'  => 'Cumartesi',
        'Sunday'    => 'Pazar',
    ];

    // --- Broadcast day from `premier` ---------------------------------
    // The first-episode air date's weekday is the weekly broadcast day.
    $premierTokyo = $parseTokyo($apiData['premier'] ?? null);
    if ($premierTokyo !== null) {
        $dayEn = $premierTokyo->format('l');
        if (isset($dayMap[$dayEn])) {
            $out['broadcast_day'] = $dayMap[$dayEn];
        }
    }

    // --- Broadcast time from `jpnTime` --------------------------------
    // Only HH:MM is meaningful (per API docs). The date part of jpnTime
    // is unreliable - see the function docblock for the explanation.
    $jpnTokyo = $parseTokyo($apiData['jpnTime'] ?? null);
    if ($jpnTokyo !== null) {
        $out['broadcast_time'] = $jpnTokyo->format('H:i');
    }

    // --- Timezone -----------------------------------------------------
    // Set Asia/Tokyo only if at least one of day/time was filled, so we
    // do not stamp a timezone onto rows where both date fields were null.
    if (isset($out['broadcast_day']) || isset($out['broadcast_time'])) {
        $out['broadcast_timezone'] = 'Asia/Tokyo';
    }

    // --- Status -------------------------------------------------------
    // API values seen: "Finished", "Ongoing". "Upcoming" exists per the
    // docs but the form has no equivalent (we only support two states).
    if (!empty($apiData['status']) && is_string($apiData['status'])) {
        if ($apiData['status'] === 'Finished') {
            $out['status'] = 'Yayın Tamamlandı';
        } elseif ($apiData['status'] === 'Ongoing') {
            $out['status'] = 'Yayın Devam Ediyor';
        }
        // "Upcoming" or unknown values: leave status unset.
    }

    // --- Episode count ------------------------------------------------
    // The API only returns 'episodes' for shows where the final count
    // is known (typically status=Finished). For ongoing shows the field
    // is omitted entirely (per API docs: "if the value is null, the
    // entire field will be omitted"). We map it to total_episodes only
    // when status is Finished - otherwise it would mislead the user into
    // thinking an ongoing show has a confirmed final count.
    if (
        isset($apiData['episodes']) &&
        is_int($apiData['episodes']) &&
        $apiData['episodes'] > 0 &&
        ($out['status'] ?? null) === 'Yayın Tamamlandı'
    ) {
        $out['total_episodes'] = $apiData['episodes'];
    }

    return $out;
}

// ============================================================================
// AnimeSchedule Timetable Helpers (aired_episodes auto-sync)
// ============================================================================
//
// These helpers power the "Senkronize Et" button on edit_anime.php and the
// once-a-day silent sync on list_settings.php. They query the
// /timetables/raw endpoint to learn how many episodes have aired so far
// for an ongoing show, since /anime/{slug} does not give us that count
// reliably.
//
// CRITICAL FIELD NAME NOTE — the API returns CAMELCASE keys ('episodeNumber',
// 'episodeDate', 'route', 'title') even though some external SDK docs show
// PascalCase. Our first cut used PascalCase and produced silent zero
// matches across every anime. Always use camelCase here.
//
// CRITICAL FILTER NOTE — the documented 'mal-ids' query parameter is
// silently IGNORED by the public endpoint. Sending mal-ids=63376 returns
// the full week's list (76+ entries), not the requested anime. Real-world
// test confirmed this on 2026-04-28. We work around it by pulling the
// week unfiltered and matching on the 'route' slug client side. Bonus:
// this turns N anime lookups into 1 request per week.
//
// MATCHING — we match by the 'route' field, which is the URL slug visible
// in animeschedule.net/anime/<slug>. Our DB stores the full URL in
// animes.anime_schedule_link; we run parseAnimeScheduleSlug() on it to
// get the slug. Animes without an anime_schedule_link cannot be matched
// (the helper returns 'no_slug' for them).

/**
 * Build a list of {week, year} pairs walking backwards from today.
 *
 * Uses ISO 8601 week numbering (PHP 'W' for week 1-53, 'o' for the year
 * the ISO week belongs to - which can differ from 'Y' at year boundaries).
 *
 * Example output for $weeks = 3:
 *   [
 *     ['week' => 18, 'year' => 2026],   // current week
 *     ['week' => 17, 'year' => 2026],   // last week
 *     ['week' => 16, 'year' => 2026],   // two weeks ago
 *   ]
 */
function buildIsoWeekWindow($weeks = 3) {
    $out = [];
    $cursor = new DateTime('now', new DateTimeZone('UTC'));
    for ($i = 0; $i < $weeks; $i++) {
        $out[] = [
            'week' => (int)$cursor->format('W'),
            'year' => (int)$cursor->format('o'),
        ];
        $cursor->modify('-1 week');
    }
    return $out;
}

/**
 * Fetch the full raw timetable for a given ISO week. No filters - we
 * always pull the complete week's anime list and match client side.
 *
 * Returns either:
 *   - On success: array of TimetableShow objects (raw API response,
 *     ~50-100 entries per week, all camelCase keys)
 *   - On failure: ['error' => 'code', 'http_code' => N (optional)]
 *
 * Possible error codes:
 *   'no_key'     - ANIMESCHEDULE_API_KEY missing in config.php
 *   'bad_input'  - week/year out of sane range
 *   'curl'       - network error
 *   'http_401'   - bad token
 *   'http_403'   - token lacks permission
 *   'http_429'   - rate limit
 *   'http_other' - any other unexpected status
 *   'bad_json'   - response not parseable as JSON array
 */
function fetchAnimeScheduleTimetable($week, $year) {
    if (!defined('ANIMESCHEDULE_API_KEY') || ANIMESCHEDULE_API_KEY === '') {
        return ['error' => 'no_key'];
    }

    $week = (int)$week;
    $year = (int)$year;
    if ($week < 1 || $week > 53 || $year < 2000 || $year > 2100) {
        return ['error' => 'bad_input'];
    }

    // Phase 1: raw track is authoritative (matches the countdown, which is
    // built from the Japanese broadcast schedule premier/jpnTime). Phase 2
    // will add the sub track alongside and surface both.
    $url = 'https://animeschedule.net/api/v3/timetables/raw'
         . '?week=' . $week . '&year=' . $year;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . ANIMESCHEDULE_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        // Force IPv4 (see fetchAnimeScheduleData) - avoids IPv6-only DNS
        // results stalling on hosts without working IPv6 egress.
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log('[anime_tracker] timetable cURL: ' . $curlErr);
        return ['error' => 'curl', 'detail' => $curlErr];
    }

    if ($httpCode === 401) return ['error' => 'http_401', 'http_code' => 401];
    if ($httpCode === 403) return ['error' => 'http_403', 'http_code' => 403];
    if ($httpCode === 429) return ['error' => 'http_429', 'http_code' => 429];
    if ($httpCode !== 200) return ['error' => 'http_other', 'http_code' => $httpCode];

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'bad_json'];
    }

    return $data;
}

/**
 * Decide whether a timetable row represents an episode that has actually
 * aired by now, or one that is scheduled for the future.
 *
 * Why this matters: a query for the current ISO week returns ALL rows
 * scheduled in that week, including episodes that are still days away.
 * Without this filter we would happily report Episode 5 as aired on
 * Tuesday when the actual broadcast slot is the upcoming Saturday.
 *
 * Returns true if the episode has aired (or if we cannot tell - safer
 * to drop into the previous week than to report an episode that does
 * not exist yet, but we log unknowns so we notice if the API ever
 * stops sending episodeDate).
 */
function isTimetableRowAired($row) {
    if (!isset($row['episodeDate']) || empty($row['episodeDate'])) {
        // No date field at all - log once and treat as "do not trust"
        // (return false so we walk back to the previous week)
        error_log('[anime_tracker] timetable row missing episodeDate, slug: '
            . ($row['route'] ?? '?'));
        return false;
    }
    try {
        $epDate = new DateTime($row['episodeDate'], new DateTimeZone('UTC'));
        $now    = new DateTime('now',                new DateTimeZone('UTC'));
        return $epDate <= $now;
    } catch (Exception $e) {
        // Unparseable date - same conservative stance, do not count it
        error_log('[anime_tracker] timetable episodeDate unparseable: '
            . $row['episodeDate'] . ' (' . $e->getMessage() . ')');
        return false;
    }
}

/**
 * Find a timetable row by its 'route' slug (case-insensitive).
 *
 * Returns the matched row (associative array) or null. The match is
 * exact on the slug - the API is consistent about route formatting
 * (lowercase, dashes) so a strict comparison is fine.
 *
 * Only rows whose episodeDate is in the past (or the present) are
 * considered. Future-dated rows are skipped here so the caller does
 * not accidentally credit an episode that is still days away.
 *
 * If multiple rows match (rare - a show that aired multiple episodes
 * the same week), returns the row with the highest episodeNumber so
 * we report the latest aired count.
 */
function findTimetableRowBySlug($timetable, $slug) {
    if (!is_array($timetable) || empty($timetable) || empty($slug)) {
        return null;
    }
    $needle = strtolower($slug);
    $best   = null;
    foreach ($timetable as $row) {
        if (!isset($row['route'])) continue;
        if (strtolower($row['route']) !== $needle) continue;
        if (!isTimetableRowAired($row)) continue;

        $epNum = isset($row['episodeNumber']) ? (int)$row['episodeNumber'] : 0;
        if ($best === null) {
            $best = $row;
            continue;
        }
        $bestEp = isset($best['episodeNumber']) ? (int)$best['episodeNumber'] : 0;
        if ($epNum > $bestEp) {
            $best = $row;
        }
    }
    return $best;
}

/**
 * Sync aired_episodes for a single anime.
 *
 * Walks the ISO week window backwards (today, last week, the week
 * before, ...). The first week where the timetable contains a row
 * matching this anime's AnimeSchedule slug wins; the episodeNumber
 * from that row is written to animes.aired_episodes (overwriting -
 * that is the whole point of this feature).
 *
 * Returns one of:
 *   ['success' => true, 'aired_episodes' => N, 'week_offset' => K,
 *    'old_value' => OLD_OR_NULL, 'changed' => bool]
 *   ['error' => 'code']
 *
 * Possible error codes:
 *   'not_found'        - $animeId does not exist in DB
 *   'no_mal_id'        - anime has no mal_id (edge case - we still
 *                        require it for the catalog system identity)
 *   'not_ongoing'      - status is not 'Yayın Devam Ediyor'
 *   'no_slug'          - anime_schedule_link is empty / unparseable;
 *                        without a slug we cannot match into the
 *                        timetable response
 *   'not_in_timetable' - slug absent from every week we tried
 *   plus any error code returned by fetchAnimeScheduleTimetable()
 */
function syncSingleAiredEpisodes($pdo, $animeId, $maxWeeksBack = 3) {
    $animeId = (int)$animeId;
    if ($animeId <= 0) return ['error' => 'not_found'];

    $stmt = $pdo->prepare("
        SELECT id, mal_id, status, aired_episodes, total_episodes, anime_schedule_link
          FROM animes
         WHERE id = ?
         LIMIT 1
    ");
    $stmt->execute([$animeId]);
    $anime = $stmt->fetch();
    if (!$anime) return ['error' => 'not_found'];

    if (empty($anime['mal_id']))                   return ['error' => 'no_mal_id'];
    if ($anime['status'] !== 'Yayın Devam Ediyor') return ['error' => 'not_ongoing'];

    $slug = parseAnimeScheduleSlug($anime['anime_schedule_link'] ?? '');
    if ($slug === null)                            return ['error' => 'no_slug'];

    $weeks = buildIsoWeekWindow($maxWeeksBack);

    foreach ($weeks as $offset => $w) {
        $result = fetchAnimeScheduleTimetable($w['week'], $w['year']);

        // API/network error: bail immediately, do not silently keep
        // walking backwards through more failed requests
        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $row = findTimetableRowBySlug($result, $slug);
        if ($row !== null && isset($row['episodeNumber'])) {
            $epNum = (int)$row['episodeNumber'];
            if ($epNum > 0) {
                $oldValue = isset($anime['aired_episodes']) ? (int)$anime['aired_episodes'] : null;

                // Resolve total: timetable row 'episodes' (0=unknown) first,
                // then our DB value. Auto-finish when the raw run is complete.
                $total = 0;
                if (isset($row['episodes']) && (int)$row['episodes'] > 0) {
                    $total = (int)$row['episodes'];
                } elseif (!empty($anime['total_episodes'])) {
                    $total = (int)$anime['total_episodes'];
                }
                $finished     = ($total > 0 && $epNum >= $total);
                $airedToWrite = $finished ? $total : $epNum;
                $changed      = ($oldValue !== $airedToWrite);

                if ($finished) {
                    $upd = $pdo->prepare("UPDATE animes SET aired_episodes = ?, status = 'Yayın Tamamlandı' WHERE id = ?");
                    $upd->execute([$airedToWrite, $animeId]);
                    error_log('[anime_tracker] auto-finish anime#' . $animeId
                        . ' raw run complete at ep ' . $epNum . '/' . $total);
                } elseif ($changed) {
                    $upd = $pdo->prepare("UPDATE animes SET aired_episodes = ? WHERE id = ?");
                    $upd->execute([$airedToWrite, $animeId]);
                }

                return [
                    'success'        => true,
                    'aired_episodes' => $airedToWrite,
                    'week_offset'    => $offset,
                    'old_value'      => $oldValue,
                    'changed'        => $changed,
                    'finished'       => $finished,
                ];
            }
        }
    }

    return ['error' => 'not_in_timetable'];
}

/**
 * Bulk sync aired_episodes for every ongoing anime that has both a
 * MAL id and an AnimeSchedule slug.
 *
 * This is the once-a-day silent sync triggered from list_settings.php.
 * One API request per week serves the entire batch (vs one request per
 * anime in the naive design), because the timetable comes back unfiltered
 * and we match locally on slug.
 *
 * Side effect: settings.last_aired_sync is updated to the current UTC
 * timestamp on a successful run. If we hit a global API failure
 * (no_key, http_401, http_429) we DO NOT update the timestamp, so the
 * next page load will retry. Per-anime soft results (not_in_table,
 * no_slug) do not block the timestamp update.
 *
 * Returns:
 *   [
 *     'updated'       => N,     // animes whose aired_episodes changed
 *     'unchanged'     => N,     // animes confirmed at same value
 *     'not_in_table'  => N,     // slug absent from every week we tried
 *     'no_slug'       => N,     // animes lacking anime_schedule_link
 *     'errors'        => N,     // unexpected per-anime failures
 *     'global_error'  => 'code' // present only if a global API error stopped the run
 *   ]
 */
function syncAllOngoingAiredEpisodes($pdo, $maxWeeksBack = 3) {
    $stats = [
        'updated'      => 0,
        'unchanged'    => 0,
        'finished'     => 0,
        'not_in_table' => 0,
        'no_slug'      => 0,
        'errors'       => 0,
    ];

    // Pull every ongoing anime that has the identity bits we need. We
    // require mal_id (project-wide convention for ongoing animes) and
    // we will additionally check anime_schedule_link per row.
    $stmt = $pdo->query("
        SELECT id, mal_id, aired_episodes, total_episodes, anime_schedule_link
          FROM animes
         WHERE status = 'Yayın Devam Ediyor'
           AND mal_id IS NOT NULL
    ");
    $animes = $stmt->fetchAll();

    if (empty($animes)) {
        // Nothing to do. Still mark a successful run so we do not
        // hammer the API on every page load looking for animes that do
        // not exist.
        markLastAiredSync($pdo);
        return $stats;
    }

    // Build slug => [anime row] map. Animes without a parseable slug go
    // straight into the no_slug bucket - no API can save them.
    $slugMap = [];
    foreach ($animes as $a) {
        $slug = parseAnimeScheduleSlug($a['anime_schedule_link'] ?? '');
        if ($slug === null) {
            $stats['no_slug']++;
            continue;
        }
        // Same slug pointing at multiple animes is theoretically
        // possible if the user has duplicate entries, but extremely
        // unlikely. Last-write wins is fine.
        $slugMap[$slug] = $a;
    }

    if (empty($slugMap)) {
        markLastAiredSync($pdo);
        return $stats;
    }

    // Update statement reused inside the loop
    $upd = $pdo->prepare("UPDATE animes SET aired_episodes = ? WHERE id = ?");

    // Reused when a show's full raw run has aired (auto-finish): clamp
    // aired to total and flip the broadcast status in one write.
    $updFin = $pdo->prepare("UPDATE animes SET aired_episodes = ?, status = 'Yayın Tamamlandı' WHERE id = ?");

    // Walk weeks backwards. Once a slug is matched in some week we drop
    // it from the remaining set so older weeks do not overwrite newer
    // numbers (we found episode 5 last week; do not let "two weeks ago"
    // pull us back to episode 4).
    $remaining = $slugMap;
    $weeks = buildIsoWeekWindow($maxWeeksBack);

    foreach ($weeks as $w) {
        if (empty($remaining)) break;

        $result = fetchAnimeScheduleTimetable($w['week'], $w['year']);

        if (isset($result['error'])) {
            // Global errors abort the whole run; per-week errors of
            // these kinds will only repeat if we keep going
            if (in_array($result['error'], ['no_key', 'http_401', 'http_429'], true)) {
                $stats['global_error'] = $result['error'];
                error_log('[anime_tracker] aired sync aborted: ' . $result['error']);
                return $stats;
            }
            // Other errors (curl glitch, http_other): log and continue
            // to the next week - maybe the next request succeeds
            error_log('[anime_tracker] aired sync week '
                . $w['week'] . '/' . $w['year'] . ': ' . $result['error']);
            $stats['errors']++;
            continue;
        }

        // Walk this week's rows ONCE and try to resolve every remaining
        // slug we still care about. The timetable has ~80 rows, our
        // remaining list has at most 20 - this is fast.
        //
        // We skip future-dated rows here too (same reason as
        // findTimetableRowBySlug): the week's timetable contains
        // episodes that have not aired yet, and counting them would
        // overshoot by one.
        foreach ($result as $row) {
            if (empty($remaining)) break;
            if (!isset($row['route'])) continue;
            $rowSlug = strtolower($row['route']);
            if (!isset($remaining[$rowSlug])) continue;
            if (!isTimetableRowAired($row)) continue;

            $epNum = isset($row['episodeNumber']) ? (int)$row['episodeNumber'] : 0;
            if ($epNum <= 0) continue;

            $anime    = $remaining[$rowSlug];
            $oldValue = isset($anime['aired_episodes']) ? (int)$anime['aired_episodes'] : null;

            // Resolve total: timetable row 'episodes' (0=unknown) first,
            // then our DB value. Auto-finish when the raw run is complete.
            $total = 0;
            if (isset($row['episodes']) && (int)$row['episodes'] > 0) {
                $total = (int)$row['episodes'];
            } elseif (!empty($anime['total_episodes'])) {
                $total = (int)$anime['total_episodes'];
            }
            $finished     = ($total > 0 && $epNum >= $total);
            $airedToWrite = $finished ? $total : $epNum;

            try {
                if ($finished) {
                    $updFin->execute([$airedToWrite, (int)$anime['id']]);
                    $stats['finished']++;
                    error_log('[anime_tracker] auto-finish anime#'
                        . $anime['id'] . ' raw run complete at ep '
                        . $epNum . '/' . $total);
                } elseif ($oldValue !== $airedToWrite) {
                    $upd->execute([$airedToWrite, (int)$anime['id']]);
                    $stats['updated']++;
                } else {
                    $stats['unchanged']++;
                }
            } catch (PDOException $e) {
                $stats['errors']++;
                error_log('[anime_tracker] aired sync UPDATE anime#'
                    . $anime['id'] . ': ' . $e->getMessage());
            }

            unset($remaining[$rowSlug]);
        }
    }

    // Anything still in the remaining set was not in any of the weeks
    $stats['not_in_table'] += count($remaining);

    markLastAiredSync($pdo);

    return $stats;
}

/**
 * Helper used by syncAllOngoingAiredEpisodes to record a successful
 * run timestamp. Pulled out so error paths can decide whether to call
 * it without duplicating the SQL.
 */
function markLastAiredSync($pdo) {
    try {
        $upd = $pdo->prepare("
            INSERT INTO settings (name, value) VALUES ('last_aired_sync', ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $upd->execute([gmdate('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        error_log('[anime_tracker] last_aired_sync write: ' . $e->getMessage());
    }
}
