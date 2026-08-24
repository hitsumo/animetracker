<?php

/**
 * Anime Tracker - Anime izleme takip listesi
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
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

// 1.1.28: id yoksa ya da sayi degilse 0'a duser ve asagidaki "bulunamadi"
// dalina gider. Eskiden dogrudan $_GET['id'] okunuyordu, yani adres cubuguna
// id'siz girilen sayfa PHP uyarisi uretiyordu. Sorgu zaten hazirlanmis ifade
// kullaniyor - bu bir guvenlik duzeltmesi degil, gurultu temizligi.
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM animes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    // 1.1.30: the message was already right, the STATUS CODE was not -
    // this answered 200 OK, which tells a crawler "this page exists" and
    // gets a one-line error indexed as content (a "soft 404"). The page
    // is unchanged; only the header is new.
    http_response_code(404);
    echo htmlspecialchars(t('anime_details.error.not_found'));
    exit();
}

// Dis site baglantilari. safe_url() tehlikeli semalarda ('javascript:',
// 'data:' vb.) bos dize dondurur ve sonuc oznitelik baglami icin zaten
// htmlspecialchars'lanmistir - asagida tekrar kacislanmazlar.
//
// 1.1.28: bu uc satir "bulunamadi" kontrolunun USTUNDEYDI, yani var olmayan
// bir id ile girildiginde $anime false iken $anime['anidb_link'] okunuyor ve
// PHP 8 "array offset on bool" uyarisi veriyordu. Kontrolun altina alindi.
$anidb_safe    = safe_url($anime['anidb_link'] ?? '');
$mal_safe      = safe_url($anime['mal_link'] ?? '');
$schedule_safe = safe_url($anime['anime_schedule_link'] ?? '');

// 1.1.2 - yetiskin (+18) icerik kapisi. Anime +18 damgaliysa VE izleyici
// "yetiskin icerigi goster" tercihini acmamissa (varsayilan kapali), detayi
// sizdirmak yerine notr bir uyari gosterip cikariz. 404 degil (varlik
// gizlenmez), icerik sizmaz; kullaniciya nasil acacagi soylenir. Moderator/
// admin de gormek icin kendi tercihini acar (tercih kisi bazlidir).
if (!empty($anime['is_adult']) && !show_adult_content()) {
    // 1.1.30: what a crawler receives here is a one-line notice, not the
    // page - thin content that must never be indexed. The sitemap leaves
    // adult rows out for the same reason; this header covers the case
    // where the URL was found some other way. Status stays 200: the row
    // exists, it is just not shown.
    header('X-Robots-Tag: noindex');
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

// Chronology display mode (1.1.15): release / story / both. The marker list
// below follows the same mode as the chronology.php timeline (session
// override from the cycle button, else the saved list-settings default).
// 'both' shows two labeled lists; the other modes show one ordered list.
$chronoMode = chrono_current_mode($pdo);
$markerSections = [];
if (!empty($chronologyMarkers)) {
    if ($chronoMode === 'both') {
        $markerSections[] = ['label' => t('chrono.mode.release'), 'rows' => $chronologyMarkers, 'story' => false];
        $markerSections[] = ['label' => t('chrono.mode.story'),   'rows' => getChronologyMarkers($pdo, $anime['id'], 'story'), 'story' => true];
    } elseif ($chronoMode === 'story') {
        $markerSections[] = ['label' => null, 'rows' => getChronologyMarkers($pdo, $anime['id'], 'story'), 'story' => true];
    } else {
        $markerSections[] = ['label' => null, 'rows' => $chronologyMarkers, 'story' => false];
    }
}

// Siradaki anime bilgisi (next_in_series foreign key)
$nextAnime = null;
if (!empty($anime['next_in_series'])) {
    // watch_status is personal (user_anime, 1.0.1) - join the current
    // user's row so the "next in series" card shows their progress.
    $nextStmt = $pdo->prepare(
        "SELECT a.id, a.title, a.alternative_titles,
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
        $nextAnime = adult_mask_related($nextAnime, 'is_adult', 'title', 'alternative_titles');
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

// 1.1.23: Seri Kronolojisi'nin Yayin Tarihi sekmesi series_name'den
// beslenir - zincire hic girmemis ama ayni seriden baska kayitlari olan
// anime de sayfaya girebilsin. $relatedAnimes zaten yuklu; ek sorgu yok.
$showSeriesTimeline = $isInSeriesChain || !empty($relatedAnimes);

// Ayni serideki tum animeler (marker ekleme formu dropdown'u icin)
$sameSeriesAnimes = [];
if (!empty($anime['series_name'])) {
    $ssStmt = $pdo->prepare("SELECT id, title, alternative_titles, media_type FROM animes a WHERE a.series_name = ? AND a.id != ?" . adult_filter_where('a') . " ORDER BY a.title ASC");
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

// 1.1.27 - "Izlenen Bolum" satirindaki hizli +/- kontrolu.
// index.php'deki liste ici widget'in ayni ucu (update_watched.php) uzerine
// oturan ikizi. Tavan (ceiling) kurali BIREBIR ayni olmak zorunda, yoksa
// iki sayfa ayni anime icin farkli sinir gosterir:
//   total_episodes varsa tavan odur; yoksa aired_episodes; ikisi de bos ise
//   tavan BILINMIYOR demektir ve kontroller hic basilmaz (senkronizasyon
//   veya elle bolum girisi once gelir). !empty() kullaniliyor - 0 ve NULL
//   ikisi de "girilmemis" sayilir, index.php'deki gibi.
// Buradaki hesap yalnizca UX icindir; son sozu her zaman sunucu soyler.
$canPersonal = can($pdo, 'personal');
$ep_watched  = (int)$anime['watched_episodes'];
$ep_total    = !empty($anime['total_episodes'])  ? (int)$anime['total_episodes']  : null;
$ep_aired    = !empty($anime['aired_episodes'])  ? (int)$anime['aired_episodes']  : null;
$ep_ceiling  = ($ep_total !== null) ? $ep_total : (($ep_aired !== null) ? $ep_aired : null);
// Anonim ziyaretcinin kisisel izleme durumu yoktur (uc de reddeder), tavani
// bilinmeyen anime icin de gosterilecek bir sinir yoktur.
$ep_controls = ($canPersonal && $ep_ceiling !== null);
$ep_at_min   = ($ep_watched <= 0);
$ep_at_max   = ($ep_ceiling !== null && $ep_watched >= $ep_ceiling);
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(display_title($anime)); ?> - <?php echo htmlspecialchars(t('anime_details.title_suffix'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - SEO meta. The description prefers the CATALOG synopsis of
    // the active language (EN falls back to TR, exactly as the visible
    // synopsis block does further down) and drops to a generic template
    // when there is none. The PERSONAL synopsis is deliberately not a
    // candidate: it is the user's own note, and a meta description is
    // published to the whole world.
    $metaSyn = (current_lang() === 'en')
        ? (!empty($anime['synopsis_en']) ? $anime['synopsis_en'] : ($anime['synopsis_tr'] ?? ''))
        : ($anime['synopsis_tr'] ?? '');
    $metaDesc = seo_excerpt($metaSyn);
    if ($metaDesc === '') {
        $metaDesc = sprintf(t('seo.anime.description_fmt'), display_title($anime));
    }
    echo seo_head([
        'title'       => display_title($anime),
        'description' => $metaDesc,
        'canonical'   => 'anime_details.php?id=' . (int)$anime['id'],
        'image'       => $anime['image_path'] ?? '',
        'type'        => 'article',
    ]);
    ?>
    <?php echo asset_styles(); ?>
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

                <?php // Yayin tarihi - bossa "Belirtilmemis" yazilir (ulkenin
                      // aksine, asagiya bakin).
                      //
                      // 1.1.31: tarihin yalnizca bilinen parcasi basilir.
                      // "??.??.2005" = yili biliniyor, "??.??.????" = kurator
                      // bilerek "bilinmiyor" demis. Hic girilmemis tarih ise
                      // eskisi gibi "Belirtilmemis"tir - ikisi ayni sey degil,
                      // bkz. functions/date_precision_helpers.php. ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.release_date'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars(format_partial_date(
                            $anime['release_date'] ?? null,
                            $anime['release_date_precision'] ?? 'full',
                            t('anime_details.label.unset')
                        ), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>

                <?php
                // Madde E - Tek bolumlu animede yayin bitis tarihi anlamsiz
                // (baslangic = bitis). Status finished AND end_date dolu AND
                // total_episodes 1 degil ise goster. 1.1.31: "dolu" olcusu
                // has_partial_date() - "bilinmiyor" da bir degerdir ve tarih
                // kolonu NULL oldugu icin !empty() onu yanlislikla elerdi.
                if ($anime['status'] == 'Yayın Tamamlandı'
                    && has_partial_date($anime['end_date'] ?? null, $anime['end_date_precision'] ?? 'full')
                    && (int)($anime['total_episodes'] ?? 0) !== 1):
                ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.end_date'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars(format_partial_date(
                            $anime['end_date'] ?? null,
                            $anime['end_date_precision'] ?? 'full'
                        ), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php
                // 1.1.17 - Yapim ulkesi. DB'de ISO kodu (JP) durur, ekranda
                // cevrilmis ad (Japonya / Japan) gorunur. Yukaridaki yayin
                // tarihinin aksine BOSSA HIC BASILMAZ ("Belirtilmemis"
                // yazilmaz): ulke sonradan eklenen opsiyonel bir alan,
                // katalogdaki animelerin cogunda henuz bos ve her detay
                // sayfasina bos bir satir koymak bilgi degil gurultu olurdu.
                // country_label() taninmayan kodda '' dondugu icin tek kontrol
                // yeterli.
                $country_name = country_label($anime['country'] ?? null);
                if ($country_name !== ''):
                ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.country'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value"><?php echo htmlspecialchars($country_name, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <?php endif; ?>

                <?php
                // 1.1.28 - Yayin bilgileri (gun/saat) DEVAM EDEN ve BASLAMAMIS
                // anime icin basilir. Haftalik yayin yuvasi bir dizi baslamadan
                // ONCE bellidir (sezon oncesi duyurulur), form da 1.1.28'den beri
                // o alanlari baslamamis animede gosterip kaydediyor; girilen
                // bilginin hicbir yerde GORUNMEMESI tutarsizdi. Bayrak burada,
                // ilk kullanildigi yerde hesaplanir ve asagida yayin bilgileri
                // blogunda TEKRAR kullanilir - iki ayri liste tutulmaz.
                $showBroadcastInfo = in_array($anime['status'],
                                        ['Yayın Devam Ediyor', 'Yayın Başlamadı'], true);
                ?>
                <?php // Kaynak notu ("Saat bilgisi ...'den alinmistir") saat
                      // GOSTERILEN her durumda basilir - yoksa baslamamis animede
                      // kaynaksiz saat gorunurdu. ?>
                <?php if ($showBroadcastInfo): ?>
                <div class="detail-row" style="margin-top: -8px;">
                    <span class="detail-label"></span>
                    <span class="detail-value" style="font-size: 11px; color: #6c757d; font-style: italic;">
                        <?php
                        // Etiketin icinde AnimeSchedule baglantisi icin bir "%s"
                        // yer tutucusu var: once baglanti HTML'i kurulur, sonra
                        // yerine konur - sonuc, kullanicinin sectigi cevirinin
                        // guvenli HTML'i sarmasidir.
                        //
                        // Adres yoksa servisin ANA SAYFASINA duser ve bu dogrudur:
                        // burasi bir KAYNAK BELIRTMEDIR (saat nereden geldi), dis
                        // baglantilar bolumundeki "bu animenin sayfasi" dugmesi
                        // degil. Oradaki genel adrese dusme 1.1.28'de kaldirildi.
                        $schedule_link_html = '<a href="' . ($schedule_safe ?: 'https://animeschedule.net') . '" target="_blank" rel="noopener noreferrer" style="color: #6c757d; text-decoration: underline;">AnimeSchedule</a>';
                        echo sprintf(t('anime_details.label.broadcast_attribution'), $schedule_link_html);
                        ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php /* 1.1.27: izlenen bolum sayisi artik yerinde
                   degistirilebilir - listedeki (+/-) widget'inin ayni ucu
                   kullanan ikizi. Tavani bilinmeyen anime ya da anonim
                   ziyaretci icin duz sayi basilir (onceki davranis). */ ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.watched_episodes'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value episode"><?php if ($ep_controls): ?><span class="ep-quick ep-quick-inline"
                              data-anime-id="<?php echo (int)$anime['id']; ?>"
                              data-ceiling="<?php echo (int)$ep_ceiling; ?>"
                              data-csrf="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="ep-text"><?php echo $ep_watched; ?></span>
                            <span class="ep-controls">
                                <button type="button" class="ep-step ep-minus" data-delta="-1"<?php echo $ep_at_min ? ' disabled' : ''; ?> title="<?php echo htmlspecialchars(t('anime_details.ep.minus_tooltip'), ENT_QUOTES, 'UTF-8'); ?>">&minus;</button>
                                <button type="button" class="ep-step ep-plus" data-delta="1"<?php echo $ep_at_max ? ' disabled' : ''; ?> title="<?php echo htmlspecialchars(t('anime_details.ep.plus_tooltip'), ENT_QUOTES, 'UTF-8'); ?>">+</button>
                            </span>
                        </span><?php else: ?><?php echo htmlspecialchars($anime['watched_episodes']); ?><?php endif; ?></span>
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
                        <?php echo render_synopsis($pdo, $showSyn); ?>
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
                    <span class="detail-value synopsis"><?php echo render_synopsis($pdo, $personalSyn); ?></span>
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
                        <?php /* js-watch-status-badge (1.1.27): +/- otomatik durum
                           gecisini tetiklerse (Planlandi -> Izleniyor gibi) rozet
                           yerinde guncellenir. Sinif YALNIZ JS kancasidir, hicbir
                           stil tasimaz - stil watch_status_css_class()'ten gelir. */ ?>
                        <span class="status-badge js-watch-status-badge <?php echo watch_status_css_class($anime['watch_status']); ?>">
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

                <?php // 1.1.28 - $showBroadcastInfo yukarida (kaynak notunun
                      // yaninda) hesaplandi: devam eden + baslamamis anime.
                      //
                      // 1.1.29 - baslamamis animeye de geri sayim geldi.
                      // 1.1.28'de "Sonraki Bolum" satiri bu blogun disinda
                      // birakilmisti; gerekce "baslamamis animenin sonraki
                      // bolumu ilk bolumudur ve onu Yayin Tarihi satiri zaten
                      // tasir" idi. O gerekce eksikti: Yayin Tarihi satiri
                      // TARIHI verir, KALAN SUREYI degil - kullanici devam
                      // eden animede tek bakista gordugu seyi baslamamista
                      // takvimden elle sayiyordu. calculatePremiereDate()
                      // premiere anini release_date + broadcast_time +
                      // broadcast_timezone'dan kurar ve UTC dondurur, yani
                      // ayni geri sayim fonksiyonu degismeden calisir.
                      // Upcoming olmayan animede null doner. ?>
                <?php $premiereUtc = calculatePremiereDate($anime); ?>
                <?php if ($showBroadcastInfo): ?>
                <div class="broadcast-info">
                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.broadcast_day'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value"><?php echo htmlspecialchars(!empty($anime['broadcast_day']) ? $anime['broadcast_day'] : t('anime_details.label.unset')); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.broadcast_time'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value broadcast-time"><?php echo htmlspecialchars(!empty($anime['broadcast_time']) ? substr($anime['broadcast_time'], 0, 5) : t('anime_details.label.unset')); ?></span>
                    </div>

                    <?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.next_episode'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value next-episode">
                            <?php echo getTimeUntilNextEpisode($anime['next_episode_date'], $anime['watched_episodes'], $anime['total_episodes'] ?? 0, $anime['aired_episodes'] ?? 0); ?>
                        </span>
                    </div>
                    <?php elseif ($premiereUtc !== null): ?>
                    <?php // 1.1.29 - baslamamis anime. Izleme sayaclari bilerek
                          // 0 gecilir: yayinlanmis bolum yoktur, yani "yetis"
                          // ve "izleme tamamlandi" dallari anlamsizdir ve
                          // etiket her zaman "1. bolume kalan sure" olur.
                          // Alti parametre premiere modu: gecmis bir tarih
                          // "yeni bolum yayinlandi" degil "yayin tarihi gecti"
                          // der - henuz hicbir sey yayinlanmadi. ?>
                    <div class="detail-row">
                        <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.premiere'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="detail-value next-episode">
                            <?php echo getTimeUntilNextEpisode($premiereUtc, 0, 0, 0, null, true); ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
                    <?php // Kronoloji dugmesi devam eden animede blok ICINDE durur
                          // (etiket sutunuyla hizali varyant); diger tum durumlarda
                          // asagidaki tek dal basar. Iki yer, ama kosullar birbirini
                          // DISLAR - dugme her zaman tam bir kez cikar. ?>
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
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php // Devam ETMEYEN animede kronoloji dugmesi burada basar
                      // (devam edende zaten broadcast-info icinde gosterildi).
                ?>
                <?php if ($anime['status'] != 'Yayın Devam Ediyor' && !empty($chronologyMarkers)): ?>
                <div class="detail-row" style="margin-top: 10px;">
                    <a href="chronology.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button">
                        <i class="fas fa-stream"></i> <?php echo htmlspecialchars(t('anime_details.btn.chronology'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php // 1.1.28 HATA DUZELTMESI - kisisel notlar.
                      // Bu satir .broadcast-info blogunun ICINDE duruyordu, yani
                      // "yalniz Yayin Devam Ediyor" kosuluna takiliydi: tamamlanmis,
                      // baslamamis ya da iptal edilmis bir animeye yazilan not
                      // detay sayfasinda HIC gorunmuyordu (yazilip kaydediliyor,
                      // duzenleme formunda duruyor, ama detayda yok). Not kisisel
                      // veridir ve yayin durumuyla hicbir ilgisi yoktur - blok
                      // disina, HER durumda basilacak sekilde tasindi.
                      // Devam eden animede yeri degismedi (blok zaten burada
                      // bitiyordu ve not onun son satiriydi). ?>
                <?php if (!empty($anime['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php echo htmlspecialchars(t('anime_details.label.notes'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($anime['notes'])); ?></span>
                </div>
                <?php endif; ?>

            </div>

            <?php // ============================================================
                  // SECTION: Dis Site Baglantilari
                  // Adresler yukarida ($anidb_safe / $mal_safe / $schedule_safe)
                  // bir kez hesaplandi ve zaten kacislanmis durumda.
                  //
                  // 1.1.28'de UC KUSUR duzeltildi:
                  //   1) Bolumun kosulu "$anidb_safe || $mal_safe || true" idi,
                  //      yani HER ZAMAN dogru: hicbir baglantisi olmayan animede
                  //      bos bir "Dis Siteler" basligi basiliyordu.
                  //   2) AnimeSchedule baglantisi MAL dalinin ICINDE duruyordu.
                  //      Yani AnimeSchedule adresi girilmis ama MAL kutusu bos
                  //      olan animede dugme HIC cikmiyordu - ki 1.1.27'den beri
                  //      "yalnizca AnimeSchedule baglantisi girmek" desteklenen
                  //      bir kullanim. Artik kendi kosulu var.
                  //   3) Adres bossa dugme animeschedule.net ANA SAYFASINA
                  //      gidiyordu (ve bunu yalnizca MAL varsa yapiyordu). Bu
                  //      bolum BU animeye ait baglantilari listeler; hicbir yere
                  //      goturmeyen bir dugme bilgi degil gurultudur. Adres yoksa
                  //      dugme artik hic basilmaz.
            ?>
            <?php if ($anidb_safe || $mal_safe || $schedule_safe): ?>
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
                <?php endif; ?>

                <?php if ($schedule_safe): ?>
                <a href="<?php echo $schedule_safe; ?>"
                   target="_blank"
                   rel="noopener noreferrer"
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

            <?php if ($showSeriesTimeline): ?>
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
                <?php // 1.1.15: single cycle button - release -> story -> both -> release.
                      // Ephemeral (session), does not overwrite the saved default. ?>
                <form method="POST" action="set_chrono_mode.php" class="chrono-mode-toggle">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                    <input type="hidden" name="mode" value="<?php echo htmlspecialchars(chrono_next_mode($chronoMode), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="chrono-mode-btn" title="<?php echo htmlspecialchars(t('chrono.mode.toggle_hint'), ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fas fa-sort"></i>
                        <?php echo htmlspecialchars(sprintf(t('chrono.mode.showing'), chrono_mode_label($chronoMode)), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                </form>
                <?php endif; ?>

                <?php foreach ($markerSections as $section): ?>
                <?php if ($section['label'] !== null): ?>
                <h4 class="marker-section-label"><?php echo htmlspecialchars($section['label'], ENT_QUOTES, 'UTF-8'); ?></h4>
                <?php endif; ?>
                <div class="marker-list">
                    <?php foreach ($section['rows'] as $cm): ?>
                        <?php
                            // Show the episode for THIS section's axis: the story
                            // section uses story_after_episode (falling back to the
                            // release point when unset), the release section uses
                            // after_episode. So the "Hikaye Sirasi" list reads 35,
                            // the "Yayin Sirasi" list reads 46 (1.1.15).
                            $displayEp = (!empty($section['story']) && $cm['story_after_episode'] !== null)
                                ? (int)$cm['story_after_episode']
                                : (int)$cm['after_episode'];
                        ?>
                        <div class="marker-item">
                            <span class="marker-episode"><?php echo htmlspecialchars(sprintf(t('anime_details.marker.after_episode'), $displayEp)); ?></span>
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
                            <?php
                                // 1.1.15: inline edit. Each section edits ITS OWN axis:
                                // the release section edits after_episode (required),
                                // the story section edits story_after_episode (empty
                                // clears it back to NULL / "same as release"). So the
                                // two boxes are independent - changing one no longer
                                // moves the other.
                                $isStorySection = !empty($section['story']);
                                $boxValue = $isStorySection
                                    ? ($cm['story_after_episode'] !== null ? (int)$cm['story_after_episode'] : '')
                                    : (int)$cm['after_episode'];
                                $boxHint = $isStorySection
                                    ? t('anime_details.marker.story_edit_hint')
                                    : t('anime_details.marker.release_edit_hint');
                            ?>
                            <form method="POST" action="update_chronology_marker.php" class="marker-story-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="marker_id" value="<?php echo (int)$cm['id']; ?>">
                                <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                                <input type="hidden" name="field" value="<?php echo $isStorySection ? 'story' : 'release'; ?>">
                                <input type="number" name="episode" class="marker-story-input"
                                       min="1" max="<?php echo $anime['total_episodes'] ? (int)$anime['total_episodes'] : 9999; ?>"
                                       value="<?php echo $boxValue; ?>"
                                       <?php echo $isStorySection ? '' : 'required'; ?>
                                       placeholder="<?php echo htmlspecialchars(t('anime_details.marker.story_placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
                                       title="<?php echo htmlspecialchars($boxHint, ENT_QUOTES, 'UTF-8'); ?>">
                                <button type="submit" class="marker-story-btn" title="<?php echo htmlspecialchars(t('anime_details.marker.story_save'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-check"></i></button>
                            </form>
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
                <?php endforeach; ?>

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
                            <label><?php echo htmlspecialchars(t('anime_details.marker_form.story_after_episode'), ENT_QUOTES, 'UTF-8'); ?></label>
                            <input type="number" name="story_after_episode" min="1" max="<?php echo $anime['total_episodes'] ? (int)$anime['total_episodes'] : 9999; ?>" placeholder="<?php echo htmlspecialchars(t('anime_details.marker_form.story_after_episode_placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
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
                <a href="edit_anime.php?id=<?php echo (int)$anime['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars(t('anime_details.btn.edit'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('anime_details.btn.back'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- 1.1.27 - "Izlenen Bolum" satirindaki hizli +/- kontrolu.
         Uc, listedekiyle ayni: update_watched.php. Sunucu otoriterdir -
         yeni sayi, sinir bayraklari (at_min/at_max) ve otomatik durum
         gecisi hep cevaptan okunur, burada hicbir sey tahmin edilmez.

         Listeden TEK FARKI, tiklamadan sonraki sessiz yenileme. Liste
         hucresinde izlenen sayidan turetilen baska bir sey yok; detay
         sayfasinda ise UC ayri blok bu sayiya bagli:
           - "Sonraki Bolum" satiri (kac bolum geride kaldiniz),
           - kronoloji uyarisi (su bolumden sonra su animeyi izleyin),
           - yayini bitmis seride izleme bitis tarihi damgasi.
         Bunlar sunucuda uretilir; yerinde guncelleme onlari ESKI birakir
         ve sayfa kendi kendisiyle celisir ("12/12" yazarken hemen altinda
         "3 bolum izlenebilir" demek gibi). Bu yuzden basildiktan ~1,5
         saniye sonra sayfa yenilenir; ustuste basilirsa sayac sifirlanir,
         yani 8 kez arka arkaya "+" tek bir yenileme yapar. -->
    <script>
    (function () {
        var box = document.querySelector('.ep-quick');
        if (!box) return;   // tavan bilinmiyor ya da anonim ziyaretci

        var csrf    = box.dataset.csrf;
        var animeId = box.dataset.animeId;
        var textEl  = box.querySelector('.ep-text');
        var minusEl = box.querySelector('.ep-minus');
        var plusEl  = box.querySelector('.ep-plus');
        var reloadTimer = null;

        // Yenileme, kullanicinin YAZDIGI bir seyi silecekse yapilmaz.
        // Sayfada kaydedilmemis metin tasiyabilecek uc yer var: duzeltme
        // onerisi kutusu, kronoloji isareti ekleme formu ve her isaretin
        // yanindaki satir ici bolum kutusu (1.1.15).
        //
        // Olcut "alan dolu mu" DEGIL, "sunucunun bastigindan farkli mi":
        // satir ici bolum kutusu isaretin mevcut numarasiyla DOLU gelir,
        // "dolu = kullanici yazdi" deseydik kronoloji isareti olan her
        // sayfada yenileme sessizce hic calismazdi - ustelik tam da
        // kronoloji uyarisinin onemli oldugu sayfalarda. defaultValue,
        // sunucunun bastigi ilk degeri tutar; value ondan farkliysa o
        // metni yazan kullanicidir ve silinemez.
        function hasUserInput() {
            var fields = document.querySelectorAll(
                'textarea, input[type="text"], input[type="number"], input[type="date"]'
            );
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].value !== fields[i].defaultValue) return true;
            }
            return false;
        }

        function scheduleRefresh() {
            if (reloadTimer) clearTimeout(reloadTimer);
            reloadTimer = setTimeout(function () {
                if (hasUserInput()) return;
                window.location.reload();
            }, 1500);
        }

        box.addEventListener('click', function (ev) {
            var btn = ev.target.closest('.ep-step');
            if (!btn || btn.disabled || box.classList.contains('busy')) return;

            var delta = parseInt(btn.dataset.delta, 10);
            box.classList.add('busy');

            var body = new URLSearchParams();
            body.set('csrf_token', csrf);
            body.set('anime_id', animeId);
            body.set('delta', String(delta));

            fetch('update_watched.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
                credentials: 'same-origin'
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                box.classList.remove('busy');

                if (!data || !data.success) {
                    alert((data && data.error) || <?php echo json_encode(t('anime_details.js.operation_failed'), JSON_UNESCAPED_UNICODE); ?>);
                    return;
                }

                // Detayda tavan zaten "Toplam Bolum" / "Yayinlanan Bolum"
                // satirlarinda yazili; burada yalnizca izlenen sayi durur.
                textEl.textContent = data.watched_episodes;
                if (minusEl) minusEl.disabled = !!data.at_min;
                if (plusEl)  plusEl.disabled  = !!data.at_max;

                // Otomatik durum gecisi (0.5.6/0.5.7 kurallari) fire
                // ettiyse rozetin hem YAZISI hem RENGI degisir. Renk
                // sinifi sunucudan gelir; gelmezse (eski onbellek) yazi
                // guncellenir, renk oldugu gibi birakilir - yanlis renk
                // basmaktansa eski renk.
                if (data.watch_status_changed && data.watch_status_new) {
                    var badge = document.querySelector('.js-watch-status-badge');
                    if (badge) {
                        badge.textContent = data.watch_status_label || data.watch_status_new;
                        if (data.watch_status_css) {
                            badge.className = 'status-badge js-watch-status-badge ' + data.watch_status_css;
                        }
                    }
                }

                box.classList.add('flash');
                setTimeout(function () { box.classList.remove('flash'); }, 350);

                scheduleRefresh();
            })
            .catch(function () {
                box.classList.remove('busy');
                alert(<?php echo json_encode(t('anime_details.js.connection_error'), JSON_UNESCAPED_UNICODE); ?>);
            });
        });
    })();
    </script>

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
    <script src="<?php echo asset_url('js/select_enhance.js'); ?>" defer></script>
</body>
</html>
