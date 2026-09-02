<?php

/**
 * Anime Tracker - Import Blacklist Helpers (kara liste)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.35.
 *
 * WHY THIS FILE EXISTS
 *
 * The catalog owner seeds the shared catalog from a public AniList list and
 * then PRUNES it by hand: entries with no AniDB counterpart are deleted from
 * animes. Two things went wrong with that workflow, and they are the same
 * bug seen from two sides:
 *
 *   1. Nothing recorded WHAT was deleted. The pruning decision lived only in
 *      the curator's head, and a deleted row leaves no trace behind.
 *   2. The very next import brought every pruned title straight back. An
 *      import bucket is "unmatched" precisely because the catalog has no such
 *      row - which is exactly the state a deletion produces. So deleting an
 *      anime made it a fresh candidate for re-adding.
 *
 * The blacklist closes both at once: deleting a catalog anime writes an entry
 * here (that IS the record of the deletion), and the import path refuses to
 * re-suggest anything the list names.
 *
 * ONLINE ONLY - AND THAT IS THE WHOLE POINT
 *
 * Every function here is a no-op unless MULTI_USER_MODE is on. The list is a
 * CURATION policy for the shared catalog: "this title does not belong in the
 * catalog I publish". A self-host install has no shared catalog and no
 * curator - its owner's deletions are personal, and its imports must keep
 * bringing whatever the owner's own list contains. Gating in one place (here)
 * rather than at each call site keeps that promise checkable: grep for
 * blacklist_active() and the self-host behaviour is proven unchanged.
 *
 * WHAT IT MATCHES ON
 *
 * mal_id and anidb_id - the same stable identities animes and catalog_requests
 * are keyed by. NOT the title: two unrelated shows share a title far too
 * often, and an import that silently dropped a legitimate anime because an
 * old deletion had the same name would be worse than the problem being
 * solved.
 *
 * A deletion whose row carried NEITHER id is still recorded (it is a log of
 * what the curator removed) but it can never block anything - there is no key
 * to match on. The admin page shows that plainly rather than pretending
 * otherwise. Manual additions therefore REQUIRE at least one id.
 *
 * FAILURE MODE: SOFT, NEVER FATAL
 *
 * Every query here is wrapped. A half-finished upgrade (files copied, the
 * migration not yet run) leaves the table missing, and the honest thing for
 * a missing table to mean is "the blacklist is empty" - reads return nothing
 * and writes are logged and dropped. The alternative was 1.1.31's failure
 * shape, where one absent column took a whole page down with a 503. Deleting
 * an anime must not fail because its bookkeeping table is not there yet.
 *
 * Loaded via the functions.php loader (helper-family convention).
 */

/**
 * Is the blacklist in force?
 *
 * Online (multi-user) only - see the file header. Both the writing side
 * (index.php's delete) and the reading side (the import gate) ask this, so
 * self-host behaviour is unchanged by construction.
 *
 * @return bool
 */
function blacklist_active()
{
    return defined('MULTI_USER_MODE') && MULTI_USER_MODE;
}

/**
 * The blacklisted identities, as two lookup sets.
 *
 * Returns ['mal' => [id => true, ...], 'anidb' => [id => true, ...]].
 *
 * Read ONCE per request and memoized: an import loop asks about every entry
 * in a list that can run to a few thousand rows, and the list itself cannot
 * change underneath a single request (the only writer is a delete handler,
 * which redirects). blacklist_add() drops the cache anyway, so a request that
 * does write still sees its own write.
 *
 * @param PDO  $pdo
 * @param bool $refresh Force a re-read (used internally after a write).
 * @return array{mal: array<int,bool>, anidb: array<int,bool>}
 */
function blacklist_ids($pdo, $refresh = false)
{
    static $cache = null;

    if ($refresh) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }

    $cache = ['mal' => [], 'anidb' => []];

    if (!blacklist_active()) {
        return $cache;
    }

    try {
        $rows = $pdo->query(
            "SELECT mal_id, anidb_id FROM import_blacklist"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            if (!empty($r['mal_id']))   { $cache['mal'][(int)$r['mal_id']]     = true; }
            if (!empty($r['anidb_id'])) { $cache['anidb'][(int)$r['anidb_id']] = true; }
        }
    } catch (PDOException $e) {
        // Missing table (migration not run yet) or an unreadable one. Treat
        // as "empty list" - see the file header. The import still works, it
        // just blocks nothing.
        error_log('[anime_tracker] blacklist read failed: ' . $e->getMessage());
    }

    return $cache;
}

/**
 * Would this identity be refused entry to the catalog?
 *
 * @param PDO      $pdo
 * @param int|null $malId
 * @param int|null $anidbId
 * @return bool True when either id is on the list.
 */
function blacklist_blocks($pdo, $malId, $anidbId = null)
{
    if (!blacklist_active()) {
        return false;
    }

    $set = blacklist_ids($pdo);

    if (!empty($malId) && isset($set['mal'][(int)$malId])) {
        return true;
    }
    if (!empty($anidbId) && isset($set['anidb'][(int)$anidbId])) {
        return true;
    }
    return false;
}

/**
 * Put an identity on the list.
 *
 * Idempotent: re-deleting an anime that was already recorded (added back by
 * hand, deleted again) updates nothing and reports success. The UNIQUE keys
 * on mal_id / anidb_id do that work, and MySQL lets a nullable UNIQUE column
 * hold any number of NULLs - which is what makes id-less deletion records
 * (see the file header) possible at all.
 *
 * @param PDO         $pdo
 * @param int|null    $malId
 * @param int|null    $anidbId
 * @param string      $title  Shown in the admin list; the only thing an
 *                            id-less record carries.
 * @param string      $reason 'deleted' (written by the delete handler) or
 *                            'manual' (typed on the admin page).
 * @param string|null $note   Free text, admin page only.
 * @return bool True when a row exists afterwards (inserted or already there).
 */
function blacklist_add($pdo, $malId, $anidbId, $title, $reason = 'manual', $note = null)
{
    if (!blacklist_active()) {
        return false;
    }

    $title = trim((string)$title);
    if ($title === '') {
        $title = '(isimsiz)';
    }
    if ($reason !== 'deleted') {
        $reason = 'manual';
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO import_blacklist
                 (mal_id, anidb_id, title, reason, note, created_by)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            !empty($malId)   ? (int)$malId   : null,
            !empty($anidbId) ? (int)$anidbId : null,
            mb_substr($title, 0, 255),
            $reason,
            ($note !== null && trim((string)$note) !== '') ? mb_substr(trim((string)$note), 0, 255) : null,
            current_user_id() ?: null,
        ]);
        blacklist_ids($pdo, true); // this request may go on to read the list
        return true;
    } catch (PDOException $e) {
        // Never let bookkeeping break the operation that triggered it - the
        // anime is deleted either way. See the file header.
        error_log('[anime_tracker] blacklist add failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Take rows off the list ("this may be imported again after all").
 *
 * @param PDO   $pdo
 * @param int[] $ids import_blacklist.id values
 * @return int Rows actually removed.
 */
function blacklist_remove($pdo, array $ids)
{
    if (!blacklist_active() || empty($ids)) {
        return 0;
    }

    $clean = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) { $clean[$id] = true; }
    }
    $clean = array_keys($clean);
    if (empty($clean)) {
        return 0;
    }

    try {
        $ph = implode(',', array_fill(0, count($clean), '?'));
        $stmt = $pdo->prepare("DELETE FROM import_blacklist WHERE id IN ($ph)");
        $stmt->execute($clean);
        blacklist_ids($pdo, true);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        error_log('[anime_tracker] blacklist remove failed: ' . $e->getMessage());
        return 0;
    }
}

/**
 * How many titles are on the list (for the dashboard card).
 *
 * @param PDO $pdo
 * @return int|null null when the count could not be read (missing table),
 *                  so the caller can render the card without a number
 *                  instead of printing a misleading 0 - the same shape
 *                  admin.php already uses for its other counts.
 */
function blacklist_count($pdo)
{
    if (!blacklist_active()) {
        return null;
    }
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM import_blacklist")->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] blacklist count failed: ' . $e->getMessage());
        return null;
    }
}
