<?php

/**
 * Anime Tracker - AniList Import Helpers (1.1.6)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
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
 * animes.status is a NOT NULL 2-value enum: 'Yayın Tamamlandı' (finished) or
 * 'Yayın Devam Ediyor' (ongoing). AniList media.status has five values, folded
 * into those two (the DB has no "unknown" state):
 *   FINISHED, CANCELLED           -> Yayın Tamamlandı (no more episodes coming)
 *   RELEASING, NOT_YET_RELEASED,
 *   HIATUS                        -> Yayın Devam Ediyor (not finished)
 * Anything unrecognized/absent falls back to 'Yayın Tamamlandı' (the historical
 * import default), so a row always has a valid enum value.
 *
 * This is AniList-only: the MAL XML export carries no airing status, so the MAL
 * import keeps its fixed default. Used ONLY by the self-host local-add path
 * (online unmatched entries go to catalog_requests, which stores no status).
 *
 * @param mixed $raw AniList media.status.
 * @return string 'Yayın Tamamlandı' or 'Yayın Devam Ediyor'.
 */
function anilist_airing_status_to_enum($raw)
{
    $key = strtoupper(trim((string)$raw));
    $ongoing = ['RELEASING', 'NOT_YET_RELEASED', 'HIATUS'];
    return in_array($key, $ongoing, true) ? 'Yayın Devam Ediyor' : 'Yayın Tamamlandı';
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
          media { idMal status title { romaji english } }
        }
      }
    }';

    $entries = [];
    $page = 1;
    do {
        // Be a good API citizen: a small gap between page requests keeps a
        // large list well under AniList's per-minute budget. No sleep before
        // the first request.
        if ($page > 1) {
            usleep(350000); // 0.35s
        }

        $resp = anilist_graphql_request($query, ['name' => $name, 'page' => $page]);
        if (isset($resp['error'])) {
            // A rate-limit or transport error mid-pagination is fatal for the
            // whole import (a partial list would silently look complete).
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
