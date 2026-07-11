<?php
/**
 /**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sümer]
 
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
 published by the Free Software Foundation.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 MA 02110-1301, USA.

*/

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// i18n - sayfa dilini baslat
lang_init($pdo);

// List Settings mixes personal items (export, title-language toggle) with
// catalog maintenance (import, clear, tag management, sync, self-update).
// Online, require a logged-in user for the page; the maintenance sections
// below carry their own moderator/admin gates (UI here + server on the
// handlers/endpoints). No-op in self-host.
require_login();

// Capability flags for UI gating (slice 3c). Both are always true in self-host
// (can() returns true when MULTI_USER_MODE is off), so the page looks exactly
// as before. Online: catalog-maintenance sections are moderator+; destructive
// / self-update sections are admin-only.
$canModerate = can($pdo, 'moderate');
$canAdmin    = can($pdo, 'admin');

// English-title preference (0.7.2) - read current state so the toggle
// section below can reflect it.
title_pref_init($pdo);

// Adult-content preference (1.1.2) - read current state so the toggle
// section below reflects it (show_adult_content()).
adult_pref_init($pdo);

// Mevcut surumu settings tablosundan al. Bu deger migration_manager tarafindan
// her sayfa yuklemesinde guncel tutuluyor. "Guncelleme Kontrolu" bolumunde
// kullaniciya hangi surumde oldugu gosterilecek.
$currentVersion = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'version'");
    $currentVersion = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec - UI'da "bilinmiyor" gosterilecek
}
if (!$currentVersion) {
    $currentVersion = t('list_settings.version.unknown');
}

// Time of the last catalog sync. If there is no row in the settings table,
// "never synced" is shown. Stored as UTC; showing it in the user's own
// timezone can be done in functions.php or inline -
// for now we just print the UTC timestamp.
$lastCatalogSync = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_catalog_sync'");
    $lastCatalogSync = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec
}

// Karar 1B: kataloga gonderilmemis (source='user') kronoloji isareti
// sayisi. Sifirdan buyukse "Katalogdan Ice Aktar" oncesi yumusak uyari
// gosterilir (buton pasif YAPILMAZ - import artik source='user'
// satirlari silmiyor, bkz catalog_import.php). Defansif: source kolonu
// henuz yoksa (0.5.3 oncesi DB, migration calismadan once) sorgu hata
// verir, sayiyi 0 kabul edip uyariyi gostermeyiz.
$unpushedUserMarkers = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM chronology_markers WHERE source = 'user'");
    $unpushedUserMarkers = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // source kolonu yok veya sorgu basarisiz - 0 kabul et, uyari gosterme
}

// Bolum sayisi (aired_episodes) son senkronizasyon zamani. Madde C ile
// eklendi. Otomatik gunluk run icin baslangic kontrolu olarak da kullanilir.
// settings tablosundaki last_aired_sync satiri hic yoksa NULL doner.
$lastAiredSync = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_aired_sync'");
    $lastAiredSync = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec
}

// catalog_import.php basarili olursa mesaji querystring ile geri yolluyor.
// Burada alip basarili alert'e ceviriyoruz.
if (isset($_GET['catalog_msg'])) {
    $success_message = $_GET['catalog_msg'];
}

// Bolum sayisi senkronizasyonu sonuc mesaji (manuel buton sonrasi).
if (isset($_GET['aired_msg'])) {
    $success_message = $_GET['aired_msg'];
}

// List Export Operation
// Asagidaki tum POST islemleri (export, import, clear) icin ortak CSRF kontrolu.
// Tek noktada yaparak ileride eklenecek POST handler'larin da otomatik
// korunmasini sagliyoruz. Mevcut catalog_import.php endpoint'i kendi CSRF
// kontrolunu kendisi yapiyor (ayri sayfa), o etkilenmez.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('list_settings.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
    }
}

// Item C - Manual "Episode Count Sync" button.
// Form'dan POST geldiginde tum ongoing animeler icin AnimeSchedule
// timetable'i sorgulanir, aired_episodes guncellenir. Sonuc mesajini
// query string ile geri donderiyoruz (klasik PRG patern: form yenileme
// sirasinda istek tekrarlanmasin).
if (isset($_POST['sync_aired'])) {
    // Catalog-wide aired-episode sync writes shared data, so restrict it to
    // moderators and above (online only; no-op in self-host).
    require_role($pdo, 'moderator');
    $stats = syncAllOngoingAiredEpisodes($pdo, 3);

    if (isset($stats['global_error'])) {
        $msg = t('list_settings.aired.cancelled_prefix') . ' ' . $stats['global_error'];
        if ($stats['global_error'] === 'no_key') {
            $msg = t('list_settings.aired.no_api_key');
        } elseif ($stats['global_error'] === 'http_429') {
            $msg = t('list_settings.aired.rate_limit');
        } elseif ($stats['global_error'] === 'http_401') {
            $msg = t('list_settings.aired.invalid_key');
        }
        header('Location: list_settings.php?aired_msg=' . urlencode($msg));
    } else {
        $parts = [
            sprintf(t('list_settings.aired.result.updated'), $stats['updated']),
            sprintf(t('list_settings.aired.result.unchanged'), $stats['unchanged']),
            sprintf(t('list_settings.aired.result.finished'), $stats['finished']),
            sprintf(t('list_settings.aired.result.not_in_table'), $stats['not_in_table']),
        ];
        if ($stats['no_slug'] > 0) {
            $parts[] = sprintf(t('list_settings.aired.result.no_slug'), $stats['no_slug']);
        }
        if ($stats['errors'] > 0) {
            $parts[] = sprintf(t('list_settings.aired.result.errors'), $stats['errors']);
        }
        $msg = implode(', ', $parts) . '.';
        header('Location: list_settings.php?aired_msg=' . urlencode($msg));
    }
    exit;
}

// Item C - Automatic daily silent sync.
// last_aired_sync timestamp'i bugune ait degilse (veya hic yoksa)
// arka planda tum ongoing animeleri senkronize et. UI'da hicbir
// gosterim olmaz (silent), kullanici sadece guncel rakamlari gorur.
//
// Karar: tetikleme list_settings.php basinda. Anasayfa (index.php)
// daha sik aciliyor ama oranin yavaslamasini istemiyoruz - liste
// ayarlari "bilincli aciliyor", kullanici 5-15 saniye beklemeyi
// kabullenir.
//
// Hata durumunda last_aired_sync GUNCELLEMEZ (helper icinde global
// hatalarda timestamp atilmaz), bu sayede bir sonraki sayfa yuklemede
// tekrar denenir. Tek tek anime hatalari ise normal kabul edilir,
// timestamp guncellenir.
//
// Karsilastirma 'Y-m-d' duzeyinde - aym gun icindeki ikinci aciliste
// tekrar calistirilmaz. UTC kullanilir cunku tum sistem UTC odakli.
$todayUtc = gmdate('Y-m-d');
$lastSyncDate = $lastAiredSync ? substr((string)$lastAiredSync, 0, 10) : null;
if ($lastSyncDate !== $todayUtc && !isset($_POST['sync_aired']) && can($pdo, 'moderate')) {
    // Sessizce arka planda. Kullaniciya duyurmak istemiyoruz cunku
    // otomatik bir sey - sayfa biraz yavas yuklenir, o kadar.
    syncAllOngoingAiredEpisodes($pdo, 3);
    // Tazelenmis timestamp'i UI gosterimi icin yeniden oku
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_aired_sync'");
        $lastAiredSync = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Sessizce gec
    }
}

if (isset($_POST['export'])) {
    $stmt = $pdo->query("SELECT * FROM animes");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Genres and tags are relational (genres/tags + anime_genres/anime_tags
    // join tables), not columns on animes, so SELECT * does not include them.
    // Attach each anime's genre and tag NAMES so the backup is complete and
    // restorable. Names (not IDs): IDs are install-specific and would not
    // match after a restore.
    // Emotions are personal (user_anime_emotion join table), like watch
    // state - SELECT * above does not include them. Fetch per anime below
    // so the export captures the current user's emotional marks too (1.0.7).
    $exportEmoStmt = $pdo->prepare(
        "SELECT emotion FROM user_anime_emotion WHERE user_id = ? AND anime_id = ? ORDER BY emotion"
    );
    // Chronology markers the user relies on have no other reliable
    // transport: in a self-host backup the catalog is not guaranteed to be
    // re-pulled on restore, so even source='catalog' markers would be lost.
    // Export EVERY marker for the host anime and carry its source, so the
    // backup is self-sufficient. Import restores the source verbatim:
    // 'catalog' markers therefore stay under catalog authority (a later
    // catalog pull may delete/replace them) while 'user' markers stay the
    // user's. The marker is nested under its host anime below, so the host
    // id is implicit; the RELATED anime is referenced by its stable
    // identity (mal_id, anidb_id, catalog_uuid, title), never the local SQL
    // id, which is install-specific (same rule the genres/tags export uses).
    $exportMarkerStmt = $pdo->prepare(
        "SELECT cm.after_episode, cm.note, cm.source,
                r.mal_id       AS related_mal_id,
                r.anidb_id     AS related_anidb_id,
                r.catalog_uuid AS related_catalog_uuid,
                r.title        AS related_title
           FROM chronology_markers cm
           JOIN animes r ON r.id = cm.related_anime_id
          WHERE cm.anime_id = ?
          ORDER BY cm.after_episode"
    );
    foreach ($animes as &$a) {
        $gRows = getAnimeGenres($pdo, $a['id']);
        $tRows = getAnimeTags($pdo, $a['id']);
        $a['genres'] = array_map(function ($r) { return $r['name']; }, $gRows);
        $a['tags']   = array_map(function ($r) { return $r['name']; }, $tRows);
        // Personal watch state lives in user_anime per user (1.0.1). Overlay
        // the current user's values so the backup captures actual progress,
        // not the vestigial animes columns (frozen at the 1.0.2 copy).
        $ua = ua_get_state($pdo, current_user_id(), $a['id']);
        $a['watch_status']     = $ua['watch_status'];
        $a['watched_episodes'] = $ua['watched_episodes'];
        $a['notes']            = $ua['notes'];
        $a['user_synopsis']    = $ua['user_synopsis'];
        $a['user_synopsis_en'] = $ua['user_synopsis_en'];
        $a['watch_start_date']  = $ua['watch_start_date'];
        $a['watch_finish_date'] = $ua['watch_finish_date'];
        $exportEmoStmt->execute([current_user_id(), $a['id']]);
        $a['emotions']         = $exportEmoStmt->fetchAll(PDO::FETCH_COLUMN);
        $exportMarkerStmt->execute([$a['id']]);
        $a['markers']          = $exportMarkerStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($a);

    // JSON formatinda disa aktar
    $filename = 'anime_list_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($animes, JSON_PRETTY_PRINT);
    exit;
}

// List Import Operation
if (isset($_POST['import']) && isset($_FILES['import_file'])) {
    if (defined('MULTI_USER_MODE') && MULTI_USER_MODE) {
        require_login();
    } else {
        require_role($pdo, 'admin');
    }

    $file = $_FILES['import_file'];

    // Upload'u DURUST hata ayrimiyla oku.
    $content   = null;
    $readError = null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $readError = sprintf(t('list_settings.import.upload_error'), (int)$file['error']);
    } else {
        $content = @file_get_contents($file['tmp_name']);
        if ($content === false) {
            $readError = t('list_settings.import.read_failed');
        }
    }

    if ($readError !== null) {
        $error_message = $readError;
    } else {
        $animes = json_decode($content, true);

        if (!is_array($animes)) {
            $error_message = t('list_settings.import.invalid_format');

        } elseif (defined('MULTI_USER_MODE') && MULTI_USER_MODE) {
            // ===== ONLINE: anime EKLEME; esle veya oneri olarak kaydet =====
            $uid = current_user_id();

            $byMal   = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
            $byAnidb = $pdo->prepare("SELECT id FROM animes WHERE anidb_id = ? LIMIT 1");

            // Dedup: ayni kullanicidan ayni anime icin bekleyen oneri var mi
            $suggExists = $pdo->prepare(
                "SELECT id FROM catalog_requests
                  WHERE suggested_by = ? AND suggestion_status = 'pending'
                    AND ( (mal_id   IS NOT NULL AND mal_id   = ?)
                       OR (anidb_id IS NOT NULL AND anidb_id = ?) )
                  LIMIT 1"
            );

            $suggInsert = $pdo->prepare("INSERT INTO catalog_requests (
                    mal_id, anidb_id, title, title_english, alternative_titles,
                    status, total_episodes, mal_link, anidb_link,
                    anime_schedule_link, episode_interval, broadcast_day,
                    broadcast_time, broadcast_timezone, synopsis_tr, synopsis_en,
                    release_date, end_date, series_name, media_type, suggested_by,
                    pending_markers
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )");

            $validStatus = ['Watched', 'Watching', 'PlanToWatch', 'OnHold', 'Dropped'];
            $applied = 0; $suggested = 0; $alreadySuggested = 0;

            foreach ($animes as $anime) {
                if (!is_array($anime)) { continue; }

                $mal   = !empty($anime['mal_id'])   ? (int)$anime['mal_id']   : null;
                $anidb = !empty($anime['anidb_id']) ? (int)$anime['anidb_id'] : null;

                $aid = null;
                if ($mal !== null)            { $byMal->execute([$mal]);     $aid = $byMal->fetchColumn(); }
                if (!$aid && $anidb !== null) { $byAnidb->execute([$anidb]); $aid = $byAnidb->fetchColumn(); }

                if ($aid) {
                    // Katalogda VAR: kisisel izleme durumunu yaz
                    $status = $anime['watch_status'] ?? 'PlanToWatch';
                    if (!in_array($status, $validStatus, true)) { $status = 'PlanToWatch'; }
                    ua_set_state($pdo, $uid, (int)$aid, [
                        'watch_status'     => $status,
                        'watched_episodes' => max(0, (int)($anime['watched_episodes'] ?? 0)),
                        'notes'            => $anime['notes']            ?? null,
                        'user_synopsis'    => $anime['user_synopsis']    ?? null,
                        'user_synopsis_en' => $anime['user_synopsis_en'] ?? null,
                        'watch_start_date'  => $anime['watch_start_date']  ?? null,
                        'watch_finish_date' => $anime['watch_finish_date'] ?? null,
                    ]);
                    emotion_import_set($pdo, $uid, (int)$aid, $anime['emotions'] ?? []);
                    $applied++;
                    continue;
                }

                // Katalogda YOK: oneri olarak kaydet (animes'e DEGIL)
                if (empty($anime['title'])) { continue; }
                $suggExists->execute([$uid, $mal, $anidb]);
                if ($suggExists->fetchColumn()) { $alreadySuggested++; continue; }

                $bstatus = $anime['status'] ?? null;
                if (!in_array($bstatus, ['Yayın Tamamlandı', 'Yayın Devam Ediyor'], true)) { $bstatus = null; }
                $mtype = $anime['media_type'] ?? null;
                if (!in_array($mtype, ['TV', 'Film', 'OVA', 'Special', 'ONA'], true)) { $mtype = null; }

                // Carry any chronology markers attached to this anime so the
                // moderator can re-link them on approval (admin_catalog_requests).
                // The related anime is referenced by stable identity (mal_id,
                // anidb_id, catalog_uuid, title), never a local SQL id, which is
                // install-specific. The carried source is dropped on purpose: a
                // moderator promoting the suggestion into the shared catalog
                // makes these catalog-authoritative (source='catalog' is written
                // at approval). NULL when there are no markers, so an anime
                // without markers stores nothing.
                $mkPayload = [];
                foreach ((array)($anime['markers'] ?? []) as $mk) {
                    if (!is_array($mk)) { continue; }
                    $mkPayload[] = [
                        'after_episode'        => max(0, (int)($mk['after_episode'] ?? 0)),
                        'note'                 => $mk['note'] ?? null,
                        'related_mal_id'       => !empty($mk['related_mal_id'])       ? (int)$mk['related_mal_id']   : null,
                        'related_anidb_id'     => !empty($mk['related_anidb_id'])     ? (int)$mk['related_anidb_id'] : null,
                        'related_catalog_uuid' => !empty($mk['related_catalog_uuid']) ? $mk['related_catalog_uuid']  : null,
                        'related_title'        => $mk['related_title'] ?? null,
                    ];
                }
                $markersJson = !empty($mkPayload)
                    ? json_encode($mkPayload, JSON_UNESCAPED_UNICODE)
                    : null;

                $suggInsert->execute([
                    $mal, $anidb,
                    $anime['title'],
                    $anime['title_english']       ?? null,
                    $anime['alternative_titles']  ?? null,
                    $bstatus,
                    $anime['total_episodes']      ?? null,
                    $anime['mal_link']            ?? null,
                    $anime['anidb_link']          ?? null,
                    $anime['anime_schedule_link'] ?? null,
                    $anime['episode_interval']    ?? 7,
                    $anime['broadcast_day']       ?? null,
                    $anime['broadcast_time']      ?? null,
                    $anime['broadcast_timezone']  ?? null,
                    $anime['synopsis_tr']         ?? ($anime['synopsis'] ?? null),
                    $anime['synopsis_en']         ?? null,
                    $anime['release_date']        ?? null,
                    $anime['end_date']            ?? null,
                    $anime['series_name']         ?? null,
                    $mtype,
                    $uid,
                    $markersJson,
                ]);
                $suggested++;
            }

            $success_message = sprintf(
                t('list_settings.import.online_result'),
                $applied, $suggested, $alreadySuggested
            );

        } else {
            // ===== SELF-HOST: tam yedek geri-yukleme (onceki davranis) =====
            $imported = 0;
            $skipped  = 0;
            // Chronology markers are deferred to a second pass: a marker's
            // related anime may not be imported yet when its host is
            // processed. Collect them here, resolve after every anime exists.
            $pendingMarkers = [];

            // Match-or-insert lookups: the catalog sync may already have filled
            // animes with the same mal_id/anidb_id/catalog_uuid, so a blind
            // INSERT would hit the UNIQUE keys and every row would be skipped.
            // Resolve an existing row first; only INSERT when there is no match.
            $matchMal   = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
            $matchAnidb = $pdo->prepare("SELECT id FROM animes WHERE anidb_id = ? LIMIT 1");
            $matchUuid  = $pdo->prepare("SELECT id FROM animes WHERE catalog_uuid = ? LIMIT 1");

            $stmt = $pdo->prepare("INSERT INTO animes (
                    title, alternative_titles, title_english, status,
                    total_episodes, aired_episodes,
                    image_path, next_episode_date,
                    anidb_link, mal_link, anime_schedule_link, episode_interval,
                    broadcast_day, broadcast_time, broadcast_timezone,
                    synopsis, synopsis_tr, synopsis_en, translation_status,
                    release_date, end_date, series_name, media_type,
                    mal_id, anidb_id, catalog_uuid, source, filler_tracking
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )");

            foreach ($animes as $anime) {
                if (!is_array($anime) || empty($anime['title'])) {
                    $skipped++;
                    continue;
                }
                try {
                    // Match-or-insert: the catalog sync may already hold this
                    // anime (same mal_id/anidb_id/catalog_uuid). Reuse the
                    // existing row when found (no UNIQUE clash); only INSERT a
                    // brand-new one. This lets import run against a populated DB.
                    $mal   = !empty($anime['mal_id'])       ? (int)$anime['mal_id']   : null;
                    $anidb = !empty($anime['anidb_id'])     ? (int)$anime['anidb_id'] : null;
                    $uuid  = !empty($anime['catalog_uuid']) ? $anime['catalog_uuid']  : null;

                    $animeId = 0;
                    if ($mal !== null)                { $matchMal->execute([$mal]);     $animeId = (int)$matchMal->fetchColumn(); }
                    if (!$animeId && $anidb !== null) { $matchAnidb->execute([$anidb]); $animeId = (int)$matchAnidb->fetchColumn(); }
                    if (!$animeId && $uuid !== null)  { $matchUuid->execute([$uuid]);   $animeId = (int)$matchUuid->fetchColumn(); }

                    if (!$animeId) {
                    $stmt->execute([
                        $anime['title'],
                        $anime['alternative_titles']  ?? null,
                        $anime['title_english']       ?? null,
                        $anime['status']              ?? 'Yayın Tamamlandı',
                        $anime['total_episodes']      ?? null,
                        $anime['aired_episodes']      ?? null,
                        $anime['image_path']          ?? null,
                        $anime['next_episode_date']   ?? null,
                        $anime['anidb_link']          ?? null,
                        $anime['mal_link']            ?? null,
                        $anime['anime_schedule_link'] ?? null,
                        $anime['episode_interval']    ?? 7,
                        $anime['broadcast_day']       ?? null,
                        $anime['broadcast_time']      ?? null,
                        $anime['broadcast_timezone']  ?? 'Asia/Tokyo',
                        $anime['synopsis']            ?? null,
                        $anime['synopsis_tr']         ?? $anime['synopsis'] ?? null,
                        $anime['synopsis_en']         ?? null,
                        $anime['translation_status']  ?? 'none',
                        $anime['release_date']        ?? null,
                        $anime['end_date']            ?? null,
                        $anime['series_name']         ?? null,
                        $anime['media_type']          ?? null,
                        $anime['mal_id']              ?? null,
                        $anime['anidb_id']            ?? null,
                        $anime['catalog_uuid']        ?? null,
                        $anime['source']              ?? 'local',
                        !empty($anime['filler_tracking']) ? 1 : 0
                    ]);
                    $animeId = (int)$pdo->lastInsertId();
                    }

                    if ($animeId > 0) {
                        ua_set_state($pdo, current_user_id(), $animeId, [
                            'watch_status'     => $anime['watch_status']     ?? 'PlanToWatch',
                            'watched_episodes' => $anime['watched_episodes']  ?? 0,
                            'notes'            => $anime['notes']             ?? null,
                            'user_synopsis'    => $anime['user_synopsis']     ?? null,
                            'user_synopsis_en' => $anime['user_synopsis_en']  ?? null,
                            'watch_start_date'  => $anime['watch_start_date']  ?? null,
                            'watch_finish_date' => $anime['watch_finish_date'] ?? null,
                        ]);

                        setAnimeGenresByNames($pdo, $animeId, $anime['genres'] ?? []);

                        $tagIds = [];
                        foreach ((array)($anime['tags'] ?? []) as $tagName) {
                            $tid = findOrCreateTag($pdo, $tagName);
                            if ($tid > 0) { $tagIds[] = $tid; }
                        }
                        setAnimeTags($pdo, $animeId, $tagIds);

                        emotion_import_set($pdo, current_user_id(), $animeId, $anime['emotions'] ?? []);

                        // Record this anime's markers for the second pass.
                        // The host id is known now; the related anime is
                        // resolved by stable identity once all rows exist.
                        foreach ((array)($anime['markers'] ?? []) as $mk) {
                            if (!is_array($mk)) { continue; }
                            // Preserve the carried source so a restored
                            // catalog marker stays catalog-managed. Whitelist
                            // it (schema enum is user|catalog); default to
                            // 'user' for older files that predate this field.
                            $mkSource = ($mk['source'] ?? 'user') === 'catalog' ? 'catalog' : 'user';
                            $pendingMarkers[] = [
                                'host'   => $animeId,
                                'after'  => max(0, (int)($mk['after_episode'] ?? 0)),
                                'note'   => $mk['note'] ?? null,
                                'source' => $mkSource,
                                'mal'    => !empty($mk['related_mal_id'])       ? (int)$mk['related_mal_id']   : null,
                                'anidb'  => !empty($mk['related_anidb_id'])     ? (int)$mk['related_anidb_id'] : null,
                                'uuid'   => !empty($mk['related_catalog_uuid']) ? $mk['related_catalog_uuid']  : null,
                                'title'  => $mk['related_title'] ?? null,
                            ];
                        }
                    }
                    $imported++;
                } catch (Exception $e) {
                    $skipped++;
                    error_log('list_settings import: row skipped - ' . $e->getMessage());
                }
            }

            // Second pass: every anime row now exists, so resolve each
            // pending marker's RELATED anime by the same stable-identity
            // chain the anime import uses (mal -> anidb -> uuid -> title)
            // and create the marker, preserving its carried source. A marker
            // needs BOTH ends present; if the related anime is absent on this
            // install the marker is skipped and counted.
            $markersLinked  = 0;
            $markersSkipped = 0;
            if (!empty($pendingMarkers)) {
                $matchTitle = $pdo->prepare("SELECT id FROM animes WHERE title = ? LIMIT 1");
                $markerIns  = $pdo->prepare(
                    "INSERT INTO chronology_markers (anime_id, after_episode, related_anime_id, note, source)
                     VALUES (?, ?, ?, ?, ?)"
                );
                foreach ($pendingMarkers as $pm) {
                    $relId = 0;
                    if ($pm['mal'] !== null)              { $matchMal->execute([$pm['mal']]);     $relId = (int)$matchMal->fetchColumn(); }
                    if (!$relId && $pm['anidb'] !== null) { $matchAnidb->execute([$pm['anidb']]); $relId = (int)$matchAnidb->fetchColumn(); }
                    if (!$relId && $pm['uuid'] !== null)  { $matchUuid->execute([$pm['uuid']]);   $relId = (int)$matchUuid->fetchColumn(); }
                    if (!$relId && !empty($pm['title']))  { $matchTitle->execute([$pm['title']]); $relId = (int)$matchTitle->fetchColumn(); }

                    // Both ends required; a self-referential marker is
                    // meaningless. Skip (and count) otherwise.
                    if ($relId <= 0 || $relId === $pm['host']) { $markersSkipped++; continue; }
                    try {
                        $markerIns->execute([$pm['host'], $pm['after'], $relId, $pm['note'], $pm['source']]);
                        $markersLinked++;
                    } catch (PDOException $e) {
                        // UNIQUE (anime_id, after_episode, related_anime_id):
                        // the marker already exists (re-import). Not an error.
                        $markersLinked++;
                    }
                }
            }

            if ($imported > 0) {
                $success_message = sprintf(t('list_settings.import.result'), $imported, $skipped);
                // Only mention markers when the file actually carried some,
                // so older backups (no markers key) show no extra clause.
                if (!empty($pendingMarkers)) {
                    $success_message .= ' ' . sprintf(
                        t('list_settings.import.markers'), $markersLinked, $markersSkipped
                    );
                }
            } else {
                $error_message = t('list_settings.import.invalid_format');
            }
        }
    }
}

// ===== MAL List Import (1.1.1) - two-step, session-backed dry-run =====
//
// Personal feature: every signed-in user imports their own MyAnimeList XML
// export into their own user_anime rows. Matches (by mal_id) are written;
// catalog misses become a pending catalog_request (online) or a local
// animes add (self-host). The preview (dry-run) is mandatory - the upload
// step writes NOTHING; it only parses, matches, and stashes the result in
// the session. The commit step reads that stash and writes.
//
// This section is intentionally NOT gated behind moderator/admin: bringing
// your own list in is a per-user action (unlike the JSON full-backup import
// above, which is an owner/restore tool). require_login() is the only gate
// (no-op in self-host).
$malPreview = null; // set below when a dry-run is ready for the HTML render

if (isset($_POST['mal_preview']) || isset($_POST['mal_commit']) || isset($_POST['mal_cancel'])) {
    require_login();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// --- Step 1: parse + match + stash a dry-run in the session -------------
if (isset($_POST['mal_preview'])) {
    $file = $_FILES['mal_file'] ?? null;
    if ($file === null || $file['error'] !== UPLOAD_ERR_OK) {
        $error_message = sprintf(t('list_settings.mal.err.upload'), (int)($file['error'] ?? -1));
    } else {
        $bytes = @file_get_contents($file['tmp_name']);
        if ($bytes === false) {
            $error_message = t('list_settings.mal.err.read');
        } else {
            $parsed = mal_parse_export($bytes);
            if (!$parsed['ok']) {
                $error_message = ($parsed['error'] === 'empty')
                    ? t('list_settings.mal.err.empty')
                    : t('list_settings.mal.err.parse');
            } else {
                // Match each entry against the catalog by mal_id and bucket
                // it: matched (in catalog, not yet in the user's list),
                // already (in catalog AND already in the user's list, skipped
                // by default), or unmatched (not in catalog).
                $uid    = current_user_id();
                $byMal  = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
                $hasRow = $pdo->prepare(
                    "SELECT 1 FROM user_anime WHERE user_id = ? AND anime_id = ? LIMIT 1"
                );

                $counts = [
                    'total'     => count($parsed['entries']),
                    'matched'   => 0,
                    'already'   => 0,
                    'unmatched' => 0,
                ];
                // Per source-status tally, powering the filter checkboxes.
                $byStatus = [];

                foreach ($parsed['entries'] as &$e) {
                    $aid = 0;
                    if ($e['mal_id'] !== null) {
                        $byMal->execute([$e['mal_id']]);
                        $aid = (int)$byMal->fetchColumn();
                    }
                    $e['anime_id'] = $aid ?: null;

                    if ($aid) {
                        $hasRow->execute([$uid, $aid]);
                        if ($hasRow->fetchColumn()) {
                            $e['bucket'] = 'already';
                            $counts['already']++;
                        } else {
                            $e['bucket'] = 'matched';
                            $counts['matched']++;
                        }
                    } else {
                        $e['bucket'] = 'unmatched';
                        $counts['unmatched']++;
                    }

                    $sk = $e['watch_status'] ?? '__unselected__';
                    $byStatus[$sk] = ($byStatus[$sk] ?? 0) + 1;
                }
                unset($e);

                $_SESSION['mal_import'] = [
                    'entries'  => $parsed['entries'],
                    'counts'   => $counts,
                    'byStatus' => $byStatus,
                    'ts'       => time(),
                ];
                $malPreview = $_SESSION['mal_import'];
            }
        }
    }
}

// --- Step 2: commit the stashed dry-run --------------------------------
if (isset($_POST['mal_commit'])) {
    if (empty($_SESSION['mal_import']['entries'])) {
        // Session expired or the user reached this step without a preview.
        $error_message = t('list_settings.mal.err.session');
    } else {
        $entries = $_SESSION['mal_import']['entries'];

        // Which source-statuses did the user keep checked? Values are our
        // enum plus the '__unselected__' sentinel (MAL rows we could not
        // map). Absent from the POST array = unchecked = skip.
        $keep = [];
        foreach ((array)($_POST['mal_status'] ?? []) as $ks) {
            $keep[$ks] = true;
        }
        $overwrite = isset($_POST['mal_overwrite']);

        $uid = current_user_id();
        $written = 0; $skipped = 0; $requested = 0;

        $byMal  = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
        $hasRow = $pdo->prepare(
            "SELECT 1 FROM user_anime WHERE user_id = ? AND anime_id = ? LIMIT 1"
        );

        if (defined('MULTI_USER_MODE') && MULTI_USER_MODE) {
            // ONLINE: matched -> user_anime; unmatched -> catalog_requests
            // (ince stub, Yol A) with per-user dedup. The shared catalog is
            // never touched here; a moderator promotes requests later.
            $suggExists = $pdo->prepare(
                "SELECT id FROM catalog_requests
                  WHERE suggested_by = ? AND suggestion_status = 'pending'
                    AND mal_id IS NOT NULL AND mal_id = ? LIMIT 1"
            );
            $suggInsert = $pdo->prepare(
                "INSERT INTO catalog_requests (mal_id, title, suggested_by) VALUES (?, ?, ?)"
            );

            foreach ($entries as $e) {
                $sk = $e['watch_status'] ?? '__unselected__';
                if (!isset($keep[$sk])) {
                    continue;
                }

                $aid = $e['anime_id'] ?? null;
                if (!$aid && $e['mal_id'] !== null) {
                    $byMal->execute([$e['mal_id']]);
                    $aid = (int)$byMal->fetchColumn();
                }

                if ($aid) {
                    $hasRow->execute([$uid, $aid]);
                    if ($hasRow->fetchColumn() && !$overwrite) {
                        $skipped++;
                        continue;
                    }
                    ua_set_state($pdo, $uid, (int)$aid, mal_ua_payload($e));
                    $written++;
                    continue;
                }

                // Unmatched: needs a mal_id (dedup key) and a title.
                if ($e['mal_id'] === null || $e['title'] === '') {
                    continue;
                }
                $suggExists->execute([$uid, $e['mal_id']]);
                if ($suggExists->fetchColumn()) {
                    continue;
                }
                $suggInsert->execute([$e['mal_id'], $e['title'], $uid]);
                $requested++;
            }
        } else {
            // SELF-HOST: matched -> user_anime; unmatched -> add a local
            // anime (source='local', title + mal_id) then write user_anime.
            // Same match-or-add shape the JSON self-host restore uses.
            $addAnime = $pdo->prepare(
                "INSERT INTO animes (title, mal_id, status, source)
                 VALUES (?, ?, 'Yayın Tamamlandı', 'local')"
            );

            foreach ($entries as $e) {
                $sk = $e['watch_status'] ?? '__unselected__';
                if (!isset($keep[$sk])) {
                    continue;
                }

                $aid = $e['anime_id'] ?? null;
                if (!$aid && $e['mal_id'] !== null) {
                    $byMal->execute([$e['mal_id']]);
                    $aid = (int)$byMal->fetchColumn();
                }

                if ($aid) {
                    $hasRow->execute([$uid, $aid]);
                    if ($hasRow->fetchColumn() && !$overwrite) {
                        $skipped++;
                        continue;
                    }
                    ua_set_state($pdo, $uid, (int)$aid, mal_ua_payload($e));
                    $written++;
                    continue;
                }

                if ($e['title'] === '') {
                    continue;
                }
                try {
                    $addAnime->execute([$e['title'], $e['mal_id']]);
                    $newId = (int)$pdo->lastInsertId();
                    if ($newId > 0) {
                        ua_set_state($pdo, $uid, $newId, mal_ua_payload($e));
                        $requested++;
                    }
                } catch (PDOException $ex) {
                    // A UNIQUE clash can happen if the same mal_id appears
                    // twice in one file; skip the duplicate quietly.
                    error_log('[anime_tracker] mal import self-host add skipped: ' . $ex->getMessage());
                }
            }
        }

        unset($_SESSION['mal_import']);
        $success_message = sprintf(
            t('list_settings.mal.result'), $written, $skipped, $requested
        );
    }
}

// Cancel: drop the stashed dry-run and fall through to a clean page.
if (isset($_POST['mal_cancel'])) {
    unset($_SESSION['mal_import']);
}

// ===== AniList List Import (1.1.6) - two-step, session-backed dry-run =====
//
// Personal feature, twin of the MAL import above: every signed-in user pulls
// their own PUBLIC AniList list into their own user_anime rows. The only
// difference from MAL is the acquisition step - instead of an uploaded file we
// fetch graphql.anilist.co by username (anilist_fetch_list). AniList hands
// back media.idMal per entry, so the matched/already/unmatched bucketing, the
// online catalog_requests path, the self-host local-add path and the commit
// writer are byte-for-byte the MAL flow (same mal_id match-or-add). Preview
// (dry-run) is mandatory - the fetch step writes NOTHING.
//
// Not gated behind moderator/admin (same reasoning as MAL): importing your own
// list is a per-user action. require_login() is the only gate (no-op self-host).
$anilistPreview = null; // set below when a dry-run is ready for the HTML render

if (isset($_POST['anilist_preview']) || isset($_POST['anilist_commit']) || isset($_POST['anilist_cancel'])) {
    require_login();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// --- Step 1: fetch + match + stash a dry-run in the session ------------
if (isset($_POST['anilist_preview'])) {
    $username = (string)($_POST['anilist_username'] ?? '');
    $fetched  = anilist_fetch_list($username);
    if (!$fetched['ok']) {
        // Map the helper's error code to a user-facing message.
        $errKeys = [
            'bad_username' => 'list_settings.anilist.err.bad_username',
            'network'      => 'list_settings.anilist.err.network',
            'rate_limit'   => 'list_settings.anilist.err.rate_limit',
            'notfound'     => 'list_settings.anilist.err.notfound',
            'http'         => 'list_settings.anilist.err.http',
            'parse'        => 'list_settings.anilist.err.parse',
            'empty'        => 'list_settings.anilist.err.empty',
        ];
        $error_message = t($errKeys[$fetched['error']] ?? 'list_settings.anilist.err.http');
    } else {
        // Match each entry against the catalog by mal_id and bucket it -
        // identical to the MAL preview (matched / already / unmatched).
        $uid    = current_user_id();
        $byMal  = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
        $hasRow = $pdo->prepare(
            "SELECT 1 FROM user_anime WHERE user_id = ? AND anime_id = ? LIMIT 1"
        );

        $counts = [
            'total'     => count($fetched['entries']),
            'matched'   => 0,
            'already'   => 0,
            'unmatched' => 0,
        ];
        $byStatus = [];

        foreach ($fetched['entries'] as &$e) {
            $aid = 0;
            if ($e['mal_id'] !== null) {
                $byMal->execute([$e['mal_id']]);
                $aid = (int)$byMal->fetchColumn();
            }
            $e['anime_id'] = $aid ?: null;

            if ($aid) {
                $hasRow->execute([$uid, $aid]);
                if ($hasRow->fetchColumn()) {
                    $e['bucket'] = 'already';
                    $counts['already']++;
                } else {
                    $e['bucket'] = 'matched';
                    $counts['matched']++;
                }
            } else {
                $e['bucket'] = 'unmatched';
                $counts['unmatched']++;
            }

            $sk = $e['watch_status'] ?? '__unselected__';
            $byStatus[$sk] = ($byStatus[$sk] ?? 0) + 1;
        }
        unset($e);

        $_SESSION['anilist_import'] = [
            'entries'  => $fetched['entries'],
            'counts'   => $counts,
            'byStatus' => $byStatus,
            'ts'       => time(),
        ];
        $anilistPreview = $_SESSION['anilist_import'];
    }
}

// --- Step 2: commit the stashed dry-run --------------------------------
if (isset($_POST['anilist_commit'])) {
    if (empty($_SESSION['anilist_import']['entries'])) {
        $error_message = t('list_settings.anilist.err.session');
    } else {
        $entries = $_SESSION['anilist_import']['entries'];

        $keep = [];
        foreach ((array)($_POST['anilist_status'] ?? []) as $ks) {
            $keep[$ks] = true;
        }
        $overwrite = isset($_POST['anilist_overwrite']);

        // 1.1.6: two import modes.
        //   'list'    (default) - bring the personal watch state (status, episodes,
        //             dates, notes) into MY user_anime rows.
        //   'content' - catalog-seed only: add the anime to the catalog/DB but write
        //             NO user_anime (no personal state). Handy for pulling a public
        //             list's titles into the catalog without inheriting its history.
        // KEY INSIGHT: 'content' is exactly "never call ua_set_state". Unmatched
        // entries already go only to catalog_requests (online) / a local animes add
        // (self-host) in BOTH modes - they never wrote user_anime anyway. The ONLY
        // per-entry difference is matched (already-in-catalog) rows: 'list' writes
        // user_anime, 'content' leaves them untouched. So the two modes share the
        // same match/add path and just gate the ua_set_state write + counters.
        // 1.1.6: default is 'content' (catalog-seed, no personal state) so an
        // accidental/malformed submit never writes someone else's watch history.
        $contentOnly = (($_POST['anilist_mode'] ?? 'content') !== 'list');

        $uid = current_user_id();
        $written = 0; $skipped = 0; $requested = 0; // list-mode tallies
        $catNew = 0; $catHave = 0;                   // content-mode tallies

        $byMal  = $pdo->prepare("SELECT id FROM animes WHERE mal_id = ? LIMIT 1");
        $hasRow = $pdo->prepare(
            "SELECT 1 FROM user_anime WHERE user_id = ? AND anime_id = ? LIMIT 1"
        );

        if (defined('MULTI_USER_MODE') && MULTI_USER_MODE) {
            // ONLINE: matched -> user_anime (list mode only); unmatched ->
            // catalog_requests (per-user dedup). Shared catalog untouched; a
            // moderator promotes requests later.
            $suggExists = $pdo->prepare(
                "SELECT id FROM catalog_requests
                  WHERE suggested_by = ? AND suggestion_status = 'pending'
                    AND mal_id IS NOT NULL AND mal_id = ? LIMIT 1"
            );
            // AniList suggestions carry the airing status too, so on approval the
            // anime is created with the real status (admin_catalog_requests uses
            // catalog_requests.status ?: 'Yayın Tamamlandı'). Without this an
            // ongoing anime would be promoted as "finished".
            $suggInsert = $pdo->prepare(
                "INSERT INTO catalog_requests (mal_id, title, status, suggested_by) VALUES (?, ?, ?, ?)"
            );

            foreach ($entries as $e) {
                $sk = $e['watch_status'] ?? '__unselected__';
                if (!isset($keep[$sk])) {
                    continue;
                }

                $aid = $e['anime_id'] ?? null;
                if (!$aid && $e['mal_id'] !== null) {
                    $byMal->execute([$e['mal_id']]);
                    $aid = (int)$byMal->fetchColumn();
                }

                if ($aid) {
                    // Already in the catalog. Content mode stops here (no personal
                    // state); list mode writes it into the user's list.
                    if ($contentOnly) {
                        $catHave++;
                        continue;
                    }
                    $hasRow->execute([$uid, $aid]);
                    if ($hasRow->fetchColumn() && !$overwrite) {
                        $skipped++;
                        continue;
                    }
                    ua_set_state($pdo, $uid, (int)$aid, anilist_ua_payload($e));
                    $written++;
                    continue;
                }

                // Unmatched: needs a mal_id (dedup key) and a title. Same catalog
                // suggestion path in both modes.
                if ($e['mal_id'] === null || $e['title'] === '') {
                    continue;
                }
                $suggExists->execute([$uid, $e['mal_id']]);
                if ($suggExists->fetchColumn()) {
                    continue;
                }
                $suggInsert->execute([
                    $e['mal_id'], $e['title'], $e['airing_status'] ?? null, $uid
                ]);
                if ($contentOnly) { $catNew++; } else { $requested++; }
            }
        } else {
            // SELF-HOST: matched -> user_anime (list mode only); unmatched -> add a
            // local anime (source='local', title + mal_id + airing status), then
            // write user_anime in list mode only. Unlike MAL, AniList gives the
            // real airing status (media.status), so a still-airing anime is not
            // forced to "Yayın Tamamlandı".
            $addAnime = $pdo->prepare(
                "INSERT INTO animes (title, mal_id, status, source)
                 VALUES (?, ?, ?, 'local')"
            );

            foreach ($entries as $e) {
                $sk = $e['watch_status'] ?? '__unselected__';
                if (!isset($keep[$sk])) {
                    continue;
                }

                $aid = $e['anime_id'] ?? null;
                if (!$aid && $e['mal_id'] !== null) {
                    $byMal->execute([$e['mal_id']]);
                    $aid = (int)$byMal->fetchColumn();
                }

                if ($aid) {
                    if ($contentOnly) {
                        $catHave++;
                        continue;
                    }
                    $hasRow->execute([$uid, $aid]);
                    if ($hasRow->fetchColumn() && !$overwrite) {
                        $skipped++;
                        continue;
                    }
                    ua_set_state($pdo, $uid, (int)$aid, anilist_ua_payload($e));
                    $written++;
                    continue;
                }

                if ($e['title'] === '') {
                    continue;
                }
                try {
                    // airing_status defaults defensively (older session stash / a
                    // rare entry AniList gave no status for) to the historical value.
                    $addAnime->execute([
                        $e['title'], $e['mal_id'], $e['airing_status'] ?? 'Yayın Tamamlandı'
                    ]);
                    $newId = (int)$pdo->lastInsertId();
                    if ($newId > 0) {
                        if ($contentOnly) {
                            $catNew++;
                        } else {
                            ua_set_state($pdo, $uid, $newId, anilist_ua_payload($e));
                            $requested++;
                        }
                    }
                } catch (PDOException $ex) {
                    // A UNIQUE clash (same mal_id twice in one list) - skip quietly.
                    error_log('[anime_tracker] anilist import self-host add skipped: ' . $ex->getMessage());
                }
            }
        }

        unset($_SESSION['anilist_import']);
        $success_message = $contentOnly
            ? sprintf(t('list_settings.anilist.result_content'), $catNew, $catHave)
            : sprintf(t('list_settings.anilist.result'), $written, $skipped, $requested);
    }
}

// Cancel: drop the stashed dry-run and fall through to a clean page.
if (isset($_POST['anilist_cancel'])) {
    unset($_SESSION['anilist_import']);
}

// List Clear Operation
if (isset($_POST['clear'])) {
    // Clearing the list runs DELETE FROM animes, which wipes the entire shared
    // catalog (and cascades to all child rows), so it is admin-only online
    // (no-op in self-host).
    require_role($pdo, 'admin');
    if (isset($_POST['confirm_clear']) && $_POST['confirm_clear'] === 'yes') {
        // DELETE, not TRUNCATE: animes is referenced by FK constraints from
        // anime_genres, anime_tags, chronology_markers, filler_episodes etc.
        // MySQL refuses TRUNCATE on an FK-referenced parent table; DELETE
        // works and ON DELETE CASCADE clears all child rows too. (The master
        // genres/tags vocabulary is kept - only the per-anime links go.)
        $pdo->exec("DELETE FROM animes");
        $success_message = t('list_settings.clear.success');
    }
}


?>

<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('list_settings.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="about.php" class="about-link"><?php echo htmlspecialchars(t('nav.about'), ENT_QUOTES, 'UTF-8'); ?></a>

            <?php echo auth_nav_links(); ?>
        </div>
        
        <div class="page-title"><?php echo htmlspecialchars(t('list_settings.heading'), ENT_QUOTES, 'UTF-8'); ?></div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Export Form -->
            <?php if ($canModerate): ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.export'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.export.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" name="export" class="settings-button">
                        <i class="fas fa-download"></i> <?php echo htmlspecialchars(t('list_settings.btn.export'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- Import Form -->
            <?php if ($canAdmin): ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.import'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.import.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="file-upload">
                        <input type="file" name="import_file" id="import_file" accept=".json" required>
                        <label for="import_file" class="file-upload-label">
                            <i class="fas fa-upload"></i> <?php echo htmlspecialchars(t('list_settings.btn.choose_file'), ENT_QUOTES, 'UTF-8'); ?>
                        </label>
                    </div>
                    <button type="submit" name="import" class="settings-button">
                        <i class="fas fa-upload"></i> <?php echo htmlspecialchars(t('list_settings.btn.import'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <!-- MAL List Import (1.1.1) - personal, no moderator/admin gate -->
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.mal_import'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.mal_import.desc'), ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if (empty($malPreview)): ?>
                    <!-- Step 1: choose file + preview -->
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="file-upload">
                            <input type="file" name="mal_file" id="mal_file" accept=".xml,.gz" required>
                            <label for="mal_file" class="file-upload-label">
                                <i class="fas fa-upload"></i> <?php echo htmlspecialchars(t('list_settings.mal.btn.choose_file'), ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        </div>
                        <button type="submit" name="mal_preview" class="settings-button">
                            <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('list_settings.mal.btn.preview'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Step 2: dry-run preview + confirm -->
                    <?php $c = $malPreview['counts']; ?>
                    <p><?php echo htmlspecialchars(sprintf(
                        t('list_settings.mal.preview.summary'),
                        (int)$c['total'], (int)$c['matched'], (int)$c['already'], (int)$c['unmatched']
                    ), ENT_QUOTES, 'UTF-8'); ?></p>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                        <fieldset class="mal-status-filter">
                            <legend><?php echo htmlspecialchars(t('list_settings.mal.preview.status_filter'), ENT_QUOTES, 'UTF-8'); ?></legend>
                            <?php foreach ($malPreview['byStatus'] as $sk => $n): ?>
                                <label style="display:block; margin:4px 0;">
                                    <input type="checkbox" name="mal_status[]" value="<?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>" checked>
                                    <?php echo htmlspecialchars(
                                        watch_status_label($sk === '__unselected__' ? null : $sk) . ' (' . (int)$n . ')',
                                        ENT_QUOTES, 'UTF-8'
                                    ); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>

                        <label style="display:block; margin:8px 0;">
                            <input type="checkbox" name="mal_overwrite" value="1">
                            <?php echo htmlspecialchars(t('list_settings.mal.preview.overwrite'), ENT_QUOTES, 'UTF-8'); ?>
                        </label>

                        <p class="mal-unmatched-note">
                            <?php echo htmlspecialchars(
                                (defined('MULTI_USER_MODE') && MULTI_USER_MODE)
                                    ? t('list_settings.mal.preview.unmatched_note.online')
                                    : t('list_settings.mal.preview.unmatched_note.selfhost'),
                                ENT_QUOTES, 'UTF-8'
                            ); ?>
                        </p>

                        <button type="submit" name="mal_commit" class="settings-button">
                            <i class="fas fa-file-import"></i> <?php echo htmlspecialchars(t('list_settings.mal.btn.commit'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <button type="submit" name="mal_cancel" class="settings-button danger" formnovalidate>
                            <i class="fas fa-times"></i> <?php echo htmlspecialchars(t('list_settings.mal.btn.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- AniList List Import (1.1.6) - personal, no moderator/admin gate -->
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.anilist_import'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.anilist_import.desc'), ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if (empty($anilistPreview)): ?>
                    <!-- Step 1: enter username + preview -->
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="anilist-username-field">
                            <label for="anilist_username"><?php echo htmlspecialchars(t('list_settings.anilist.username_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" name="anilist_username" id="anilist_username"
                                   maxlength="50" autocomplete="off" spellcheck="false"
                                   pattern="[A-Za-z0-9_-]{1,50}"
                                   placeholder="<?php echo htmlspecialchars(t('list_settings.anilist.username_placeholder'), ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <button type="submit" name="anilist_preview" class="settings-button">
                            <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('list_settings.anilist.btn.preview'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <!-- Step 2: dry-run preview + confirm -->
                    <?php $ac = $anilistPreview['counts']; ?>
                    <p><?php echo htmlspecialchars(sprintf(
                        t('list_settings.anilist.preview.summary'),
                        (int)$ac['total'], (int)$ac['matched'], (int)$ac['already'], (int)$ac['unmatched']
                    ), ENT_QUOTES, 'UTF-8'); ?></p>

                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                        <fieldset class="mal-status-filter">
                            <legend><?php echo htmlspecialchars(t('list_settings.anilist.preview.mode'), ENT_QUOTES, 'UTF-8'); ?></legend>
                            <label style="display:block; margin:4px 0;">
                                <input type="radio" name="anilist_mode" value="content" checked>
                                <?php echo htmlspecialchars(t('list_settings.anilist.preview.mode.content'), ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                            <label style="display:block; margin:4px 0;">
                                <input type="radio" name="anilist_mode" value="list">
                                <?php echo htmlspecialchars(t('list_settings.anilist.preview.mode.list'), ENT_QUOTES, 'UTF-8'); ?>
                            </label>
                        </fieldset>

                        <fieldset class="mal-status-filter">
                            <legend><?php echo htmlspecialchars(t('list_settings.anilist.preview.status_filter'), ENT_QUOTES, 'UTF-8'); ?></legend>
                            <?php foreach ($anilistPreview['byStatus'] as $sk => $n): ?>
                                <label style="display:block; margin:4px 0;">
                                    <input type="checkbox" name="anilist_status[]" value="<?php echo htmlspecialchars($sk, ENT_QUOTES, 'UTF-8'); ?>" checked>
                                    <?php echo htmlspecialchars(
                                        watch_status_label($sk === '__unselected__' ? null : $sk) . ' (' . (int)$n . ')',
                                        ENT_QUOTES, 'UTF-8'
                                    ); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>

                        <label style="display:block; margin:8px 0;">
                            <input type="checkbox" name="anilist_overwrite" value="1">
                            <?php echo htmlspecialchars(t('list_settings.anilist.preview.overwrite'), ENT_QUOTES, 'UTF-8'); ?>
                            <small style="display:block; color:#888; margin-left:24px;"><?php echo htmlspecialchars(t('list_settings.anilist.preview.overwrite_hint'), ENT_QUOTES, 'UTF-8'); ?></small>
                        </label>

                        <p class="mal-unmatched-note">
                            <?php echo htmlspecialchars(
                                (defined('MULTI_USER_MODE') && MULTI_USER_MODE)
                                    ? t('list_settings.anilist.preview.unmatched_note.online')
                                    : t('list_settings.anilist.preview.unmatched_note.selfhost'),
                                ENT_QUOTES, 'UTF-8'
                            ); ?>
                        </p>

                        <button type="submit" name="anilist_commit" class="settings-button">
                            <i class="fas fa-file-import"></i> <?php echo htmlspecialchars(t('list_settings.anilist.btn.commit'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                        <button type="submit" name="anilist_cancel" class="settings-button danger" formnovalidate>
                            <i class="fas fa-times"></i> <?php echo htmlspecialchars(t('list_settings.anilist.btn.cancel'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Liste Temizleme Formu -->
            <?php if ($canAdmin): ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.clear'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.clear.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" onsubmit="return confirmClear()">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="confirm_clear" value="yes">
                    <button type="submit" name="clear" class="settings-button danger">
                        <i class="fas fa-trash-alt"></i> <?php echo htmlspecialchars(t('list_settings.btn.clear'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
			
			

            <?php // 1.1.4 - arayuz dili secimi. Onceden her sayfanin header'indaki
                  // TR/EN switcher ile degisiyordu; artik tek yer burasi (switcher
                  // 6 sayfadan kaldirildi). set_language.php endpoint'i degismedi:
                  // ayni display_language user_pref'ini yazar. onchange auto-submit;
                  // JS kapaliysa noscript kaydet butonu gorunur. Baslik dilinden
                  // (asagidaki toggle) BAGIMSIZDIR - arayuz TR + baslik EN mumkun. ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.language'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.language.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="set_language.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <select name="lang" onchange="this.form.submit()" aria-label="<?php echo htmlspecialchars(t('list_settings.section.language'), ENT_QUOTES, 'UTF-8'); ?>">
                        <option value="tr"<?php echo current_lang() === 'tr' ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('list_settings.language.option_tr'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="en"<?php echo current_lang() === 'en' ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('list_settings.language.option_en'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                    <noscript>
                        <button type="submit" class="settings-button"><?php echo htmlspecialchars(t('list_settings.language.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </noscript>
                </form>
            </div>

            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.title_lang'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.title_lang.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="set_title_pref.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="enabled" value="0">
                    <label class="title-lang-toggle">
                        <input type="checkbox" name="enabled" value="1"<?php echo show_english_titles() ? ' checked' : ''; ?> onchange="this.form.submit()">
                        <?php echo htmlspecialchars(t('list_settings.title_lang.checkbox'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <noscript>
                        <button type="submit" class="settings-button"><?php echo htmlspecialchars(t('list_settings.title_lang.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </noscript>
                </form>
            </div>

            <?php // 1.1.2 - yetiskin (+18) icerik gorunurlugu. Varsayilan kapali;
                  // acilinca +18 damgali animeler listelerde/aramada/kesifte gorunur.
                  // Kisi bazli tercih (user_pref show_adult_content); title_lang
                  // toggle deseninin aynisi, set_adult_pref.php'ye POST eder. ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.adult'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.adult.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="set_adult_pref.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="enabled" value="0">
                    <label class="title-lang-toggle">
                        <input type="checkbox" name="enabled" value="1"<?php echo show_adult_content() ? ' checked' : ''; ?> onchange="this.form.submit()">
                        <?php echo htmlspecialchars(t('list_settings.adult.checkbox'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <noscript>
                        <button type="submit" class="settings-button"><?php echo htmlspecialchars(t('list_settings.adult.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </noscript>
                </form>
            </div>

            <?php if ($canModerate): ?>
            <div class="settings-section">
                <h3><?php echo htmlspecialchars(t('list_settings.section.genres'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('list_settings.section.genres.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <a href="manage_genres.php" class="settings-button">
                    <i class="fas fa-tags"></i> <?php echo htmlspecialchars(t('list_settings.btn.manage_genres'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ($canModerate): ?>
            <div class="settings-section">
    <h3><?php echo htmlspecialchars(t('list_settings.section.tags'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars(t('list_settings.section.tags.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    <a href="manage_tags.php" class="settings-button">
        <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars(t('list_settings.btn.manage_tags'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
	
		</div>
		
				<!-- Katalog Senkronizasyonu -->
<div class="settings-section">
    <h3><?php echo htmlspecialchars(t('list_settings.section.catalog'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars(t('list_settings.section.catalog.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    <div id="catalog-status">
        <?php if ($lastCatalogSync): ?>
            <?php echo htmlspecialchars(t('list_settings.catalog.last_sync_prefix'), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo htmlspecialchars($lastCatalogSync, ENT_QUOTES, 'UTF-8'); ?> UTC</strong>
        <?php else: ?>
            <em><?php echo htmlspecialchars(t('list_settings.catalog.never_synced'), ENT_QUOTES, 'UTF-8'); ?></em>
        <?php endif; ?>
    </div>
    <?php if ($unpushedUserMarkers > 0): ?>
    <div style="margin-top: 10px; padding: 10px; border-left: 4px solid #e6a700; background: #fff8e1; color: #5a4500; font-size: 0.92em;">
        <?php echo sprintf(t('list_settings.catalog.unpushed_warning'), (int)$unpushedUserMarkers); ?>
    </div>
    <?php endif; ?>
    <form method="post" action="catalog_import.php" onsubmit="return confirmCatalogSync()" style="margin-top: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="settings-button">
            <i class="fas fa-cloud-download-alt"></i> <?php echo htmlspecialchars(t('list_settings.btn.catalog_import'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </form>
</div>

	<!-- Bolum Sayisi Senkronizasyonu (Madde C) -->
<div class="settings-section">
    <h3><?php echo htmlspecialchars(t('list_settings.section.aired'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars(t('list_settings.section.aired.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    <div id="aired-status">
        <?php if ($lastAiredSync): ?>
            <?php echo htmlspecialchars(t('list_settings.catalog.last_sync_prefix'), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo htmlspecialchars($lastAiredSync, ENT_QUOTES, 'UTF-8'); ?> UTC</strong>
        <?php else: ?>
            <em><?php echo htmlspecialchars(t('list_settings.catalog.never_synced'), ENT_QUOTES, 'UTF-8'); ?></em>
        <?php endif; ?>
    </div>
    <form method="post" action="list_settings.php" style="margin-top: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="sync_aired" value="1">
        <button type="submit" class="settings-button">
            <i class="fas fa-sync"></i> <?php echo htmlspecialchars(t('list_settings.btn.sync_now'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </form>
</div>
            <?php endif; ?>

	<!-- Add this to the settings-container div in list_settings.php -->
            <?php /* Self-host only: the ZIP auto-update channel is the self-host
                     package. Online installs update via git/Docker, so the whole
                     update section is hidden in multi-user mode (update.php also
                     refuses server-side). */ ?>
            <?php if ($canAdmin && !MULTI_USER_MODE): ?>
<div class="settings-section">
    <h3><?php echo htmlspecialchars(t('list_settings.section.update'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo htmlspecialchars(t('list_settings.section.update.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
    <div id="update-status"><?php echo htmlspecialchars(t('list_settings.update.current_version'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8'); ?></div>
    <button onclick="checkUpdate()" class="settings-button">
        <i class="fas fa-sync"></i> <?php echo htmlspecialchars(t('list_settings.btn.check_update'), ENT_QUOTES, 'UTF-8'); ?>
    </button>
    <input type="hidden" id="update-csrf-token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
</div>
            <?php elseif ($canAdmin && MULTI_USER_MODE): ?>
            <?php /* Multi-user (online): the ZIP auto-update channel is self-host
                     only. Instead of an update button, show the source repo link
                     so the operator knows online updates come via git/Docker. */ ?>
<div class="settings-section">
    <h3><?php echo htmlspecialchars(t('list_settings.section.update'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <div id="update-status"><?php echo htmlspecialchars(t('list_settings.update.current_version'), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8'); ?></strong></div>
    <p><?php echo htmlspecialchars(t('list_settings.update.online_note'), ENT_QUOTES, 'UTF-8'); ?></p>
    <a href="https://github.com/hitsumo/animetracker" target="_blank" rel="noopener noreferrer" class="settings-button">
        <i class="fab fa-github"></i> <?php echo htmlspecialchars(t('list_settings.update.github_link'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
</div>
            <?php endif; ?>
        </div>


        <div class="button-container">
            <a href="index.php" class="anime-list-button"><?php echo htmlspecialchars(t('list_settings.back_to_list'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>

    <script>
    const LANG = <?php echo json_encode([
        'confirm_clear'              => t('list_settings.js.confirm_clear'),
        'confirm_sync_intro'         => t('list_settings.js.confirm_sync_intro'),
        'confirm_sync_safe'          => t('list_settings.js.confirm_sync_safe'),
        'confirm_sync_overwrite'     => t('list_settings.js.confirm_sync_overwrite'),
        'confirm_sync_unpushed'      => t('list_settings.js.confirm_sync_unpushed'),
        'confirm_continue'           => t('list_settings.js.confirm_continue'),
        'checking'                   => t('list_settings.js.checking'),
        'update_error'               => t('list_settings.js.update_error'),
        'current_version'            => t('list_settings.update.current_version'),
        'new_version_label'          => t('list_settings.js.new_version_label'),
        'up_to_date_suffix'          => t('list_settings.js.up_to_date_suffix'),
        'confirm_install'            => t('list_settings.js.confirm_install'),
        'network_error'              => t('list_settings.js.network_error'),
        'installing'                 => t('list_settings.js.installing'),
        'installing_note'            => t('list_settings.js.installing_note'),
        'install_failed'             => t('list_settings.js.install_failed'),
        'install_failed_alert'       => t('list_settings.js.install_failed_alert'),
        'unknown_error'              => t('list_settings.js.unknown_error'),
        'install_success'            => t('list_settings.js.install_success'),
        'install_previous'           => t('list_settings.js.install_previous'),
        'install_new'                => t('list_settings.js.install_new'),
        'reloading'                  => t('list_settings.js.reloading'),
        'install_network_error'      => t('list_settings.js.install_network_error'),
        'install_network_error_alert' => t('list_settings.js.install_network_error_alert'),
    ], JSON_UNESCAPED_UNICODE); ?>;

    function confirmClear() {
        return confirm(LANG.confirm_clear);
		
		
		
    }

    function confirmCatalogSync() {
        var unpushedMarkers = <?php echo (int)$unpushedUserMarkers; ?>;
        var msg =
            LANG.confirm_sync_intro + "\n\n" +
            LANG.confirm_sync_safe + "\n" +
            LANG.confirm_sync_overwrite + "\n\n";
        if (unpushedMarkers > 0) {
            msg += LANG.confirm_sync_unpushed.replace('%d', unpushedMarkers) + "\n\n";
        }
        msg += LANG.confirm_continue;
        return confirm(msg);
    }
	
function checkUpdate() {
    const statusDiv = document.getElementById('update-status');
    const originalText = statusDiv.innerHTML;
    statusDiv.innerHTML = '<em>' + LANG.checking + '</em>';

    fetch('check_update.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                statusDiv.innerHTML = originalText;
                alert(data.message || LANG.update_error);
                return;
            }

            if (data.needs_update) {
                // Yeni versiyon var - bilgi goster
                statusDiv.innerHTML =
                    LANG.current_version + ' <strong>' + data.current_version + '</strong><br>' +
                    LANG.new_version_label + ' <strong>' + data.latest_version + '</strong>';

                // Kullaniciya onay sor. Onaylarsa runUpdate() WordPress tarzi
                // in-place update yapiyor - hicbir .exe indirmeye veya manuel
                // adima gerek yok.
                if (confirm(LANG.confirm_install.replace('%s', data.latest_version))) {
                    runUpdate();
                }
            } else {
                // Sistem guncel
                statusDiv.innerHTML = LANG.current_version + ' <strong>' + data.current_version + '</strong> ' + LANG.up_to_date_suffix;
            }
        })
        .catch(error => {
            statusDiv.innerHTML = originalText;
            alert(LANG.network_error + ' ' + error);
        });
}

function runUpdate() {
    const statusDiv = document.getElementById('update-status');
    const csrfToken = document.getElementById('update-csrf-token').value;

    // Kullaniciyi bilgilendirerek sureci belirginlestir. Guncelleme birkac
    // saniye surebilir (indirme + extract + kopyalama + migration).
    statusDiv.innerHTML =
        '<em>' + LANG.installing + '</em><br>' +
        '<small>' + LANG.installing_note + '</small>';

    // POST istegi ile update.php cagriliyor. CSRF token gerekli.
    const formData = new URLSearchParams();
    formData.append('csrf_token', csrfToken);

    fetch('update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString(),
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Update basarisiz - hatayi goster, sayfayi yenileme
            statusDiv.innerHTML = '<strong style="color:#d32f2f;">' + LANG.install_failed + '</strong>';
            alert(LANG.install_failed_alert + ' ' + (data.message || LANG.unknown_error));
            return;
        }

        if (data.already_latest) {
            // Arada biri baska bir sekmeden zaten guncellemis olabilir
            statusDiv.innerHTML = LANG.current_version + ' <strong>' + data.message + '</strong>';
            return;
        }

        // Basarili - yeni versiyon bilgisini goster ve sayfayi yenile
        statusDiv.innerHTML =
            '<strong style="color:#2e7d32;">' + LANG.install_success + '</strong><br>' +
            LANG.install_previous + ' ' + data.previous_version + '<br>' +
            LANG.install_new + ' <strong>' + data.new_version + '</strong><br>' +
            '<small>' + LANG.reloading + '</small>';

        // Kisa bir gecikme ile sayfayi yenile ki kullanici basari
        // mesajini gorebilsin
        setTimeout(function() {
            window.location.reload();
        }, 2000);
    })
    .catch(error => {
        statusDiv.innerHTML = '<strong style="color:#d32f2f;">' + LANG.install_network_error + '</strong>';
        alert(LANG.install_network_error_alert + ' ' + error);
    });
}
    </script>

    <style>
    .settings-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    .settings-section {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .settings-section h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .settings-section p {
        color: #666;
        margin-bottom: 15px;
    }

    .settings-button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .settings-button:hover {
        background-color: #0056b3;
    }

    .settings-button.danger {
        background-color: #dc3545;
    }

    .settings-button.danger:hover {
        background-color: #c82333;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        text-align: center;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .file-upload {
        margin-bottom: 15px;
    }
    </style>
</body>
</html>
