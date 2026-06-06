<?php

/**
 * Anime Tracker - Series + Chronology Helpers
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

// =====================================================================
// Series relationship helpers (v0.5 mid-cycle addition)
// =====================================================================

/**
 * Return all animes that share the same series_name, excluding the
 * given anime itself. Results are grouped by media_type (TV first,
 * then Film, then OVA/Special/ONA) and within each group sorted by
 * release_date ascending.
 *
 * Returns an empty array if $series_name is empty/null or no related
 * animes exist.
 */
function getRelatedAnimes($pdo, $series_name, $exclude_id) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT id, title, title_english, media_type, watch_status,
               watched_episodes, total_episodes, release_date, image_path
        FROM animes
        WHERE series_name = ? AND id != ?
        ORDER BY
            FIELD(media_type, 'TV', 'Film', 'OVA', 'Special', 'ONA'),
            release_date ASC,
            id ASC
    ");
    $stmt->execute([$series_name, (int)$exclude_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return all chronology markers for a given anime, with full details
 * of the related anime (title, watch_status, etc.) via JOIN.
 *
 * Results are sorted by after_episode ascending so the UI can display
 * them in episode order.
 */
function getChronologyMarkers($pdo, $anime_id) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title,
               a.title_english AS related_title_english,
               a.watch_status AS related_watch_status,
               a.media_type AS related_media_type
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
        ORDER BY cm.after_episode ASC
    ");
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check whether the user's current watch progress triggers a
 * chronology marker alert. Returns the marker row (with related
 * anime details) if the NEXT episode to watch (watched + 1) matches
 * a marker's after_episode. Returns null if no alert is needed.
 *
 * Example: anime has a marker with after_episode=23. If the user has
 * watched 23 episodes, the next one would be 24, but the marker says
 * "watch the related anime first". So we compare watched_episodes
 * against after_episode: if watched >= after_episode AND the related
 * anime is not yet watched, show the alert.
 *
 * We only alert for markers where the related anime's watch_status
 * is NOT 'Izlendi' — if the user already watched the film, no need
 * to remind them.
 */
function getActiveChronologyAlert($pdo, $anime_id, $watched_episodes) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title,
               a.title_english AS related_title_english,
               a.watch_status AS related_watch_status,
               a.media_type AS related_media_type, a.id AS related_id
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
          AND cm.after_episode <= ?
          AND a.watch_status != 'Watched'
        ORDER BY cm.after_episode ASC
        LIMIT 1
    ");
    $stmt->execute([(int)$anime_id, (int)$watched_episodes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Pick the display title for a related-anime row that uses the aliased
 * column names produced by the chronology queries above (related_title /
 * related_title_english). Bridges those aliases to display_title() so the
 * English-title preference (0.7.2) applies to related anime the same way
 * it does to top-level rows. Falls back to the Romaji title when the
 * preference is off or title_english is empty. Output still needs
 * htmlspecialchars() at the call site.
 *
 * @param array $row  A row with 'related_title' and optionally 'related_title_english'.
 * @return string
 */
function display_related_title($row) {
    return display_title([
        'title'         => $row['related_title'] ?? '',
        'title_english' => $row['related_title_english'] ?? null,
    ]);
}

/**
 * Return all distinct series_name values from the animes table,
 * sorted alphabetically. Used to populate the datalist/auto-complete
 * in the add/edit forms so the user does not have to type series
 * names from memory (and risk typos).
 */
function getAllSeriesNames($pdo) {
    $stmt = $pdo->query("
        SELECT DISTINCT series_name
        FROM animes
        WHERE series_name IS NOT NULL AND series_name != ''
        ORDER BY series_name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Validate that setting next_in_series does not create a direct
 * circular reference (A -> B -> A). Does NOT check transitive
 * cycles (A -> B -> C -> A) — that would require a recursive
 * walk and is overkill for a single-user app.
 *
 * Returns true if the link is safe, false if it would create a
 * direct loop.
 */
function validateNextInSeries($pdo, $anime_id, $target_id) {
    if (empty($target_id) || $target_id == $anime_id) {
        // Pointing to yourself is always invalid
        return $target_id != $anime_id;
    }
    // Check if the target already points back to us
    $stmt = $pdo->prepare("SELECT next_in_series FROM animes WHERE id = ?");
    $stmt->execute([(int)$target_id]);
    $targetNext = $stmt->fetchColumn();
    if ($targetNext !== false && (int)$targetNext === (int)$anime_id) {
        return false; // direct circular: A -> B -> A
    }
    return true;
}
