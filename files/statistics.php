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
// grouped by the current user's status. A missing user_anime row means the
// user has made no choice yet; we surface it as its own "unselected" bucket
// (NULL -> '' via COALESCE) instead of folding it into PlanToWatch.
$by_watch_stmt = $pdo->prepare("
    SELECT COALESCE(ua.watch_status, '') AS watch_status,
           COUNT(*) AS cnt
    FROM animes a
    LEFT JOIN user_anime ua
           ON ua.anime_id = a.id AND ua.user_id = :uid
    GROUP BY COALESCE(ua.watch_status, '')
");
$by_watch_stmt->execute([':uid' => current_user_id()]);
$by_watch_raw = $by_watch_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$by_watch = [];
// '' key = secim yapilmamis: no user_anime row OR an explicit NULL
// watch_status row (1.0.10) - COALESCE folds both to ''. En uste konur.
$by_watch[] = [
    'label' => t('index.watch_status.unselected'),
    'cnt'   => (int)($by_watch_raw[''] ?? 0),
];
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

// Son izlenenler: kullanicinin izleme ilerlemesini en son guncelledigi
// animeler (watched_episodes > 0), ua.updated_at'e gore yeniden eskiye.
// Bu, "+1 izlenen bolum" gibi kisisel izleme aktivitesinin yeridir; recent.php
// artik yalniz katalog duzenlemelerini gosterdigi icin bu gorunum buraya tasindi.
// watch_status / watched_episodes / ua.updated_at hepsi user_anime'da (1.0.1),
// current_user_id() ile kapsanir (self-host=1, online=oturum kullanicisi).
$recent_watched_stmt = $pdo->prepare("
    SELECT a.id, a.title, a.total_episodes, a.aired_episodes,
           ua.watch_status, ua.watched_episodes, ua.updated_at
    FROM user_anime ua
    JOIN animes a ON a.id = ua.anime_id
    WHERE ua.user_id = :uid AND ua.watched_episodes > 0
    ORDER BY ua.updated_at DESC
    LIMIT 10
");
$recent_watched_stmt->execute([':uid' => current_user_id()]);
$recent_watched = $recent_watched_stmt->fetchAll(PDO::FETCH_ASSOC);

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

// Global duygu dagilimi: tum kullanicilarin isaretleri (user_id filtresi YOK).
// Global sekmede gosterilir. Self-host'ta (tek kullanici) kullanici dagilimiyla
// ayni cikar; online'da herkesin toplamidir.
$by_emotion_global = $pdo->query("
    SELECT emotion, COUNT(*) AS cnt
    FROM user_anime_emotion
    GROUP BY emotion
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);
$emotion_total_marks_global = 0;
foreach ($by_emotion_global as $er) {
    $emotion_total_marks_global += (int)$er['cnt'];
}
$emotion_anime_count_global = (int)$pdo->query(
    "SELECT COUNT(DISTINCT anime_id) FROM user_anime_emotion"
)->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
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
        .stats-tabs { display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 2px solid #cbd5e0; }
        .stats-tab-btn { appearance: none; background: transparent; border: none; border-bottom: 3px solid transparent; padding: 10px 18px; font-size: 1.05em; color: #4a5568; cursor: pointer; margin-bottom: -2px; }
        .stats-tab-btn:hover { color: #2b6cb0; }
        .stats-tab-btn.active { color: #2b6cb0; border-bottom-color: #2b6cb0; font-weight: bold; }
        .stats-tab-panel { display: none; }
        .stats-tab-panel.active { display: block; }
    </style>
</head>
<body>
<div class="stats-container">
    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>
    <h1><?php echo htmlspecialchars(t('statistics.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <div class="stats-tabs">
        <button type="button" class="stats-tab-btn active" data-tab="user"><?php echo htmlspecialchars(t('statistics.tab.user'), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="button" class="stats-tab-btn" data-tab="recent"><?php echo htmlspecialchars(t('statistics.tab.recent_watched'), ENT_QUOTES, 'UTF-8'); ?></button>
        <button type="button" class="stats-tab-btn" data-tab="global"><?php echo htmlspecialchars(t('statistics.tab.global'), ENT_QUOTES, 'UTF-8'); ?></button>
    </div>

    <!-- Kullanici istatistigi: kisiye ozel veriler (izlenen bolum, izleme durumu, duygular) -->
    <div class="stats-tab-panel active" id="stats-panel-user">
        <div class="stats-card">
            <div class="stats-big"><?php echo $total_watched; ?></div>
            <div class="stats-label"><?php echo htmlspecialchars(t('statistics.label.total_watched'), ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <div class="stats-grid">
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
    </div>

    <!-- Son izlenenler: kullanicinin izleme ilerlemesini en son guncelledigi animeler (kisisel) -->
    <div class="stats-tab-panel" id="stats-panel-recent">
        <div class="stats-card">
            <h2><?php echo htmlspecialchars(t('statistics.tab.recent_watched'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if (empty($recent_watched)): ?>
                <p class="stats-emotion-empty"><?php echo htmlspecialchars(t('statistics.recent_watched.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
            <table class="stats-table">
                <tr>
                    <th><?php echo htmlspecialchars(t('index.col.anime'), ENT_QUOTES, 'UTF-8'); ?></th>
                    <th><?php echo htmlspecialchars(t('statistics.col.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                    <th><?php echo htmlspecialchars(t('index.col.watched_episodes'), ENT_QUOTES, 'UTF-8'); ?></th>
                    <th><?php echo htmlspecialchars(t('statistics.col.last_watched'), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
                <?php foreach ($recent_watched as $row): ?>
                    <?php
                        // Bolum gosterimi: izlenen/toplam, yoksa izlenen/yayinlanan (yayinda), yoksa izlenen/?
                        $rw_ep = (int)$row['watched_episodes'];
                        if ($row['total_episodes']) {
                            $rw_epDisplay = $rw_ep . '/' . $row['total_episodes'];
                        } elseif ($row['aired_episodes']) {
                            $rw_epDisplay = $rw_ep . '/' . $row['aired_episodes'] . ' ' . t('index.row.ep_aired_badge');
                        } else {
                            $rw_epDisplay = $rw_ep . '/?';
                        }
                        // Gecen sure (recent.php ile ayni esikler, ayni lang anahtarlari)
                        $rw_diff = time() - strtotime($row['updated_at']);
                        if ($rw_diff < 60) {
                            $rw_timeAgo = t('recent.time.now');
                        } elseif ($rw_diff < 3600) {
                            $rw_timeAgo = sprintf(t('recent.time.minutes_ago'), floor($rw_diff / 60));
                        } elseif ($rw_diff < 86400) {
                            $rw_timeAgo = sprintf(t('recent.time.hours_ago'), floor($rw_diff / 3600));
                        } else {
                            $rw_timeAgo = sprintf(t('recent.time.days_ago'), floor($rw_diff / 86400));
                        }
                    ?>
                    <tr>
                        <td><a href="anime_details.php?id=<?php echo (int)$row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></td>
                        <td><?php echo htmlspecialchars(watch_status_label($row['watch_status'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars($rw_epDisplay); ?></td>
                        <td><?php echo htmlspecialchars($rw_timeAgo); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Global istatistik: katalog geneli veriler. Izlenen bolum sayisi BURADA YOK (kisisel veridir). -->
    <div class="stats-tab-panel" id="stats-panel-global">
        <div class="stats-card">
            <div class="stats-big"><?php echo $total; ?></div>
            <div class="stats-label"><?php echo htmlspecialchars(t('statistics.label.total_anime'), ENT_QUOTES, 'UTF-8'); ?></div>
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
            <h2><?php echo htmlspecialchars(t('statistics.section.by_emotion'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($emotion_total_marks_global === 0): ?>
                <p class="stats-emotion-empty"><?php echo htmlspecialchars(t('statistics.emotion.empty_global'), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <p class="stats-emotion-summary"><?php
                    echo htmlspecialchars(sprintf(t('statistics.emotion.summary'), $emotion_total_marks_global, $emotion_anime_count_global), ENT_QUOTES, 'UTF-8');
                ?></p>
                <table class="stats-table">
                    <tr><th><?php echo htmlspecialchars(t('statistics.col.emotion'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(t('statistics.col.count'), ENT_QUOTES, 'UTF-8'); ?></th></tr>
                    <?php foreach ($by_emotion_global as $row): ?>
                        <tr>
                            <td><span class="emotion-badge emotion-badge-<?php echo emotion_css_class($row['emotion']); ?>"><?php echo htmlspecialchars(emotion_label($row['emotion'])); ?></span></td>
                            <td><?php echo (int)$row['cnt']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        </div>
    </div>
</div>
<script>
(function () {
    var buttons = document.querySelectorAll('.stats-tab-btn');
    var panels = {
        user: document.getElementById('stats-panel-user'),
        recent: document.getElementById('stats-panel-recent'),
        global: document.getElementById('stats-panel-global')
    };
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.getAttribute('data-tab');
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            Object.keys(panels).forEach(function (key) {
                if (panels[key]) { panels[key].classList.toggle('active', key === tab); }
            });
        });
    });
})();
</script>
</body>
</html>
