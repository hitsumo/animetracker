<?php

/**
 * Anime Tracker - Public Catalog API
 * https://animetracker.sicakcikolata.com/catalog.php
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Read-only JSON endpoint that exposes the curated anime catalog.
 * Client applications (local installs of AnimeTracker) call this
 * endpoint to sync anime metadata without touching user's personal
 * watch progress.
 *
 * Returns:
 *   {
 *     "generated_at": "2026-04-12T11:15:00Z",
 *     "animes": [ ...rows where source='catalog'... ],
 *     "chronology": [ ...all chronology_markers rows... ]
 *   }
 *
 * Personal columns (watched_episodes, watch_status, notes,
 * next_episode_date, user_synopsis) are explicitly EXCLUDED from
 * the SELECT - they would be meaningless to other users and
 * including them would leak the admin's private watching data.
 *
 * image_filename is a derived field - it's the basename of the
 * admin's image_path, sent so that clients can fetch the poster
 * from /uploads/{filename}. The full image_path is never exposed
 * (it's a server-side filesystem detail).
 *
 * Cached aggressively: catalog_cache.json is written on first
 * request and served as-is for 1 hour. This is fine because the
 * catalog changes rarely (new animes added manually by the admin).
 *
 * 0.6.2 schema update (26 May 2026):
 *   - 'genres' text column removed from animes - now backed by
 *     anime_genres join table + master genres table. We rebuild
 *     the CSV wire format from that join, same pattern as tags.
 *   - 'end_date' DATE column added (last episode air date). Sent
 *     to clients as a regular field.
 */

// --- Config -------------------------------------------------------------

$configPath = __DIR__ . '/../private/anime_api_config.php';
if (!file_exists($configPath)) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Service configuration missing']);
    error_log('[catalog] config not found: ' . $configPath);
    exit;
}
require_once $configPath;

// DB_HOST may be "host:port" - split it
$dbHost = DB_HOST;
$dbPort = 3306;
if (strpos($dbHost, ':') !== false) {
    list($dbHost, $dbPort) = explode(':', $dbHost, 2);
    $dbPort = (int)$dbPort;
}

// --- Cache --------------------------------------------------------------

const CACHE_FILE    = __DIR__ . '/catalog_cache.json';
const CACHE_TTL_SEC = 3600; // 1 hour

if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL_SEC) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    readfile(CACHE_FILE);
    exit;
}

// --- Build fresh payload ------------------------------------------------

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Select catalog rows. Personal columns excluded. image_path replaced
    // with image_filename (basename only) so clients can fetch posters
    // from the public /uploads/ directory without knowing our filesystem
    // layout.
    //
    // 0.6.2: 'genres' column removed from this SELECT - it no longer
    // exists in animes. Built separately from anime_genres + genres
    // join below and attached as CSV per anime (wire format unchanged).
    // 'end_date' column added.
    $sql = "
        SELECT
            id,
            title,
            alternative_titles,
            status,
            total_episodes,
            aired_episodes,
            synopsis,
            release_date,
            end_date,
            anidb_link,
            mal_link,
            anime_schedule_link,
            episode_interval,
            broadcast_day,
            broadcast_time,
            broadcast_timezone,
            series_name,
            media_type,
            mal_id,
            anidb_id,
            catalog_uuid,
            CASE
                WHEN image_path IS NOT NULL AND image_path != ''
                THEN SUBSTRING_INDEX(image_path, '/', -1)
                ELSE NULL
            END AS image_filename
        FROM animes
        WHERE source = 'catalog'
        ORDER BY id
    ";
    $animes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // Chronology markers - one flat list. Client translates server IDs
    // to local IDs via the anime id mapping built during import.
    $chronologySql = "
        SELECT anime_id, after_episode, related_anime_id, note
        FROM chronology_markers
        ORDER BY anime_id, after_episode
    ";
    $chronology = $pdo->query($chronologySql)->fetchAll(PDO::FETCH_ASSOC);

    // Recommendation system sentences (tags). We send tag NAMES per
    // anime, not IDs - tag IDs are local to this database and would
    // collide with the client's own tag IDs. The client matches by
    // name (case-insensitive via utf8mb4_general_ci) when importing.
    $tagRows = $pdo->query("
        SELECT id, name FROM tags ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $tagById = [];
    foreach ($tagRows as $t) {
        $tagById[(int)$t['id']] = $t['name'];
    }

    $linkRows = $pdo->query("
        SELECT anime_id, tag_id FROM anime_tags
    ")->fetchAll(PDO::FETCH_ASSOC);

    $tagsByAnime = [];
    foreach ($linkRows as $row) {
        $aid = (int)$row['anime_id'];
        $tid = (int)$row['tag_id'];
        if (isset($tagById[$tid])) {
            $tagsByAnime[$aid][] = $tagById[$tid];
        }
    }

    // Attach the sentence list to each anime row. Animes with no tags
    // get an empty array, which the client interprets as "this anime
    // currently has no tags".
    foreach ($animes as &$a) {
        $aid = (int)$a['id'];
        $a['tags'] = $tagsByAnime[$aid] ?? [];
    }
    unset($a);

    // Genres (canonical taxonomy).
    //
    // 0.6.2 schema: genres live in anime_genres + master genres tables.
    // Build a single in-memory map of anime_id -> [name, name, ...]
    // by JOINing once, then attach each anime's genres as a
    // comma-separated string. CSV format is the wire format clients
    // still expect (catalog_import.php parses CSV). Mirror of the
    // admin_sync.php pre-payload logic.
    $genreLinkRows = $pdo->query("
        SELECT ag.anime_id, g.name
        FROM anime_genres ag
        INNER JOIN genres g ON g.id = ag.genre_id
        ORDER BY ag.anime_id, g.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    $genresByAnime = [];
    foreach ($genreLinkRows as $row) {
        $aid = (int)$row['anime_id'];
        $genresByAnime[$aid][] = $row['name'];
    }

    foreach ($animes as &$a) {
        $aid = (int)$a['id'];
        $names = $genresByAnime[$aid] ?? [];
        $a['genres'] = implode(',', $names);
    }
    unset($a);

    $payload = [
        'generated_at' => gmdate('c'),
        'animes'       => $animes,
        'chronology'   => $chronology,
        // Full sentence library (de-duplicated, sorted). Clients can
        // use this to populate their tags table even for sentences
        // that no anime currently uses.
        'tags'         => array_map(function($t) { return $t['name']; }, $tagRows),
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Atomic cache write - write to temp, then rename. Prevents partial
    // reads when a second request arrives during cache regeneration.
    $tmp = CACHE_FILE . '.tmp';
    file_put_contents($tmp, $json);
    rename($tmp, CACHE_FILE);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo $json;

} catch (Exception $e) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Service temporarily unavailable']);
    // Log the real detail for debugging but don't expose to client
    error_log('[catalog] ' . $e->getMessage());
}
