<?php
/**
 * Anime Tracker - Chronology Page
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Displays the full watch-order timeline for an anime that has
 * chronology markers. Episode ranges are interleaved with related anime
 * (films, OVAs, etc.) based on the markers' insertion points.
 *
 * Display mode (1.1.15): a single cycle button switches between
 *   - release : order by after_episode (where the related anime aired)
 *   - story   : order by COALESCE(story_after_episode, after_episode)
 *               (the recommended-watch point)
 *   - both    : render both timelines, one under the other
 * The default comes from the per-user list-settings preference; the button
 * stores an ephemeral session override (see set_chrono_mode.php). The mode
 * is shared with the marker list on anime_details.php.
 *
 * The page automatically builds the timeline from the markers - no manual
 * ordering needed. Adding/removing markers (or their story point) on the
 * detail page instantly updates this view.
 *
 * Watch progress is shown for each item:
 *   - Episode ranges: based on watched_episodes vs range boundaries
 *   - Related anime: based on their own watch_status field
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init($pdo);

// English-title display preference (0.7.2). Read once so display_title()
// applies to the parent and related anime titles on this page.
title_pref_init($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Ana anime bilgisini cek
$stmt = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    header('Location: index.php');
    exit;
}

// Kisisel izleme durumu user_anime'da (1.0.1). Ana animenin watched_episodes
// degeri (asagidaki bolum-araligi isaretlemesi ve chronology alert) mevcut
// kullanicinin satirindan okunur. ua_get_state satir yoksa varsayilan doner.
$uaState = ua_get_state($pdo, current_user_id(), $id);
$anime['watch_status']     = $uaState['watch_status'];
$anime['watched_episodes'] = $uaState['watched_episodes'];
$anime['notes']            = $uaState['notes'];
$anime['user_synopsis']    = $uaState['user_synopsis'];
$anime['user_synopsis_en'] = $uaState['user_synopsis_en'];

// Kronoloji markerlari (yayin sirali). Marker yoksa detaya geri don.
$markersRelease = getChronologyMarkers($pdo, $id, 'release');
if (empty($markersRelease)) {
    header('Location: anime_details.php?id=' . $id);
    exit;
}

// Gorunum modu (1.1.15): session override > kayitli tercih > 'release'.
$chronoMode = chrono_current_mode($pdo);

$totalEp = $anime['total_episodes'] ?? $anime['aired_episodes'] ?? null;
$watched = (int)$anime['watched_episodes'];

/**
 * Build the interleaved timeline (episode ranges + related-anime inserts)
 * from an ordered marker list. When $useStory is true the range boundary is
 * the story point (story_after_episode, falling back to after_episode);
 * otherwise it is the release point (after_episode). Markers must already be
 * sorted by the matching axis so boundaries are non-decreasing.
 *
 *   marker.boundary = 54 -> Bolum 1-54, sonra Film 1
 *   marker.boundary = 97 -> Bolum 55-97, sonra Film 2
 *   ... son markerdan sonra: kalan bolumler (total_episodes veya "devam")
 */
function buildChronologyTimeline($markers, $totalEp, $useStory) {
    $timeline = [];
    $prevEnd = 0;

    foreach ($markers as $m) {
        $rangeStart = $prevEnd + 1;
        if ($useStory) {
            $rangeEnd = ($m['story_after_episode'] !== null)
                ? (int)$m['story_after_episode']
                : (int)$m['after_episode'];
        } else {
            $rangeEnd = (int)$m['after_episode'];
        }

        if ($rangeStart <= $rangeEnd) {
            $timeline[] = [
                'type'  => 'episodes',
                'start' => $rangeStart,
                'end'   => $rangeEnd,
            ];
        }

        $timeline[] = [
            'type'               => 'anime',
            'id'                 => (int)$m['related_anime_id'],
            'title'              => $m['related_title'],
            'alternative_titles' => $m['related_alternative_titles'] ?? null,
            'media_type'         => $m['related_media_type'],
            'watch_status'       => $m['related_watch_status'],
            'note'               => $m['note'] ?? null,
        ];

        $prevEnd = $rangeEnd;
    }

    $remainStart = $prevEnd + 1;
    if ($totalEp !== null && $remainStart <= $totalEp) {
        $timeline[] = [
            'type'  => 'episodes',
            'start' => $remainStart,
            'end'   => (int)$totalEp,
        ];
    } elseif ($totalEp === null) {
        $timeline[] = [
            'type'  => 'episodes',
            'start' => $remainStart,
            'end'   => null, // "devam ediyor"
        ];
    }

    return $timeline;
}

// Modun gerektirdigi timeline(lar)i olustur. 'both' iki ayri baslikli liste.
$views = [];
if ($chronoMode === 'both') {
    $views[] = ['label' => t('chrono.mode.release'), 'timeline' => buildChronologyTimeline($markersRelease, $totalEp, false)];
    $markersStory = getChronologyMarkers($pdo, $id, 'story');
    $views[] = ['label' => t('chrono.mode.story'),   'timeline' => buildChronologyTimeline($markersStory, $totalEp, true)];
} elseif ($chronoMode === 'story') {
    $markersStory = getChronologyMarkers($pdo, $id, 'story');
    $views[] = ['label' => null, 'timeline' => buildChronologyTimeline($markersStory, $totalEp, true)];
} else {
    $views[] = ['label' => null, 'timeline' => buildChronologyTimeline($markersRelease, $totalEp, false)];
}

// Her bolum araligi icin izleme durumunu hesapla
function getEpisodeRangeStatus($watched, $start, $end) {
    if ($end !== null && $watched >= $end) {
        return 'watched';    // Tum aralik izlendi
    } elseif ($watched >= $start) {
        return 'watching';   // Aralik icindeyiz
    }
    return 'upcoming';       // Henuz bu araliga gelmedik
}

// Media type ikonu
function getMediaTypeIcon($type) {
    switch ($type) {
        case 'Film': return '&#127916;';   // film kamera
        case 'OVA':  return '&#128192;';   // disk
        case 'Special': return '&#11088;'; // yildiz
        case 'ONA':  return '&#127760;';   // dunya
        default:     return '&#128250;';   // TV
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(display_title($anime)); ?> - <?php echo htmlspecialchars(t('chronology.title_suffix'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="chronology-container">
        <h1 class="chronology-title">
            <?php echo htmlspecialchars(display_title($anime)); ?>
            <small><?php echo htmlspecialchars(t('chronology.subtitle'), ENT_QUOTES, 'UTF-8'); ?></small>
        </h1>

        <?php // 1.1.15: single cycle button - release -> story -> both -> release. ?>
        <form method="POST" action="set_chrono_mode.php" class="chrono-mode-toggle">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="mode" value="<?php echo htmlspecialchars(chrono_next_mode($chronoMode), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="chrono-mode-btn" title="<?php echo htmlspecialchars(t('chrono.mode.toggle_hint'), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="fas fa-sort"></i>
                <?php echo htmlspecialchars(sprintf(t('chrono.mode.showing'), chrono_mode_label($chronoMode)), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>

        <?php foreach ($views as $view): ?>
        <?php if ($view['label'] !== null): ?>
        <h2 class="chrono-section-heading"><?php echo htmlspecialchars($view['label'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php endif; ?>
        <div class="chronology-timeline">
            <?php foreach ($view['timeline'] as $item): ?>
                <?php if ($item['type'] === 'episodes'): ?>
                    <?php
                    $status = getEpisodeRangeStatus($watched, $item['start'], $item['end']);
                    $endLabel = $item['end'] !== null ? $item['end'] : '...';
                    $statusClass = $status;

                    if ($status === 'watched') {
                        $statusText = t('chronology.status.watched');
                        $statusCss = 'done';
                    } elseif ($status === 'watching') {
                        $statusText = sprintf(t('chronology.episode.range.watching'), $watched, $endLabel);
                        $statusCss = 'active';
                    } else {
                        $statusText = t('chronology.status.upcoming');
                        $statusCss = 'pending';
                    }
                    ?>
                    <div class="chrono-item <?php echo $statusClass; ?>">
                        <span class="chrono-status <?php echo $statusCss; ?>"><?php echo $statusText; ?></span>
                        <span class="chrono-type-icon">&#128250;</span>
                        <span class="chrono-label">
                            <?php if ($item['end'] === null): ?>
                                <?php echo htmlspecialchars(sprintf(t('chronology.episode.range.single'), $item['start']), ENT_QUOTES, 'UTF-8'); ?>
                            <?php elseif ($item['start'] == $item['end']): ?>
                                <?php echo htmlspecialchars(sprintf(t('chronology.episode.range.single'), $item['start']), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars(sprintf(t('chronology.episode.range.multi'), $item['start'], $item['end']), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($status === 'watching'): ?>
                            <div class="chrono-progress">
                                <?php
                                $rangeTotal = ($item['end'] !== null) ? ($item['end'] - $item['start'] + 1) : '?';
                                $rangeDone = $watched - $item['start'] + 1;
                                echo htmlspecialchars(sprintf(t('chronology.episode.progress'), $rangeDone, (string)$rangeTotal), ENT_QUOTES, 'UTF-8');
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($item['type'] === 'anime'): ?>
                    <?php
                    if ($item['watch_status'] === 'Watched') {
                        $statusClass = 'watched';
                        $statusText = htmlspecialchars(t('chronology.status.watched'));
                        $statusCss = 'done';
                    } elseif ($item['watch_status'] === 'Watching') {
                        $statusClass = 'watching';
                        $statusText = htmlspecialchars(t('chronology.status.watching'));
                        $statusCss = 'active';
                    } else {
                        $statusClass = 'upcoming';
                        $statusText = htmlspecialchars(watch_status_label($item['watch_status']));
                        $statusCss = 'pending';
                    }
                    ?>
                    <div class="chrono-item <?php echo $statusClass; ?>">
                        <span class="chrono-status <?php echo $statusCss; ?>"><?php echo $statusText; ?></span>
                        <span class="chrono-type-icon"><?php echo getMediaTypeIcon($item['media_type']); ?></span>
                        <span class="chrono-label">
                            <a href="anime_details.php?id=<?php echo (int)$item['id']; ?>">
                                <?php echo htmlspecialchars(display_title($item)); ?>
                            </a>
                            <?php if (!empty($item['media_type'])): ?>
                                <small>(<?php echo htmlspecialchars($item['media_type']); ?>)</small>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($item['note'])): ?>
                            <div class="chrono-progress"><?php echo htmlspecialchars($item['note']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div class="chronology-back">
            <a href="anime_details.php?id=<?php echo (int)$anime['id']; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('chronology.back_to_details'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</body>
</html>
