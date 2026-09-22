<?php

/**
 * Anime Tracker - Watch log helpers (user_watch_log)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.1.46 - the personal WATCH LOG.
 *
 * user_anime.updated_at is a single stamp: "the last time this row was
 * touched". A note edit, a status change and a MAL import all move it,
 * and an import stamps every row with the import day. So "what did I
 * watch last week" had no honest answer - the row only remembers where
 * you are now, not how you got there.
 *
 * user_watch_log keeps one row per CHANGE of watched_episodes:
 *
 *   (user_id, anime_id, episode_from, episode_to, logged_at)
 *
 * It is written from ONE place - ua_set_state() - whenever the value it
 * writes differs from the value already stored. Every writer of
 * watched_episodes goes through that helper (the +/- endpoint on the
 * list and detail pages, the edit form, add_anime, the MAL / AniList /
 * JSON imports), so none of them has to know the log exists. An import
 * therefore leaves ONE row per anime, stamped with the import moment and
 * spanning the whole jump (0 -> 24): that is the truth of what happened.
 *
 * A "-" is logged too (episode_from > episode_to). The period views sum
 * the deltas per anime, so a "+1" followed by an undo "-1" nets to zero
 * and disappears; a plain correction downwards nets negative and is
 * likewise left out of "what I watched".
 *
 * NO SEED. Rows that existed before 1.1.46 get no fabricated history
 * (the 1.1.44 rule: a stamp that was never taken is not invented). The
 * "all" view on recent.php still lists them, by user_anime.updated_at,
 * with a "pre-log" label; the week / month / range views read the log
 * only.
 *
 * Local-only: the log never goes to the central catalog. It DOES go into
 * the JSON backup (list_settings export/import), nested under each anime
 * as 'watch_log', so a backup / restore round trip keeps the history.
 * Timestamps are written from PHP (date_default_timezone_set('UTC') in
 * db.php), not MySQL NOW(), so the period bounds computed in PHP and the
 * stored values share one clock.
 */

/**
 * Append one log row. Called by ua_set_state() after a successful upsert
 * whose watched_episodes actually changed; imports that carry their own
 * log rows go through watch_log_import_set() instead.
 *
 * $loggedAt: 'Y-m-d H:i:s' or null for "now".
 * Returns true on success, false on a logged DB error (the caller's
 * write already succeeded; a failed log line must not fail the request).
 */
function watch_log_record($pdo, $userId, $animeId, $from, $to, $loggedAt = null)
{
    $userId  = (int)$userId;
    $animeId = (int)$animeId;
    $from    = (int)$from;
    $to      = (int)$to;
    if ($userId <= 0 || $animeId <= 0 || $from === $to) {
        return false;
    }
    if ($loggedAt === null) {
        $loggedAt = date('Y-m-d H:i:s');
    }
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO user_watch_log (user_id, anime_id, episode_from, episode_to, logged_at)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $animeId, $from, $to, $loggedAt]);
        return true;
    } catch (PDOException $e) {
        error_log('[anime_tracker] watch_log_record(' . $userId . ',' . $animeId . '): '
            . $e->getMessage());
        return false;
    }
}

/**
 * The current user's log rows for one anime, oldest first - the shape
 * the JSON export nests under each anime as 'watch_log'.
 *
 * @return array  list of ['episode_from' => int, 'episode_to' => int, 'logged_at' => 'Y-m-d H:i:s']
 */
function watch_log_export($pdo, $userId, $animeId)
{
    try {
        $stmt = $pdo->prepare(
            "SELECT episode_from, episode_to, logged_at
               FROM user_watch_log
              WHERE user_id = ? AND anime_id = ?
              ORDER BY logged_at, id"
        );
        $stmt->execute([(int)$userId, (int)$animeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['episode_from'] = (int)$r['episode_from'];
            $r['episode_to']   = (int)$r['episode_to'];
        }
        unset($r);
        return $rows;
    } catch (PDOException $e) {
        error_log('[anime_tracker] watch_log_export(' . $userId . ',' . $animeId . '): '
            . $e->getMessage());
        return [];
    }
}

/**
 * Restore log rows from a JSON backup for one (user, anime).
 *
 * Idempotent: a row whose (episode_from, episode_to, logged_at) already
 * exists for this user/anime is skipped, so importing the same file
 * twice - or restoring into an install that already holds part of the
 * history (the self-host restore MERGES, it does not wipe) - never
 * duplicates. Rows with a from == to, a non-numeric episode or an
 * unparseable timestamp are dropped silently: the backup is trusted for
 * shape, not for content.
 *
 * @param  array $rows  the anime's 'watch_log' array from the file
 * @return int          number of rows inserted
 */
function watch_log_import_set($pdo, $userId, $animeId, $rows)
{
    if (!is_array($rows) || empty($rows)) {
        return 0;
    }
    $userId  = (int)$userId;
    $animeId = (int)$animeId;
    if ($userId <= 0 || $animeId <= 0) {
        return 0;
    }

    try {
        $exists = $pdo->prepare(
            "SELECT 1 FROM user_watch_log
              WHERE user_id = ? AND anime_id = ?
                AND episode_from = ? AND episode_to = ? AND logged_at = ?
              LIMIT 1"
        );
        $insert = $pdo->prepare(
            "INSERT INTO user_watch_log (user_id, anime_id, episode_from, episode_to, logged_at)
             VALUES (?, ?, ?, ?, ?)"
        );
    } catch (PDOException $e) {
        error_log('[anime_tracker] watch_log_import_set(' . $userId . ',' . $animeId . '): '
            . $e->getMessage());
        return 0;
    }

    $added = 0;
    foreach ($rows as $r) {
        if (!is_array($r)) { continue; }
        if (!isset($r['episode_from'], $r['episode_to'], $r['logged_at'])) { continue; }
        if (!is_numeric($r['episode_from']) || !is_numeric($r['episode_to'])) { continue; }
        $from = (int)$r['episode_from'];
        $to   = (int)$r['episode_to'];
        if ($from === $to || $from < 0 || $to < 0) { continue; }
        $ts = strtotime((string)$r['logged_at']);
        if ($ts === false) { continue; }
        $at = date('Y-m-d H:i:s', $ts);
        try {
            $exists->execute([$userId, $animeId, $from, $to, $at]);
            if ($exists->fetchColumn()) { continue; }
            $insert->execute([$userId, $animeId, $from, $to, $at]);
            $added++;
        } catch (PDOException $e) {
            error_log('[anime_tracker] watch_log_import_set(' . $userId . ',' . $animeId . '): '
                . $e->getMessage());
        }
    }
    return $added;
}

// ---------------------------------------------------------------------
// Period filters (recent.php ?tab=watched)
// ---------------------------------------------------------------------

/** Valid values of recent.php's ?period= on the watched tab. */
function watch_log_periods()
{
    return ['all', 'week', 'month', 'range'];
}

/**
 * Turn the request's period / from / to into concrete bounds.
 *
 * Returns ['period' => 'all'|'week'|'month'|'range',
 *          'since' => 'Y-m-d H:i:s'|null, 'until' => 'Y-m-d H:i:s'|null,
 *          'from' => 'Y-m-d'|null, 'to' => 'Y-m-d'|null].
 *
 * 'week' and 'month' are ROLLING windows (last 7 / 30 days up to now),
 * which is what "son bir hafta" means in speech; a calendar week would
 * show one anime on a Monday morning. 'range' needs two valid dates;
 * reversed dates are swapped, a missing or malformed date falls back to
 * 'all' rather than erroring - the filter lives in the URL and a stale
 * link should still show something. 'until' is exclusive (midnight after
 * the 'to' day) so the whole last day counts.
 */
function watch_log_period_bounds($period, $from = null, $to = null)
{
    $period = (string)$period;
    if (!in_array($period, watch_log_periods(), true)) {
        $period = 'all';
    }
    $out = ['period' => $period, 'since' => null, 'until' => null, 'from' => null, 'to' => null];

    if ($period === 'week' || $period === 'month') {
        $days = ($period === 'week') ? 7 : 30;
        $out['since'] = date('Y-m-d H:i:s', time() - $days * 86400);
        return $out;
    }

    if ($period === 'range') {
        $f = watch_log_parse_day($from);
        $t = watch_log_parse_day($to);
        if ($f === null || $t === null) {
            $out['period'] = 'all';
            return $out;
        }
        if ($f > $t) {
            list($f, $t) = [$t, $f];
        }
        $out['from']  = date('Y-m-d', $f);
        $out['to']    = date('Y-m-d', $t);
        $out['since'] = date('Y-m-d 00:00:00', $f);
        $out['until'] = date('Y-m-d 00:00:00', $t + 86400);
        return $out;
    }

    return $out;
}

/** 'YYYY-MM-DD' -> midnight timestamp, or null when not a real date. */
function watch_log_parse_day($s)
{
    if (!is_string($s) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
        return null;
    }
    if (!checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
        return null;
    }
    return mktime(0, 0, 0, (int)$m[2], (int)$m[3], (int)$m[1]);
}

/**
 * "N anime, M episodes" for a window. Per anime the deltas are summed
 * and only a positive net counts (an undone "+1" is not a watch; a
 * downward correction is not a watch either). $until null = open end.
 *
 * @return array ['animes' => int, 'episodes' => int]
 */
function watch_log_summary($pdo, $userId, $since, $until = null)
{
    $sql = "SELECT COUNT(*) AS animes, COALESCE(SUM(net), 0) AS episodes
              FROM (
                    SELECT anime_id, SUM(episode_to - episode_from) AS net
                      FROM user_watch_log
                     WHERE user_id = :uid AND logged_at >= :since"
         . ($until !== null ? " AND logged_at < :until" : "")
         . "     GROUP BY anime_id
                    HAVING net > 0
                   ) x";
    $params = [':uid' => (int)$userId, ':since' => $since];
    if ($until !== null) { $params[':until'] = $until; }
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'animes'   => (int)($row['animes'] ?? 0),
            'episodes' => (int)($row['episodes'] ?? 0),
        ];
    } catch (PDOException $e) {
        error_log('[anime_tracker] watch_log_summary(' . $userId . '): ' . $e->getMessage());
        return ['animes' => 0, 'episodes' => 0];
    }
}
