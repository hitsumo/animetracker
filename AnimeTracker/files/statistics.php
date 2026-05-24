<?php
/**
 * Anime Tracker - Istatistik Sayfasi
 * Toplam anime sayisi, medya turu dagilimi, yayin/izleme durumu istatistikleri
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

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
$by_watch_raw = $pdo->query("
    SELECT watch_status, COUNT(*) AS cnt FROM animes GROUP BY watch_status
")->fetchAll(PDO::FETCH_KEY_PAIR);
$by_watch = [];
foreach (watch_status_options() as $ws_value => $ws_label) {
    $by_watch[] = [
        'label' => $ws_label,
        'cnt'   => (int)($by_watch_raw[$ws_value] ?? 0),
    ];
}

// Toplam izlenen bolum sayisi
$total_watched = (int)$pdo->query("SELECT COALESCE(SUM(watched_episodes),0) FROM animes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İstatistikler - Anime Tracker</title>
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
    </style>
</head>
<body>
<div class="stats-container">
    <a href="index.php" class="back-link">&larr; Ana Sayfaya Dön</a>
    <h1>İstatistikler</h1>

    <div class="stats-card">
        <div class="stats-big"><?php echo $total; ?></div>
        <div class="stats-label">Toplam Anime</div>
        <div class="stats-big" style="margin-top:20px;"><?php echo $total_watched; ?></div>
        <div class="stats-label">Toplam İzlenen Bölüm</div>
    </div>

    <div class="stats-grid">
    <div class="stats-card">
        <h2>Medya Türüne Göre</h2>
        <table class="stats-table">
            <tr><th>Tür</th><th>Adet</th></tr>
            <?php foreach ($by_media as $row): ?>
                <tr><td><?php echo htmlspecialchars($row['media_type']); ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="stats-card">
        <h2>Yayın Durumuna Göre</h2>
        <table class="stats-table">
            <tr><th>Durum</th><th>Adet</th></tr>
            <?php foreach ($by_status as $row): ?>
                <tr><td><?php echo htmlspecialchars($row['status']); ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="stats-card">
        <h2>İzleme Durumuna Göre</h2>
        <table class="stats-table">
            <tr><th>Durum</th><th>Adet</th></tr>
            <?php foreach ($by_watch as $row): ?>
                <tr><td><?php echo htmlspecialchars($row['label']); ?></td><td><?php echo $row['cnt']; ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    </div>
</div>
</body>
</html>
