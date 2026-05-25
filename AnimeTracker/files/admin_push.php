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
 *   success: { status: "ok", inserted: N, updated: N, markers: N }
 *   error:   { status: "error", message: "..." }
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

// --- Rate limit (file-based, 5s per IP) ---------------------------------
// Kept short because admin push is already protected by HMAC + secret.
// This only prevents accidental double-clicks or runaway scripts.

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateDir = __DIR__ . '/../private/rate_limit';
if (!is_dir($rateDir)) {
    @mkdir($rateDir, 0700, true);
}
// Sanitize IP for filename (IPv6 has colons)
$ipKey = preg_replace('/[^a-z0-9._-]/i', '_', $ip);
$rateFile = $rateDir . '/admin_push_' . $ipKey . '.txt';

if (file_exists($rateFile) && (time() - filemtime($rateFile)) < 5) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'message' => 'Rate limited']);
    exit;
}
@touch($rateFile);

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

// Body size limit - 5 MB is plenty for a catalog of thousands of animes
if (strlen($rawBody) > 5 * 1024 * 1024) {
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

if (count($animes) > 5000) {
    // Sanity limit - way more than expected
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
$stats = [
    'inserted' => 0,
    'updated'  => 0,
    'markers'  => 0,
    'tags'     => 0,
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
    $updateSql = "
        UPDATE animes SET
            title = :title,
            alternative_titles = :alternative_titles,
            status = :status,
            total_episodes = :total_episodes,
            aired_episodes = :aired_episodes,
            genres = :genres,
            synopsis = :synopsis,
            release_date = :release_date,
            anidb_link = :anidb_link,
            mal_link = :mal_link,
            anime_schedule_link = :anime_schedule_link,
            episode_interval = :episode_interval,
            broadcast_day = :broadcast_day,
            broadcast_time = :broadcast_time,
            broadcast_timezone = :broadcast_timezone,
            series_name = :series_name,
            media_type = :media_type,
            mal_id = :mal_id,
            anidb_id = :anidb_id,
            catalog_uuid = :catalog_uuid,
            image_path = :image_path,
            source = 'catalog'
        WHERE id = :id
    ";
    $updateStmt = $pdo->prepare($updateSql);

    // INSERT. watched_episodes=0, watch_status='PlanToWatch' - this is
    // the server's view of the anime, not tied to any user's progress.
    $insertSql = "
        INSERT INTO animes (
            title, alternative_titles, status, total_episodes, aired_episodes,
            watched_episodes, notes, genres, image_path,
            watch_status, next_episode_date,
            anidb_link, mal_link, anime_schedule_link,
            episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
            synopsis, release_date,
            series_name, media_type,
            mal_id, anidb_id, catalog_uuid, source
        ) VALUES (
            :title, :alternative_titles, :status, :total_episodes, :aired_episodes,
            0, NULL, :genres, :image_path,
            'PlanToWatch', NULL,
            :anidb_link, :mal_link, :anime_schedule_link,
            :episode_interval, :broadcast_day, :broadcast_time, :broadcast_timezone,
            :synopsis, :release_date,
            :series_name, :media_type,
            :mal_id, :anidb_id, :catalog_uuid, 'catalog'
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
            ':status'              => $a['status']              ?? 'Yayin Tamamlandi',
            ':total_episodes'      => $a['total_episodes']      ?? null,
            ':aired_episodes'      => $a['aired_episodes']      ?? null,
            ':genres'              => $a['genres']              ?? null,
            ':synopsis'            => $a['synopsis']            ?? null,
            ':release_date'        => $a['release_date']        ?? null,
            ':anidb_link'          => $a['anidb_link']          ?? null,
            ':mal_link'            => $a['mal_link']            ?? null,
            ':anime_schedule_link' => $a['anime_schedule_link'] ?? null,
            ':episode_interval'    => $a['episode_interval']    ?? 7,
            ':broadcast_day'       => $a['broadcast_day']       ?? null,
            ':broadcast_time'      => $a['broadcast_time']      ?? null,
            ':broadcast_timezone'  => $a['broadcast_timezone']  ?? 'Asia/Tokyo',
            ':series_name'         => $a['series_name']         ?? null,
            ':media_type'          => $a['media_type']          ?? null,
            ':mal_id'              => !empty($a['mal_id'])      ? (int)$a['mal_id']   : null,
            ':anidb_id'            => !empty($a['anidb_id'])    ? (int)$a['anidb_id'] : null,
            ':catalog_uuid'        => $a['catalog_uuid']        ?? null,
            ':image_path'          => $a['image_path']          ?? null,
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
    $pdo->exec("DELETE FROM chronology_markers WHERE source = 'catalog'");

    if (!empty($chronology)) {
        $markerStmt = $pdo->prepare("
            INSERT INTO chronology_markers (anime_id, after_episode, related_anime_id, note, source)
            VALUES (?, ?, ?, ?, 'catalog')
            ON DUPLICATE KEY UPDATE
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

            $markerStmt->execute([
                $serverAnimeId,
                (int)($m['after_episode'] ?? 0),
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
]);
