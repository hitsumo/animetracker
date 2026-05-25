<?php
/**
 * Anime Tracker - Chronology Page
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Displays the full chronological watch order for an anime that has
 * chronology markers. Episode ranges are interleaved with related
 * anime (films, OVAs, etc.) based on the markers' after_episode values.
 *
 * The page automatically builds the timeline from the markers — no
 * manual ordering needed. Adding/removing markers on the detail page
 * instantly updates this view.
 *
 * Watch progress is shown for each item:
 *   - Episode ranges: based on watched_episodes vs range boundaries
 *   - Related anime: based on their own watch_status field
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

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

// Kronoloji markerlarini cek (after_episode sirali)
$markers = getChronologyMarkers($pdo, $id);

if (empty($markers)) {
    // Marker yoksa detay sayfasina geri don
    header('Location: anime_details.php?id=' . $id);
    exit;
}

// Timeline olustur: bolum araliklari + araya giren animeler
// Mantik:
//   marker[0].after_episode = 54 → Bolum 1-54, sonra Film 1
//   marker[1].after_episode = 97 → Bolum 55-97, sonra Film 2
//   ...
//   Son markerdan sonra: kalan bolumler (total_episodes veya "devam")
$timeline = [];
$prevEnd = 0; // Onceki aralik sonu

foreach ($markers as $m) {
    $rangeStart = $prevEnd + 1;
    $rangeEnd = (int)$m['after_episode'];

    // Bolum araligi (eger aralik gecerli ise)
    if ($rangeStart <= $rangeEnd) {
        $timeline[] = [
            'type' => 'episodes',
            'start' => $rangeStart,
            'end' => $rangeEnd,
        ];
    }

    // Araya giren anime (film, OVA, vs.)
    $timeline[] = [
        'type' => 'anime',
        'id' => (int)$m['related_anime_id'],
        'title' => $m['related_title'],
        'media_type' => $m['related_media_type'],
        'watch_status' => $m['related_watch_status'],
        'note' => $m['note'] ?? null,
    ];

    $prevEnd = $rangeEnd;
}

// Son markerdan sonraki kalan bolumler
$totalEp = $anime['total_episodes'] ?? $anime['aired_episodes'] ?? null;
$remainStart = $prevEnd + 1;

if ($totalEp !== null && $remainStart <= $totalEp) {
    $timeline[] = [
        'type' => 'episodes',
        'start' => $remainStart,
        'end' => (int)$totalEp,
    ];
} elseif ($totalEp === null) {
    // Devam eden anime, son bolum bilinmiyor
    $timeline[] = [
        'type' => 'episodes',
        'start' => $remainStart,
        'end' => null, // "devam ediyor"
    ];
}

$watched = (int)$anime['watched_episodes'];

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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['title']); ?> - Kronoloji</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="chronology-container">
        <h1 class="chronology-title">
            <?php echo htmlspecialchars($anime['title']); ?>
            <small>Kronolojik İzleme Sırası</small>
        </h1>

        <div class="chronology-timeline">
            <?php foreach ($timeline as $item): ?>
                <?php if ($item['type'] === 'episodes'): ?>
                    <?php
                    $status = getEpisodeRangeStatus($watched, $item['start'], $item['end']);
                    $endLabel = $item['end'] !== null ? $item['end'] : '...';
                    $statusClass = $status;
                    
                    if ($status === 'watched') {
                        $statusText = 'Izlendi';
                        $statusCss = 'done';
                    } elseif ($status === 'watching') {
                        $statusText = 'Izleniyor (' . $watched . '/' . $endLabel . ')';
                        $statusCss = 'active';
                    } else {
                        $statusText = 'Sirada';
                        $statusCss = 'pending';
                    }
                    ?>
                    <div class="chrono-item <?php echo $statusClass; ?>">
                        <span class="chrono-status <?php echo $statusCss; ?>"><?php echo $statusText; ?></span>
                        <span class="chrono-type-icon">&#128250;</span>
                        <span class="chrono-label">
                            <?php if ($item['end'] === null): ?>
                                Bolum <?php echo $item['start']; ?>
                            <?php elseif ($item['start'] == $item['end']): ?>
                                Bolum <?php echo $item['start']; ?>
                            <?php else: ?>
                                Bolum <?php echo $item['start']; ?> - <?php echo $item['end']; ?>
                            <?php endif; ?>
                        </span>
                        <?php if ($status === 'watching'): ?>
                            <div class="chrono-progress">
                                <?php
                                $rangeTotal = ($item['end'] !== null) ? ($item['end'] - $item['start'] + 1) : '?';
                                $rangeDone = $watched - $item['start'] + 1;
                                echo $rangeDone . ' / ' . $rangeTotal . ' bolum izlendi';
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($item['type'] === 'anime'): ?>
                    <?php
                    // 0.6.1 fix: 0.6'da DB ASCII enum'a gecti ama bu blok TR
                    // enum karsilastirmasinda kaldi - tum animeler else dalina
                    // (upcoming) dusuyordu, kronoloji sayfasi yanlis goruntulu.
                    // KARARLAR Bolum 2: substring/gevsek karsilastirma kontrolu
                    // disinda "kesin TR enum karsilastirma" da arananacak.
                    if ($item['watch_status'] === 'Watched') {
                        $statusClass = 'watched';
                        $statusText = 'Izlendi';
                        $statusCss = 'done';
                    } elseif ($item['watch_status'] === 'Watching') {
                        $statusClass = 'watching';
                        $statusText = 'Izleniyor';
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
                                <?php echo htmlspecialchars($item['title']); ?>
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

        <div class="chronology-back">
            <a href="anime_details.php?id=<?php echo (int)$anime['id']; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> Detaya Don
            </a>
        </div>
    </div>
</body>
</html>
