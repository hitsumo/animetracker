<?php

/**
 * Anime Tracker - AniList Import Helpers (1.1.6)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Pull a user's public AniList anime list over the AniList GraphQL API and
 * normalize it into the SAME entry shape mal_import_helpers produces, so the
 * import writer in list_settings.php (the two-step, session-backed dry-run)
 * can consume it through the existing mal_id match-or-add path with no new
 * downstream code.
 *
 * WHY mal_id and not an anilist_id column: the local catalog is keyed by
 * mal_id / anidb_id / catalog_uuid / title (schema.sql). AniList's API hands
 * back media.idMal for every entry, so each AniList row carries a mal_id and
 * matches the catalog exactly like a MAL row would - no schema change, 1.1.6
 * stays a no-op migration ring (like 1.1.5). Entries AniList has no idMal for
 * fall through to the same "unmatched" bucket a MAL row without a mal_id does.
 *
 * NETWORK: unlike the MAL import (offline file parse), this reaches out to
 * graphql.anilist.co at import time. That is consistent with the app's other
 * outbound features that already run in self-host too (fetch_animeschedule,
 * fetch_aired_episodes, fetch_filler, check_update) and reuses the same cURL
 * shape (TLS verify on, timeouts, IPv4 pin) as animeschedule_helpers.
 *
 * Loaded via the functions.php loader (helper-family convention, same as
 * mal_import_helpers / animeschedule_helpers).
 */

/** AniList GraphQL endpoint. */
if (!defined('ANILIST_GRAPHQL_ENDPOINT')) {
    define('ANILIST_GRAPHQL_ENDPOINT', 'https://graphql.anilist.co');
}

/**
 * Validate an AniList username before spending an API call on it.
 *
 * AniList usernames are letters, digits and underscore (2-20 in practice).
 * The value is sent as a bound GraphQL *variable*, not string-interpolated,
 * so this is a fail-fast / junk filter, not an injection guard. Kept lenient
 * (allow hyphen, up to 50) so a rare valid handle is never rejected.
 *
 * @param mixed $raw
 * @return string|null Trimmed username, or null when unusable.
 */
function anilist_valid_username($raw)
{
    if (!is_string($raw)) {
        return null;
    }
    $s = trim($raw);
    if ($s === '' || !preg_match('/^[A-Za-z0-9_-]{1,50}$/', $s)) {
        return null;
    }
    return $s;
}

// =====================================================================
// Import source limit (1.1.11)
// =====================================================================
// Online (multi-user) mode: any signed-in normal member can type ANYONE's
// public AniList name and import it. To stop one member from pulling an
// unbounded number of different people's lists (flooding the moderation
// queue / catalog), a member may import from at most N DISTINCT AniList
// accounts (default 3). The SAME account may be re-synced without limit
// (legitimate: come back later and pull again). The cap counts distinct
// SOURCE usernames, not imports, and is stored per user in
// anilist_import_sources (one row per distinct username, UNIQUE'd).
//
// Exempt: self-host (single owner) and moderator+ (bulk seeding is their
// legitimate job). The limit is admin-tunable via the settings key
// anilist_import_source_limit (0 = unlimited, emergency off-valve).

/**
 * Is the current user exempt from the AniList import source limit?
 * Self-host (single owner) and moderator+ never hit the cap.
 *
 * @param PDO $pdo
 * @return bool
 */
function anilist_source_exempt($pdo)
{
    if (!defined('MULTI_USER_MODE') || !MULTI_USER_MODE) {
        return true; // self-host: single user, no cap
    }
    return can($pdo, 'moderate'); // moderator+ exempt
}

/**
 * The configured distinct-source cap. settings.anilist_import_source_limit,
 * default 3. A value <= 0 means "unlimited".
 *
 * @param PDO $pdo
 * @return int
 */
function anilist_source_limit($pdo)
{
    $raw = get_setting($pdo, 'anilist_import_source_limit', '3');
    $n = (int)$raw;
    return $n < 0 ? 0 : $n;
}

/**
 * Normalize an AniList username to its slot key: lower-cased + trimmed.
 * AniList resolves usernames case-insensitively, so Mahmut / mahmut / MAHMUT
 * are one slot (prevents trivial gaming of the cap). Pass a value that has
 * already cleared anilist_valid_username().
 *
 * @param string $username
 * @return string
 */
function anilist_source_norm($username)
{
    return mb_strtolower(trim((string)$username), 'UTF-8');
}

/**
 * How many distinct sources the user has already used.
 *
 * @param PDO $pdo
 * @param int $userId
 * @return int
 */
function anilist_source_used_count($pdo, $userId)
{
    $q = $pdo->prepare(
        "SELECT COUNT(*) FROM anilist_import_sources WHERE user_id = ?"
    );
    $q->execute([(int)$userId]);
    return (int)$q->fetchColumn();
}

/**
 * Has the user already imported from this (normalized) source before?
 * A known source is always allowed again (re-sync) and never opens a slot.
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $normUser
 * @return bool
 */
function anilist_source_known($pdo, $userId, $normUser)
{
    $q = $pdo->prepare(
        "SELECT 1 FROM anilist_import_sources
          WHERE user_id = ? AND anilist_username = ? LIMIT 1"
    );
    $q->execute([(int)$userId, $normUser]);
    return (bool)$q->fetchColumn();
}

/**
 * Record a distinct source use. INSERT IGNORE against the UNIQUE
 * (user_id, anilist_username) key, so a re-synced (already known) source
 * opens no new slot and a race between two tabs is absorbed.
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $normUser
 * @return void
 */
function anilist_source_record($pdo, $userId, $normUser)
{
    $q = $pdo->prepare(
        "INSERT IGNORE INTO anilist_import_sources (user_id, anilist_username)
         VALUES (?, ?)"
    );
    $q->execute([(int)$userId, $normUser]);
}

/**
 * May this user import from the given (normalized) source right now?
 * Order: exempt -> unlimited setting -> known source (re-sync) -> under cap.
 *
 * @param PDO $pdo
 * @param int $userId
 * @param string $normUser
 * @return bool
 */
function anilist_source_allowed($pdo, $userId, $normUser)
{
    if (anilist_source_exempt($pdo)) {
        return true;
    }
    $limit = anilist_source_limit($pdo);
    if ($limit <= 0) {
        return true; // unlimited
    }
    if (anilist_source_known($pdo, $userId, $normUser)) {
        return true; // re-sync of an already-used source
    }
    return anilist_source_used_count($pdo, $userId) < $limit;
}

/**
 * Map an AniList media-list status to our watch_status enum.
 *
 * AniList enum: CURRENT, PLANNING, COMPLETED, DROPPED, PAUSED, REPEATING.
 * REPEATING (rewatching) folds into Watching; PAUSED into OnHold. Returns
 * null for anything unrecognized so the caller decides the fallback, never a
 * wrong bucket (mirrors mal_status_to_enum).
 *
 * @param mixed $raw
 * @return string|null One of Watched/Watching/PlanToWatch/OnHold/Dropped, or null.
 */
function anilist_status_to_enum($raw)
{
    if ($raw === null) {
        return null;
    }
    $key = strtoupper(trim((string)$raw));
    if ($key === '') {
        return null;
    }
    $map = [
        'CURRENT'   => 'Watching',
        'REPEATING' => 'Watching',
        'COMPLETED' => 'Watched',
        'PAUSED'    => 'OnHold',
        'DROPPED'   => 'Dropped',
        'PLANNING'  => 'PlanToWatch',
    ];
    return $map[$key] ?? null;
}

/**
 * Map an AniList media (airing) status to our animes.status enum.
 *
 * Since 1.1.10 animes.status has five values, so AniList's five states map
 * almost one-to-one instead of being folded into two:
 *   FINISHED          -> Yayın Tamamlandı  (finished airing)
 *   RELEASING         -> Yayın Devam Ediyor (currently airing)
 *   HIATUS            -> Yayın Devam Ediyor (paused but not finished)
 *   NOT_YET_RELEASED  -> Yayın Başlamadı   (upcoming)
 *   CANCELLED         -> Yayın İptal Edildi (cancelled)
 * Anything unrecognized/absent falls back to 'Seçim Yapılmadı' (the 1.1.10
 * unknown default), so a row always has a valid enum value.
 *
 * This is AniList-only: the MAL XML export carries no airing status, so the MAL
 * import keeps its fixed default. Used ONLY by the self-host local-add path
 * (online unmatched entries go to catalog_requests, which stores no status).
 *
 * @param mixed $raw AniList media.status.
 * @return string One of the five animes.status enum values.
 */
function anilist_airing_status_to_enum($raw)
{
    $key = strtoupper(trim((string)$raw));
    switch ($key) {
        case 'FINISHED':         return 'Yayın Tamamlandı';
        case 'RELEASING':
        case 'HIATUS':           return 'Yayın Devam Ediyor';
        case 'NOT_YET_RELEASED': return 'Yayın Başlamadı';
        case 'CANCELLED':        return 'Yayın İptal Edildi';
        default:                 return 'Seçim Yapılmadı';
    }
}

/**
 * Normalize an AniList FuzzyDate ({year, month, day}, any part nullable) into
 * a clean 'YYYY-MM-DD' string, or null.
 *
 * A partial date (missing month or day, e.g. year-only) is rejected as null,
 * matching the MAL rule (mal_normalize_date) that a partial date is dropped
 * rather than guessed. Values map straight onto our DATE columns
 * (watch_start_date / watch_finish_date); no time-zone math.
 *
 * @param mixed $fuzzy An assoc array with year/month/day, or null.
 * @return string|null
 */
function anilist_normalize_date($fuzzy)
{
    if (!is_array($fuzzy)) {
        return null;
    }
    $y = isset($fuzzy['year'])  ? (int)$fuzzy['year']  : 0;
    $m = isset($fuzzy['month']) ? (int)$fuzzy['month'] : 0;
    $d = isset($fuzzy['day'])   ? (int)$fuzzy['day']   : 0;
    if ($y <= 0 || $m <= 0 || $d <= 0) {
        return null;
    }
    if (!checkdate($m, $d, $y)) {
        return null;
    }
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

/**
 * Map an AniList countryOfOrigin to the LANGUAGE of media.title.native.
 *
 * AniList writes the native title in the origin country's language but
 * labels it only with a COUNTRY code (JP/CN/TW/KR...), never a language.
 * This is the country->language bridge for the [xx] title tags (1.1.20):
 * a Japanese anime's native title is tagged [ja], a donghua's [zh], a
 * Korean animation's [ko]. An unmapped or missing country returns '' and
 * the caller stores the name UNTAGGED - an untagged name is honest, a
 * guessed tag would feed display_title() the wrong language.
 *
 * @param mixed $country AniList media.countryOfOrigin.
 * @return string Two-letter title-language code, or '' when unbridgeable.
 */
function anilist_native_lang($country)
{
    $map = ['JP' => 'ja', 'CN' => 'zh', 'TW' => 'zh', 'KR' => 'ko'];
    return $map[strtoupper(trim((string)$country))] ?? '';
}

/**
 * Build the alternative_titles column value for one AniList media node.
 *
 * 1.1.22: until now the import kept ONE name (romaji, falling back to
 * english) and threw the rest of title{} away, so an imported anime never
 * benefited from the Title Language preference (1.1.21) - it rendered as
 * Romaji for everyone. The discarded names now become TAGGED alternative
 * titles, in the exact storage format the edit form writes:
 *
 *     [en]Frieren: Beyond Journey's End|[ja]葬送のフリーレン
 *
 * - title.english -> [en], unless it is just a case-variant of the main
 *   title (AniList often repeats "One Piece" in both fields).
 * - title.native  -> tagged via anilist_native_lang(), untagged when the
 *   origin country is not bridgeable.
 *
 * Built through build_alt_titles() (title_lang_helpers) so the import
 * obeys the same hygiene as the form: pipes become spaces, a leading
 * valid [xx] inside a name is stripped, an unknown code degrades to
 * untagged. Returns NULL when nothing usable remains, the column's
 * "no data" idiom.
 *
 * @param array  $media     Decoded media node (title / countryOfOrigin).
 * @param string $mainTitle The name already chosen for the entry's title.
 * @return string|null      alternative_titles value, or null.
 */
function anilist_alt_titles(array $media, $mainTitle)
{
    $titles = [];
    $langs  = [];
    // Case-insensitive dedup pot, seeded with the main title: a name that
    // only repeats the main title adds noise, not information.
    $seen = [mb_strtolower(trim((string)$mainTitle), 'UTF-8') => true];

    $english = trim((string)($media['title']['english'] ?? ''));
    if ($english !== '') {
        $k = mb_strtolower($english, 'UTF-8');
        if (!isset($seen[$k])) {
            $seen[$k] = true;
            $titles[] = $english;
            $langs[]  = 'en';
        }
    }

    $native = trim((string)($media['title']['native'] ?? ''));
    if ($native !== '') {
        $k = mb_strtolower($native, 'UTF-8');
        if (!isset($seen[$k])) {
            $seen[$k] = true;
            $titles[] = $native;
            $langs[]  = anilist_native_lang($media['countryOfOrigin'] ?? null);
        }
    }

    if (empty($titles)) {
        return null;
    }
    $out = build_alt_titles($titles, $langs);
    return $out !== '' ? $out : null;
}

/**
 * Perform one AniList GraphQL POST. Thin cURL wrapper mirroring the shape used
 * in animeschedule_helpers (TLS verify on, IPv4 pin, short timeouts). Returns
 * the decoded response array on HTTP 200, or an ['error' => ...] shape:
 *   'network'    - transport failure (curl body === false)
 *   'rate_limit' - HTTP 429
 *   'notfound'   - HTTP 404 (AniList returns 404 for an unknown user)
 *   'http'       - any other non-200 status
 *   'parse'      - 200 but body was not decodable JSON
 *
 * @param string $query     GraphQL query string.
 * @param array  $variables Bound variables (sent as JSON, never interpolated).
 * @return array
 */
function anilist_graphql_request($query, array $variables)
{
    $payload = json_encode(['query' => $query, 'variables' => $variables]);

    $ch = curl_init(ANILIST_GRAPHQL_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 5,
        // Pin IPv4 for the same reason animeschedule_helpers does: some hosts
        // resolve an AAAA record but have no working IPv6 egress and stall.
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
        error_log('[anime_tracker] AniList cURL error: ' . $curlErr);
        return ['error' => 'network'];
    }
    if ($httpCode === 429) {
        return ['error' => 'rate_limit'];
    }
    if ($httpCode === 404) {
        return ['error' => 'notfound'];
    }
    if ($httpCode !== 200) {
        // AniList also returns 404 for unknown users; some deployments surface
        // "User not found" inside a 200/errors body instead - handled by caller.
        $data = json_decode($body, true);
        if (is_array($data) && !empty($data['errors'])) {
            foreach ($data['errors'] as $err) {
                if (isset($err['status']) && (int)$err['status'] === 404) {
                    return ['error' => 'notfound'];
                }
            }
        }
        error_log('[anime_tracker] AniList HTTP ' . $httpCode);
        return ['error' => 'http'];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'parse'];
    }
    // A 200 can still carry a GraphQL errors array (e.g. user not found).
    if (!empty($data['errors'])) {
        foreach ($data['errors'] as $err) {
            if (isset($err['status']) && (int)$err['status'] === 404) {
                return ['error' => 'notfound'];
            }
        }
        return ['error' => 'http'];
    }
    return $data;
}

/**
 * Fetch a user's public AniList anime list and normalize it into the MAL entry
 * shape the import writer consumes.
 *
 * Returns:
 *   [ 'ok' => true,  'entries' => [ ... ] ]                on success, or
 *   [ 'ok' => false, 'error' => 'bad_username'|'network'|'rate_limit'
 *                              |'notfound'|'http'|'parse'|'empty' ]
 *
 * Each entry is identical in shape to mal_parse_export()'s:
 *   [ 'mal_id' => int|null, 'title' => string,
 *     'watch_status' => enum|null, 'watched_episodes' => int,
 *     'watch_start_date' => 'YYYY-MM-DD'|null,
 *     'watch_finish_date' => 'YYYY-MM-DD'|null, 'notes' => string|null ]
 * plus the AniList-only extras MAL's XML cannot supply: 'airing_status'
 * (1.1.6), 'is_adult' (1.1.7), 'country' (1.1.17) and 'alternative_titles'
 * (1.1.22, tagged names - see anilist_alt_titles()).
 *
 * A row with neither a positive mal_id nor a title is dropped (nothing to
 * match on), mirroring the MAL parser. An empty/private list yields 'empty'.
 * Paginated at 50/page (AniList Page cap); $maxPages bounds a runaway list.
 *
 * @param string $username   Raw username from the form.
 * @param int    $maxPages   Safety cap on pages fetched (50 each).
 * @return array
 */
function anilist_fetch_list($username, $maxPages = 100)
{
    $name = anilist_valid_username($username);
    if ($name === null) {
        return ['ok' => false, 'error' => 'bad_username'];
    }

    $query = '
    query ($name: String, $page: Int) {
      Page(page: $page, perPage: 50) {
        pageInfo { hasNextPage }
        mediaList(userName: $name, type: ANIME) {
          status
          progress
          notes
          startedAt { year month day }
          completedAt { year month day }
          media { idMal status isAdult countryOfOrigin title { romaji english native } }
        }
      }
    }';

    $entries = [];
    $page = 1;
    // AniList currently enforces a low per-minute request budget (it has run in
    // a degraded ~30 req/min mode for a long stretch). Two guards keep a large,
    // multi-page list importable:
    //   1) ~2.5s between page requests, to stay under the per-minute cap;
    //   2) on a 429, wait one AniList window (60s) and retry the SAME page a few
    //      times instead of aborting - so a transient limit (e.g. a burst from
    //      another AniList call) does not kill the whole import.
    // Lift the PHP time limit because that pacing/waiting can outlast the default
    // max_execution_time on a big list. This runs in the web preview request, so
    // the guard matters (function_exists: some hosts disable set_time_limit).
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }
    $retryWait = 60; // seconds to wait after a 429 (AniList window)
    $maxRetry  = 3;  // per-page retries on a rate limit
    do {
        // No sleep before the first request.
        if ($page > 1) {
            usleep(2500000); // 2.5s between pages
        }

        // Fetch this page, retrying a rate-limit after a wait so a big list is
        // not lost to a transient per-minute cap.
        $resp = null;
        for ($try = 0; ; $try++) {
            $resp = anilist_graphql_request($query, ['name' => $name, 'page' => $page]);
            if (!isset($resp['error'])) {
                break; // got the page
            }
            if ($resp['error'] === 'rate_limit' && $try < $maxRetry) {
                sleep($retryWait);
                continue;
            }
            // Any other error, or retries exhausted: fatal for the whole import
            // (a partial list would silently look complete).
            return ['ok' => false, 'error' => $resp['error']];
        }

        $pageData = $resp['data']['Page'] ?? null;
        if (!is_array($pageData)) {
            return ['ok' => false, 'error' => 'parse'];
        }

        $rows = $pageData['mediaList'] ?? [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $media = $row['media'] ?? [];
                $malId = isset($media['idMal']) ? (int)$media['idMal'] : 0;
                $title = trim((string)(
                    ($media['title']['romaji'] ?? '') !== ''
                        ? $media['title']['romaji']
                        : ($media['title']['english'] ?? '')
                ));
                if ($malId <= 0 && $title === '') {
                    continue;
                }
                $notes = trim((string)($row['notes'] ?? ''));
                $entries[] = [
                    'mal_id'            => $malId > 0 ? $malId : null,
                    'title'             => $title,
                    'watch_status'      => anilist_status_to_enum($row['status'] ?? null),
                    'watched_episodes'  => max(0, (int)($row['progress'] ?? 0)),
                    'watch_start_date'  => anilist_normalize_date($row['startedAt'] ?? null),
                    'watch_finish_date' => anilist_normalize_date($row['completedAt'] ?? null),
                    'notes'             => $notes !== '' ? $notes : null,
                    // AniList-only: the anime's airing status, for the self-host
                    // local-add (so a still-airing anime is not forced to "finished").
                    'airing_status'     => anilist_airing_status_to_enum($media['status'] ?? null),
                    // AniList media.isAdult -> animes.is_adult / catalog_requests.is_adult
                    // (1.1.7): flag imported adult titles so they are not written as
                    // is_adult=0 and slip past the +18 filter (1.1.2/1.1.3). Bool->tinyint.
                    'is_adult'          => !empty($media['isAdult']) ? 1 : 0,
                    // AniList media.countryOfOrigin -> animes.country (1.1.17). Already
                    // an ISO 3166-1 alpha-2 code, the exact form we store, so this is a
                    // read and not a guess. Filtered through is_valid_country_code():
                    // AniList can return a code we have no name for, and writing one
                    // would leave the anime unshowable by country_label() and
                    // unreachable by the country filter - NULL is the honest value
                    // there, and the one-time backfill script reports such codes so
                    // the list can be extended deliberately.
                    'country'           => (isset($media['countryOfOrigin'])
                                            && is_valid_country_code($media['countryOfOrigin']))
                                            ? strtoupper($media['countryOfOrigin'])
                                            : null,
                    // 1.1.22: the names NOT chosen as the main title, kept as
                    // TAGGED alternative titles ([en]/[ja]/[zh]/[ko]) so an
                    // imported anime follows the Title Language preference
                    // instead of always rendering Romaji. NULL when AniList
                    // offers nothing beyond the main title.
                    'alternative_titles' => anilist_alt_titles($media, $title),
                ];
            }
        }

        $hasNext = !empty($pageData['pageInfo']['hasNextPage']);
        $page++;
    } while ($hasNext && $page <= $maxPages);

    if (empty($entries)) {
        // Genuinely empty, or a private list (AniList returns an empty
        // mediaList for lists the viewer may not see).
        return ['ok' => false, 'error' => 'empty'];
    }
    return ['ok' => true, 'entries' => $entries];
}

/**
 * Build the ua_set_state payload for one normalized AniList entry.
 *
 * Identical policy to mal_ua_payload: fall back to PlanToWatch on an
 * unmappable status, always carry status + watched_episodes, and include the
 * optional fields (notes, dates) ONLY when present so an overwrite never
 * erases values the user already had (ua_set_state writes every key present,
 * including null).
 *
 * @param array $e A normalized entry from anilist_fetch_list().
 * @return array Payload for ua_set_state().
 */
function anilist_ua_payload(array $e)
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

/**
 * Fetch an anime's broadcast START and END dates from AniList, by MAL ID.
 *
 * WHY ANILIST AND NOT ANIMESCHEDULE (1.1.27)
 * The "Otomatik Doldur" button talks to AnimeSchedule, but the AnimeSchedule
 * v3 anime object has NO end-date field at all. Its date fields are `premier`,
 * `subPremier`, `dubPremier`, the `delayed*` pair and `jpnTime`/`subTime`/
 * `dubTime` - a first-episode date and a broadcast clock, nothing that marks
 * the finale. (Checked against their own API documentation and confirmed
 * against a third-party SDK's full field list.)
 *
 * WHY THE START DATE ALSO COMES FROM HERE, not from AnimeSchedule's `premier`
 * (which IS a first-episode timestamp and looks like the obvious source):
 * `premier` is an instant in time, and turning an instant into a CALENDAR DATE
 * needs a timezone. Japanese late-night anime is broadcast after midnight but
 * announced under the PREVIOUS day ("Friday at 25:25" = Saturday 01:25). So the
 * Tokyo calendar date of the premier instant can be one day later than the air
 * date everyone - including our own catalog - calls the release date. AniList's
 * startDate is already the curated calendar date, with that convention resolved,
 * and we are making this request anyway: one query, two fields, no timezone
 * guesswork.
 *
 * The alternative was to DERIVE it: premier + (episodes - 1) x 7 days. That
 * was rejected. It is right only for a series that never skipped a week, and
 * skipped weeks are common enough that AnimeSchedule itself models them
 * (delayedFrom / delayedUntil). A derived date would land in the form looking
 * exactly like fetched data and be silently wrong by a week or more - the same
 * class of problem as the false "field filled" report fixed in the same
 * version.
 *
 * AniList's Media.endDate is a real, curated field, so we ask the source that
 * actually knows. AniList's public GraphQL endpoint needs NO API KEY, so this
 * also works on a self-host install that never configured one (unlike the
 * AnimeSchedule half of the same button).
 *
 * MATCHING takes an AniList id when one is known and falls back to a MAL id
 * (animes.mal_id <-> AniList Media.idMal, the bridge the catalog already uses).
 * The AniList id is preferred when available because it needs no bridge at all
 * and still resolves titles that have no MyAnimeList entry. With neither id
 * there is nothing to match on and the call yields nulls - the known limit that
 * also governs the synopsis link shortcode.
 *
 * FUZZY DATES: AniList dates are {year, month, day} and any part may be null
 * (a show known only to have ended "in 2019"). anilist_normalize_date() returns
 * null unless all three are present and form a real date, so a partial date is
 * never written into a date input as a guess. A still-airing show has no end
 * date yet and falls out the same way.
 *
 * Errors are swallowed into nulls on purpose: this is a best-effort extra on top
 * of the AnimeSchedule fetch, and a network hiccup here must not turn the whole
 * autofill into a failure. anilist_graphql_request() logs the details.
 *
 * Note for callers: a SINGLE-EPISODE work (film, special, OVA) normally reports
 * the SAME value for both dates - AniList gives start == end for e.g. Lupin III
 * vs Meitantei Conan (2009-03-27). That is correct data, not a bug; deciding
 * whether an end date is worth showing in that case belongs to the caller.
 *
 * @param int|null $malId      MyAnimeList id, or null/0 when unknown.
 * @param int|null $anilistId  AniList id; wins over $malId when both are given.
 * @return array{start_date: ?string, end_date: ?string} 'YYYY-MM-DD' or null each.
 */
function anilist_fetch_dates($malId, $anilistId = null)
{
    $empty = ['start_date' => null, 'end_date' => null];

    $malId     = (int)$malId;
    $anilistId = (int)$anilistId;

    // Only ONE selector is sent. AniList would happily take both, but if the
    // two disagreed (a stale cross-link on either side) the row it returned
    // would be a coin flip. Preferring the direct id keeps that unambiguous.
    if ($anilistId > 0) {
        $query = 'query ($id: Int) {
            Media(id: $id, type: ANIME) {
                startDate { year month day }
                endDate { year month day }
            }
        }';
        $variables = ['id' => $anilistId];
    } elseif ($malId > 0) {
        $query = 'query ($idMal: Int) {
            Media(idMal: $idMal, type: ANIME) {
                startDate { year month day }
                endDate { year month day }
            }
        }';
        $variables = ['idMal' => $malId];
    } else {
        return $empty;
    }

    $data = anilist_graphql_request($query, $variables);
    if (!is_array($data) || isset($data['error'])) {
        return $empty;
    }

    $media = $data['data']['Media'] ?? null;
    if (!is_array($media)) {
        return $empty;
    }

    return [
        'start_date' => anilist_normalize_date($media['startDate'] ?? null),
        'end_date'   => anilist_normalize_date($media['endDate']   ?? null),
    ];
}
