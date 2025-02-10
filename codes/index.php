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

// Türleri çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

// Silme işlemi
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM animes WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$delete_id]);
    header("Location: index.php");
    exit();
}

$genre_filter = isset($_GET['genre_filter']) ? $_GET['genre_filter'] : '';
$watch_status_filter = isset($_GET['watch_status_filter']) ? $_GET['watch_status_filter'] : '';

$sql = "SELECT * FROM animes WHERE 1=1";

if ($genre_filter) {
    $sql .= " AND genres LIKE :genre";
}

if ($watch_status_filter) {
    $sql .= " AND watch_status = :status";
}

$stmt = $pdo->prepare($sql);

if ($genre_filter) {
    $stmt->bindValue(':genre', '%' . $genre_filter . '%');
}
if ($watch_status_filter) {
    $stmt->bindValue(':status', $watch_status_filter);
}

$stmt->execute();
$animes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Her anime için sonraki bölüm tarihini kontrol et
foreach ($animes as $anime) {
    if (!empty($anime['next_episode_date'])) {
        updateNextEpisodeDate($pdo, $anime);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anime İzleme Listesi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>
<body>
    <div class="container">
        <div class="page-title">
    Anime İzleme Listesi
</div>
        
        <div class="filter-container">
            <form method="GET" action="">
                <div class="filter-group">
                    <label for="genre_filter">Türe Göre Filtrele:</label>
                    <select name="genre_filter">
                        <option value="">Tümü</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>" 
                                    <?php echo $genre_filter == $genre['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
<div style="margin-top: 20px;"></div> <!-- Ekstra boşluk için -->
                <div class="filter-group">
                    <label for="watch_status_filter">İzleme Durumuna Göre Filtrele:</label>
                    <select name="watch_status_filter">
                        <option value="">Tümü</option>
                        <option value="İzlendi" <?php echo $watch_status_filter == 'İzlendi' ? 'selected' : ''; ?>>İzlendi</option>
                        <option value="İzleniyor" <?php echo $watch_status_filter == 'İzleniyor' ? 'selected' : ''; ?>>İzleniyor</option>
                        <option value="İzlenme Planlandı" <?php echo $watch_status_filter == 'İzlenme Planlandı' ? 'selected' : ''; ?>>İzlenme Planlandı</option>
                    </select>
                </div>
                
                <input type="submit" value="Filtrele">
            </form>
        </div>

        <div class="button-container">
            <a href="add_anime.php" class="anime-list-button">Yeni Anime Ekle</a>
        </div>

        <table>
            <table>
    <tr>
        <th>Anime</th>
        <th>Durum</th>
        <th style="white-space: nowrap;">İzlenen Bölüm</th>  <!-- white-space: nowrap ekledik -->
        <th>Tür</th>
        <th>Resim</th>
        <th>Sonraki Bölüm</th>
        <th>Eylem</th>
    </tr>
            <?php foreach ($animes as $anime): ?>
            <tr>
                <td><?php echo htmlspecialchars($anime['title']); ?></td>
                <td><?php echo htmlspecialchars($anime['watch_status']); ?></td>
                <td><?php echo htmlspecialchars($anime['watched_episodes']); ?></td>
                <td><?php echo htmlspecialchars($anime['genres']); ?></td>
                <td><img src="<?php echo htmlspecialchars($anime['image_path']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>" width="100"></td>
                <td>
                    <?php 
                    if (!empty($anime['next_episode_date'])) {
                        $nextDate = new DateTime($anime['next_episode_date']);
                        $now = new DateTime();

                        if ($nextDate > $now) {
                            $interval = $now->diff($nextDate);
                            echo "Kalan süre: " . $interval->format('%a gün, %h saat, %i dakika');
                        } else {
                            echo "Bölüm yayınlandı.";
                        }
                    } else {
                        echo "-";
                    }
                    ?>
                </td>
                
                <td>
                    <div class="action-buttons">
                        <a href="anime_details.php?id=<?php echo $anime['id']; ?>" class="more-button">Daha Fazla</a>
                        <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button">Düzenle</a>
                        <a href="?delete_id=<?php echo $anime['id']; ?>" class="delete-button" 
                           onclick="return confirm('Bu animeyi silmek istediğinize emin misiniz?');">Sil</a>
                    
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>