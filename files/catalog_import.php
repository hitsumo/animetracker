<?php

/**
 * Anime Tracker - Catalog Import Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Fetches the public catalog JSON from the remote server and merges
 * it into the local animes table. Personal data (watched_episodes,
 * watch_status, notes, next_episode_date) is NEVER touched - only
 * catalog fields are overwritten.
 *
 * image_path handling (see "Poster download" section below for details):
 *   - For NEW catalog animes (INSERT): the poster is downloaded from
 *     the server's public /uploads/ and saved locally; image_path
 *     points to the local copy.
 *   - For EXISTING rows (UPDATE): image_path is preserved AS-IS. This
 *     protects users who replaced the default poster with their own
 *     upload ("user customisation wins").
 *
 * Matching order (first hit wins):
 *   1. mal_id     (MyAnimeList ID)
 *   2. anidb_id   (AniDB ID)
 *   3. catalog_uuid (server-assigned UUID, fallback)
 *
 * Chronology markers are replaced wholesale: all existing rows are
 * deleted and the catalog markers are re-inserted. The catalog's
 * chronology is considered authoritative (same for every user).
 *
 * Access:
 *   POST from list_settings.php with a valid CSRF token.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// --- Configuration -------------------------------------------------------

// Fixed catalog URL - see PROJE_DURUMU.md for why this is hardcoded.
// If the catalog ever moves, ship a new release via update.php.
const CATALOG_URL = 'https://animetracker.sicakcikolata.com/catalog.php';

// Base URL for poster downloads. This is the directory on the server
// where cover images are publicly served. Derived from the same host
// as CATALOG_URL so they stay in sync.
const CATALOG_UPLOADS_BASE = 'https://animetracker.sicakcikolata.com/uploads/';

// HTTP timeout in seconds when fetching the catalog JSON. The catalog
// JSON is small (~100KB for a few thousand animes) so a short timeout
// is fine.
const FETCH_TIMEOUT = 15;

// HTTP timeout in seconds when fetching an individual poster image.
// Posters are larger (~50-200 KB each) and we download many of them,
// so allow a bit more time per image.
const IMAGE_TIMEOUT = 10;

// Maximum poster file size in bytes. Protects against someone hosting
// a malicious huge image on the server. 5 MB is generous for a cover.
const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

// Allowed image MIME types. Anything else is rejected. We rely on the
// server's response, not on file extensions, so an attacker can't trick
// us with a renamed file.
const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_settings.php');
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// Importing a catalog writes to the shared animes table, so a moderator+ is
// required (online only; no-op in self-host).
require_role($pdo, 'moderator');

// --- Helper: download poster image --------------------------------------

/**
 * Download a poster image from the catalog server into the local uploads
 * directory. Idempotent: if the local file already exists, returns the
 * existing relative path without re-downloading.
 *
 * Returns the local relative path (e.g. "uploads/sakamoto_days.jpg") on
 * success, or null on any failure. A failed download is NOT an error
 * worth aborting the whole sync - the anime row is still imported, just
 * without a poster (user can upload one later via edit_anime.php).
 */
function fetch_catalog_poster($filename, &$stats) {
    // Filename validation - prevent path traversal. We only accept
    // plain filenames with a safe character set. The server is
    // supposed to send clean basenames but we enforce it client-side
    // too as defence in depth.
    if (!is_string($filename) || $filename === '') {
        return null;
    }
    if (!preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|gif|webp)$/i', $filename)) {
        error_log('[anime_tracker] rejected poster filename: ' . $filename);
        return null;
    }

    $localPath = __DIR__ . '/uploads/' . $filename;
    $relPath   = 'uploads/' . $filename;

    // Idempotent - already downloaded
    if (file_exists($localPath)) {
        $stats['poster_cached']++;
        return $relPath;
    }

    $url = CATALOG_UPLOADS_BASE . rawurlencode($filename);

    $ctx = stream_context_create([
        'http'  => ['timeout' => IMAGE_TIMEOUT, 'follow_location' => 1, 'max_redirects' => 3],
        'https' => ['timeout' => IMAGE_TIMEOUT, 'follow_location' => 1, 'max_redirects' => 3],
    ]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false) {
        $stats['poster_failed']++;
        error_log('[anime_tracker] poster download failed: ' . $url);
        return null;
    }

    // Size check - reject absurdly large images
    if (strlen($data) > MAX_IMAGE_BYTES) {
        $stats['poster_failed']++;
        error_log('[anime_tracker] poster too large (' . strlen($data) . ' bytes): ' . $url);
        return null;
    }

    // MIME sniffing - verify it's actually an image, not HTML error page
    // or disguised content. finfo_* is bundled with PHP by default.
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_buffer($finfo, $data);
    finfo_close($finfo);

    if (!in_array($mime, ALLOWED_IMAGE_MIMES, true)) {
        $stats['poster_failed']++;
        error_log('[anime_tracker] rejected poster MIME ' . $mime . ' for ' . $url);
        return null;
    }

    // Atomic write - write to .tmp, then rename. Prevents half-written
    // files if the process is interrupted mid-download.
    $tmp = $localPath . '.tmp';
    if (@file_put_contents($tmp, $data) === false) {
        $stats['poster_failed']++;
        error_log('[anime_tracker] poster write failed: ' . $tmp);
        return null;
    }
    if (!@rename($tmp, $localPath)) {
        @unlink($tmp);
        $stats['poster_failed']++;
        error_log('[anime_tracker] poster rename failed: ' . $tmp);
        return null;
    }

    $stats['poster_downloaded']++;
    return $relPath;
}

// --- Fetch the catalog JSON ---------------------------------------------

$ctx = stream_context_create([
    'http' => [
        'timeout' => FETCH_TIMEOUT,
        'header'  => "Accept: application/json\r\n",
    ],
    'https' => [
        'timeout' => FETCH_TIMEOUT,
        'header'  => "Accept: application/json\r\n",
    ],
]);

$raw = @file_get_contents(CATALOG_URL, false, $ctx);
if ($raw === false) {
    error_log('[anime_tracker] catalog fetch failed: ' . CATALOG_URL);
    die('Katalog sunucusuna erisilemedi. Internet baglantinizi kontrol edin ve tekrar deneyin.');
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['animes']) || !is_array($payload['animes'])) {
    error_log('[anime_tracker] catalog JSON invalid: ' . substr($raw, 0, 200));
    die('Katalog verisi gecersiz format. Lutfen daha sonra tekrar deneyin.');
}

$catalogAnimes  = $payload['animes'];
$catalogMarkers = $payload['chronology'] ?? [];
$catalogTags    = $payload['tags'] ?? []; // global sentence library

// 0.7.7: optional English-name translation maps, keyed by Turkish name.
// Absent when syncing from a pre-0.7.7 server - treated as empty, which
// leaves local name_en untouched (catalog never clears a local English
// name; it only fills/overwrites when it actually sends one).
$catalogTagNameEn   = $payload['tag_name_en']   ?? [];
$catalogGenreNameEn = $payload['genre_name_en'] ?? [];
// 1.1.3: adult-flag maps (keyed by name, only adult entries present).
// Absent on old payloads -> empty -> no local flag is ever cleared.
$catalogTagIsAdult   = $payload['tag_is_adult']   ?? [];
$catalogGenreIsAdult = $payload['genre_is_adult'] ?? [];
if (!is_array($catalogTagNameEn))   $catalogTagNameEn   = [];
if (!is_array($catalogGenreNameEn)) $catalogGenreNameEn = [];

// --- Ensure uploads directory exists ------------------------------------

$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    if (!@mkdir($uploadsDir, 0755, true)) {
        die('uploads/ klasoru olusturulamadi. Yazma izinlerini kontrol edin.');
    }
}
if (!is_writable($uploadsDir)) {
    die('uploads/ klasoru yazilamaz durumda. Sunucu yazma izinlerini kontrol edin.');
}

// --- Load local animes into a lookup map --------------------------------

// We pull only the identity columns + source, so the memory footprint is
// minimal even with thousands of rows.
$localMap = [
    'mal'    => [], // mal_id    -> local id
    'anidb'  => [], // anidb_id  -> local id
    'uuid'   => [], // catalog_uuid -> local id
    'byId'   => [], // local id  -> [mal_id, anidb_id, catalog_uuid, source]
];

$rows = $pdo->query("SELECT id, mal_id, anidb_id, catalog_uuid, source FROM animes")
            ->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $localMap['byId'][(int)$r['id']] = $r;
    if (!empty($r['mal_id']))       $localMap['mal'][(int)$r['mal_id']]       = (int)$r['id'];
    if (!empty($r['anidb_id']))     $localMap['anidb'][(int)$r['anidb_id']]   = (int)$r['id'];
    if (!empty($r['catalog_uuid'])) $localMap['uuid'][$r['catalog_uuid']]     = (int)$r['id'];
}

// --- Prepare UPDATE and INSERT statements -------------------------------

// UPDATE does NOT touch image_path. User customisation wins - if they
// uploaded their own poster, we keep it. If they want the catalog poster
// back they can delete it and re-sync (then the new INSERT path applies
// on next catalog update of that anime, or they can manually upload).
//
// Genres are no longer in this row - they are written to the
// anime_genres join table by the dedicated genres block below the
// merge loop, mirroring the tags handler.
$updateSql = "
    UPDATE animes SET
        title = :title,
        alternative_titles = :alternative_titles,
        title_english = :title_english,
        status = :status,
        total_episodes = :total_episodes,
        aired_episodes = :aired_episodes,
        synopsis_tr = :synopsis_tr,
        synopsis_en = :synopsis_en,
        translation_status = :translation_status,
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
        is_adult = :is_adult,
        mal_id = :mal_id,
        anidb_id = :anidb_id,
        catalog_uuid = :catalog_uuid,
        source = 'catalog'
    WHERE id = :id
";
$updateStmt = $pdo->prepare($updateSql);

// 0.7.3 personal-synopsis MOVE support.
//
// Before each UPDATE overwrites the catalog synopsis, we must rescue any
// edit the user made to it. The rule (per language, independent):
//   if user_synopsis(_en) IS NULL AND the local catalog synopsis differs
//   from the incoming server synopsis, the user has hand-edited it; move
//   that local text into user_synopsis(_en) so it is not lost, then let
//   the UPDATE write the fresh catalog text.
// An empty string '' in user_synopsis(_en) counts as "set" (intentionally
// cleared) and is NOT NULL, so it never re-triggers the move. NULL means
// "still catalog-managed". This is the regression that dropped in 0.7.1
// when synopsis became multi-language; restored here per-language.
//
// We read the current local synopsis/personal columns just-in-time per
// matched row (localMap holds only identity columns, to keep its memory
// footprint small).
// Personal synopsis (user_synopsis / user_synopsis_en) lives in user_anime
// per user (1.0.1); it is read via ua_get_state and written via
// ua_set_state in the move block below. Here we only need the local
// CATALOG synopsis (animes) to compare against the incoming server text.
$uid = current_user_id();
$readSynopsisStmt = $pdo->prepare(
    "SELECT synopsis_tr, synopsis_en
     FROM animes WHERE id = :id"
);

// INSERT for new catalog entries. image_path gets set to the local
// relative path of the downloaded poster (or NULL if download failed).
// Genres are written to anime_genres in the dedicated block below.
$insertSql = "
    INSERT INTO animes (
        title, alternative_titles, status, total_episodes, aired_episodes,
        image_path,
        next_episode_date,
        anidb_link, mal_link, anime_schedule_link,
        episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
        synopsis_tr, synopsis_en, translation_status, release_date,
        series_name, media_type, next_in_series,
        mal_id, anidb_id, catalog_uuid, source, title_english, is_adult
    ) VALUES (
        :title, :alternative_titles, :status, :total_episodes, :aired_episodes,
        :image_path,
        NULL,
        :anidb_link, :mal_link, :anime_schedule_link,
        :episode_interval, :broadcast_day, :broadcast_time, :broadcast_timezone,
        :synopsis_tr, :synopsis_en, :translation_status, :release_date,
        :series_name, :media_type, NULL,
        :mal_id, :anidb_id, :catalog_uuid, 'catalog', :title_english, :is_adult
    )
";
$insertStmt = $pdo->prepare($insertSql);

// --- Merge loop ---------------------------------------------------------

$stats = [
    'inserted'          => 0,
    'updated'           => 0,
    'unchanged'         => 0,
    'markers'           => 0,
    'tags'              => 0,
    'genres'            => 0,
    'poster_downloaded' => 0,
    'poster_cached'     => 0, // already had the local copy
    'poster_failed'     => 0,
    'poster_repaired'   => 0, // DB said path exists but file was missing - re-downloaded
];

// Track which local catalog rows were seen in this sync. Anything
// marked source='catalog' that we don't see in the payload gets
// downgraded to source='local' at the end.
$seenLocalIds = [];

// Map catalog remote id -> local id, used later for chronology markers
// (the server's anime_id and related_anime_id reference server IDs,
// but locally we have different IDs - we need to translate).
$catalogIdToLocalId = [];

$pdo->beginTransaction();

try {
    foreach ($catalogAnimes as $ca) {
        $matchId = null;

        // Matching order: mal_id -> anidb_id -> catalog_uuid
        if (!empty($ca['mal_id']) && isset($localMap['mal'][(int)$ca['mal_id']])) {
            $matchId = $localMap['mal'][(int)$ca['mal_id']];
        } elseif (!empty($ca['anidb_id']) && isset($localMap['anidb'][(int)$ca['anidb_id']])) {
            $matchId = $localMap['anidb'][(int)$ca['anidb_id']];
        } elseif (!empty($ca['catalog_uuid']) && isset($localMap['uuid'][$ca['catalog_uuid']])) {
            $matchId = $localMap['uuid'][$ca['catalog_uuid']];
        }

        // Common parameter set used for both UPDATE and INSERT.
        // Genres are NOT in this set - they are handled separately
        // after the merge loop, written to anime_genres via the
        // dedicated genres block (mirrors the tags handler).
        $params = [
            ':title'               => $ca['title']               ?? '',
            ':alternative_titles'  => $ca['alternative_titles']  ?? null,
            ':title_english'       => $ca['title_english']        ?? null,
            ':status'              => $ca['status']              ?? 'Seçim Yapılmadı',
            ':total_episodes'      => $ca['total_episodes']      ?? null,
            ':aired_episodes'      => $ca['aired_episodes']      ?? null,
            ':synopsis_tr'         => $ca['synopsis_tr']         ?? null,
            ':synopsis_en'         => $ca['synopsis_en']         ?? null,
            ':translation_status'  => $ca['translation_status']  ?? 'none',
            ':release_date'        => $ca['release_date']        ?? null,
            ':anidb_link'          => $ca['anidb_link']          ?? null,
            ':mal_link'            => $ca['mal_link']            ?? null,
            ':anime_schedule_link' => $ca['anime_schedule_link'] ?? null,
            ':episode_interval'    => $ca['episode_interval']    ?? 7,
            ':broadcast_day'       => $ca['broadcast_day']       ?? null,
            ':broadcast_time'      => $ca['broadcast_time']      ?? null,
            ':broadcast_timezone'  => $ca['broadcast_timezone']  ?? 'Asia/Tokyo',
            ':series_name'         => $ca['series_name']         ?? null,
            ':media_type'          => $ca['media_type']          ?? null,
            // 1.1.2 - yetiskin bayragi. Eski/alansiz katalog JSON'i is_adult
            // tasimayabilir; eksikse 0 (yetiskin degil) - geriye uyumlu.
            ':is_adult'            => !empty($ca['is_adult']) ? 1 : 0,
            ':mal_id'              => !empty($ca['mal_id'])      ? (int)$ca['mal_id']   : null,
            ':anidb_id'            => !empty($ca['anidb_id'])    ? (int)$ca['anidb_id'] : null,
            ':catalog_uuid'        => $ca['catalog_uuid']        ?? null,
        ];

        if ($matchId !== null) {
            // --- 0.7.3 personal-synopsis MOVE (per language, before UPDATE) ---
            // Rescue a user hand-edit of the catalog synopsis into
            // user_synopsis(_en) so the imminent UPDATE does not erase it.
            // Must run BEFORE the UPDATE (which overwrites synopsis_tr/en,
            // destroying the difference we compare against).
            $readSynopsisStmt->execute([':id' => $matchId]);
            $localSyn = $readSynopsisStmt->fetch(PDO::FETCH_ASSOC);
            // Personal synopsis state for this user (NULL = that language is
            // not yet personalized, so a catalog hand-edit can be rescued).
            $localPers = ua_get_state($pdo, $uid, $matchId);
            if ($localSyn !== false) {
                $serverTr = $ca['synopsis_tr'] ?? null;
                $serverEn = $ca['synopsis_en'] ?? null;

                // TR: only when not yet personal (NULL) and the local text
                // diverges from the server text. The rescued catalog text is
                // written to user_anime.user_synopsis for this user (partial
                // upsert - only the TR field is touched).
                if ($localPers['user_synopsis'] === null
                    && (string)($localSyn['synopsis_tr'] ?? '') !== (string)($serverTr ?? '')) {
                    ua_set_state($pdo, $uid, $matchId, [
                        'user_synopsis' => (string)($localSyn['synopsis_tr'] ?? ''),
                    ]);
                    $stats['synopsis_moved_tr'] = ($stats['synopsis_moved_tr'] ?? 0) + 1;
                }

                // EN: independent, same rule.
                if ($localPers['user_synopsis_en'] === null
                    && (string)($localSyn['synopsis_en'] ?? '') !== (string)($serverEn ?? '')) {
                    ua_set_state($pdo, $uid, $matchId, [
                        'user_synopsis_en' => (string)($localSyn['synopsis_en'] ?? ''),
                    ]);
                    $stats['synopsis_moved_en'] = ($stats['synopsis_moved_en'] ?? 0) + 1;
                }
            }

            // UPDATE existing row - image_path is preserved (not in SQL)
            $params[':id'] = $matchId;
            $updateStmt->execute($params);
            $stats['updated']++;
            $seenLocalIds[$matchId] = true;
            $catalogIdToLocalId[(int)$ca['id']] = $matchId;

            // Opportunistic poster backfill/repair. Three cases:
            //
            //   1. DB has no image_path AND catalog has one
            //      -> first-time download (e.g. pre-image-sync installs,
            //         or rows that were added without a poster).
            //
            //   2. DB has image_path BUT the file is missing on disk
            //      -> user accidentally deleted the file from uploads/
            //         or restored DB from backup without files. Re-download
            //         so the UI is not broken. This is the "repair" path.
            //
            //   3. DB has image_path AND file exists on disk
            //      -> leave it alone. Respects user's own uploaded poster
            //         (if they replaced the catalog default with a better one).
            $currentImage = $pdo->prepare("SELECT image_path FROM animes WHERE id = ?");
            $currentImage->execute([$matchId]);
            $existingPath = $currentImage->fetchColumn();

            $needsDownload = false;

            if ((empty($existingPath) || $existingPath === '') && !empty($ca['image_filename'])) {
                // Case 1: no image at all
                $needsDownload = true;
            } elseif (!empty($existingPath) && !empty($ca['image_filename'])) {
                // Case 2: DB has a path - check if file actually exists
                $fsPath = __DIR__ . '/' . ltrim($existingPath, '/');
                if (!file_exists($fsPath)) {
                    $needsDownload = true;
                    $stats['poster_repaired']++;
                }
            }

            if ($needsDownload) {
                $newPath = fetch_catalog_poster($ca['image_filename'], $stats);
                if ($newPath !== null) {
                    $pdo->prepare("UPDATE animes SET image_path = ? WHERE id = ?")
                        ->execute([$newPath, $matchId]);
                }
            }
        } else {
            // INSERT new row - download poster first
            $posterPath = null;
            if (!empty($ca['image_filename'])) {
                $posterPath = fetch_catalog_poster($ca['image_filename'], $stats);
            }
            $params[':image_path'] = $posterPath;

            $insertStmt->execute($params);
            $newId = (int)$pdo->lastInsertId();
            $stats['inserted']++;
            $seenLocalIds[$newId] = true;
            $catalogIdToLocalId[(int)$ca['id']] = $newId;
        }
    }

    // Downgrade orphan catalog rows to local. These are rows that were
    // previously synced from the catalog but are no longer in the catalog.
    foreach ($localMap['byId'] as $localId => $info) {
        if ($info['source'] === 'catalog' && !isset($seenLocalIds[$localId])) {
            $pdo->prepare("UPDATE animes SET source = 'local' WHERE id = ?")
                ->execute([$localId]);
        }
    }

    // Chronology markers: catalog is authoritative for catalog markers,
    // but the user's own markers (source='user') must survive an import.
    //
    // Karar 1B: the DELETE is scoped to WHERE source='catalog' so a
    // pull never wipes the user's local source='user' markers. This is
    // the local-side half of the 14 Nisan 2026 marker-loss fix
    // (admin_sync.php / admin_push.php are the server-side half). The
    // INSERT writes source='catalog' and ON DUPLICATE KEY UPDATE also
    // forces source='catalog', so a marker the user happens to have
    // locally as source='user' that the catalog also publishes gets
    // reconverged to 'catalog' (it is now catalog-managed). Markers the
    // user created that the catalog does NOT publish stay source='user'
    // and keep showing the list_settings.php "push these up" warning
    // until the admin pushes them.
    $pdo->exec("DELETE FROM chronology_markers WHERE source = 'catalog'");

    if (!empty($catalogMarkers)) {
        $markerStmt = $pdo->prepare("
            INSERT INTO chronology_markers (anime_id, after_episode, story_after_episode, related_anime_id, note, source)
            VALUES (?, ?, ?, ?, ?, 'catalog')
            ON DUPLICATE KEY UPDATE
                story_after_episode = VALUES(story_after_episode),
                note = VALUES(note),
                source = 'catalog'
        ");
        foreach ($catalogMarkers as $m) {
            $serverAnimeId   = (int)($m['anime_id'] ?? 0);
            $serverRelatedId = (int)($m['related_anime_id'] ?? 0);

            $localAnimeId   = $catalogIdToLocalId[$serverAnimeId]   ?? null;
            $localRelatedId = $catalogIdToLocalId[$serverRelatedId] ?? null;

            if ($localAnimeId === null || $localRelatedId === null) {
                continue;
            }

            // story_after_episode (1.1.15): NULL stays NULL ("same as release").
            $storyAfter = (isset($m['story_after_episode']) && $m['story_after_episode'] !== null && $m['story_after_episode'] !== '')
                ? (int)$m['story_after_episode']
                : null;

            $markerStmt->execute([
                $localAnimeId,
                (int)($m['after_episode'] ?? 0),
                $storyAfter,
                $localRelatedId,
                $m['note'] ?? null,
            ]);
            $stats['markers']++;
        }
    }

    // Genres (canonical taxonomy).
    //
    // Server still emits genres as a CSV string in $ca['genres']
    // ("Aksiyon,Macera,Komedi"). We parse that here, resolve each
    // name to a local genres.id (creating the master row on the fly
    // if needed), then DELETE+INSERT the anime_genres link rows.
    //
    // Animes the catalog did not send (i.e. user's source='local'
    // entries) are left untouched - their genres are local-only.
    //
    // Note on inline pattern: setAnimeGenresByNames() in functions.php
    // wraps DELETE+INSERT in its own beginTransaction(), which would
    // collide with the outer transaction we are already in. The tags
    // handler below uses the same inline pattern for the same reason.
    $findGenreStmt        = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
    $insertGenreStmt      = $pdo->prepare("INSERT INTO genres (name, name_en, is_adult) VALUES (?, ?, ?)");
    $updateGenreEnStmt    = $pdo->prepare("UPDATE genres SET name_en = ? WHERE id = ?");
    $updateGenreAdultStmt = $pdo->prepare("UPDATE genres SET is_adult = 1 WHERE id = ?");
    $deleteGenreLinkStmt  = $pdo->prepare("DELETE FROM anime_genres WHERE anime_id = ?");
    $insertGenreLinkStmt  = $pdo->prepare("INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?)");

    // 0.7.7: name_en handling. The catalog is authoritative when it sends
    // a non-empty English name (it overwrites the local value). A missing
    // or empty name_en is NOT sent down as NULL - we leave the local value
    // alone, so a sync never erases an English name the user typed.
    //
    // 1.1.3: is_adult handling. genre_is_adult carries only adult (1)
    // genres. We PROMOTE a local genre to adult when the catalog flags it,
    // but never demote on absence - an old payload has no map and must not
    // clear local flags. Fail-safe (once adult, stays adult until cleared
    // locally) and consistent with the never-erase name_en rule above.
    $resolveGenreId = function($name) use ($findGenreStmt, $insertGenreStmt, $updateGenreEnStmt, $updateGenreAdultStmt, $pdo, $catalogGenreNameEn, $catalogGenreIsAdult) {
        $name = trim((string)$name);
        if ($name === '') return 0;
        if (mb_strlen($name) > 50) {
            $name = mb_substr($name, 0, 50);
        }
        $nameEn = isset($catalogGenreNameEn[$name]) ? trim((string)$catalogGenreNameEn[$name]) : '';
        if ($nameEn !== '' && mb_strlen($nameEn) > 50) {
            $nameEn = mb_substr($nameEn, 0, 50);
        }
        $isAdult = !empty($catalogGenreIsAdult[$name]) ? 1 : 0;
        $findGenreStmt->execute([$name]);
        $id = $findGenreStmt->fetchColumn();
        $findGenreStmt->closeCursor();
        if ($id !== false) {
            $id = (int)$id;
            // Catalog wins only when it actually carries an English name.
            if ($nameEn !== '') {
                $updateGenreEnStmt->execute([$nameEn, $id]);
            }
            // Promote to adult when the catalog flags it; never demote.
            if ($isAdult === 1) {
                $updateGenreAdultStmt->execute([$id]);
            }
            return $id;
        }
        try {
            $insertGenreStmt->execute([$name, $nameEn !== '' ? $nameEn : null, $isAdult]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $findGenreStmt->execute([$name]);
                $existingId = $findGenreStmt->fetchColumn();
                $findGenreStmt->closeCursor();
                if ($existingId !== false) {
                    $existingId = (int)$existingId;
                    if ($nameEn !== '') {
                        $updateGenreEnStmt->execute([$nameEn, $existingId]);
                    }
                    if ($isAdult === 1) {
                        $updateGenreAdultStmt->execute([$existingId]);
                    }
                    return $existingId;
                }
            }
            throw $e;
        }
    };

    // Rebuild anime_genres for every anime that arrived in this sync.
    foreach ($catalogAnimes as $ca) {
        $serverId = (int)($ca['id'] ?? 0);
        if ($serverId === 0) continue;
        $localAnimeId = $catalogIdToLocalId[$serverId] ?? null;
        if ($localAnimeId === null) continue;

        // Server sends genres as a comma-separated string. Empty / missing
        // is fine - the DELETE below still clears any stale links.
        $genreCsv = $ca['genres'] ?? '';
        $genreNames = [];
        if (is_string($genreCsv) && $genreCsv !== '') {
            $genreNames = array_filter(array_map('trim', explode(',', $genreCsv)));
        }

        $deleteGenreLinkStmt->execute([$localAnimeId]);
        $seen = [];
        foreach ($genreNames as $gn) {
            $genreId = $resolveGenreId($gn);
            if ($genreId <= 0) continue;
            if (isset($seen[$genreId])) continue;
            $seen[$genreId] = true;
            $insertGenreLinkStmt->execute([$localAnimeId, $genreId]);
            $stats['genres']++;
        }
    }

    // Tags (recommendation system sentences).
    //
    // Each anime in the payload carries its own tag list as plain
    // sentence texts (not IDs - server tag IDs would be meaningless
    // here). We resolve each text to a local tags.id, creating new
    // rows on the fly via the same race-safe pattern used in
    // findOrCreateTag(). Then we DELETE+INSERT the anime_tags rows
    // for every anime that came in this sync.
    //
    // Animes the catalog did not send (i.e. user's source='local'
    // entries) are left untouched - their tags are local-only.
    //
    // The global $catalogTags list ensures we also create empty tag
    // rows for sentences that no anime currently uses (rare, but
    // useful so manage_tags.php shows the same library as the
    // server).
    $findTagStmt    = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
    $insertTagStmt  = $pdo->prepare("INSERT INTO tags (name, name_en, is_adult) VALUES (?, ?, ?)");
    $updateTagEnStmt = $pdo->prepare("UPDATE tags SET name_en = ? WHERE id = ?");
    $updateTagAdultStmt = $pdo->prepare("UPDATE tags SET is_adult = 1 WHERE id = ?");
    $deleteLinkStmt = $pdo->prepare("DELETE FROM anime_tags WHERE anime_id = ?");
    $insertLinkStmt = $pdo->prepare("INSERT INTO anime_tags (anime_id, tag_id) VALUES (?, ?)");

    // 0.7.7: same name_en rule as genres - catalog overwrites only when it
    // sends a non-empty English name; an absent/empty value leaves the
    // local name_en untouched.
    //
    // 1.1.3: same is_adult rule as genres - tag_is_adult carries only
    // adult (1) sentences; we promote locally, never demote on absence.
    $resolveTagId = function($name) use ($findTagStmt, $insertTagStmt, $updateTagEnStmt, $updateTagAdultStmt, $pdo, $catalogTagNameEn, $catalogTagIsAdult) {
        $name = trim((string)$name);
        if ($name === '') return 0;
        if (mb_strlen($name) > 150) {
            $name = mb_substr($name, 0, 150);
        }
        $nameEn = isset($catalogTagNameEn[$name]) ? trim((string)$catalogTagNameEn[$name]) : '';
        if ($nameEn !== '' && mb_strlen($nameEn) > 150) {
            $nameEn = mb_substr($nameEn, 0, 150);
        }
        $isAdult = !empty($catalogTagIsAdult[$name]) ? 1 : 0;
        $findTagStmt->execute([$name]);
        $id = $findTagStmt->fetchColumn();
        $findTagStmt->closeCursor();
        if ($id !== false) {
            $id = (int)$id;
            if ($nameEn !== '') {
                $updateTagEnStmt->execute([$nameEn, $id]);
            }
            if ($isAdult === 1) {
                $updateTagAdultStmt->execute([$id]);
            }
            return $id;
        }
        try {
            $insertTagStmt->execute([$name, $nameEn !== '' ? $nameEn : null, $isAdult]);
            return (int)$pdo->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $findTagStmt->execute([$name]);
                $existingId = $findTagStmt->fetchColumn();
                $findTagStmt->closeCursor();
                if ($existingId !== false) {
                    $existingId = (int)$existingId;
                    if ($nameEn !== '') {
                        $updateTagEnStmt->execute([$nameEn, $existingId]);
                    }
                    if ($isAdult === 1) {
                        $updateTagAdultStmt->execute([$existingId]);
                    }
                    return $existingId;
                }
            }
            throw $e;
        }
    };

    // First, ensure every sentence from the global library exists
    // locally. This covers tags that no anime currently references.
    foreach ($catalogTags as $tn) {
        $resolveTagId($tn);
    }

    // Now rebuild anime_tags for every anime that arrived in this sync.
    foreach ($catalogAnimes as $ca) {
        // Find the local id for this catalog anime via the mapping
        // built during the merge loop above.
        $serverId = (int)($ca['id'] ?? 0);
        if ($serverId === 0) continue;
        $localAnimeId = $catalogIdToLocalId[$serverId] ?? null;
        if ($localAnimeId === null) continue;

        $tagNames = $ca['tags'] ?? [];
        if (!is_array($tagNames)) $tagNames = [];

        $deleteLinkStmt->execute([$localAnimeId]);
        $seen = [];
        foreach ($tagNames as $tn) {
            $tagId = $resolveTagId($tn);
            if ($tagId <= 0) continue;
            if (isset($seen[$tagId])) continue;
            $seen[$tagId] = true;
            $insertLinkStmt->execute([$localAnimeId, $tagId]);
            $stats['tags']++;
        }
    }

    // Record sync timestamp
    $pdo->prepare("
        INSERT INTO settings (name, value) VALUES ('last_catalog_sync', ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ")->execute([gmdate('Y-m-d H:i:s')]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('[anime_tracker] catalog import failed: ' . $e->getMessage());
    die('Katalog senkronizasyonu sirasinda hata olustu: ' . htmlspecialchars($e->getMessage()));
}

// --- Success redirect ---------------------------------------------------

$msg = sprintf(
    'Katalog senkronizasyonu tamamlandi: %d yeni, %d guncellendi, %d kronoloji notu, %d tur bagi, %d cumle bagi, %d poster indirildi (%d mevcut).',
    $stats['inserted'],
    $stats['updated'],
    $stats['markers'],
    $stats['genres'],
    $stats['tags'],
    $stats['poster_downloaded'],
    $stats['poster_cached']
);

if ($stats['poster_repaired'] > 0) {
    $msg .= sprintf(' %d eksik poster onarildi.', $stats['poster_repaired']);
}

if ($stats['poster_failed'] > 0) {
    $msg .= sprintf(' %d poster indirilemedi (detay icin log).', $stats['poster_failed']);
}

$movedTr = $stats['synopsis_moved_tr'] ?? 0;
$movedEn = $stats['synopsis_moved_en'] ?? 0;
if ($movedTr > 0 || $movedEn > 0) {
    $msg .= sprintf(' %d TR + %d EN konu kisisel alana tasindi.', $movedTr, $movedEn);
}

header('Location: list_settings.php?catalog_msg=' . urlencode($msg));
exit;
