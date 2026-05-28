<?php

/**
 * Anime Tracker - Taxonomy Helpers (genres + tags)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

/**
 * Genre helpers (canonical taxonomy).
 *
 * Genres are the canonical classification of an anime (Action, Drama,
 * Comedy, ...) sourced from MAL/AniDB and shared with the catalog.
 * They live in the `genres` table and are linked to animes through
 * the `anime_genres` join table. This is the parallel of the tag
 * helpers below; the two systems never mix.
 *
 * Before the v0.5 in-place patch (genres_relational_upgrade.sql),
 * genres were stored as a comma-separated TEXT column on animes.
 * That column has been dropped; all reads and writes go through
 * the helpers in this section.
 */

/**
 * Return every genre in alphabetical order.
 * Used by the dropdown in add_anime / edit_anime and by the filter
 * dropdown in index.php.
 */
function getAllGenres($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return the genres currently attached to a single anime.
 * Each row has both `id` and `name` so callers can render badges and
 * still know the IDs to keep the form's selected state in sync.
 */
function getAnimeGenres($pdo, $anime_id) {
    $stmt = $pdo->prepare(
        "SELECT g.id, g.name
         FROM genres g
         INNER JOIN anime_genres ag ON ag.genre_id = g.id
         WHERE ag.anime_id = ?
         ORDER BY g.name ASC"
    );
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Replace the genre set attached to an anime with the given list of
 * genre IDs.
 *
 * Implementation notes (mirrors setAnimeTags):
 * - Wrapped in a transaction so a half-applied update can never leave
 *   the link table in a partial state.
 * - DELETE + INSERT is simpler than computing the diff and produces
 *   the same end state. The link table is small (a handful of rows
 *   per anime) so the cost is negligible.
 * - Duplicate IDs in the input are silently ignored (the PRIMARY KEY
 *   on the link table would otherwise raise 23000).
 * - Zero/negative IDs are dropped so a stray empty value from the form
 *   does not become a bogus row.
 */
function setAnimeGenres($pdo, $anime_id, $genre_ids) {
    $anime_id = (int)$anime_id;

    // Normalize: cast to int, drop zero/negative, deduplicate
    $clean = [];
    foreach ((array)$genre_ids as $gid) {
        $gid = (int)$gid;
        if ($gid > 0) {
            $clean[$gid] = true;
        }
    }
    $clean = array_keys($clean);

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM anime_genres WHERE anime_id = ?");
        $del->execute([$anime_id]);

        if (!empty($clean)) {
            $ins = $pdo->prepare(
                "INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?)"
            );
            foreach ($clean as $gid) {
                $ins->execute([$anime_id, $gid]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Look up a genre by name (case-insensitive). If it does not exist,
 * create it. Returns the genre ID either way.
 *
 * This is what powers two flows:
 *   1. add_anime / edit_anime form submissions, where the user picks
 *      from the dropdown by name (the form posts the names, the
 *      handler resolves them to IDs here).
 *   2. catalog_import sync, where the catalog payload may contain a
 *      genre name that does not exist in the local master list yet
 *      (e.g. a typo correction or a newly-introduced genre on the
 *      server). Auto-creating keeps sync robust; the new genre then
 *      shows up in manage_genres.php like any other.
 *
 * Whitespace is trimmed and the name is capped at 50 characters to
 * match the schema. Empty names return 0 so the caller can skip them.
 */
function findOrCreateGenre($pdo, $name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 0;
    }
    if (mb_strlen($name) > 50) {
        $name = mb_substr($name, 0, 50);
    }

    // Case-insensitive lookup using the default utf8mb4_general_ci
    // collation - "Aksiyon" and "aksiyon" resolve to the same row.
    $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    // Race-safe insert: if another request created the same genre a
    // moment ago, the UNIQUE constraint on genres.name will fire and
    // we re-query to get the ID created by the other request.
    try {
        $ins = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
        $ins->execute([$name]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int)$id;
            }
        }
        throw $e;
    }
}

/**
 * Convenience wrapper: take a list of genre names (e.g. from a form
 * post or a catalog payload) and replace the anime's genres with the
 * resolved set. Names that cannot be resolved or created are skipped.
 *
 * Used by:
 *   - add_anime / edit_anime: form posts the comma-separated names
 *     from the hidden input, handler explodes and calls this.
 *   - catalog_import: payload contains the CSV genres string, handler
 *     explodes and calls this.
 */
function setAnimeGenresByNames($pdo, $anime_id, $names) {
    $ids = [];
    foreach ((array)$names as $name) {
        $gid = findOrCreateGenre($pdo, $name);
        if ($gid > 0) {
            $ids[] = $gid;
        }
    }
    setAnimeGenres($pdo, $anime_id, $ids);
}

/**
 * Serialize an anime's genres as a comma-separated name string.
 * Used by admin_sync*.php to emit the legacy CSV format the catalog
 * server still expects. Once the server side moves to a JSON array
 * format this helper can be removed.
 */
function getAnimeGenresAsCsv($pdo, $anime_id) {
    $rows = getAnimeGenres($pdo, $anime_id);
    $names = array_map(function ($r) { return $r['name']; }, $rows);
    return implode(',', $names);
}


/**
 * Tag helpers (recommendation system).
 *
 * Tags are descriptive labels (e.g. "Okul", "Spor", "Buyu") used by
 * the recommendation system as buckets. They live in their own table
 * (`tags`) and are linked to animes via `anime_tags`. This is a parallel
 * classification system to `genres` and the two never mix.
 */

/**
 * Return every tag in alphabetical order.
 * Used by the auto-complete dropdown in add_anime / edit_anime and by
 * the sentence list in recommendations.php.
 */
function getAllTags($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM tags ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return the tags currently attached to a single anime.
 * Each row has both `id` and `name` so callers can render badges and
 * still know the IDs to keep the form's selected state in sync.
 */
function getAnimeTags($pdo, $anime_id) {
    $stmt = $pdo->prepare(
        "SELECT t.id, t.name
         FROM tags t
         INNER JOIN anime_tags at ON at.tag_id = t.id
         WHERE at.anime_id = ?
         ORDER BY t.name ASC"
    );
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Replace the tag set attached to an anime with the given list of tag IDs.
 *
 * Implementation notes:
 * - Wrapped in a transaction so a half-applied update can never leave
 *   the link table in a partial state.
 * - DELETE + INSERT is simpler than computing the diff and produces
 *   the same end state. The link table is small (a few rows per anime)
 *   so the cost is negligible.
 * - Duplicate IDs in the input are silently ignored (the PRIMARY KEY
 *   on the link table would otherwise raise 23000).
 */
function setAnimeTags($pdo, $anime_id, $tag_ids) {
    $anime_id = (int)$anime_id;
    // Normalize: cast to int, drop zero/negative, deduplicate
    $clean = [];
    foreach ((array)$tag_ids as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $clean[$tid] = true;
        }
    }
    $clean = array_keys($clean);

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM anime_tags WHERE anime_id = ?");
        $del->execute([$anime_id]);

        if (!empty($clean)) {
            $ins = $pdo->prepare(
                "INSERT INTO anime_tags (anime_id, tag_id) VALUES (?, ?)"
            );
            foreach ($clean as $tid) {
                $ins->execute([$anime_id, $tid]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Look up a tag by name (case-insensitive). If it does not exist,
 * create it. Returns the tag ID either way.
 *
 * This is what powers the "create on the fly" behaviour of the
 * add_anime / edit_anime tag input: when the user types a new tag and
 * confirms it, the form posts the raw name and the server resolves it
 * to an ID here.
 *
 * Whitespace is trimmed and the name is capped at 50 characters to
 * match the schema. Empty names return 0 so the caller can skip them.
 */
function findOrCreateTag($pdo, $name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 0;
    }
    if (mb_strlen($name) > 150) {
        $name = mb_substr($name, 0, 150);
    }

    // Case-insensitive lookup using the default utf8mb4_general_ci
    // collation - "Okul" and "okul" resolve to the same row.
    $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    // Race-safe insert: if another request created the same tag a
    // moment ago, the UNIQUE constraint on tags.name will fire and we
    // re-query to get the ID created by the other request.
    try {
        $ins = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $ins->execute([$name]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int)$id;
            }
        }
        throw $e;
    }
}
