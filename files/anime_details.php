<?php

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

// Initialise the i18n layer (see lang_init() in functions.php).
lang_init($pdo);

// English-title display preference (0.7.2). Read once so display_title()
// picks the right title for the heading, image alt and page <title>.
title_pref_init($pdo);

// Adult-content visibility preference (1.1.2). Read here so the +18 gate
// below can decide whether to show this page or a neutral notice.
adult_pref_init($pdo);

$id = $_GET['id'];
$sql = "SELECT * FROM animes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

$anidb_safe = safe_url($anime['anidb_link'] ?? '');
            $mal_safe   = safe_url($anime['mal_link']   ?? '');
			$schedule_safe = safe_url($anime['anime_schedule_link'] ?? '');

if (!$anime) {
    echo htmlspecialchars(t('anime_details.error.not_found'));
    exit();
}

// 1.1.2 - yetiskin (+18) icerik kapisi. Anime +18 damgaliysa VE izleyici
// "yetiskin icerigi goster" tercihini acmamissa (varsayilan kapali), detayi
// sizdirmak yerine notr bir uyari gosterip cikariz. 404 degil (varlik
// gizlenmez), icerik sizmaz; kullaniciya nasil acacagi soylenir. Moderator/
// admin de gormek icin kendi tercihini acar (tercih kisi bazlidir).
if (!empty($anime['is_adult']) && !show_adult_content()) {
    echo htmlspecialchars(t('anime_details.adult.hidden'), ENT_QUOTES, 'UTF-8');
    exit();
}

if (!empty($anime['next_episode_date'])) {
    updateNextEpisodeDate($pdo, $anime);
    $stmt->execute([$id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Personal watch state lives in user_anime per user (1.0.1). Overlay the
// current user's values onto the catalog row AFTER any re-fetch above, so
// everything below (chronology alert, checkIfAnimeCompleted, render,
// dil-ozel Kisisel Konu) reads the right source. ua_get_state returns
// defaults if this user has no row yet.
$uaState = ua_get_state($pdo, current_user_id(), $id);
$anime['watch_status']     = $uaState['watch_status'];
$anime['watched_episodes'] = $uaState['watched_episodes'];
$anime['notes']            = $uaState['notes'];
$anime['user_synopsis']    = $uaState['user_synopsis'];
$anime['user_synopsis_en'] = $uaState['user_synopsis_en'];
$anime['watch_start_date']  = $uaState['watch_start_date'];
$anime['watch_finish_date'] = $uaState['watch_finish_date'];

// Anime tamamlanmis mi kontrol et
checkIfAnimeCompleted($pdo, $anime);

// Series relationship data
$relatedAnimes = getRelatedAnimes($pdo, $anime['series_name'] ?? null, $anime['id']);
$chronologyMarkers = getChronologyMarkers($pdo, $anime['id']);
$chronologyAlert = getActiveChronologyAlert($pdo, $anime['id'], $anime['watched_episodes']);

// Chronology markers are shared catalog structure: only a moderator+ may add
// or remove them. KORU is enforced server-side in add_chronology_marker.php
// and delete_chronology_marker.php via require_role('moderator'). Gizle: hide
// the add and delete controls from members who cannot moderate, so they never
// see a form the endpoint would reject. In self-host can() is always true
// (owner), so the owner's view is unchanged. The read-only marker list stays
// visible to everyone (detail viewing is free).
$canModerate = can($pdo, 'moderate');

// Siradaki anime bilgisi (next_in_series foreign key)
$nextAnime = null;
if (!empty($anime['next_in_series'])) {
    // watch_status is personal (user_anime, 1.0.1) - join the current
    // user's row so the "next in series" card shows their progress.
    $nextStmt = $pdo->prepare(
        "SELECT a.id, a.title, a.title_english,
                ua.watch_status,
                a.media_type, a.image_path, a.is_adult
         FROM animes a
         LEFT JOIN user_anime ua
                ON ua.anime_id = a.id AND ua.user_id = :uid
         WHERE a.id = :id"
    );
    $nextStmt->execute([
        ':uid' => current_user_id(),
        ':id'  => (int)$anime['next_in_series'],
    ]);
    $nextAnime = $nextStmt->fetch(PDO::FETCH_ASSOC);
    // 1.1.2 - sirali seri iliskisi: sonraki anime +18 ise basligini notr yer
    // tutucuyla maskele (kart kalir, baslik sizmaz; link gated detaya gider).
    if ($nextAnime) {
        $nextAnime = adult_mask_related($nextAnime, 'is_adult', 'title', 'title_english');
    }
}

// Check if this anime is part of a next_in_series chain (either it
// points forward or another anime points to it). Used to show the
// "Seri Kronolojisi" button.
$isInSeriesChain = !empty($anime['next_in_series']);
if (!$isInSeriesChain) {
    $chainCheck = $pdo->prepare("SELECT COUNT(*) FROM animes WHERE next_in_series = ?");
    $chainCheck->execute([(int)$anime['id']]);
    $isInSeriesChain = ((int)$chainCheck->fetchColumn() > 0);
    $chainCheck->closeCursor();
}

// Ayni serideki tum animeler (marker ekleme formu dropdown'u icin)
$sameSeriesAnimes = [];
if (!empty($anime['series_name'])) {
    $ssStmt = $pdo->prepare("SELECT id, title, title_english, media_type FROM animes a WHERE a.series_name = ? AND a.id != ?" . adult_filter_where('a') . " ORDER BY a.title ASC");
    $ssStmt->execute([$anime['series_name'], (int)$anime['id']]);
    $sameSeriesAnimes = $ssStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 0.6.1 - Emotion tags. Load the current user's emotion marks for this
// anime, scoped via current_user_id() (1.0.x data model): single-user mode
// returns 1 (behaviour unchanged), multi-user mode returns the session user.
$emoStmt = $pdo->prepare(
    "SELECT emotion FROM user_anime_emotion
      WHERE user_id = ? AND anime_id = ?"
);
$emoStmt->execute([current_user_id(), (int)$anime['id']]);
$currentEmotions = $emoStmt->fetchAll(PDO::FETCH_COLUMN, 0);
$emoStmt->closeCursor();

// 0.7 - Filler bolum izleme (salt-okunur ozet).
// filler_tracking acik ise bu anime'nin filler kayitlarini yukle ve
// kompakt ozet uret (filler_summary). Kapali ise hic yukleme yapma -
// detay sayfasinda filler satiri da gosterilmez. Filler katalog-seviyesi
// veri (anime'ye bagli), user-scope DEGIL - emotion'dan farkli olarak
// burada user_id yoktur. KARARLAR Bolum 8.
$fillerTracking = !empty($anime['filler_tracking']);
$fillerSummary = '';
if ($fillerTracking) {
    $flStmt = $pdo->prepare(
        "SELECT episode_no, type FROM filler_episodes
          WHERE anime_id = ? ORDER BY episode_no"
    );
    $flStmt->execute([(int)$anime['id']]);
    $fillerRows = $flStmt->fetchAll(PDO::FETCH_ASSOC);
    $flStmt->closeCursor();
    $fillerSummary = filler_count_summary($fillerRows);
}
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(display_title($anime)); ?> - <?php echo htmlspecialchars(t('anime_details.title_suffix'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <?php // SECTION: Language switcher (snippet copy - see _lang_switcher_reference.php) ?>
            <?php echo auth_nav_links(); ?>
        </div>
        <h1>
            <div class="anime-title-container">
                <div class="anime-title page-title">
                    <?php echo htmlspecialchars(display_title($anime)); ?>
                </div>
            </div>
        </h1>
        
        <div class="anime-header">
            <div class="anime-cover-container">
                <img src="<?php echo htmlspecialchars(poster_src($anime['image_path'])); ?>"
                    alt="<?php echo htmlspecialchars(display_title($anime)); ?>"
                    class="anime-cover">
            </div>
        </div>

        <div class="anime-details-container">
            <div class="anime-details">
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.status'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value status"><?php echo htmlspecialchars(broadcast_status_label($anime['status'])); ?></span>
                </div>

               

                <div class="detail-row">
    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.total_episodes'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="detail-value episode"><?php
        // v0.5+: total_episodes can be NULL for ongoing anime with
        // unknown final episode count (One Piece, Detective Conan).
        if (!empty($anime['total_episodes'])) {
            echo htmlspecialchars($anime['total_episodes']);
        } else {
            echo '<em>' . htmlspecialchars(t('anime_details.label.unknown')) . '</em>';
        }
    ?></span>
</div>

<?php if (!empty($anime['aired_episodes'])): ?>
<div class="detail-row">
    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.aired_episodes'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="detail-value episode"><?php echo htmlspecialchars($anime['aired_episodes']); ?></span>
</div>
<?php endif; ?>

<!-- Yayin tarihi -->
<div class="detail-row">
    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.release_date'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="detail-value">
        <?php 
        if (!empty($anime['release_date'])) {
            echo date('d.m.Y', strtotime($anime['release_date']));
        } else {
            echo htmlspecialchars(t('anime_details.label.unset'));
        }
        ?>
    </span>
</div>
<?php
// Madde E - Tek bolumlu animede yayin bitis tarihi anlamsiz (baslangic = bitis).
// Status finished AND end_date dolu AND total_episodes 1 degil ise goster.
if ($anime['status'] == 'Yayın Tamamlandı'
    && !empty($anime['end_date'])
    && (int)($anime['total_episodes'] ?? 0) !== 1):
?>
<div class="detail-row">
    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.end_date'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="detail-value">
        <?php echo date('d.m.Y', strtotime($anime['end_date'])); ?>
    </span>
</div>
<?php endif; ?>
<?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
<div class="detail-row" style="margin-top: -8px;">
    <span class="detail-label"></span>
    <span class="detail-value" style="font-size: 11px; color: #6c757d; font-style: italic;">
        <?php
        // The label has a "%s" placeholder for the AnimeSchedule link.
        // We build the HTML link first, then substitute it - the result
        // contains the user's chosen translation around safe HTML.
        $schedule_link_html = '<a href="' . ($schedule_safe ?: 'https://animeschedule.net') . '" target="_blank" rel="noopener noreferrer" style="color: #6c757d; text-decoration: underline;">AnimeSchedule</a>';
        echo sprintf(t('anime_details.label.broadcast_attribution'), $schedule_link_html);
        ?>
    </span>
</div>
<?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.watched_episodes'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value episode"><?php echo htmlspecialchars($anime['watched_episodes']); ?></span>
                </div>

                <?php
                // Synopsis display (0.7.3 - behaviour C): show the CATALOG
                // synopsis on top (language-aware), and the personal synopsis
                // of the active language BELOW it as a separate row when
                // present. Personal does NOT replace catalog - it is shown in
                // addition, so the curator's official summary stays visible
                // alongside the user's own note. The legacy single synopsis
                // column is not read.
                $curLang     = current_lang();
                $synTr       = $anime['synopsis_tr'] ?? '';
                $synEn       = $anime['synopsis_en'] ?? '';
                $uSynTr      = $anime['user_synopsis'] ?? '';
                $uSynEn      = $anime['user_synopsis_en'] ?? '';
                $transStatus = $anime['translation_status'] ?? 'none';
                // Catalog text for the active language (EN falls back to TR).
                if ($curLang === 'en') {
                    $showSyn    = ($synEn !== '') ? $synEn : $synTr;
                    $enLabeled  = ($synEn !== '');
                    $enFallback = ($synEn === '' && $synTr !== '');
                    $personalSyn = $uSynEn;   // active-language personal text
                } else {
                    $showSyn    = $synTr;
                    $enLabeled  = false;
                    $enFallback = false;
                    $personalSyn = $uSynTr;
                }
                $hasPersonal = ($personalSyn !== '' && $personalSyn !== null);
                ?>
                <?php if (!empty($showSyn)): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.synopsis'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="detail-value synopsis">
                        <?php echo nl2br(htmlspecialchars($showSyn)); ?>
                        <?php if ($enLabeled): ?>
                        <span class="synopsis-meta">
                            <span class="synopsis-status synopsis-status-<?php echo htmlspecialchars($transStatus, ENT_QUOTES, 'UTF-8'); ?>"></span>
                            <small><em><a href="help/help_discovery.php#translation" class="translation-note"><?php echo htmlspecialchars(t('anime_details.synopsis.auto_translated'), ENT_QUOTES, 'UTF-8'); ?></a></em></small>
                        </span>
                        <?php elseif ($enFallback): ?>
                        <span class="synopsis-meta"><small><em><?php echo htmlspecialchars(t('anime_details.synopsis.en_unavailable'), ENT_QUOTES, 'UTF-8'); ?></em></small></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php /* 0.7.3 (behaviour C): personal synopsis of the active
                   language shown BELOW the catalog synopsis, as a separate
                   row, when present. */ ?>
                <?php if ($hasPersonal): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.user_synopsis'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value synopsis"><?php echo nl2br(htmlspecialchars($personalSyn)); ?></span>
                </div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.genres'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="detail-value genres">
                        <?php
                        // Genres come from the anime_genres join table.
                        // Helper returns rows with id and name; trim is no
                        // longer needed because the names are stored
                        // canonically in the genres table.
                        $genre_rows = getAnimeGenres($pdo, $anime['id']);
                        // 1.1.3: silently omit adult-flagged genre badges when
                        // adult content is off (Method A - term hidden, anime not).
                        $genre_rows = adult_filter_terms($genre_rows);
                        foreach ($genre_rows as $genre_row): ?>
                            <span class="genre-tag"><?php echo htmlspecialchars(genre_display_name($genre_row)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.watch_status'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo watch_status_css_class($anime['watch_status']); ?>">
                            <?php echo htmlspecialchars(watch_status_label($anime['watch_status'])); ?>
                        </span>
                    </span>
                </div>

                <?php /* 1.1.0: kisisel izleme tarihleri, sadece dolu ise gosterilir. */ ?>
                <?php if (!empty($anime['watch_start_date'])): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.watch_start_date'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value"><?php echo htmlspecialchars($anime['watch_start_date']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($anime['watch_finish_date'])): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.watch_finish_date'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value"><?php echo htmlspecialchars($anime['watch_finish_date']); ?></span>
                </div>
                <?php endif; ?>

                <!-- 0.6.1 - Duygu Etiketleri (single-user). Kullanici bu
                     animeye en fazla 3 duygu isareti koyabilir. Tikla =
                     toggle (varsa kaldir, yoksa ekle); 3'e ulasinca diger
                     pasif butonlar disabled olur. Sunucu tarafi update_emotion.php
                     ayni siniri zorlar (UI bypass edilirse sunucu reddeder).
                     KARARLAR Bolum 8 v1 spec. -->
                <div class="detail-row emotion-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.emotion'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="detail-value">
                        <div class="emotion-toolbar"
                             data-anime-id="<?php echo (int)$anime['id']; ?>"
                             data-csrf="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <?php foreach (emotion_options() as $value => $label):
                                $isActive = in_array($value, $currentEmotions, true);
                                $atMax    = (count($currentEmotions) >= 3 && !$isActive);
                            ?>
                                <button type="button"
                                        class="emotion-btn emotion-btn-<?php echo emotion_css_class($value); ?><?php echo $isActive ? ' is-active' : ''; ?>"
                                        data-emotion="<?php echo htmlspecialchars($value); ?>"
                                        <?php echo $atMax ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                            <?php endforeach; ?>
                            <span class="emotion-toolbar-meta">
                                <span class="emotion-count"><?php echo count($currentEmotions); ?></span>/3
                            </span>
                        </div>
                    </div>
                </div>

                <?php // 0.7 - Filler ozet satiri. filler_tracking acikken
                      // gosterilir; ozet metni sadece veri varsa (empty-state:
                      // hic isaret yoksa metin yerine "henuz isaretlenmedi",
                      // ama Duzenle butonu editore girisi her zaman acik
                      // tutar). filler_tracking kapaliysa satir hic cikmaz.
                      // KARARLAR Bolum 8. ?>
                <?php if ($fillerTracking): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.filler'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value">
                        <?php if ($fillerSummary !== ''): ?>
                            <span class="filler-summary"><?php echo htmlspecialchars($fillerSummary, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php else: ?>
                            <span class="filler-summary filler-summary-empty"><?php echo htmlspecialchars(t('anime_details.filler_empty'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <a href="filler_edit.php?id=<?php echo (int)$anime['id']; ?>" class="filler-edit-link">
                            <i class="fas fa-edit"></i> <?php echo htmlspecialchars(t('anime_details.btn.filler_edit'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>

                <?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
                <div class="broadcast-info">
                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.broadcast_day'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value"><?php echo htmlspecialchars(!empty($anime['broadcast_day']) ? $anime['broadcast_day'] : t('anime_details.label.unset')); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.broadcast_time'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value broadcast-time"><?php echo htmlspecialchars(!empty($anime['broadcast_time']) ? substr($anime['broadcast_time'], 0, 5) : t('anime_details.label.unset')); ?></span>
                    </div>

     <div class="detail-row">
    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.next_episode'), ENT_QUOTES, 'UTF-8'); ?></span>
    <span class="detail-value next-episode">
        <?php echo getTimeUntilNextEpisode($anime['next_episode_date'], $anime['watched_episodes'], $anime['total_episodes'] ?? 0, $anime['aired_episodes'] ?? 0); ?>
    </span>
</div>

<?php if (!empty($chronologyMarkers)): ?>
<div class="detail-row">
    <span class="detail-label"></span>
    <span class="detail-value">
        <a href="chronology.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button">
            <i class="fas fa-stream"></i> <?php echo htmlspecialchars(t('anime_details.btn.chronology'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </span>
</div>
<?php endif; ?>

                    <?php if (!empty($anime['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.notes'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($anime['notes'])); ?></span>
                </div>
                <?php endif; ?>


                </div>
                <?php endif; ?>

                <?php // Yayin Tamamlandi durumunda broadcast-info yok, kronoloji
                      // butonunu burada goster (devam eden animede zaten broadcast-info
                      // icinde gosteriliyor)
                ?>
                <?php if ($anime['status'] != 'Yayın Devam Ediyor' && !empty($chronologyMarkers)): ?>
                <div class="detail-row" style="margin-top: 10px;">
                    <a href="chronology.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button">
                        <i class="fas fa-stream"></i> <?php echo htmlspecialchars(t('anime_details.btn.chronology'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <?php
            // Pre-compute safe URLs once. safe_url() returns empty string
            // for dangerous schemes (javascript:, data:, etc.) and the result
            // is already htmlspecialchars-encoded for attribute context.
            
            ?>
            <?php if ($anidb_safe || $mal_safe || true): ?>
            <div class="external-links">
                <h3><?php echo htmlspecialchars(t('anime_details.section.external_sites'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if ($anidb_safe): ?>
                <a href="<?php echo $anidb_safe; ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="site-link anidb-link">
                    <i class="fas fa-database"></i> AniDB
                </a>
                <?php endif; ?>
                
                <?php if ($mal_safe): ?>
                <a href="<?php echo $mal_safe; ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="site-link mal-link">
                    <i class="fas fa-list"></i> MyAnimeList
                </a>
				<a href="<?php echo $schedule_safe ?: 'https://animeschedule.net'; ?>"
   target="_blank" rel="noopener noreferrer"
   class="site-link schedule-link">
    <i class="fas fa-calendar-alt"></i> AnimeSchedule
</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Kronoloji Uyarisi
                  // Kullanicinin izleme ilerlemesi bir kronoloji marker'ina
                  // denk geliyorsa, "bu bolumden sonra sunu izle" uyarisi goster.
                  // ============================================================
            ?>
            <?php if ($chronologyAlert): ?>
            <div class="chronology-alert">
                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="alert-content">
                    <strong><?php echo htmlspecialchars(sprintf(t('anime_details.alert.watch_after'), (int)$chronologyAlert['after_episode'])); ?></strong>
                    <a href="anime_details.php?id=<?php echo (int)$chronologyAlert['related_id']; ?>" class="alert-anime-link">
                        <?php echo htmlspecialchars(display_related_title($chronologyAlert)); ?>
                        <?php if (!empty($chronologyAlert['related_media_type'])): ?>
                            (<?php echo htmlspecialchars($chronologyAlert['related_media_type']); ?>)
                        <?php endif; ?>
                    </a>
                    <span class="alert-watch-status ws-<?php echo watch_status_css_class($chronologyAlert['related_watch_status']); ?>">
                        <?php echo htmlspecialchars(watch_status_label($chronologyAlert['related_watch_status'])); ?>
                    </span>
                    <?php if (!empty($chronologyAlert['note'])): ?>
                        <small class="alert-note"><?php echo htmlspecialchars($chronologyAlert['note']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Siradaki Anime (next_in_series)
                  // Bu animeyi tamamen bitirdikten sonra izlenecek anime.
                  // Sadece next_in_series FK dolu ise gosterilir.
                  // ============================================================
            ?>
            <?php if ($nextAnime): ?>
            <div class="next-anime-panel">
                <h3><i class="fas fa-arrow-right"></i> <?php echo htmlspecialchars(t('anime_details.section.next_up'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <div class="next-anime-info">
                    <a href="anime_details.php?id=<?php echo (int)$nextAnime['id']; ?>" class="next-anime-link">
                        <?php echo htmlspecialchars(display_title($nextAnime)); ?>
                        <?php if (!empty($nextAnime['media_type'])): ?>
                            (<?php echo htmlspecialchars($nextAnime['media_type']); ?>)
                        <?php endif; ?>
                    </a>
                    <span class="next-anime-status ws-<?php echo watch_status_css_class($nextAnime['watch_status']); ?>">
                        <?php echo htmlspecialchars(watch_status_label($nextAnime['watch_status'])); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isInSeriesChain): ?>
            <div style="margin: 15px 0;">
                <a href="series_timeline.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button" style="background: #8e44ad;">
                    <i class="fas fa-list-ol"></i> <?php echo htmlspecialchars(t('anime_details.btn.series_chronology'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Baglantili Animeler
                  // Ayni series_name'i paylasan tum animeler, media_type'a gore
                  // gruplanmis. Sadece series_name dolu ise gosterilir.
                  // ============================================================
            ?>
            <?php if (!empty($relatedAnimes)): ?>
            <div class="related-animes-section">
                <h3><i class="fas fa-link"></i> <?php echo htmlspecialchars(t('anime_details.section.related'), ENT_QUOTES, 'UTF-8'); ?>
                    <small>(<?php echo htmlspecialchars($anime['series_name']); ?>)</small>
                </h3>
                <div class="related-animes-list">
                    <?php
                    // media_type'a gore grupla. Type ASCII string (DB enum-ish),
                    // i18n fallback label is shown only for the "Other" bucket.
                    $grouped = [];
                    foreach ($relatedAnimes as $ra) {
                        $type = $ra['media_type'] ?? '__other__';
                        if ($type === '' || $type === '__other__') {
                            $type = '__other__';
                        }
                        $grouped[$type][] = $ra;
                    }
                    ?>
                    <?php foreach ($grouped as $type => $animes): ?>
                        <div class="related-group">
                            <h4><?php
                                echo htmlspecialchars(
                                    $type === '__other__'
                                        ? t('anime_details.section.related_other_type')
                                        : $type
                                );
                            ?></h4>
                            <?php foreach ($animes as $ra): ?>
                                <div class="related-anime-item">
                                    <a href="anime_details.php?id=<?php echo (int)$ra['id']; ?>" class="related-anime-link">
                                        <?php echo htmlspecialchars(display_title($ra)); ?>
                                    </a>
                                    <span class="related-anime-progress">
                                        <?php echo (int)$ra['watched_episodes']; ?>/<?php echo $ra['total_episodes'] ? (int)$ra['total_episodes'] : '?'; ?>
                                    </span>
                                    <span class="related-anime-status ws-<?php echo watch_status_css_class($ra['watch_status']); ?>">
                                        <?php echo htmlspecialchars(watch_status_label($ra['watch_status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Kronoloji Markerlari
                  // Bu animenin bolum-seviyesinde kronoloji notlari.
                  // Ornegin "23. bolumden sonra Film 1'i izle".
                  // Marker ekleme formu da burada (ayni seri icerisinden secer).
                  // ============================================================
            ?>
            <?php if (!empty($chronologyMarkers) || ($canModerate && !empty($sameSeriesAnimes))): ?>
            <div class="chronology-section">
                <h3><i class="fas fa-clock"></i> <?php echo htmlspecialchars(t('anime_details.section.chronology'), ENT_QUOTES, 'UTF-8'); ?></h3>

                <?php if (!empty($chronologyMarkers)): ?>
                <div class="marker-list">
                    <?php foreach ($chronologyMarkers as $cm): ?>
                        <div class="marker-item">
                            <span class="marker-episode"><?php echo htmlspecialchars(sprintf(t('anime_details.marker.after_episode'), (int)$cm['after_episode'])); ?></span>
                            <span class="marker-arrow">→</span>
                            <a href="anime_details.php?id=<?php echo (int)$cm['related_anime_id']; ?>" class="marker-anime-link">
                                <?php echo htmlspecialchars(display_related_title($cm)); ?>
                                <?php if (!empty($cm['related_media_type'])): ?>
                                    (<?php echo htmlspecialchars($cm['related_media_type']); ?>)
                                <?php endif; ?>
                            </a>
                            <span class="marker-watch-status ws-<?php echo watch_status_css_class($cm['related_watch_status']); ?>">
                                <?php echo htmlspecialchars(watch_status_label($cm['related_watch_status'])); ?>
                            </span>
                            <?php if (!empty($cm['note'])): ?>
                                <small class="marker-note">(<?php echo htmlspecialchars($cm['note']); ?>)</small>
                            <?php endif; ?>
                            <?php if ($canModerate): ?>
                            <form method="POST" action="delete_chronology_marker.php" class="marker-delete-form"
                                  onsubmit="return confirm(<?php echo htmlspecialchars(json_encode(t('anime_details.marker.delete_confirm'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="marker_id" value="<?php echo (int)$cm['id']; ?>">
                                <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                                <button type="submit" class="marker-delete-btn" title="<?php echo htmlspecialchars(t('anime_details.marker.delete_tooltip'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-times"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($canModerate && !empty($sameSeriesAnimes)): ?>
                <div class="marker-add-form">
                    <h4><?php echo htmlspecialchars(t('anime_details.marker_form.title'), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <form method="POST" action="add_chronology_marker.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                        <div class="marker-form-row">
                            <label><?php echo htmlspecialchars(t('anime_details.marker_form.after_episode'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="number" name="after_episode" min="1" max="<?php echo $anime['total_episodes'] ? (int)$anime['total_episodes'] : 9999; ?>" required placeholder="<?php echo htmlspecialchars(t('anime_details.marker_form.after_episode_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="marker-form-row">
                            <label><?php echo htmlspecialchars(t('anime_details.marker_form.target_anime'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <select name="related_anime_id" required>
                                <option value=""><?php echo htmlspecialchars(t('anime_details.marker_form.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php foreach ($sameSeriesAnimes as $ssa): ?>
                                    <option value="<?php echo (int)$ssa['id']; ?>">
                                        <?php echo htmlspecialchars(display_title($ssa)); ?>
                                        <?php if (!empty($ssa['media_type'])): ?>(<?php echo htmlspecialchars($ssa['media_type']); ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="marker-form-row">
                            <label><?php echo htmlspecialchars(t('anime_details.marker_form.note'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="text" name="note" placeholder="<?php echo htmlspecialchars(t('anime_details.marker_form.note_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="submit" class="marker-add-btn"><i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('anime_details.marker_form.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Duzeltme Onerisi (1.0.5 - Faz 2, Milestone 2)
                  // Anyone (anonymous or signed-in) can submit a free-text
                  // correction note -> suggest.php -> pending queue. Multi-user
                  // only; in self-host the owner edits the catalog directly so
                  // this section is not rendered.
                  // ============================================================
            ?>
            <?php if (MULTI_USER_MODE): ?>
            <?php $suggestFlash = $_GET['suggest'] ?? ''; ?>
            <div class="suggest-section" style="margin-top: 25px; padding: 18px; border: 1px solid #e0e0e0; border-radius: 6px; background: #fafafa;">
                <h3 style="margin: 0 0 8px 0; font-size: 1.05em; color: #333;">
                    <i class="fas fa-flag"></i> <?php echo htmlspecialchars(t('anime_details.suggest.title'), ENT_QUOTES, 'UTF-8'); ?>
                </h3>
                <?php if ($suggestFlash === 'ok'): ?>
                    <div style="background:#d4edda;color:#155724;padding:8px 12px;border-radius:4px;margin-bottom:10px;font-size:0.9em;"><?php echo htmlspecialchars(t('anime_details.suggest.ok'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php elseif ($suggestFlash === 'rate'): ?>
                    <div style="background:#fff3cd;color:#856404;padding:8px 12px;border-radius:4px;margin-bottom:10px;font-size:0.9em;"><?php echo htmlspecialchars(t('anime_details.suggest.rate'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php elseif ($suggestFlash === 'err'): ?>
                    <div style="background:#f8d7da;color:#721c24;padding:8px 12px;border-radius:4px;margin-bottom:10px;font-size:0.9em;"><?php echo htmlspecialchars(t('anime_details.suggest.err'), ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <p style="margin: 0 0 10px 0; color: #666; font-size: 0.88em;"><?php echo htmlspecialchars(t('anime_details.suggest.intro'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="POST" action="suggest.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                    <div aria-hidden="true" style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
                        <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>
                    <textarea name="note" rows="3" maxlength="2000" required
                        placeholder="<?php echo htmlspecialchars(t('anime_details.suggest.placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                        style="width:100%;box-sizing:border-box;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:14px;font-family:inherit;resize:vertical;"></textarea>
                    <button type="submit" style="margin-top:10px;background:#007bff;color:#fff;border:none;padding:9px 18px;border-radius:4px;cursor:pointer;font-size:14px;font-weight:500;">
                        <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars(t('anime_details.suggest.submit'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars(t('anime_details.btn.edit'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('anime_details.btn.back'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- 0.6.1 - Duygu Etiketleri toggle scripti
         Her butona tiklama: POST update_emotion.php ile toggle. Sunucu
         otoriter; cevaptaki current_emotions listesini DOM'a yansitir.
         Cap kontrolu (3'te diger pasif butonlari disabled yap) sunucu
         cevabindaki at_max flag'i ile yapilir, lokalde sayma yok. -->
    <script>
    (function() {
        var toolbar = document.querySelector('.emotion-toolbar');
        if (!toolbar) return;

        var animeId = toolbar.dataset.animeId;
        var csrf    = toolbar.dataset.csrf;
        var meta    = toolbar.querySelector('.emotion-count');
        var buttons = toolbar.querySelectorAll('.emotion-btn');

        function syncFromServer(currentEmotions, atMax) {
            // Aktif/disabled durumlarini sunucudaki gercege gore yeniden
            // kur. currentEmotions: ASCII emotion degerlerini icerir.
            var active = {};
            for (var i = 0; i < currentEmotions.length; i++) {
                active[currentEmotions[i]] = true;
            }
            buttons.forEach(function(btn) {
                var emo = btn.dataset.emotion;
                var isOn = !!active[emo];
                btn.classList.toggle('is-active', isOn);
                // 3'e ulasildiysa pasif butonlari disable et; aktif olanlar
                // her zaman tiklanabilir (toggle off serbest).
                btn.disabled = (atMax && !isOn);
            });
            meta.textContent = currentEmotions.length;
        }

        toolbar.addEventListener('click', function(ev) {
            var btn = ev.target.closest('.emotion-btn');
            if (!btn || btn.disabled) return;

            var emotion = btn.dataset.emotion;
            // Geri donus gelene kadar tum butonlari kilitle - cift tikla
            // race'i onler.
            buttons.forEach(function(b) { b.disabled = true; });

            var form = new FormData();
            form.append('csrf_token', csrf);
            form.append('anime_id', animeId);
            form.append('emotion', emotion);

            fetch('update_emotion.php', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    syncFromServer(data.current_emotions || [], !!data.at_max);
                } else {
                    // Sunucu reddetti - butonlari onceki haline geri dondur
                    // ve mesaji goster. Sayfayi tekrar render etmek yerine
                    // mevcut DOM'dan aktif listesini cikarip kullaniyoruz.
                    var fallback = [];
                    buttons.forEach(function(b) {
                        if (b.classList.contains('is-active')) {
                            fallback.push(b.dataset.emotion);
                        }
                    });
                    syncFromServer(fallback, fallback.length >= 3);
                    alert(data.error || <?php echo json_encode(t('anime_details.js.operation_failed'), JSON_UNESCAPED_UNICODE); ?>);
                }
            })
            .catch(function(err) {
                // Ag hatasi - butonlari onceki aktif/disabled durumuna
                // dondur. Hata aciklayici degil cunku JSON donmedi.
                var fallback = [];
                buttons.forEach(function(b) {
                    if (b.classList.contains('is-active')) {
                        fallback.push(b.dataset.emotion);
                    }
                });
                syncFromServer(fallback, fallback.length >= 3);
                alert(<?php echo json_encode(t('anime_details.js.connection_error'), JSON_UNESCAPED_UNICODE); ?>);
            });
        });
    })();
    </script>
    <script src="js/select_enhance.js" defer></script>
</body>
</html>
