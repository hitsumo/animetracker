<?php

/**
 * Anime Tracker - Series + Chronology Helpers
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
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
    // watch_status / watched_episodes are personal (user_anime, 1.0.1):
    // join the current user's rows. 1.0.10: watch_status comes RAW -
    // NULL means "not selected" and the label/css helpers render it as
    // such; watched_episodes still defaults to 0. The :uid param is
    // first because it appears first in the statement (JOIN ON clause).
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.media_type,
               ua.watch_status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               a.total_episodes, a.release_date, a.image_path
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.series_name = ? AND a.id != ?
        ORDER BY
            FIELD(a.media_type, 'TV', 'Film', 'OVA', 'Special', 'ONA'),
            a.release_date ASC,
            a.id ASC
    ");
    $stmt->execute([current_user_id(), $series_name, (int)$exclude_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return all chronology markers for a given anime, with full details
 * of the related anime (title, watch_status, etc.) via JOIN.
 *
 * $order selects the sort axis (1.1.15):
 *   'release' (default) - by after_episode (where the related anime aired).
 *   'story'             - by COALESCE(story_after_episode, after_episode),
 *                         i.e. the recommended-watch point, falling back to
 *                         the release point when a marker has no story point.
 * $order is an internal enum (never raw user input), so the ORDER BY clause
 * is chosen from fixed strings - no injection surface.
 */
function getChronologyMarkers($pdo, $anime_id, $order = 'release') {
    $orderBy = ($order === 'story')
        ? 'COALESCE(cm.story_after_episode, cm.after_episode) ASC, cm.after_episode ASC'
        : 'cm.after_episode ASC';
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.story_after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title,
               a.alternative_titles AS related_alternative_titles,
               ua.watch_status AS related_watch_status,
               a.media_type AS related_media_type
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE cm.anime_id = ?
        ORDER BY $orderBy
    ");
    $stmt->execute([current_user_id(), (int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================================
// Chronology display mode (1.1.15)
// =====================================================================
// The marker list (anime_details) and the timeline (chronology.php) can be
// shown in three modes:
//   'release' - order by the release point (after_episode).
//   'story'   - order by the story point (COALESCE(story_after_episode, ...)).
//   'both'    - render both lists, one under the other.
// The persistent default is a per-user preference set in list settings
// (key 'chrono_display_mode', default 'release'). A single cycle button
// stores an EPHEMERAL override in the session so it never overwrites the
// saved default. Precedence: session override > saved pref > 'release'.

/** Valid display modes, also the cycle order for chrono_next_mode(). */
function chrono_display_modes() {
    return ['release', 'story', 'both'];
}

/**
 * Resolve the active display mode for this request.
 * session override (cycle button) > saved user pref > 'release'.
 */
function chrono_current_mode($pdo) {
    if (isset($_SESSION['chrono_display_mode'])
        && in_array($_SESSION['chrono_display_mode'], chrono_display_modes(), true)) {
        return $_SESSION['chrono_display_mode'];
    }
    $pref = get_user_pref($pdo, current_user_id(), 'chrono_display_mode', 'release');
    return in_array($pref, chrono_display_modes(), true) ? $pref : 'release';
}

/** Next mode in the cycle release -> story -> both -> release. */
function chrono_next_mode($mode) {
    switch ($mode) {
        case 'release': return 'story';
        case 'story':   return 'both';
        default:        return 'release';
    }
}

/**
 * Localized label for a display mode (for the cycle button / settings).
 * Falls back to the raw mode key if a translation is missing.
 */
function chrono_mode_label($mode, $lang = null) {
    $key = 'chrono.mode.' . $mode;
    $label = t($key);
    return ($label === $key) ? $mode : $label;
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
               a.alternative_titles AS related_alternative_titles,
               ua.watch_status AS related_watch_status,
               a.media_type AS related_media_type, a.id AS related_id
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE cm.anime_id = ?
          AND cm.after_episode <= ?
          AND COALESCE(ua.watch_status, 'PlanToWatch') != 'Watched'
        ORDER BY cm.after_episode ASC
        LIMIT 1
    ");
    $stmt->execute([current_user_id(), (int)$anime_id, (int)$watched_episodes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Pick the display title for a related-anime row that uses the aliased
 * column names produced by the chronology queries above (related_title /
 * related_alternative_titles). Bridges those aliases to display_title() so
 * the Title Language preference applies to related anime the same way it
 * does to top-level rows. Falls back to the Romaji title when the row
 * carries no title in the chosen language. Output still needs
 * htmlspecialchars() at the call site.
 *
 * @param array $row  A row with 'related_title' and optionally 'related_alternative_titles'.
 * @return string
 */
function display_related_title($row) {
    return display_title([
        'title'              => $row['related_title'] ?? '',
        'alternative_titles' => $row['related_alternative_titles'] ?? null,
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

// =====================================================================
// Series timeline view modes (1.1.23)
// =====================================================================
// series_timeline.php iki sekmeyle acilir:
//   'chain'   - next_in_series baglantili listesinin yuruyusu (ilk gorunum).
//   'airdate' - ayni series_name'i tasiyan HER kayit, ilk gosterim tarihine
//               gore. Elle kurulan zincire hic bagimli degildir; katalogdan
//               ice aktarilmis (baglanmamis) anime de listede yerini alir.
// Secim 1.1.15'teki chrono-mode kalibini izler: oturumdaki gecici sekme
// secimi > kayitli kisisel varsayilan (user_pref 'series_timeline_mode',
// liste ayarlarindan) > geriye uyumlu 'chain'.

/** Valid series-timeline view modes. */
function series_timeline_modes() {
    return ['chain', 'airdate'];
}

/**
 * Resolve the active series-timeline mode for this request.
 * session override (page tabs) > saved user pref > 'chain'.
 */
function series_timeline_current_mode($pdo) {
    if (isset($_SESSION['series_timeline_mode'])
        && in_array($_SESSION['series_timeline_mode'], series_timeline_modes(), true)) {
        return $_SESSION['series_timeline_mode'];
    }
    $pref = get_user_pref($pdo, current_user_id(), 'series_timeline_mode', 'chain');
    return in_array($pref, series_timeline_modes(), true) ? $pref : 'chain';
}

/**
 * Return every anime sharing the given series_name, ordered by first
 * air/release date. NULL tarihler sona duser - tarihi girilmemis bir
 * kayit serinin baslangici gibi gorunmesin. Kolon kumesi zincir
 * yuruyusununkiyle ayni sekildedir (arti end_date/is_adult), boylece
 * series_timeline.php tek kart sablonuyla iki modu da cizer.
 */
function getSeriesAnimesByAirDate($pdo, $series_name) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.media_type, a.total_episodes, a.aired_episodes,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               ua.watch_status,
               a.status, a.image_path,
               a.release_date, a.release_date_precision,
               a.end_date, a.end_date_precision,
               a.series_name, a.is_adult
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.series_name = ?
        ORDER BY (a.release_date IS NULL) ASC, a.release_date ASC, a.id ASC
    ");
    $stmt->execute([current_user_id(), $series_name]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================================
// Series chain walking + multi-chain discovery (1.1.25)
// =====================================================================
// Bir seri adi altinda TEK bir zincir olmak zorunda degil: Koukaku
// Kidoutai'de filmler bir zincir, SAC dizileri bambaska bir zincirdir.
// series_timeline.php 1.1.24'e kadar yalnizca istenen animenin icinde
// bulundugu zinciri cizerdi - digerlerinin VARLIGI bile gorunmezdi.
// Asagidaki yardimcilar seri adi grubunu tarayip kac ayri zincir
// oldugunu bulur; sayfa da bunlari "Diger Zincir 1..N" sekmesi yapar.
//
// Zincir yuruyusu (geri/ileri) 1.1.25'te series_timeline.php'den buraya
// tasindi: hem sayfa hem de kesif ayni yuruyusu kullansin, iki ayri
// kopya birbirinden ayrisamasin diye. Yuruyus next_in_series'i seri
// adina bakmadan izler - 1.1.24 oncesi davranisla birebir aynidir.

/**
 * Walk backwards via next_in_series until nobody points at the current
 * anime; that anime is the chain's start. Visited-set guards a cycle.
 */
function seriesChainStartId($pdo, $anime_id) {
    $current = (int)$anime_id;
    $visited = [];
    while (true) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("SELECT id FROM animes WHERE next_in_series = ?");
        $stmt->execute([$current]);
        $prev = $stmt->fetchColumn();
        $stmt->closeCursor();
        if (!$prev) break;
        $current = (int)$prev;
    }
    return $current;
}

/**
 * Walk the chain forward from its start, returning only the ids. Used by
 * getSeriesChains() where the full display row would be wasted work.
 */
function seriesChainIds($pdo, $start_id) {
    $ids = [];
    $current = (int)$start_id;
    $visited = [];
    while ($current) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("SELECT id, next_in_series FROM animes WHERE id = ?");
        $stmt->execute([$current]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$row) break;
        $ids[] = (int)$row['id'];
        $current = $row['next_in_series'] ? (int)$row['next_in_series'] : null;
    }
    return $ids;
}

/**
 * Walk the chain forward from its start, collecting the full display rows
 * series_timeline.php draws. Column set matches getSeriesAnimesByAirDate()
 * so the page renders both views with one card template.
 */
function getSeriesChainRows($pdo, $start_id) {
    $chain = [];
    $current = (int)$start_id;
    $visited = [];
    while ($current) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.alternative_titles, a.media_type, a.total_episodes, a.aired_episodes,
                   COALESCE(ua.watched_episodes, 0) AS watched_episodes,
                   ua.watch_status,
                   a.status, a.image_path,
                   a.release_date, a.release_date_precision,
                   a.end_date, a.end_date_precision,
                   a.is_adult, a.next_in_series, a.series_name
            FROM animes a
            LEFT JOIN user_anime ua
                   ON ua.anime_id = a.id AND ua.user_id = ?
            WHERE a.id = ?
        ");
        $stmt->execute([current_user_id(), $current]);
        $anime = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$anime) break;
        $chain[] = $anime;
        $current = $anime['next_in_series'] ? (int)$anime['next_in_series'] : null;
    }
    return $chain;
}

/**
 * Discover every distinct next_in_series chain inside a series_name group.
 *
 * Grup uyeleri ilk gosterim tarihine gore taranir, her uye icin zincirin
 * basi bulunur ve zincir bir kez yuruyulur; ayni zincirin diger uyeleri
 * atlanir. Sonuc bu yuzden hep ayni sirada gelir: "Diger Zincir 1" en
 * eski tarihli zincirdir.
 *
 * $min_length varsayilan olarak 2'dir: hicbir yere baglanmamis TEK bir
 * kayit zincir sayilmaz, yoksa seri adini paylasan her bagimsiz film ayri
 * bir sekme uretirdi. O kayitlar zaten "Yayin Tarihi" sekmesinde durur.
 *
 * Donen her oge: ['start_id' => int, 'ids' => int[], 'count' => int].
 */
function getSeriesChains($pdo, $series_name, $min_length = 2) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT id
        FROM animes
        WHERE series_name = ?
        ORDER BY (release_date IS NULL) ASC, release_date ASC, id ASC
    ");
    $stmt->execute([$series_name]);
    $memberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();

    $seen = [];
    $chains = [];
    foreach ($memberIds as $memberId) {
        $memberId = (int)$memberId;
        if (isset($seen[$memberId])) {
            continue;
        }
        $startId = seriesChainStartId($pdo, $memberId);
        $ids = seriesChainIds($pdo, $startId);
        foreach ($ids as $cid) {
            $seen[$cid] = true;
        }
        if (count($ids) < $min_length) {
            continue;
        }
        $chains[] = [
            'start_id' => $startId,
            'ids'      => $ids,
            'count'    => count($ids),
        ];
    }
    return $chains;
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
