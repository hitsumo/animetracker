<?php

/**
 * Anime Tracker - Series Timeline
 *
 * Displays the full series watch order by following the next_in_series
 * chain. Given any anime ID in the series, it finds the chain start
 * (the anime nobody points to) and walks forward to the end.
 *
 * This is separate from chronology.php which handles episode-level
 * markers within a single anime. This page shows the inter-anime
 * order across the entire series.
 *
 * Example: Tensei shitara Slime Datta Ken
 *   S1 (TV) -> OVA -> S2 Part 1 (TV) -> Slime Diaries -> S2 Part 2 -> ...
 *
 * 1.1.23: iki sekme. "Zincir Sirasi" yukaridaki yuruyusun kendisi;
 * "Yayin Tarihi" ise ayni series_name'i tasiyan HER kaydi ilk gosterim
 * tarihine gore dizer - zincire hic bagimli degildir, bu yuzden eksik
 * bir next_in_series halkasi ya da katalogdan baglanmadan gelen bir
 * kayit bu gorunumu bolemez. Ic ice gecen yayin donemleri (ayni anda
 * yayinda iki dizi) tarih araliklariyla oldugu gibi gorunur.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init($pdo);

// English-title display preference (0.7.2). Read once so display_title()
// applies to the chain titles below.
title_pref_init($pdo);

// +18 tercihi (1.1.2 politikasi): kart kalir, baslik sizmaz. Opt-in eden
// kullanici gercek basligi gorur; init edilmezse guvenli taraf (maske) kalir.
adult_pref_init($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// 1.1.23: istekteki animeyi en basta getir - sekme gosterimi ve yayin
// tarihi gorunumu series_name'den beslenir, zincirden bagimsizdir.
$reqStmt = $pdo->prepare("SELECT id, series_name FROM animes WHERE id = ?");
$reqStmt->execute([$id]);
$reqAnime = $reqStmt->fetch(PDO::FETCH_ASSOC);
$reqStmt->closeCursor();
if (!$reqAnime) {
    header('Location: index.php');
    exit;
}
$hasSeriesName = !empty($reqAnime['series_name']);

// Mod cozumu (1.1.15 kalibi): sekme linki ?mode= ile gelir, gecerliyse
// oturuma yazilir (gezinirken secim korunur, kayitli varsayilan ezilmez).
// Sonra oturum > kayitli tercih > 'chain'. Seri adi olmayan animede
// yayin-tarihi sekmesi yoktur; zincire duser.
if (isset($_GET['mode']) && in_array($_GET['mode'], series_timeline_modes(), true)) {
    $_SESSION['series_timeline_mode'] = $_GET['mode'];
}
$stMode = series_timeline_current_mode($pdo);
if (!$hasSeriesName) {
    $stMode = 'chain';
}

// Find chain start: walk backwards via next_in_series until no anime
// points to the current one.
function findChainStart($pdo, $anime_id) {
    $current = $anime_id;
    $visited = [];
    while (true) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("SELECT id FROM animes WHERE next_in_series = ?");
        $stmt->execute([$current]);
        $prev = $stmt->fetchColumn();
        $stmt->closeCursor();
        if (!$prev) break;
        $current = (int)$prev;
    }
    return $current;
}

// Walk the chain forward from start, collecting all anime records.
function getSeriesChain($pdo, $start_id) {
    $chain = [];
    $current = $start_id;
    $visited = [];
    while ($current) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("
            SELECT a.id, a.title, a.alternative_titles, a.media_type, a.total_episodes, a.aired_episodes,
                   COALESCE(ua.watched_episodes, 0) AS watched_episodes,
                   ua.watch_status,
                   a.status, a.image_path,
                   a.release_date, a.end_date, a.is_adult, a.next_in_series, a.series_name
            FROM animes a
            LEFT JOIN user_anime ua
                   ON ua.anime_id = a.id AND ua.user_id = ?
            WHERE a.id = ?
        ");
        $stmt->execute([current_user_id(), $current]);
        $anime = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$anime) break;
        $chain[] = $anime;
        $current = $anime['next_in_series'] ? (int)$anime['next_in_series'] : null;
    }
    return $chain;
}

// 1.1.23: aktif sekmeye gore listeyi kur. Iki mod da ayni $chain
// degiskenini doldurur; asagidaki kart dongusu tek sablondur.
if ($stMode === 'airdate') {
    $chain = getSeriesAnimesByAirDate($pdo, $reqAnime['series_name']);
    $seriesName = $reqAnime['series_name'];
} else {
    $startId = findChainStart($pdo, $id);
    $chain = getSeriesChain($pdo, $startId);
    // Series name from first item in chain
    $seriesName = !empty($chain) ? ($chain[0]['series_name'] ?? $chain[0]['title']) : '';
}

if (empty($chain)) {
    header('Location: anime_details.php?id=' . $id);
    exit;
}

// 1.1.2 politikasi: +18 uye kartini korur ama basligi sizdirmaz (opt-in
// eden kullanici adult_pref_init sonrasi gercek basligi gorur).
foreach ($chain as &$stRow) {
    $stRow = adult_mask_related($stRow, 'is_adult', 'title', 'alternative_titles');
}
unset($stRow);

// Find which anime in chain is the one user came from (highlight it)
$currentAnimeId = $id;

// Media type icon
function seriesMediaIcon($type) {
    switch ($type) {
        case 'Film': return '<i class="fas fa-film"></i>';
        case 'OVA':  return '<i class="fas fa-compact-disc"></i>';
        case 'Special': return '<i class="fas fa-star"></i>';
        case 'ONA':  return '<i class="fas fa-globe"></i>';
        default:     return '<i class="fas fa-tv"></i>';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($seriesName); ?> - <?php echo htmlspecialchars(t('series_timeline.title_suffix'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php echo asset_styles(); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; padding: 0; }

        .st-container {
            max-width: 700px;
            margin: 30px auto;
            padding: 20px;
        }
        .st-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .st-header h1 {
            font-size: 1.4em;
            color: #2c3e50;
            margin: 0 0 4px;
        }
        .st-header .subtitle {
            color: #888;
            font-size: 0.9em;
        }
        .st-header .count {
            color: #999;
            font-size: 0.85em;
            margin-top: 4px;
        }

        /* Tabs (1.1.23) - zincir / yayin tarihi sekmeleri */
        .st-tabs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }
        .st-tabs a {
            padding: 7px 18px;
            border-radius: 18px;
            background: #fff;
            color: #666;
            text-decoration: none;
            font-size: 0.88em;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s;
        }
        .st-tabs a.active {
            background: #8e44ad;
            color: #fff;
        }
        .st-tabs a:hover:not(.active) {
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }

        /* Timeline */
        .st-timeline {
            position: relative;
            padding-left: 30px;
        }
        .st-timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #ddd;
            border-radius: 2px;
        }

        .st-item {
            position: relative;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Dot */
        .st-item::before {
            content: '';
            position: absolute;
            left: -23px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            border: 2.5px solid #ddd;
            background: #fff;
            z-index: 1;
        }
        .st-item.is-watched::before {
            background: #27ae60;
            border-color: #27ae60;
        }
        .st-item.is-watching::before {
            background: #3498db;
            border-color: #3498db;
        }
        .st-item.is-plantowatch::before {
            background: #fff;
            border-color: #bbb;
        }
        .st-item.is-onhold::before {
            background: #e0a000;
            border-color: #e0a000;
        }
        .st-item.is-dropped::before {
            background: #e74c3c;
            border-color: #e74c3c;
        }
        .st-item.is-unselected::before {
            background: #fff;
            border-color: #ddd;
        }

        /* Card */
        .st-card {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border-radius: 8px;
            padding: 12px 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .st-card:hover {
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }
        .st-item.is-current .st-card {
            border: 2px solid #3498db;
        }

        .st-card img {
            width: 45px;
            height: 64px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .st-card .no-img {
            width: 45px;
            height: 64px;
            background: #eee;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #bbb;
            flex-shrink: 0;
        }

        .st-info {
            flex: 1;
            min-width: 0;
        }
        .st-info .title {
            font-weight: 600;
            font-size: 0.95em;
            color: #2c3e50;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .st-info .meta {
            display: flex;
            gap: 10px;
            margin-top: 3px;
            font-size: 0.8em;
            color: #888;
            flex-wrap: wrap;
        }
        .st-info .meta span {
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .st-badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.75em;
            font-weight: 500;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .badge-watched { background: #d1fae5; color: #065f46; }
        .badge-watching { background: #dbeafe; color: #1e40af; }
        .badge-plantowatch { background: #f3f4f6; color: #4b5563; }
        .badge-onhold { background: #fef3c7; color: #92400e; }
        .badge-dropped { background: #fee2e2; color: #991b1b; }
        .badge-unselected { background: #e5e7eb; color: #6b7280; }

        .st-order {
            color: #bbb;
            font-size: 0.8em;
            font-weight: 600;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        /* Back button */
        .st-back {
            text-align: center;
            margin-top: 30px;
        }
        .st-back a {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .st-back a:hover { background: #5a6268; }

        @media (max-width: 600px) {
            .st-card img, .st-card .no-img { display: none; }
            .st-order { display: none; }
        }
    </style>
</head>
<body>
<div class="st-container">
    <div class="st-header">
        <h1><?php echo htmlspecialchars($seriesName); ?></h1>
        <div class="subtitle"><?php echo htmlspecialchars(t('series_timeline.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="count"><?php echo htmlspecialchars(sprintf(t('series_timeline.count'), count($chain)), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <?php // 1.1.23: sekmeler yalniz seri adi dolu animede cikar - yayin
          // tarihi gorunumu series_name'den beslenir. ?>
    <?php if ($hasSeriesName): ?>
    <div class="st-tabs">
        <a href="series_timeline.php?id=<?php echo (int)$id; ?>&amp;mode=chain"
           class="<?php echo $stMode === 'chain' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('series_timeline.tab.chain'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="series_timeline.php?id=<?php echo (int)$id; ?>&amp;mode=airdate"
           class="<?php echo $stMode === 'airdate' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('series_timeline.tab.airdate'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <?php endif; ?>

    <div class="st-timeline">
        <?php foreach ($chain as $i => $item): ?>
            <?php
                $ws = $item['watch_status'] ?? '';
                // 0.6: ASCII enum -> stable CSS suffix via central helper.
                // style.css (0.6 adim 8) targets is-watched / badge-watched
                // and the corresponding watching / plantowatch / onhold
                // variants. Label text comes from watch_status_label.
                $wsKey = watch_status_css_class($ws);
                $statusClass = 'is-' . $wsKey;
                $badgeClass = 'badge-' . $wsKey;
                $badgeText = watch_status_label($ws);

                $isCurrent = ((int)$item['id'] === $currentAnimeId);

                // Episode display
                $ep = (int)($item['watched_episodes'] ?? 0);
                $total = $item['total_episodes'] ?? $item['aired_episodes'] ?? null;
                $epText = $total ? ($ep . '/' . $total) : ($ep . '/?');

                // Media type
                $mediaType = $item['media_type'] ?? 'TV';
                $mediaIcon = seriesMediaIcon($mediaType);

                // 1.1.23: yayin tarihi gorunumunde tarih onemli veridir -
                // gun.ay.yil (araligiyla) gosterilir; zincir gorunumu eski
                // davranisiyla yalnizca yili gosterir.
                $stDateText = '';
                if ($stMode === 'airdate') {
                    if (!empty($item['release_date'])) {
                        $stDateText = date('d.m.Y', strtotime($item['release_date']));
                        if (!empty($item['end_date']) && $item['end_date'] !== $item['release_date']) {
                            $stDateText .= ' – ' . date('d.m.Y', strtotime($item['end_date']));
                        }
                    } else {
                        $stDateText = t('series_timeline.no_date');
                    }
                }
            ?>
            <div class="st-item <?php echo $statusClass; ?> <?php echo $isCurrent ? 'is-current' : ''; ?>">
                <a href="anime_details.php?id=<?php echo (int)$item['id']; ?>" class="st-card">
                    <div class="st-order"><?php echo $i + 1; ?></div>

                    <img src="<?php echo htmlspecialchars(poster_src($item['image_path'] ?? '')); ?>"
                         alt="<?php echo htmlspecialchars(display_title($item)); ?>">

                    <div class="st-info">
                        <div class="title"><?php echo htmlspecialchars(display_title($item)); ?></div>
                        <div class="meta">
                            <span><?php echo $mediaIcon; ?> <?php echo htmlspecialchars($mediaType); ?></span>
                            <span><i class="fas fa-play-circle"></i> <?php echo $epText; ?></span>
                            <?php if ($stMode === 'airdate'): ?>
                                <span><i class="far fa-calendar"></i> <?php echo htmlspecialchars($stDateText, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php elseif (!empty($item['release_date'])): ?>
                                <span><i class="far fa-calendar"></i> <?php echo date('Y', strtotime($item['release_date'])); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="st-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="st-back">
        <a href="anime_details.php?id=<?php echo $currentAnimeId; ?>">
            <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('series_timeline.back_to_details'), ENT_QUOTES, 'UTF-8'); ?>
        </a>
    </div>
</div>
</body>
</html>
