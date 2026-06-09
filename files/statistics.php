<?php
/**
 * Anime Tracker - Istatistik Sayfasi
 * Toplam anime sayisi, medya turu dagilimi, yayin/izleme durumu istatistikleri
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Sayfa dilini baslat
lang_init($pdo);

// Toplam anime sayisi
$total = $pdo->query("SELECT COUNT(*) FROM animes")->fetchColumn();

// Medya turune gore dagilim (NULL olanlari "Belirtilmemis" olarak grupla)
$by_media = $pdo->query("
    SELECT COALESCE(NULLIF(media_type, ''), 'Belirtilmemis') AS media_type, COUNT(*) AS cnt
    FROM animes
    GROUP BY COALESCE(NULLIF(media_type, ''), 'Belirtilmemis')
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Yayin durumuna gore
$by_status = $pdo->query("
    SELECT status, COUNT(*) AS cnt FROM animes GROUP BY status ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Izleme durumuna gore
// 0.6: ASCII enum geciste sabit dropdown sirasiyla goster (Watched ->
// Watching -> PlanToWatch -> OnHold). Veride hic OnHold yoksa GROUP BY
// o satiri uretmez; biz yine de sifir gostermek istiyoruz - kullanici
// 4. ozelligin var oldugunu gorsun. Bunu yapmak icin SQL sonucunu ASCII
// degerine gore lookup'a cevirip helper'in sirasiyla doluyoruz.
// watch_status is personal (user_anime, 1.0.1). Count every catalog anime
// grouped by the current user's status, defaulting un-tracked animes to
// PlanToWatch (matches the old animes default).
$by_watch_stmt = $pdo->prepare("
    SELECT COALESCE(ua.watch_status, 'PlanToWatch') AS watch_status,
           COUNT(*) AS cnt
    FROM animes a
    LEFT JOIN user_anime ua
           ON ua.anime_id = a.id AND ua.user_id = :uid
    GROUP BY COALESCE(ua.watch_status, 'PlanToWatch')
");
$by_watch_stmt->execute([':uid' => current_user_id()]);
$by_watch_raw = $by_watch_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$by_watch = [];
foreach (watch_status_options() as $ws_value => $ws_label) {
    $by_watch[] = [
        'label' => $ws_label,
        'cnt'   => (int)($by_watch_raw[$ws_value] ?? 0),
    ];
}

// Toplam izlenen bolum sayisi
// watched_episodes is personal (user_anime, 1.0.1). Sum the current
// user's rows; un-tracked animes have no row and contribute 0.
$total_watched_stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(watched_episodes), 0) FROM user_anime WHERE user_id = :uid"
);
$total_watched_stmt->execute([':uid' => current_user_id()]);
$total_watched = (int)$total_watched_stmt->fetchColumn();

// Emotion distribution (0.6.1 user_anime_emotion table). Scoped to the
// current user via current_user_id() (1.0.x data model). The table is
// already user_id keyed, so this just binds the id: in single-user mode
// current_user_id() returns 1 (behaviour unchanged); in multi-user mode it
// returns the session user. idx_emotion serves this query. Only marked
// emotions, most-frequent first: the stat answers "what is in the data";
// the full palette shows on detail + recommendations pages.
$by_emotion_stmt = $pdo->prepare("
    SELECT emotion, COUNT(*) AS cnt
    FROM user_anime_emotion
    WHERE user_id = :uid
    GROUP BY emotion
    ORDER BY cnt DESC
");
$by_emotion_stmt->execute([':uid' => current_user_id()]);
$by_emotion = $by_emotion_stmt->fetchAll(PDO::FETCH_ASSOC);

// Ozet: toplam isaret sayisi (satirlardan toplanir, ek sorgu yok) +
// kac farkli anime isaretlenmis (bir anime 3 duyguya kadar alabilir,
// o yuzden ayri DISTINCT sayim gerekir).
$emotion_total_marks = 0;
foreach ($by_emotion as $er) {
    $emotion_total_marks += (int)$er['cnt'];
}
$emotion_anime_count_stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT anime_id) FROM user_anime_emotion WHERE user_id = :uid"
);
$emotion_anime_count_stmt->execute([':uid' => current_user_id()]);
$emotion_anime_count = (int)$emotion_anime_count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(t('statistics.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .stats-container { max-width: 1100px; margin: 30px auto; padding: 20px; }
        .stats-card { background: #f4f7fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stats-grid .stats-card { margin-bottom: 0; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr; } }
        .stats-card h2 { margin-top: 0; color: #2c5282; border-bottom: 2px solid #cbd5e0; padding-bottom: 8px; }
        .stats-big { font-size: 2.5em; font-weight: bold; color: #2b6cb0; text-align: center; }
        .stats-label { text-align: center; color: #4a5568; font-size: 1.1em; margin-top: 5px; }
        table.stats-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.stats-table th, table.stats-table td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        table.stats-table th { background: #edf2f7; }
        table.stats-table td:last-child { text-align: right; font-weight: bold; color: #2b6cb0; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2b6cb0; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .stats-emotion-summary { color: #4a5568; margin: 0 0 12px; }
        .stats-emotion-empty { color: #4a5568; margin: 6px 0; }
        table.stats-table td .emotion-badge { font-size: 0.95em; }
    </style>
</head>
<body>
<div class="stats-container">
    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>
    <h1><?php echo htmlspecialchars(t('statistics.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="stats-card">
        <div class="stats-big"><?php echo $total; ?></div>
        <div class="stats-label"><?php echo htmlspecialchars(t('statistics.label.total_anime'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="stats-big" style="margin-top:20px;"><?php echo $total_watched; ?></div>
        <div class="stats-label"><?php echo htmlspecialchars(t('statistics.label.total_watched'), ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <div class="stats-grid">
    <div class="stats-card">
        <h2><?php echo htmlspecialchars(t('statistics.section.by_media'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <table class="stats-table">
            <tr><th><?php echo htmlspecialchars(t('statistics.col.type'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(t('statistics.col.count'), ENT_QUOTES, 'UTF-8'); ?></th></tr>
            <?php foreach ($by_media as $row): ?>
                <tr><td><?php
                    $mt = $row['media_type'];
                    echo htmlspecialchars($mt === 'Belirtilmemis' ? t('statistics.value.unspecified') : $mt);
                ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="stats-card">
        <h2><?php echo htmlspecialchars(t('statistics.section.by_broadcast'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <table class="stats-table">
            <tr><th><?php echo htmlspecialchars(t('statistics.col.status'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(t('statistics.col.count'), ENT_QUOTES, 'UTF-8'); ?></th></tr>
            <?php foreach ($by_status as $row): ?>
                <tr><td><?php
                    // animes.status DB enum: Yayin Tamamlandi / Yayin Devam Ediyor (TR)
                    // Index sayfasinin broadcast.* anahtarlarini yeniden kullaniriz.
                    $s = $row['status'];
                    if ($s === 'Yayın Tamamlandı') {
                        $sLabel = t('index.broadcast.finished');
                    } elseif ($s === 'Yayın Devam Ediyor') {
                        $sLabel = t('index.broadcast.ongoing');
                    } else {
                        $sLabel = $s; // bilinmeyen deger ham gosterilir
                    }
                    echo htmlspecialchars($sLabel);
                ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="stats-card">
        <h2><?php echo htmlspecialchars(t('statistics.section.by_watch'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <table class="stats-table">
            <tr><th><?php echo htmlspecialchars(t('statistics.col.status'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(t('statistics.col.count'), ENT_QUOTES, 'UTF-8'); ?></th></tr>
            <?php foreach ($by_watch as $row): ?>
                <tr><td><?php echo htmlspecialchars($row['label']); ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="stats-card">
        <h2><?php echo htmlspecialchars(t('statistics.section.by_emotion'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if ($emotion_total_marks === 0): ?>
            <p class="stats-emotion-empty"><?php echo htmlspecialchars(t('statistics.emotion.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
            <p class="stats-emotion-summary"><?php
                echo htmlspecialchars(sprintf(t('statistics.emotion.summary'), $emotion_total_marks, $emotion_anime_count), ENT_QUOTES, 'UTF-8');
            ?></p>
            <table class="stats-table">
                <tr><th><?php echo htmlspecialchars(t('statistics.col.emotion'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(t('statistics.col.count'), ENT_QUOTES, 'UTF-8'); ?></th></tr>
                <?php foreach ($by_emotion as $row): ?>
                    <tr>
                        <td><span class="emotion-badge emotion-badge-<?php echo emotion_css_class($row['emotion']); ?>"><?php echo htmlspecialchars(emotion_label($row['emotion'])); ?></span></td>
                        <td><?php echo (int)$row['cnt']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
