<?php

/**
 * Anime Tracker - Per-user state helpers (user_anime + user_pref)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.0.1 / Faz 2, Milestone 1 - part 2b.
 *
 * These helpers are the single access point for a user's PERSONAL state:
 *   - user_anime: watch_status, watched_episodes, notes, user_synopsis,
 *     user_synopsis_en  (one row per (user, anime))
 *   - user_pref:  per-user key/value preferences (display_language,
 *     display_title_english, ...) - the user-scope twin of settings.
 *
 * Every endpoint that reads or writes personal state goes through these
 * (passing current_user_id() as $userId) instead of touching animes
 * columns or the global settings table directly. With MULTI_USER_MODE
 * off, $userId is always 1 (the seeded owner), so single-user behaviour
 * is byte-for-byte the same as before the split.
 *
 * READ PATTERN FOR LIST PAGES (index, recent, recommendations, ...):
 * pages that previously did "SELECT * FROM animes" join user_anime, so a
 * catalog anime with no personal row still renders. 1.0.10: watch_status
 * is selected RAW (NULL = "not selected"; the watch_status_label /
 * watch_status_css_class helpers map NULL to the unselected label and
 * class). COALESCE stays only where a missing value must ACT as one in
 * logic, e.g. != 'Watched' filters. watched_episodes still COALESCEs
 * to 0 (single-user: every anime has a row after the copy, so this is
 * just defensive; multi-user "my list" later switches the LEFT JOIN to
 * an INNER JOIN or adds a WHERE ua.user_id filter):
 *
 *     SELECT a.*,
 *            ua.watch_status,
 *            COALESCE(ua.watched_episodes, 0)         AS watched_episodes,
 *            ua.notes, ua.user_synopsis, ua.user_synopsis_en
 *       FROM animes a
 *       LEFT JOIN user_anime ua
 *              ON ua.anime_id = a.id AND ua.user_id = :uid
 *
 * with :uid bound to current_user_id().
 */

// ---------------------------------------------------------------------
// user_anime
// ---------------------------------------------------------------------

/**
 * The personal state of an anime that has no user_anime row yet. Matches
 * the user_anime column DEFAULTs so a missing row and a default row look
 * identical to the rest of the application. 1.0.10: watch_status is NULL
 * (column DEFAULT NULL) - a missing row means "not selected", not
 * PlanToWatch; callers that need a concrete value must decide explicitly.
 */
function ua_default_state()
{
    return [
        'watch_status'     => null,
        'watched_episodes' => 0,
        'notes'            => null,
        'user_synopsis'    => null,
        'user_synopsis_en' => null,
        'watch_start_date'  => null,
        'watch_finish_date' => null,
    ];
}

/**
 * Read one user's personal state for a single anime.
 * Returns ua_default_state() if the user has no row for it (anime not in
 * their list, or freshly imported), so callers never get a null row.
 */
function ua_get_state($pdo, $userId, $animeId)
{
    try {
        $stmt = $pdo->prepare(
            "SELECT watch_status, watched_episodes, notes,
                    user_synopsis, user_synopsis_en,
                    watch_start_date, watch_finish_date
               FROM user_anime
              WHERE user_id = ? AND anime_id = ?
              LIMIT 1"
        );
        $stmt->execute([$userId, $animeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : ua_default_state();
    } catch (PDOException $e) {
        error_log('[anime_tracker] ua_get_state(' . $userId . ',' . $animeId . '): '
            . $e->getMessage());
        return ua_default_state();
    }
}

/**
 * Upsert a subset of personal fields for (user, anime). Only the keys
 * present in $fields are written; the row is created if it does not
 * exist (a "+" on an anime not yet in the list creates the row). Columns
 * not listed keep their value (existing row) or their column DEFAULT (new
 * row), so partial writes are safe - e.g. writing only 'notes' on a new
 * row leaves watch_status NULL ("not selected", 1.0.10) and
 * watched_episodes=0.
 *
 * Allowed keys: watch_status, watched_episodes, notes, user_synopsis,
 * user_synopsis_en, watch_start_date, watch_finish_date. Anything else is
 * ignored. Date columns expect a 'YYYY-MM-DD' string or null; callers must
 * turn an empty form value into null (an empty string is an invalid DATE
 * and would fail the whole upsert).
 *
 * Returns true on success, false on a logged DB error.
 */
function ua_set_state($pdo, $userId, $animeId, array $fields)
{
    $allowed = [
        'watch_status', 'watched_episodes', 'notes',
        'user_synopsis', 'user_synopsis_en',
        'watch_start_date', 'watch_finish_date',
    ];

    // Keep only recognised columns, preserving caller order.
    $set = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $fields)) {
            $set[$col] = $fields[$col];
        }
    }
    if (!$set) {
        return true; // nothing to write
    }

    $insertCols   = array_merge(['user_id', 'anime_id'], array_keys($set));
    $colList      = '`' . implode('`, `', $insertCols) . '`';
    $placeholders = implode(', ', array_fill(0, count($insertCols), '?'));

    $updateParts = [];
    foreach (array_keys($set) as $col) {
        $updateParts[] = "`$col` = VALUES(`$col`)";
    }
    $updateClause = implode(', ', $updateParts);

    $values = array_merge([$userId, $animeId], array_values($set));

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO user_anime ($colList) VALUES ($placeholders)
             ON DUPLICATE KEY UPDATE $updateClause"
        );
        $stmt->execute($values);
        return true;
    } catch (PDOException $e) {
        error_log('[anime_tracker] ua_set_state(' . $userId . ',' . $animeId . '): '
            . $e->getMessage());
        return false;
    }
}

// ---------------------------------------------------------------------
// user_pref - mirrors get_setting / set_setting (i18n_helpers.php) but
// scoped to a user. Same key/value shape, same ON DUPLICATE KEY pattern.
// ---------------------------------------------------------------------

/**
 * Read a per-user preference. Returns $default if the user has no row for
 * that key (or on a DB error), exactly like get_setting() does globally.
 */
function get_user_pref($pdo, $userId, $name, $default = null)
{
    try {
        $stmt = $pdo->prepare(
            "SELECT value FROM user_pref
              WHERE user_id = ? AND name = ?
              LIMIT 1"
        );
        $stmt->execute([$userId, $name]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Write a per-user preference (created on first use). Mirrors
 * set_setting(): returns true on success, false on a logged DB error.
 */
function set_user_pref($pdo, $userId, $name, $value)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_pref (user_id, name, value) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$userId, $name, $value]);
        return true;
    } catch (PDOException $e) {
        error_log('[anime_tracker] set_user_pref(' . $userId . ',' . $name . '): '
            . $e->getMessage());
        return false;
    }
}
