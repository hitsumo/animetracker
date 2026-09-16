<?php

/**
 * Anime Tracker - Shared Identity Helpers (paylasimli MAL / AniDB kimligi)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.41.
 *
 * WHY THIS FILE EXISTS
 *
 * The sources disagree about what "one anime" is. MAL keeps Death Note:
 * Rewrite as ONE record of two episodes (2994); AniDB keeps it as TWO
 * records of one episode each. The catalog wanted to follow AniDB (two
 * rows) while still pointing both at MAL 2994 - and animes.mal_id was
 * UNIQUE, so the second row was refused with a 1062 ("this MAL ID already
 * exists"). The mirror case (AniDB single, MAL split) exists too, so
 * anidb_id gets the same treatment.
 *
 * THE MODEL: A PART NUMBER, NOT A FLAG
 *
 *   animes.mal_part    tinyint  NOT NULL DEFAULT 1
 *   animes.anidb_part  tinyint  NOT NULL DEFAULT 1
 *   UNIQUE (mal_id, mal_part)     UNIQUE (anidb_id, anidb_part)
 *
 * Every row that existed before 1.1.41 is part 1 of its identity, so the
 * old world is a special case of the new one: one part per id, the
 * composite key behaves exactly like the old single-column key, nothing
 * changes for the 8,000 rows already there.
 *
 * Sharing is a DELIBERATE DECLARATION, made on the form with a checkbox
 * ("this MAL record corresponds to more than one anime"). With the box
 * unticked a second row with the same id is still refused - an accidental
 * duplicate is still an accident. With the box ticked the row takes the
 * next free part number for that id (MAX(part) + 1); the curator never
 * types a number. The checkbox itself is not stored: whether a row is
 * "shared" is DERIVED from the data (other rows carry the same id), so
 * the form can never disagree with the table.
 *
 * Why a real column and not "checkbox + hidden counter": everything that
 * addresses a row by identity (imports, catalog sync, JSON backup, the
 * blacklist, synopsis links) must be able to say WHICH part it means. A
 * flag says "there are several"; it cannot say "the second one".
 *
 * WHO USES WHAT
 *
 *   add_anime / edit_anime      identity_resolve_parts()      part on save
 *   anime_details               identity_siblings()           "same MAL record" list,
 *                               identity_part_count()         "2/2" badge
 *   index.php (delete)          identity_part_count()         blacklist only when
 *                                                             the last part goes
 *   list_settings (MAL/AniList) identity_shared_ua_payload()  the import rule (§4
 *                                                             of the plan)
 *   synopsis_helpers            [[anime:2994/2]] grammar
 *   anime_link_search           identity_shortcode_ref(), identity_part_badge()
 *
 * The catalog wire, the JSON backup and the push receiver carry the two
 * columns as plain fields (mal_part / anidb_part); a payload that lacks
 * them (an older server, an older backup) reads as part 1, which is what
 * every pre-1.1.41 row IS.
 *
 * Loaded via the functions.php loader (helper-family convention).
 */

/**
 * Column pair for an identity kind.
 *
 * @param string $kind 'mal' | 'anidb'
 * @return array{0:string,1:string} [id column, part column]
 */
function identity_columns($kind)
{
    return ($kind === 'anidb')
        ? ['anidb_id', 'anidb_part']
        : ['mal_id', 'mal_part'];
}

/**
 * How many rows carry this identity (all parts, including the caller's).
 * 0 when the id is empty.
 *
 * @param PDO      $pdo
 * @param string   $kind     'mal' | 'anidb'
 * @param int|null $sourceId
 * @return int
 */
function identity_part_count($pdo, $kind, $sourceId)
{
    if (empty($sourceId)) {
        return 0;
    }
    list($idCol) = identity_columns($kind);
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM animes WHERE `$idCol` = ?");
        $stmt->execute([(int)$sourceId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] identity_part_count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Does any OTHER row (not $excludeAnimeId) carry this identity?
 * This is what "shared" means on the form and in the delete handler.
 *
 * @param PDO      $pdo
 * @param string   $kind
 * @param int|null $sourceId
 * @param int|null $excludeAnimeId
 * @return bool
 */
function identity_other_parts_exist($pdo, $kind, $sourceId, $excludeAnimeId = null)
{
    if (empty($sourceId)) {
        return false;
    }
    list($idCol) = identity_columns($kind);
    try {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM animes WHERE `$idCol` = ? AND id <> ? LIMIT 1"
        );
        $stmt->execute([(int)$sourceId, (int)($excludeAnimeId ?? 0)]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] identity_other_parts_exist: ' . $e->getMessage());
        return false;
    }
}

/**
 * The next free part number for an identity: MAX(part) + 1 over the OTHER
 * rows, or 1 when nobody else carries the id. Part numbers are never
 * reused downwards on purpose - a backup or a synopsis link written as
 * "2994/3" must keep meaning the same record after a sibling is deleted.
 *
 * @param PDO      $pdo
 * @param string   $kind
 * @param int      $sourceId
 * @param int|null $excludeAnimeId
 * @return int
 */
function identity_next_part($pdo, $kind, $sourceId, $excludeAnimeId = null)
{
    list($idCol, $partCol) = identity_columns($kind);
    try {
        $stmt = $pdo->prepare(
            "SELECT MAX(`$partCol`) FROM animes WHERE `$idCol` = ? AND id <> ?"
        );
        $stmt->execute([(int)$sourceId, (int)($excludeAnimeId ?? 0)]);
        $max = (int)$stmt->fetchColumn();
        return max(1, $max + 1);
    } catch (PDOException $e) {
        error_log('[anime_tracker] identity_next_part: ' . $e->getMessage());
        return 1;
    }
}

/**
 * Decide the part numbers a row is about to be saved with. Shared by the
 * add and edit forms so the two cannot drift.
 *
 * Rules (identical for MAL and AniDB, evaluated per identity):
 *
 *   id empty                 -> part 1 (nothing to share; the box is ignored)
 *   box TICKED
 *     id unchanged on edit   -> keep the current part
 *     new id / new row       -> next free part for that id (MAX + 1)
 *   box NOT ticked
 *     other rows carry id    -> refused: the caller shows the error. The
 *                               row cannot "go back to being the only
 *                               one" while siblings exist - part 1 is
 *                               either this row already (then the tick is
 *                               just the truth) or taken by a sibling.
 *                               Either way the curator must separate the
 *                               other parts first.
 *     nobody else has it     -> part 1. This is also the self-heal: a
 *                               lone survivor that was part 2 (its
 *                               sibling deleted) settles back to 1 the
 *                               next time it is saved unticked.
 *
 * On a NEW row with the box unticked and the id already taken the part
 * is 1 and the INSERT hits the composite UNIQUE exactly as it hit the old
 * single-column one - the forms' existing 1062 page handles that (with a
 * hint pointing at the checkbox, 1.1.41). That is deliberate: the "this
 * already exists, here is the record" page is the right answer to an
 * accidental duplicate, and the add form has no "current row" to protect.
 *
 * @param PDO        $pdo
 * @param int|null   $malId
 * @param int|null   $anidbId
 * @param bool       $malShared    checkbox state
 * @param bool       $anidbShared  checkbox state
 * @param array|null $current      the row being edited (id, mal_id, mal_part,
 *                                 anidb_id, anidb_part) or null on add
 * @return array{mal_part:int, anidb_part:int, errors:string[]}
 *         errors holds 'mal' and/or 'anidb' when the unshare was refused.
 */
function identity_resolve_parts($pdo, $malId, $anidbId, $malShared, $anidbShared, $current = null)
{
    $out = ['mal_part' => 1, 'anidb_part' => 1, 'errors' => []];
    $selfId = $current ? (int)$current['id'] : null;

    foreach (['mal' => [$malId, $malShared], 'anidb' => [$anidbId, $anidbShared]] as $kind => $pair) {
        list($sourceId, $shared) = $pair;
        list($idCol, $partCol)   = identity_columns($kind);

        if (empty($sourceId)) {
            $out[$partCol] = 1;
            continue;
        }

        $others    = identity_other_parts_exist($pdo, $kind, $sourceId, $selfId);
        $unchanged = $current
            && (int)($current[$idCol] ?? 0) === (int)$sourceId
            && (int)($current[$partCol] ?? 0) > 0;

        if ($shared) {
            $out[$partCol] = $unchanged
                ? (int)$current[$partCol]
                : identity_next_part($pdo, $kind, $sourceId, $selfId);
            continue;
        }

        if ($others && $current) {
            $out['errors'][] = $kind;
            $out[$partCol]   = $unchanged ? (int)$current[$partCol] : 1;
            continue;
        }

        $out[$partCol] = 1;
    }

    return $out;
}

/**
 * The rows that share an identity with $anime (either kind), for the
 * detail page's "same source record" section. Each row says which kind(s)
 * it shares via 'shares' => ['mal' => bool, 'anidb' => bool] and carries
 * the personal watch status like every other detail-page list.
 *
 * Derived purely from the data - no relation row is needed for two parts
 * to know about each other. (The curator MAY still add a typed relation
 * between them; that is a separate statement about the story, not about
 * the source record.)
 *
 * @param PDO   $pdo
 * @param array $anime  the detail-page row (id, mal_id, anidb_id, ...)
 * @return array
 */
function identity_siblings($pdo, array $anime)
{
    $mal   = !empty($anime['mal_id'])   ? (int)$anime['mal_id']   : 0;
    $anidb = !empty($anime['anidb_id']) ? (int)$anime['anidb_id'] : 0;
    if ($mal === 0 && $anidb === 0) {
        return [];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.alternative_titles, a.media_type,
                   a.total_episodes, a.release_date, a.is_adult,
                   a.mal_id, a.mal_part, a.anidb_id, a.anidb_part,
                   ua.watch_status,
                   COALESCE(ua.watched_episodes, 0) AS watched_episodes
              FROM animes a
         LEFT JOIN user_anime ua
                ON ua.anime_id = a.id AND ua.user_id = :uid
             WHERE a.id <> :self
               AND ( (:mal1 > 0 AND a.mal_id = :mal2)
                  OR (:anidb1 > 0 AND a.anidb_id = :anidb2) )
          ORDER BY a.mal_part ASC, a.anidb_part ASC, a.id ASC
        ");
        $stmt->execute([
            ':uid'    => current_user_id(),
            ':self'   => (int)$anime['id'],
            ':mal1'   => $mal,
            ':mal2'   => $mal,
            ':anidb1' => $anidb,
            ':anidb2' => $anidb,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[anime_tracker] identity_siblings: ' . $e->getMessage());
        return [];
    }

    foreach ($rows as &$row) {
        $row['shares'] = [
            'mal'   => $mal   > 0 && (int)$row['mal_id']   === $mal,
            'anidb' => $anidb > 0 && (int)$row['anidb_id'] === $anidb,
        ];
        if (function_exists('adult_mask_related')) {
            $row = adult_mask_related($row, 'is_adult', 'title', 'alternative_titles');
        }
    }
    unset($row);

    return $rows;
}

/**
 * Every row carrying an identity, first part first: [{id, mal_part,
 * anidb_part, total_episodes}, ...]. Empty array for an empty id or an
 * unknown one. The MAL / AniList importers walk this list: a list line
 * names ONE source id, and locally that may be several parts.
 *
 * @param PDO      $pdo
 * @param string   $kind     'mal' | 'anidb'
 * @param int|null $sourceId
 * @return array
 */
function identity_parts($pdo, $kind, $sourceId)
{
    if (empty($sourceId)) {
        return [];
    }
    list($idCol, $partCol) = identity_columns($kind);
    try {
        $stmt = $pdo->prepare(
            "SELECT id, mal_part, anidb_part, total_episodes
               FROM animes WHERE `$idCol` = ? ORDER BY `$partCol` ASC, id ASC"
        );
        $stmt->execute([(int)$sourceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('[anime_tracker] identity_parts: ' . $e->getMessage());
        return [];
    }
}

/**
 * The import rule for a shared identity ("Kural 2 + istisna", decided
 * 12 Sep 2026). A MAL / AniList list carries ONE line per source id
 * (status + watched count); locally that id may be N parts. The count
 * cannot be split between parts (2/3 -> one to A and one to B? or two to
 * A?), so no invented number is ever written:
 *
 *   1. The status goes to every part.
 *   2. The watched count is left alone ...
 *   3. ... except: a 0/N line writes 0 to every part (someone who has
 *      not started has not started any part), and a 'Watched' line
 *      writes each part's OWN total (someone who finished the whole has
 *      finished every part) - when that total is known. Unknown total:
 *      status only.
 *   4. The caller counts these entries and tells the user to check the
 *      episode numbers by hand (list_settings result line).
 *
 * The rejected alternative ("count goes to part 1 only") produced
 * "Watching 1/1" for a 1/2 line - a contradiction the user would have
 * had to notice and undo.
 *
 * @param array $payload  what mal_ua_payload() / anilist_ua_payload() built
 * @param array $part     the part's animes row (needs total_episodes)
 * @return array  the payload to hand to ua_set_state() for this part
 */
function identity_shared_ua_payload(array $payload, array $part)
{
    $watched = (int)($payload['watched_episodes'] ?? 0);
    $status  = $payload['watch_status'] ?? null;
    $total   = !empty($part['total_episodes']) ? (int)$part['total_episodes'] : 0;

    if ($watched === 0) {
        $payload['watched_episodes'] = 0;
    } elseif ($status === 'Watched' && $total > 0) {
        $payload['watched_episodes'] = $total;
    } else {
        unset($payload['watched_episodes']);
    }

    return $payload;
}

/**
 * "2/2"-style badge text for a part, or '' when the identity is not shared
 * (one part) so the callers can print nothing in the common case.
 *
 * @param int $part
 * @param int $count
 * @return string
 */
function identity_part_badge($part, $count)
{
    $count = (int)$count;
    if ($count <= 1) {
        return '';
    }
    return (int)$part . '/' . $count;
}

/**
 * The synopsis shortcode reference for a row: "2994" for the only part,
 * "2994/2" when the MAL id is shared. Used by anime_link_search.php so the
 * picker writes a code that resolves to THIS row and not to part 1.
 *
 * @param int $malId
 * @param int $part
 * @param int $count  parts carrying the id
 * @return string
 */
function identity_shortcode_ref($malId, $part, $count)
{
    $ref = (string)(int)$malId;
    if ((int)$count > 1) {
        $ref .= '/' . (int)$part;
    }
    return $ref;
}
