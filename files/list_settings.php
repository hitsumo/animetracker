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
        $exportEmoStmt->execute([current_user_id(), $a['id']]);
        $a['emotions']         = $exportEmoStmt->fetchAll(PDO::FETCH_COLUMN);
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
                    release_date, end_date, series_name, media_type, suggested_by
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
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
                    if ($animeId > 0) {
                        ua_set_state($pdo, current_user_id(), $animeId, [
                            'watch_status'     => $anime['watch_status']     ?? 'PlanToWatch',
                            'watched_episodes' => $anime['watched_episodes']  ?? 0,
                            'notes'            => $anime['notes']             ?? null,
                            'user_synopsis'    => $anime['user_synopsis']     ?? null,
                            'user_synopsis_en' => $anime['user_synopsis_en']  ?? null,
                        ]);

                        setAnimeGenresByNames($pdo, $animeId, $anime['genres'] ?? []);

                        $tagIds = [];
                        foreach ((array)($anime['tags'] ?? []) as $tagName) {
                            $tid = findOrCreateTag($pdo, $tagName);
                            if ($tid > 0) { $tagIds[] = $tid; }
                        }
                        setAnimeTags($pdo, $animeId, $tagIds);

                        emotion_import_set($pdo, current_user_id(), $animeId, $anime['emotions'] ?? []);
                    }
                    $imported++;
                } catch (Exception $e) {
                    $skipped++;
                    error_log('list_settings import: row skipped - ' . $e->getMessage());
                }
            }

            if ($imported > 0) {
                $success_message = sprintf(t('list_settings.import.result'), $imported, $skipped);
            } else {
                $error_message = t('list_settings.import.invalid_format');
            }
        }
    }
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
            <div class="lang-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('lang.aria_label'), ENT_QUOTES, 'UTF-8'); ?>">
                <form method="POST" action="set_language.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="lang" value="tr">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'list_settings.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="lang-switch<?php echo current_lang() === 'tr' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.tr_label'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                <form method="POST" action="set_language.php" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="lang" value="en">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'list_settings.php', ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="lang-switch<?php echo current_lang() === 'en' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en_label'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>
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

            <div class="settings-section">
    </a>
	
		</div>

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
