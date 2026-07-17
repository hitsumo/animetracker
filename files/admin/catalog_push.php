<?php

/**
 * Anime Tracker - Server-side catalog push helper
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Pushes the local source='catalog' rows (plus chronology, tags and the
 * tag/genre English-name maps) to the central catalog server's
 * admin_push.php endpoint, server-to-server, signed with HMAC-SHA256.
 *
 * This is the AUTOMATIC equivalent of the manual admin_sync.php
 * "Push to Server" button, but callable from PHP code (e.g. right after
 * an admin approves a pending anime in admin_pending.php). Because the
 * request originates from the server process itself - not from a browser -
 * the localhost-only gate that protects admin_sync.php does not apply and
 * is not needed here: access is gated upstream by admin_pending.php
 * (require_role('admin') in multi-user mode), and the push itself is gated
 * by the shared HMAC secret.
 *
 * The wire format is byte-for-byte the same as admin_sync.php so the
 * existing admin_push.php receiver accepts it unchanged (dedup by
 * mal_id / anidb_id / catalog_uuid, idempotent upsert -> source='catalog').
 *
 * Configuration (multi-user / online instance only), in the git-ignored
 * file  admin/admin_secret.php  (same folder as this helper):
 *
 *     define('ADMIN_PUSH_SECRET', '<same value as the server ADMIN_SECRET>');
 *     define('CATALOG_PUSH_URL',  'https://animetracker.sicakcikolata.com/admin_push.php');
 *
 * CATALOG_PUSH_URL is a separate name from admin_sync.php's ADMIN_PUSH_URL
 * const on purpose, so a self-host admin_sync.php that also includes
 * admin_secret.php never hits a "constant already defined" error.
 *
 * Location: this file lives in admin/ (not functions/) on purpose. It is
 * admin/online-side only - its single caller is admin_pending.php - so it
 * belongs with the rest of the admin tooling that the build excludes from
 * the self-host .exe (ZIP blacklist). Keeping it out of functions/ also
 * keeps it out of the loader's required helper modules, which are shipped.
 *
 * 1.1.8 update: optional $animeId scopes the push. With no argument (or
 * null) it pushes the ENTIRE source='catalog' set as before. With an anime
 * id it pushes only that anime's SERIES (rows sharing its non-empty
 * series_name, or just that one anime if series_name is empty), metadata
 * only, with skip_chronology so the central chronology is never touched.
 * This keeps the per-edit auto-push cheap on a large catalog; the full push
 * (admin resync button, add_chronology_marker) still carries chronology.
 *
 * Returns an array - it NEVER throws to the caller, so a failed push can
 * never roll back or break a successful local promotion:
 *   success: ['ok' => true,  'inserted' => N, 'updated' => N,
 *             'markers' => N, 'anime_count' => N]
 *   failure: ['ok' => false, 'message' => '<reason>']
 */

if (!function_exists('catalog_push_to_server')) {

    function catalog_push_to_server(PDO $pdo, ?int $animeId = null): array
    {
        // --- Resolve configuration -------------------------------------
        // Load the git-ignored secret file only if the constants are not
        // already defined in this request.
        $secretFile = __DIR__ . '/admin_secret.php';
        $needSecret = !defined('ADMIN_PUSH_SECRET');
        $needUrl    = !defined('CATALOG_PUSH_URL') && !defined('ADMIN_PUSH_URL');
        if (($needSecret || $needUrl) && file_exists($secretFile)) {
            require_once $secretFile;
        }

        if (!defined('ADMIN_PUSH_SECRET') || strlen(ADMIN_PUSH_SECRET) < 32) {
            return ['ok' => false, 'message' => 'ADMIN_PUSH_SECRET admin/admin_secret.php icinde tanimli degil.'];
        }

        $pushUrl = '';
        if (defined('CATALOG_PUSH_URL') && CATALOG_PUSH_URL !== '') {
            $pushUrl = CATALOG_PUSH_URL;
        } elseif (defined('ADMIN_PUSH_URL') && ADMIN_PUSH_URL !== '') {
            $pushUrl = ADMIN_PUSH_URL;
        }
        if ($pushUrl === '') {
            return ['ok' => false, 'message' => 'CATALOG_PUSH_URL admin/admin_secret.php icinde tanimli degil.'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'message' => 'cURL eklentisi gerekli.'];
        }

        try {
            // --- Resolve push scope (1.1.8) ----------------------------
            // $animeId === null -> full catalog (classic behaviour).
            // $animeId given    -> scoped push: that anime's whole SERIES
            //   (catalog rows sharing its non-empty series_name), or just that
            //   one anime if series_name is empty. A scoped push carries anime
            //   metadata only and NEVER touches chronology (skip_chronology),
            //   so it stays cheap on a large catalog. Chronology stays
            //   authoritative via the full push (admin resync) and the
            //   add_chronology_marker trigger.
            $scopeIds = null; // null = full catalog
            if ($animeId !== null && $animeId > 0) {
                $snStmt = $pdo->prepare("SELECT series_name FROM animes WHERE id = ? AND source = 'catalog' LIMIT 1");
                $snStmt->execute([$animeId]);
                $snRow = $snStmt->fetch(PDO::FETCH_ASSOC);
                if ($snRow === false) {
                    // Not a catalog anime (or gone) - nothing to push.
                    return ['ok' => true, 'inserted' => 0, 'updated' => 0,
                            'markers' => 0, 'anime_count' => 0, 'marker_count' => 0,
                            'batches' => 0, 'scope' => 'none'];
                }
                $series = isset($snRow['series_name']) ? trim((string)$snRow['series_name']) : '';
                if ($series !== '') {
                    $q = $pdo->prepare("SELECT id FROM animes WHERE source = 'catalog' AND series_name = ? ORDER BY id");
                    $q->execute([$series]);
                    $scopeIds = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
                }
                if (empty($scopeIds)) {
                    $scopeIds = [(int)$animeId];
                }
            }

            // --- Gather catalog (mirror of admin_sync.php) -------------
            // Catalog-only fields. Personal data (watched_episodes,
            // watch_status, notes, next_episode_date) is never sent.
            $animeSelect = "
                SELECT id, title, alternative_titles, title_english, status,
                       total_episodes, aired_episodes,
                       synopsis_tr, synopsis_en, translation_status, release_date, end_date,
                       anidb_link, mal_link, anime_schedule_link,
                       episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
                       series_name, media_type, is_adult,
                       mal_id, anidb_id, catalog_uuid,
                       image_path
                FROM animes
                WHERE source = 'catalog'";
            if ($scopeIds === null) {
                $animeRows = $pdo->query($animeSelect . " ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $ph = implode(',', array_fill(0, count($scopeIds), '?'));
                $stmt = $pdo->prepare($animeSelect . " AND id IN ($ph) ORDER BY id");
                $stmt->execute($scopeIds);
                $animeRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Chronology markers: intentionally NO source filter (mirror of
            // admin_sync.php). The admin curates the universal chronology;
            // admin_push.php stores every pushed marker as source='catalog'.
            // 1.1.8: chronology only travels on a FULL push. A scoped push
            // sets skip_chronology, so we do not even read the markers.
            $markers = [];
            if ($scopeIds === null) {
                $markers = $pdo->query("
                    SELECT anime_id, after_episode, story_after_episode, related_anime_id, note
                    FROM chronology_markers
                    ORDER BY anime_id, after_episode
                ")->fetchAll(PDO::FETCH_ASSOC);
            }

            // Tags (recommendation sentences) + English-name map.
            $tagRows = $pdo->query("
                SELECT id, name, name_en, is_adult FROM tags ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $tagById = [];
            $tagNameEn = [];
            $tagIsAdult = [];
            foreach ($tagRows as $t) {
                $tagById[(int)$t['id']] = $t['name'];
                if (isset($t['name_en']) && $t['name_en'] !== '') {
                    $tagNameEn[$t['name']] = $t['name_en'];
                }
                // 1.1.3: adult-flag map, keyed by name like tag_name_en.
                // Only adult (1) entries travel; absence means not-adult.
                if (!empty($t['is_adult'])) {
                    $tagIsAdult[$t['name']] = 1;
                }
            }

            // 1.1.8: scope the link read to the pushed animes when scoped.
            if ($scopeIds === null) {
                $linkRows = $pdo->query("
                    SELECT anime_id, tag_id FROM anime_tags
                ")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $ph = implode(',', array_fill(0, count($scopeIds), '?'));
                $stmt = $pdo->prepare("SELECT anime_id, tag_id FROM anime_tags WHERE anime_id IN ($ph)");
                $stmt->execute($scopeIds);
                $linkRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $tagsByAnime = [];
            foreach ($linkRows as $row) {
                $aid = (int)$row['anime_id'];
                $tid = (int)$row['tag_id'];
                if (isset($tagById[$tid])) {
                    $tagsByAnime[$aid][] = $tagById[$tid];
                }
            }

            foreach ($animeRows as &$a) {
                $aid = (int)$a['id'];
                $a['tags'] = $tagsByAnime[$aid] ?? [];
            }
            unset($a);

            // Genres as CSV (wire-format the server expects).
            $genreLinkSelect = "
                SELECT ag.anime_id, g.name
                FROM anime_genres ag
                INNER JOIN genres g ON g.id = ag.genre_id";
            if ($scopeIds === null) {
                $genreLinkRows = $pdo->query($genreLinkSelect . " ORDER BY ag.anime_id, g.name")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $ph = implode(',', array_fill(0, count($scopeIds), '?'));
                $stmt = $pdo->prepare($genreLinkSelect . " WHERE ag.anime_id IN ($ph) ORDER BY ag.anime_id, g.name");
                $stmt->execute($scopeIds);
                $genreLinkRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $genresByAnime = [];
            foreach ($genreLinkRows as $row) {
                $genresByAnime[(int)$row['anime_id']][] = $row['name'];
            }

            foreach ($animeRows as &$a) {
                $aid = (int)$a['id'];
                $names = $genresByAnime[$aid] ?? [];
                $a['genres'] = implode(',', $names);
            }
            unset($a);

            // Genre English-name map (all genres, mirror of catalog.php).
            $genreNameEn = [];
            $genreIsAdult = [];
            $genreNameEnRows = $pdo->query("
                SELECT name, name_en, is_adult FROM genres ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($genreNameEnRows as $row) {
                if (isset($row['name_en']) && $row['name_en'] !== '') {
                    $genreNameEn[$row['name']] = $row['name_en'];
                }
                // 1.1.3: adult-flag map, mirror of genre_name_en. Only
                // adult (1) entries travel; absence means not-adult.
                if (!empty($row['is_adult'])) {
                    $genreIsAdult[$row['name']] = 1;
                }
            }

            // --- Batched push ------------------------------------------
            // Send the catalog in chunks so a large catalog never trips the
            // receiver's per-request caps (animes count / body bytes). The
            // receiver is upsert-only and touches ONLY animes present in each
            // body (no demote-on-absence), so splitting is safe. Chronology is
            // authoritative (server wipe+reload) and its markers reference the
            // admin's LOCAL anime IDs, so it is sent in ONE final request AFTER
            // every anime exists on the server, carrying an id_map that lets the
            // receiver translate those IDs. The wire format per request is the
            // same admin_push.php already accepts (id_map / skip_chronology are
            // additive and ignored by an old receiver).
            $CHUNK = 1000;

            // One signed request. Stamps a fresh timestamp (replay window),
            // signs, POSTs. Returns the decoded 'ok' body or throws on failure
            // (caught by the outer try -> ['ok' => false] contract preserved).
            $sendBatch = function (array $payload) use ($pushUrl): array {
                $payload['timestamp'] = time();
                $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($body === false) {
                    throw new Exception('JSON encode hatasi: ' . json_last_error_msg());
                }
                $signature = hash_hmac('sha256', $payload['timestamp'] . '|' . $body, ADMIN_PUSH_SECRET);

                $ch = curl_init($pushUrl);
                curl_setopt_array($ch, [
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => $body,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 60,
                    CURLOPT_HTTPHEADER     => [
                        'Content-Type: application/json',
                        'X-Admin-Signature: ' . $signature,
                    ],
                    CURLOPT_SSL_VERIFYPEER => true,
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr  = curl_error($ch);
                curl_close($ch);

                if ($response === false) {
                    throw new Exception('Baglanti hatasi: ' . $curlErr);
                }
                $decoded = json_decode($response, true);
                if (!is_array($decoded) || $httpCode !== 200 || ($decoded['status'] ?? '') !== 'ok') {
                    $msg = is_array($decoded)
                        ? ($decoded['message'] ?? 'bilinmeyen hata')
                        : substr((string)$response, 0, 200);
                    throw new Exception('Sunucu hatasi (HTTP ' . $httpCode . '): ' . $msg);
                }
                return $decoded;
            };

            // Taxonomy maps ride with every anime batch (small; the receiver
            // applies them idempotently).
            $taxonomy = [
                'tags'           => array_map(function ($t) { return $t['name']; }, $tagRows),
                'tag_name_en'    => $tagNameEn,
                'genre_name_en'  => $genreNameEn,
                'tag_is_adult'   => $tagIsAdult,
                'genre_is_adult' => $genreIsAdult,
            ];

            $totalInserted = 0;
            $totalUpdated  = 0;
            $totalMarkers  = 0;
            $batchCount    = 0;

            if ($scopeIds !== null) {
                // Scoped (series / single-anime) push: metadata only, chronology
                // untouched (skip_chronology). A series is tiny so this is
                // normally one request. NEVER the classic single-shot shape
                // below (which wipe+reloads chronology from an EMPTY set and
                // would delete every catalog marker).
                foreach (array_chunk($animeRows, $CHUNK) as $chunk) {
                    $decoded = $sendBatch(array_merge($taxonomy, [
                        'animes'          => $chunk,
                        'chronology'      => [],
                        'skip_chronology' => true,
                    ]));
                    $totalInserted += (int)($decoded['inserted'] ?? 0);
                    $totalUpdated  += (int)($decoded['updated']  ?? 0);
                    $batchCount++;
                }
            } elseif (count($animeRows) <= $CHUNK) {
                // Small catalog: one request, the classic single-shot shape
                // (animes + chronology together; no id_map / skip flag).
                $decoded = $sendBatch(array_merge($taxonomy, [
                    'animes'     => $animeRows,
                    'chronology' => $markers,
                ]));
                $totalInserted += (int)($decoded['inserted'] ?? 0);
                $totalUpdated  += (int)($decoded['updated']  ?? 0);
                $totalMarkers  += (int)($decoded['markers']  ?? 0);
                $batchCount++;
            } else {
                // Anime chunks first - no chronology, so they do NOT wipe the
                // server's markers (skip_chronology).
                foreach (array_chunk($animeRows, $CHUNK) as $chunk) {
                    $decoded = $sendBatch(array_merge($taxonomy, [
                        'animes'          => $chunk,
                        'chronology'      => [],
                        'skip_chronology' => true,
                    ]));
                    $totalInserted += (int)($decoded['inserted'] ?? 0);
                    $totalUpdated  += (int)($decoded['updated']  ?? 0);
                    $batchCount++;
                }

                // Final chronology batch: no animes, an id_map to translate
                // marker local IDs, and the authoritative marker set. This runs
                // the wipe+reload once, after every anime is on the server. On
                // failure here the server's markers are left untouched (the
                // anime batches did not wipe them), so a re-run recovers.
                $idMap = [];
                foreach ($animeRows as $a) {
                    $idMap[] = [
                        'id'           => (int)$a['id'],
                        'mal_id'       => $a['mal_id'],
                        'anidb_id'     => $a['anidb_id'],
                        'catalog_uuid' => $a['catalog_uuid'],
                    ];
                }
                $decoded = $sendBatch([
                    'animes'     => [],
                    'chronology' => $markers,
                    'id_map'     => $idMap,
                ]);
                $totalMarkers += (int)($decoded['markers'] ?? 0);
                $batchCount++;
            }

            return [
                'ok'           => true,
                'inserted'     => $totalInserted,
                'updated'      => $totalUpdated,
                'markers'      => $totalMarkers,
                'anime_count'  => count($animeRows),
                'marker_count' => count($markers),
                'batches'      => $batchCount,
                'scope'        => $scopeIds === null ? 'full' : 'series',
            ];

        } catch (Exception $e) {
            error_log('[catalog_push] ' . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

}
