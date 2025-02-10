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


require_once 'functions.php';
$pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');

$id = $_GET['id'];
$sql = "SELECT * FROM animes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    echo "Anime bulunamadı.";
    exit();
}

if (!empty($anime['next_episode_date'])) {
    updateNextEpisodeDate($pdo, $anime);
    $stmt->execute([$id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['title']); ?> - Detaylar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>
<body>
    <div class="container">
        <h1>
            <div class="anime-title-container">
                <div class="anime-title page-title">
                    <?php echo htmlspecialchars($anime['title']); ?>
                </div>
            </div>
        </h1>
        
        <div class="anime-header">
            <div class="anime-cover-container">
                <img src="<?php echo htmlspecialchars($anime['image_path']); ?>" 
                    alt="<?php echo htmlspecialchars($anime['title']); ?>" 
                    class="anime-cover">
            </div>
        </div>

        <div class="anime-details-container">
            <div class="anime-details">
                <div class="detail-row">
                    <span class="detail-label">Durum:</span>
                    <span class="detail-value status"><?php echo htmlspecialchars($anime['status']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Toplam Bölüm:</span>
                    <span class="detail-value episode"><?php echo htmlspecialchars($anime['total_episodes']); ?></span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">İzlenen Bölüm:</span>
                    <span class="detail-value episode"><?php echo htmlspecialchars($anime['watched_episodes']); ?></span>
                </div>

                <?php if (!empty($anime['synopsis'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Konu:</span>
                    <span class="detail-value synopsis"><?php echo nl2br(htmlspecialchars($anime['synopsis'])); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($anime['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Notlar:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($anime['notes'])); ?></span>
                </div>
                <?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label">Türler:</span>
                    <div class="detail-value genres">
                        <?php 
                        $genres = explode(',', $anime['genres']);
                        foreach ($genres as $genre): ?>
                            <span class="genre-tag"><?php echo htmlspecialchars(trim($genre)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">İzleme Durumu:</span>
                    <span class="detail-value status-badge <?php echo strtolower(str_replace(' ', '-', $anime['watch_status'])); ?>">
                        <?php echo htmlspecialchars($anime['watch_status']); ?>
                    </span>
                </div>

                <?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
                <div class="broadcast-info">
                    <div class="detail-row">
                        <span class="detail-label">Yayın Günü:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($anime['broadcast_day'] ?? 'Belirtilmemiş'); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Yayın Saati:</span>
                        <span class="detail-value broadcast-time"><?php echo htmlspecialchars($anime['broadcast_time'] ?? 'Belirtilmemiş'); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Sonraki Bölüm:</span>
                        <span class="detail-value next-episode">
                            <?php echo getTimeUntilNextEpisode($anime['next_episode_date']); ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($anime['anidb_link']) || !empty($anime['mal_link'])): ?>
            <div class="external-links">
                <h3>Anime Siteleri</h3>
                <?php if (!empty($anime['anidb_link'])): ?>
                <a href="<?php echo htmlspecialchars($anime['anidb_link']); ?>" 
                   target="_blank" 
                   class="site-link anidb-link">
                    <i class="fas fa-database"></i> AniDB
                </a>
                <?php endif; ?>
                
                <?php if (!empty($anime['mal_link'])): ?>
                <a href="<?php echo htmlspecialchars($anime['mal_link']); ?>" 
                   target="_blank" 
                   class="site-link mal-link">
                    <i class="fas fa-list"></i> MyAnimeList
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> Düzenle
                </a>
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>
</body>
</html>