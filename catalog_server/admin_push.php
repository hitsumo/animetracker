<?php

/**
 * Anime Tracker - Admin Push Endpoint (Server Side)
 * https://animetracker.sicakcikolata.com/admin_push.php
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Receives catalog updates from the admin's local installation and
 * applies them to the server database. This is a one-way push: admin
 * local -> server. Other users never use this endpoint; they only
 * consume catalog.php (read-only).
 *
 * Security model:
 *   - Shared secret stored in ../private/admin_push_config.php
 *     (outside web root, never in git)
 *   - Every request carries an HMAC-SHA256 signature over:
 *       timestamp + body
 *   - Server verifies HMAC with the shared secret, constant-time
 *     comparison (hash_equals) to avoid timing attacks.
 *   - Timestamp must be within +/- 300 seconds of server time to
 *     prevent replay attacks.
 *   - Rate limit: one request per 5 seconds per IP (file-based).
 *   - HTTPS is assumed (enforced elsewhere via htaccess).
 *
 * Request format (POST, application/json):
 *   {
 *     "timestamp": 1744123456,
 *     "animes": [ {...}, {...}, ... ],
 *     "chronology": [ {...}, ... ]
 *   }
 *
 * Required header:
 *   X-Admin-Signature: <hex HMAC-SHA256 of timestamp + "|" + raw body>
 *
 * Response format (JSON):
 *   success: { status: "ok", inserted: N, updated: N, markers: N,
 *              tags: N, genres: N }
 *   error:   { status: "error", message: "..." }
 *
 * 0.6.2 update (26 May 2026):
 *   Old schema had a text column animes.genres (CSV string). New schema
 *   (since 0.5.4 / canonicalized 26 May 2026) uses an anime_genres join
 *   table with FK to a master genres table. This endpoint now resolves
 *   the incoming CSV from $a['genres'] into rows in genres + anime_genres,
 *   mirroring the tag pattern. The wire format from admin_sync.php is
 *   still CSV - kept stable for backwards compatibility.
 */

header('Content-Type: application/json; charset=utf-8');

// --- Load config -------------------------------------------------------

$configPath = __DIR__ . '/../private/admin_push_config.php';
if (!file_exists($configPath)) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Not configured']);
    error_log('[admin_push] config missing: ' . $configPath);
    exit;
}
require_once $configPath;

// Validate config
if (!defined('ADMIN_SECRET') || strlen(ADMIN_SECRET) < 32) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Misconfigured secret']);
    error_log('[admin_push] ADMIN_SECRET missing or too short');
    exit;
}
if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER') || !defined('DB_PASS')) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'DB config missing']);
    exit;
}

// --- Rate limit ---------------------------------------------------------
// The old "1 request per 5s per IP" file lock was REMOVED: batched push sends
// a large catalog as several signed requests back-to-back, legitimately firing
// multiple requests per second from the same admin IP, and the lock rejected
// the 2nd batch onward with HTTP 429. The endpoint is already gated by the
// shared HMAC secret + a 300s timestamp window; an unauthenticated flood is
// rejected cheaply at the signature check below (no DB work), so the per-IP
// throttle added little and now breaks legitimate batched use.
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// --- Method check ------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

// --- Read and verify HMAC ----------------------------------------------

$rawBody = file_get_contents('php://input');
if ($rawBody === false || $rawBody === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty body']);
    exit;
}

// Body size limit. Raised 5 MB -> 32 MB as the shared catalog grew past a few
// thousand animes. NOTE: this only helps if the server's PHP post_max_size (and
// any web-server body limit, e.g. nginx client_max_body_size) is at least as
// large; otherwise the body is truncated before this script runs and the JSON
// decode below fails with a 400 instead.
if (strlen($rawBody) > 32 * 1024 * 1024) {
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'Payload too large']);
    exit;
}

$sigHeader = $_SERVER['HTTP_X_ADMIN_SIGNATURE'] ?? '';
if (empty($sigHeader)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Missing signature']);
    exit;
}

$payload = json_decode($rawBody, true);
if (!is_array($payload) || !isset($payload['timestamp'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$timestamp = (int)$payload['timestamp'];
$now = time();
if (abs($now - $timestamp) > 300) {
    // 5 minute window - prevents replay of old captured requests
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Timestamp out of range']);
    exit;
}

// Recompute HMAC and compare in constant time
$expectedSig = hash_hmac('sha256', $timestamp . '|' . $rawBody, ADMIN_SECRET);
if (!hash_equals($expectedSig, $sigHeader)) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Bad signature']);
    error_log('[admin_push] HMAC mismatch from ' . $ip);
    exit;
}

// --- Validate payload structure ----------------------------------------

if (!isset($payload['animes']) || !is_array($payload['animes'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing animes array']);
    exit;
}

$animes = $payload['animes'];
$chronology = $payload['chronology'] ?? [];
// Batched-push support (backward compatible - an old single-shot push sends
// neither field, so behavior is unchanged):
//   id_map          - [{id, mal_id, anidb_id, catalog_uuid}, ...]. Lets a final
//                     chronology-only batch (animes: []) translate marker local
//                     IDs to server IDs even though the referenced animes were
//                     upserted in EARLIER batches and are absent from this body.
//   skip_chronology - true on the anime-only batches so they do NOT run the
//                     authoritative "wipe all catalog markers and reload" - that
//                     runs exactly once, in the final chronology batch.
$idMap = $payload['id_map'] ?? [];
$skipChronology = !empty($payload['skip_chronology']);

if (count($animes) > 50000) {
    // Sanity limit - way more than expected. Raised from 5000 as the shared
    // catalog grew past it (AniList seeding). Still a DoS/sanity bound. The real
    // scaling fix is to send the catalog in batches: this receiver is upsert-only
    // and touches ONLY animes present in the payload (no demote-on-absence), so
    // splitting a large catalog across several signed POSTs is safe.
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'Too many animes']);
    exit;
}

// --- Connect to DB -----------------------------------------------------

$dbHost = DB_HOST;
$dbPort = 3306;
if (strpos($dbHost, ':') !== false) {
    list($dbHost, $dbPort) = explode(':', $dbHost, 2);
    $dbPort = (int)$dbPort;
}

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';port=' . $dbPort . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES     => false,
            // Tampon sorgular - MySQL driver'in cursor yonetimindeki
            // "unbuffered queries are active" hatasini onler. Birden
            // fazla hazir sorguyu ayni baglanti uzerinden art arda
            // calistirdigimiz icin bu zorunlu.
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'DB connection failed']);
    error_log('[admin_push] DB connection: ' . $e->getMessage());
    exit;
}

// --- Apply changes in a transaction ------------------------------------

// Stats:
//   inserted  - new animes added to server
//   updated   - existing animes whose metadata changed
//   markers   - chronology markers inserted
//   tags      - anime_tags link rows inserted (recommendation sentences)
//   genres    - anime_genres link rows inserted (canonical taxonomy)
$stats = [
    'inserted' => 0,
    'updated'  => 0,
    'markers'  => 0,
    'tags'     => 0,
    'genres'   => 0,
];

// Map: local admin ID -> server ID (needed to translate chronology markers)
$adminIdToServerId = [];

$pdo->beginTransaction();

try {
    // Matching is by identity fields only - mal_id, anidb_id, catalog_uuid.
    // The admin's local ID (which comes in the payload as 'id') has no meaning
    // on the server; server has its own auto-incrementing IDs. We use it only
    // as a lookup key to translate chronology references.
    $findByMal = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
    $findByAnidb = $pdo->prepare("SELECT id FROM animes WHERE anidb_id = ? LIMIT 1");
    $findByUuid = $pdo->prepare("SELECT id FROM animes WHERE catalog_uuid = ? LIMIT 1");

    // Catalog-only fields. Personal data (watched, status, notes, image) is
    // deliberately NOT transferred - the admin's personal watch progress is
    // private even on their own server. image_path is kept locally because
    // the filesystem path means nothing on another machine (the actual files
    // have to be uploaded via FTP to the server uploads/ dir separately).
    //
    // 0.6.2 schema update: 'genres' text column removed (now in
    // anime_genres join table - handled separately below, same pattern
    // as tags).
    $updateSql = "
        UPDATE animes SET
            title = :title,
            alternative_titles = :alternative_titles,
            status = :status,
            total_episodes = :total_episodes,
            aired_episodes = :aired_episodes,
            synopsis_tr = :synopsis_tr,
            synopsis_en = :synopsis_en,
            translation_status = :translation_status,
            release_date = :release_date,
            end_date = :end_date,
            anidb_link = :anidb_link,
            mal_link = :mal_link,
            anime_schedule_link = :anime_schedule_link,
            episode_interval = :episode_interval,
            broadcast_day = :broadcast_day,
            broadcast_time = :broadcast_time,
            broadcast_timezone = :broadcast_timezone,
            series_name = :series_name,
            media_type = :media_type,
            country = :country,
            mal_id = :mal_id,
            anidb_id = :anidb_id,
            catalog_uuid = :catalog_uuid,
            image_path = :image_path,
            is_adult = :is_adult,
            source = 'catalog'
        WHERE id = :id
    ";
    $updateStmt = $pdo->prepare($updateSql);

    // INSERT. watched_episodes=0, watch_status='PlanToWatch' - this is
    // the server's view of the anime, not tied to any user's progress.
    $insertSql = "
        INSERT INTO animes (
            title, alternative_titles, status, total_episodes, aired_episodes,
            watched_episodes, notes, image_path,
            watch_status, next_episode_date,
            anidb_link, mal_link, anime_schedule_link,
            episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
            synopsis_tr, synopsis_en, translation_status, release_date, end_date,
            series_name, media_type, country,
            mal_id, anidb_id, catalog_uuid, source, is_adult
        ) VALUES (
            :title, :alternative_titles, :status, :total_episodes, :aired_episodes,
            0, NULL, :image_path,
            'PlanToWatch', NULL,
            :anidb_link, :mal_link, :anime_schedule_link,
            :episode_interval, :broadcast_day, :broadcast_time, :broadcast_timezone,
            :synopsis_tr, :synopsis_en, :translation_status, :release_date, :end_date,
            :series_name, :media_type, :country,
            :mal_id, :anidb_id, :catalog_uuid, 'catalog', :is_adult
        )
    ";
    $insertStmt = $pdo->prepare($insertSql);

    foreach ($animes as $a) {
        $adminId = isset($a['id']) ? (int)$a['id'] : null;
        $matchId = null;

        // Try each identity field until one matches
        if (!empty($a['mal_id'])) {
            $findByMal->execute([(int)$a['mal_id']]);
            $matchId = $findByMal->fetchColumn() ?: null;
            $findByMal->closeCursor();
        }
        if ($matchId === null && !empty($a['anidb_id'])) {
            $findByAnidb->execute([(int)$a['anidb_id']]);
            $matchId = $findByAnidb->fetchColumn() ?: null;
            $findByAnidb->closeCursor();
        }
        if ($matchId === null && !empty($a['catalog_uuid'])) {
            $findByUuid->execute([$a['catalog_uuid']]);
            $matchId = $findByUuid->fetchColumn() ?: null;
            $findByUuid->closeCursor();
        }

        $params = [
            ':title'               => $a['title']               ?? '',
            ':alternative_titles'  => $a['alternative_titles']  ?? null,
            ':status'              => $a['status']              ?? 'Seçim Yapılmadı',
            ':total_episodes'      => $a['total_episodes']      ?? null,
            ':aired_episodes'      => $a['aired_episodes']      ?? null,
            ':synopsis_tr'         => $a['synopsis_tr']         ?? null,
            ':synopsis_en'         => $a['synopsis_en']         ?? null,
            ':translation_status'  => $a['translation_status']  ?? 'none',
            ':release_date'        => $a['release_date']        ?? null,
            ':end_date'            => $a['end_date']            ?? null,
            ':anidb_link'          => $a['anidb_link']          ?? null,
            ':mal_link'            => $a['mal_link']            ?? null,
            ':anime_schedule_link' => $a['anime_schedule_link'] ?? null,
            ':episode_interval'    => $a['episode_interval']    ?? 7,
            ':broadcast_day'       => $a['broadcast_day']       ?? null,
            ':broadcast_time'      => $a['broadcast_time']      ?? null,
            ':broadcast_timezone'  => $a['broadcast_timezone']  ?? 'Asia/Tokyo',
            ':series_name'         => $a['series_name']         ?? null,
            ':media_type'          => $a['media_type']          ?? null,
            // 1.1.17: yapim ulkesi (ISO alpha-2). Sunucu tarafinda animes
            // tablosuna `country` kolonu ELLE eklenmis olmali - catalog_server
            // migration calistirmaz. Kolon yoksa bu push HATA verir.
            ':country'             => $a['country']             ?? null,
            ':mal_id'              => !empty($a['mal_id'])      ? (int)$a['mal_id']   : null,
            ':anidb_id'            => !empty($a['anidb_id'])    ? (int)$a['anidb_id'] : null,
            ':catalog_uuid'        => $a['catalog_uuid']        ?? null,
            ':image_path'          => $a['image_path']          ?? null,
            ':is_adult'            => !empty($a['is_adult'])     ? 1 : 0,
        ];

        if ($matchId !== null) {
            $params[':id'] = $matchId;
            $updateStmt->execute($params);
            $stats['updated']++;
            if ($adminId !== null) {
                $adminIdToServerId[$adminId] = (int)$matchId;
            }
        } else {
            $insertStmt->execute($params);
            $newId = (int)$pdo->lastInsertId();
            $stats['inserted']++;
            if ($adminId !== null) {
                $adminIdToServerId[$adminId] = $newId;
            }
        }
    }

    // Batched push: merge any id_map entries into the local->server ID table so
    // a chronology-only final batch resolves markers whose animes arrived in
    // earlier batches. Identity lookup only (no upsert); entries already mapped
    // from this body's animes win.
    if (is_array($idMap) && !empty($idMap)) {
        foreach ($idMap as $entry) {
            $adminId = isset($entry['id']) ? (int)$entry['id'] : 0;
            if ($adminId <= 0 || isset($adminIdToServerId[$adminId])) {
                continue;
            }
            $sid = null;
            if (!empty($entry['mal_id'])) {
                $findByMal->execute([(int)$entry['mal_id']]);
                $sid = $findByMal->fetchColumn() ?: null;
                $findByMal->closeCursor();
            }
            if ($sid === null && !empty($entry['anidb_id'])) {
                $findByAnidb->execute([(int)$entry['anidb_id']]);
                $sid = $findByAnidb->fetchColumn() ?: null;
                $findByAnidb->closeCursor();
            }
            if ($sid === null && !empty($entry['catalog_uuid'])) {
                $findByUuid->execute([$entry['catalog_uuid']]);
                $sid = $findByUuid->fetchColumn() ?: null;
                $findByUuid->closeCursor();
            }
            if ($sid !== null) {
                $adminIdToServerId[$adminId] = (int)$sid;
            }
        }
    }

    // Chronology markers: wipe and reload. The admin's chronology is
    // authoritative - there is no server-side editing of markers.
    //
    // Karar 1B: the DELETE is scoped to WHERE source='catalog' and the
    // INSERT writes source='catalog'. On the server every marker is a
    // catalog marker (no per-user markers exist here), so this is a
    // consistency/forward-safety measure, not a behavioural change in
    // normal operation. It matters for two things:
    //   1. The local client pulls these rows via catalog.php; storing
    //      them as 'catalog' keeps the round-trip label correct so
    //      catalog_import.php's "DELETE WHERE source='catalog'" targets
    //      the right rows and never wipes the admin's local source='user'
    //      markers.
    //   2. Reconvergence: the manual server-side ALTER backfills existing
    //      rows to 'user' (DEFAULT). ON DUPLICATE KEY UPDATE promotes any
    //      row matching the UNIQUE KEY back to 'catalog', so the first
    //      push after the schema change succeeds even if the admin has
    //      not run the one-time "UPDATE chronology_markers SET
    //      source='catalog'" cleanup yet (no UNIQUE-key collision /
    //      transaction rollback).
    // Anime-only batches (batched push) skip the authoritative wipe+reload;
    // it runs once, in the final chronology batch. A single-shot push has
    // skip_chronology unset, so it wipes+reloads exactly as before.
    if (!$skipChronology) {
        $pdo->exec("DELETE FROM chronology_markers WHERE source = 'catalog'");
    }

    if (!$skipChronology && !empty($chronology)) {
        $markerStmt = $pdo->prepare("
            INSERT INTO chronology_markers (anime_id, after_episode, story_after_episode, related_anime_id, note, source)
            VALUES (?, ?, ?, ?, ?, 'catalog')
            ON DUPLICATE KEY UPDATE
                story_after_episode = VALUES(story_after_episode),
                note = VALUES(note),
                source = 'catalog'
        ");
        foreach ($chronology as $m) {
            $adminAnimeId   = (int)($m['anime_id'] ?? 0);
            $adminRelatedId = (int)($m['related_anime_id'] ?? 0);

            // Translate admin IDs to server IDs
            $serverAnimeId   = $adminIdToServerId[$adminAnimeId]   ?? null;
            $serverRelatedId = $adminIdToServerId[$adminRelatedId] ?? null;

            if ($serverAnimeId === null || $serverRelatedId === null) {
                continue; // skip unresolvable
            }

            // story_after_episode (1.1.15): NULL stays NULL ("same as release").
            $storyAfter = (isset($m['story_after_episode']) && $m['story_after_episode'] !== null && $m['story_after_episode'] !== '')
                ? (int)$m['story_after_episode']
                : null;

            $markerStmt->execute([
                $serverAnimeId,
                (int)($m['after_episode'] ?? 0),
                $storyAfter,
                $serverRelatedId,
                $m['note'] ?? null,
            ]);
            $stats['markers']++;
        }
    }

    // Tags (recommendation system sentences).
    //
    // The admin's tag library is authoritative. We rebuild every
    // anime's tag links from scratch on each push - simpler and avoids
    // diff bugs.
    //
    // Two-step process:
    //   1. Resolve each sentence text to a server-side tag.id via
    //      findOrCreateTag-style logic (case-insensitive lookup, INSERT
    //      if missing, race-safe via UNIQUE on tags.name).
    //   2. For every anime that was synced this round, DELETE its
    //      current anime_tags rows and INSERT the fresh set.
    //
    // We only touch animes that appeared in this payload. Animes the
    // admin did not push (rare - they would have to be source='local'
    // on the admin's machine but exist on the server) keep their tag
    // links untouched. This is conservative; tag drift between admin
    // and server is the admin's responsibility to clean up.
    $findTagStmt    = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
    $insertTagStmt  = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
    $deleteLinkStmt = $pdo->prepare("DELETE FROM anime_tags WHERE anime_id = ?");
    $insertLinkStmt = $pdo->prepare("INSERT INTO anime_tags (anime_id, tag_id) VALUES (?, ?)");

    // Resolve a sentence text to a tag.id, creating the tag row if
    // necessary. Returns 0 for empty input.
    $resolveTagId = function($name) use ($findTagStmt, $insertTagStmt, $pdo) {
        $name = trim((string)$name);
        if ($name === '') return 0;
        if (mb_strlen($name) > 150) {
            $name = mb_substr($name, 0, 150);
        }
        $findTagStmt->execute([$name]);
        $id = $findTagStmt->fetchColumn();
        $findTagStmt->closeCursor();
        if ($id !== false) {
            return (int)$id;
        }
        // Race-safe insert (UNIQUE on tags.name catches concurrent inserts)
        try {
            $insertTagStmt->execute([$name]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $findTagStmt->execute([$name]);
                $existingId = $findTagStmt->fetchColumn();
                $findTagStmt->closeCursor();
                if ($existingId !== false) {
                    return (int)$existingId;
                }
            }
            throw $e;
        }
    };

    foreach ($animes as $a) {
        $adminId = isset($a['id']) ? (int)$a['id'] : null;
        if ($adminId === null) continue;
        $serverAnimeId = $adminIdToServerId[$adminId] ?? null;
        if ($serverAnimeId === null) continue;

        $tagNames = $a['tags'] ?? [];
        if (!is_array($tagNames)) $tagNames = [];

        // Replace this anime's tag set
        $deleteLinkStmt->execute([$serverAnimeId]);
        $seen = [];
        foreach ($tagNames as $tn) {
            $tagId = $resolveTagId($tn);
            if ($tagId <= 0) continue;
            if (isset($seen[$tagId])) continue;  // dedup within payload
            $seen[$tagId] = true;
            $insertLinkStmt->execute([$serverAnimeId, $tagId]);
            $stats['tags']++;
        }
    }

    // Genres (canonical taxonomy).
    //
    // 0.6.2 update (26 May 2026): server schema migrated from a text
    // column animes.genres (CSV string) to an anime_genres join table
    // backed by a master genres table. The wire format from admin_sync
    // is still CSV in $a['genres'] (kept stable for backwards
    // compatibility). Here we parse the CSV, resolve each name to a
    // genre.id (creating new master rows on demand), and rewrite the
    // anime's anime_genres links from scratch - same pattern as tags.
    //
    // Two-step process:
    //   1. Resolve each genre name to a server-side genres.id via
    //      findOrCreate logic (race-safe via UNIQUE on genres.name).
    //   2. For every anime synced this round, DELETE its current
    //      anime_genres rows and INSERT the fresh set.
    $findGenreStmt    = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
    $insertGenreStmt  = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
    $deleteGenreLinkStmt = $pdo->prepare("DELETE FROM anime_genres WHERE anime_id = ?");
    $insertGenreLinkStmt = $pdo->prepare("INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?)");

    $resolveGenreId = function($name) use ($findGenreStmt, $insertGenreStmt, $pdo) {
        $name = trim((string)$name);
        if ($name === '') return 0;
        if (mb_strlen($name) > 100) {
            $name = mb_substr($name, 0, 100);
        }
        $findGenreStmt->execute([$name]);
        $id = $findGenreStmt->fetchColumn();
        $findGenreStmt->closeCursor();
        if ($id !== false) {
            return (int)$id;
        }
        try {
            $insertGenreStmt->execute([$name]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $findGenreStmt->execute([$name]);
                $existingId = $findGenreStmt->fetchColumn();
                $findGenreStmt->closeCursor();
                if ($existingId !== false) {
                    return (int)$existingId;
                }
            }
            throw $e;
        }
    };

    foreach ($animes as $a) {
        $adminId = isset($a['id']) ? (int)$a['id'] : null;
        if ($adminId === null) continue;
        $serverAnimeId = $adminIdToServerId[$adminId] ?? null;
        if ($serverAnimeId === null) continue;

        // Wire format: CSV string. Empty/missing -> no genres for this anime.
        $genresCsv = $a['genres'] ?? '';
        $genreNames = [];
        if (is_string($genresCsv) && $genresCsv !== '') {
            $genreNames = array_map('trim', explode(',', $genresCsv));
            $genreNames = array_filter($genreNames, function($n) { return $n !== ''; });
        }

        // Replace this anime's genre set
        $deleteGenreLinkStmt->execute([$serverAnimeId]);
        $seenGenre = [];
        foreach ($genreNames as $gn) {
            $genreId = $resolveGenreId($gn);
            if ($genreId <= 0) continue;
            if (isset($seenGenre[$genreId])) continue;  // dedup within payload
            $seenGenre[$genreId] = true;
            $insertGenreLinkStmt->execute([$serverAnimeId, $genreId]);
            $stats['genres']++;
        }
    }

    $pdo->commit();

    // Invalidate the catalog cache so next client sync sees fresh data
    $cacheFile = __DIR__ . '/catalog_cache.json';
    if (file_exists($cacheFile)) {
        @unlink($cacheFile);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'DB transaction failed']);
    error_log('[admin_push] transaction: ' . $e->getMessage());
    exit;
}

echo json_encode([
    'status'   => 'ok',
    'inserted' => $stats['inserted'],
    'updated'  => $stats['updated'],
    'markers'  => $stats['markers'],
    'tags'     => $stats['tags'],
    'genres'   => $stats['genres'],
]);
