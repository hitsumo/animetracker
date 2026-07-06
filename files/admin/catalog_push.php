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
 * Returns an array - it NEVER throws to the caller, so a failed push can
 * never roll back or break a successful local promotion:
 *   success: ['ok' => true,  'inserted' => N, 'updated' => N,
 *             'markers' => N, 'anime_count' => N]
 *   failure: ['ok' => false, 'message' => '<reason>']
 */

if (!function_exists('catalog_push_to_server')) {

    function catalog_push_to_server(PDO $pdo): array
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
            // --- Gather catalog (mirror of admin_sync.php) -------------
            // Catalog-only fields. Personal data (watched_episodes,
            // watch_status, notes, next_episode_date) is never sent.
            $animeRows = $pdo->query("
                SELECT id, title, alternative_titles, title_english, status,
                       total_episodes, aired_episodes,
                       synopsis_tr, synopsis_en, translation_status, release_date, end_date,
                       anidb_link, mal_link, anime_schedule_link,
                       episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
                       series_name, media_type,
                       mal_id, anidb_id, catalog_uuid,
                       image_path
                FROM animes
                WHERE source = 'catalog'
                ORDER BY id
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Chronology markers: intentionally NO source filter (mirror of
            // admin_sync.php). The admin curates the universal chronology;
            // admin_push.php stores every pushed marker as source='catalog'.
            $markers = $pdo->query("
                SELECT anime_id, after_episode, related_anime_id, note
                FROM chronology_markers
                ORDER BY anime_id, after_episode
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Tags (recommendation sentences) + English-name map.
            $tagRows = $pdo->query("
                SELECT id, name, name_en FROM tags ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $tagById = [];
            $tagNameEn = [];
            foreach ($tagRows as $t) {
                $tagById[(int)$t['id']] = $t['name'];
                if (isset($t['name_en']) && $t['name_en'] !== '') {
                    $tagNameEn[$t['name']] = $t['name_en'];
                }
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

            foreach ($animeRows as &$a) {
                $aid = (int)$a['id'];
                $a['tags'] = $tagsByAnime[$aid] ?? [];
            }
            unset($a);

            // Genres as CSV (wire-format the server expects).
            $genreLinkRows = $pdo->query("
                SELECT ag.anime_id, g.name
                FROM anime_genres ag
                INNER JOIN genres g ON g.id = ag.genre_id
                ORDER BY ag.anime_id, g.name
            ")->fetchAll(PDO::FETCH_ASSOC);

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
            $genreNameEnRows = $pdo->query("
                SELECT name, name_en FROM genres ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($genreNameEnRows as $row) {
                if (isset($row['name_en']) && $row['name_en'] !== '') {
                    $genreNameEn[$row['name']] = $row['name_en'];
                }
            }

            // --- Build payload + sign (identical to admin_sync.php) ----
            $timestamp = time();
            $payload = [
                'timestamp'     => $timestamp,
                'animes'        => $animeRows,
                'chronology'    => $markers,
                'tags'          => array_map(function ($t) { return $t['name']; }, $tagRows),
                'tag_name_en'   => $tagNameEn,
                'genre_name_en' => $genreNameEn,
            ];
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($body === false) {
                return ['ok' => false, 'message' => 'JSON encode hatasi: ' . json_last_error_msg()];
            }

            $signature = hash_hmac('sha256', $timestamp . '|' . $body, ADMIN_PUSH_SECRET);

            // --- POST server-to-server ---------------------------------
            $ch = curl_init($pushUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
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
                error_log('[catalog_push] cURL error: ' . $curlErr);
                return ['ok' => false, 'message' => 'Baglanti hatasi: ' . $curlErr];
            }

            $decoded = json_decode($response, true);
            if (!is_array($decoded) || $httpCode !== 200 || ($decoded['status'] ?? '') !== 'ok') {
                $msg = is_array($decoded)
                    ? ($decoded['message'] ?? 'bilinmeyen hata')
                    : substr((string)$response, 0, 200);
                error_log('[catalog_push] server error HTTP ' . $httpCode . ': ' . $msg);
                return ['ok' => false, 'message' => 'Sunucu hatasi (HTTP ' . $httpCode . '): ' . $msg];
            }

            return [
                'ok'          => true,
                'inserted'    => (int)($decoded['inserted'] ?? 0),
                'updated'     => (int)($decoded['updated']  ?? 0),
                'markers'     => (int)($decoded['markers']  ?? 0),
                'anime_count' => count($animeRows),
            ];

        } catch (Exception $e) {
            error_log('[catalog_push] ' . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

}
