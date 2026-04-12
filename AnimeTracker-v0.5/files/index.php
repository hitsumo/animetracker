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

// Türleri çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

// Silme islemi — POST + CSRF token
// GET kullanmiyoruz cunku (a) HTTP standartina aykiri, (b) tarayici pre-fetch
// veya <img> tag injection ile kazara/niyetli silinebilir, (c) CSRF saldirisi
// icin ideal yuzey. Offline single-user app icin risk dusuk ama disiplin onemli.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.');
    }

    $delete_id = (int)$_POST['delete_id'];

    // Once image_path'i al ki DELETE sonrasi disktan silebilelim
    $img_stmt = $pdo->prepare("SELECT image_path FROM animes WHERE id = ?");
    $img_stmt->execute([$delete_id]);
    $image_path = $img_stmt->fetchColumn();

    // DB'den sil
    $stmt = $pdo->prepare("DELETE FROM animes WHERE id = ?");
    $stmt->execute([$delete_id]);

    // Resmi disktan sil (varsa). Basarisiz olsa bile delete tamamlandi.
    if (!empty($image_path) && file_exists(__DIR__ . '/' . $image_path)) {
        @unlink(__DIR__ . '/' . $image_path);
    }

    header("Location: index.php");
    exit();
}

// Sıralama parametrelerini al
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Geçerli sıralama sütunlarını tanımla
$valid_sort_columns = ['title', 'watch_status', 'watched_episodes', 'next_episode_date'];
if (!in_array($sort_column, $valid_sort_columns)) {
    $sort_column = 'title';
}

// Geçerli sıralama yönlerini tanımla
$valid_sort_orders = ['asc', 'desc'];
if (!in_array($sort_order, $valid_sort_orders)) {
    $sort_order = 'asc';
}

// Filtre parametrelerini al
$genre_filter = isset($_GET['genre_filter']) ? $_GET['genre_filter'] : '';
$watch_status_filter = isset($_GET['watch_status_filter']) ? $_GET['watch_status_filter'] : '';
$broadcast_status_filter = isset($_GET['broadcast_status_filter']) ? $_GET['broadcast_status_filter'] : '';
$letter_filter = isset($_GET['letter_filter']) ? $_GET['letter_filter'] : '';

// Sayfa basina gosterilecek anime sayisi
$allowed_per_page = [10, 20, 30, 50, 100, 0]; // 0 = hepsi
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, $allowed_per_page, true)) {
    $per_page = 10;
}

// SQL sorgusunu oluştur
$sql = "SELECT * FROM animes WHERE 1=1";

if ($genre_filter) {
    $sql .= " AND genres LIKE :genre";
}

if ($watch_status_filter) {
    $sql .= " AND watch_status = :status";
}

if ($broadcast_status_filter) {
    $sql .= " AND status = :broadcast_status";
}

// Harf filtresi: A-Z tek harf, "0-9" rakam, "Other" hicbiri
if ($letter_filter) {
    if ($letter_filter === '0-9') {
        $sql .= " AND title REGEXP '^[0-9]'";
    } elseif ($letter_filter === 'Other') {
        $sql .= " AND title NOT REGEXP '^[A-Za-z0-9]'";
    } elseif (preg_match('/^[A-Za-z]$/', $letter_filter)) {
        $sql .= " AND title LIKE :letter";
    }
}

// Sıralama ekle
$sql .= " ORDER BY " . $sort_column . " " . strtoupper($sort_order);

// Özel sıralama durumları
if ($sort_column == 'watched_episodes') {
    $sql = "SELECT * FROM animes WHERE 1=1";
    
    if ($genre_filter) {
        $sql .= " AND genres LIKE :genre";
    }
    
    if ($watch_status_filter) {
        $sql .= " AND watch_status = :status";
    }
    
    if ($broadcast_status_filter) {
        $sql .= " AND status = :broadcast_status";
    }
    
    if ($letter_filter) {
        if ($letter_filter === '0-9') {
            $sql .= " AND title REGEXP '^[0-9]'";
        } elseif ($letter_filter === 'Other') {
            $sql .= " AND title NOT REGEXP '^[A-Za-z0-9]'";
        } elseif (preg_match('/^[A-Za-z]$/', $letter_filter)) {
            $sql .= " AND title LIKE :letter";
        }
    }
    
    $sql .= " ORDER BY watched_episodes " . strtoupper($sort_order) . ", total_episodes " . strtoupper($sort_order);
}

$stmt = $pdo->prepare($sql);

if ($genre_filter) {
    $stmt->bindValue(':genre', '%' . $genre_filter . '%');
}
if ($watch_status_filter) {
    $stmt->bindValue(':status', $watch_status_filter);
}
if ($broadcast_status_filter) {
    $stmt->bindValue(':broadcast_status', $broadcast_status_filter);
}
if ($letter_filter && preg_match('/^[A-Za-z]$/', $letter_filter)) {
    $stmt->bindValue(':letter', $letter_filter . '%');
}

$stmt->execute();
$animes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Her anime için kontrollerimizi yapalım
foreach ($animes as $key => $anime) {
    // Anime tamamlanmış mı kontrol et ve güncelle
    $animes[$key] = checkIfAnimeCompleted($pdo, $anime);
    
    // Sonraki bölüm tarihini kontrol et
    if (!empty($anime['next_episode_date'])) {
        updateNextEpisodeDate($pdo, $anime);
    }
}

// Toplam sayiyi sakla (gosterim oncesi), sonra limite gore kes
$total_results = count($animes);
if ($per_page > 0 && $total_results > $per_page) {
    $animes = array_slice($animes, 0, $per_page);
}

// Sıralama bağlantısı oluşturma fonksiyonu
function getSortLink($column, $order, $genre_filter, $watch_status_filter) {
    $params = [
        'sort' => $column,
        'order' => $order
    ];
    
    if ($genre_filter) {
        $params['genre_filter'] = $genre_filter;
    }
    
    if ($watch_status_filter) {
        $params['watch_status_filter'] = $watch_status_filter;
    }
    
    global $broadcast_status_filter;
    if ($broadcast_status_filter) {
        $params['broadcast_status_filter'] = $broadcast_status_filter;
    }
    
    global $letter_filter;
    if ($letter_filter) {
        $params['letter_filter'] = $letter_filter;
    }
    
    global $per_page;
    if ($per_page !== 10) {
        $params['per_page'] = $per_page;
    }
    
    return '?' . http_build_query($params);
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
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .next-episode-cell {
            vertical-align: middle;
            white-space: normal;
        }
        
        .next-episode-info {
            margin: 0;
            padding: 0;
            font-family: inherit;
            font-size: inherit;
            color: #4a90e2;
            white-space: pre-line;
            line-height: 1.4;
            background: none;
            border: none;
        }
        
        .sort-buttons {
            display: inline-block;
            margin-left: 5px;
            vertical-align: middle;
        }
        
        .sort-button {
            display: inline-block;
            width: 16px;
            height: 16px;
            line-height: 16px;
            text-align: center;
            margin: 0 2px;
            color: #666;
            text-decoration: none;
            font-size: 12px;
            border: 1px solid #ccc;
            border-radius: 3px;
            background-color: #f9f9f9;
        }
        
        .sort-button:hover {
            background-color: #e9e9e9;
        }
        
        .sort-button.active {
            background-color: #4a90e2;
            color: white;
            border-color: #3a80d2;
        }
        
        th {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="list_settings.php" class="about-link">Liste Ayarları</a>
            <a href="statistics.php" class="about-link">İstatistikler</a>
        </div>
        <div class="page-title">
            ANİME İZLEME LİSTESİ
        </div>
        
        <div class="filter-container">
            <form method="GET" action="" onsubmit="for(var i=0;i&lt;this.elements.length;i++){var el=this.elements[i];if(el.name&amp;&amp;el.value===''){el.disabled=true;}}">
                <div class="filter-group">
                    <label for="genre_filter">Türe Göre Filtrele:</label>
                    <select name="genre_filter" id="genre_filter">
                        <option value="">Tümü</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>" 
                                    <?php echo $genre_filter == $genre['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group">
                    <label for="watch_status_filter">İzleme Durumuna Göre Filtrele:</label>
                    <select name="watch_status_filter" id="watch_status_filter">
                        <option value="">Tümü</option>
                        <option value="İzlendi" <?php echo $watch_status_filter == 'İzlendi' ? 'selected' : ''; ?>>İzlendi</option>
                        <option value="İzleniyor" <?php echo $watch_status_filter == 'İzleniyor' ? 'selected' : ''; ?>>İzleniyor</option>
                        <option value="İzlenme Planlandı" <?php echo $watch_status_filter == 'İzlenme Planlandı' ? 'selected' : ''; ?>>İzlenme Planlandı</option>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group">
                    <label for="broadcast_status_filter">Yayın Durumuna Göre Filtrele:</label>
                    <select name="broadcast_status_filter" id="broadcast_status_filter">
                        <option value="">Tümü</option>
                        <option value="Yayın Devam Ediyor" <?php echo $broadcast_status_filter == 'Yayın Devam Ediyor' ? 'selected' : ''; ?>>Yayın Devam Ediyor</option>
                        <option value="Yayın Tamamlandı" <?php echo $broadcast_status_filter == 'Yayın Tamamlandı' ? 'selected' : ''; ?>>Yayın Tamamlandı</option>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group filter-full">
                    <details class="letter-filter-details" <?php echo $letter_filter ? 'open' : ''; ?>>
                        <summary>Harfe Göre Filtrele <?php echo $letter_filter ? '(' . htmlspecialchars($letter_filter) . ')' : ''; ?></summary>
                        <div class="letter-filter">
                        <?php
                        // Mevcut diger filtreleri korumak icin querystring olustur
                        $preserve = [];
                        if ($genre_filter) $preserve['genre_filter'] = $genre_filter;
                        if ($watch_status_filter) $preserve['watch_status_filter'] = $watch_status_filter;
                        if ($broadcast_status_filter) $preserve['broadcast_status_filter'] = $broadcast_status_filter;
                        if ($per_page !== 10) $preserve['per_page'] = $per_page;

                        $letters = array_merge(['All', '0-9'], range('A', 'Z'), ['Other']);
                        foreach ($letters as $L) {
                            $params = $preserve;
                            if ($L !== 'All') $params['letter_filter'] = $L;
                            $url = '?' . http_build_query($params);
                            $active_class = ($letter_filter === $L || ($L === 'All' && !$letter_filter)) ? ' active' : '';
                            echo '<a href="' . htmlspecialchars($url) . '" class="letter-btn' . $active_class . '">' . htmlspecialchars($L) . '</a>';
                        }
                        ?>
                        </div>
                    </details>
                </div>

                <div style="margin-top: 20px;"></div>
                <div class="filter-group">
                    <label for="per_page">Sayfada Göster:</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $per_page == 30 ? 'selected' : ''; ?>>30</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="0" <?php echo $per_page == 0 ? 'selected' : ''; ?>>Hepsi</option>
                    </select>
                </div>
                
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                
                <div class="form-actions filter-full">
                    <input type="submit" value="Filtrele">
                </div>
            </form>
        </div>

        <div class="button-container">
            <a href="add_anime.php" class="anime-list-button">Yeni Anime Ekle</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>
                        Anime
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('title', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'title' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('title', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'title' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th>
                        Durum
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('watch_status', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watch_status' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('watch_status', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watch_status' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th>
                        İzlenen Bölüm
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('watched_episodes', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watched_episodes' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('watched_episodes', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watched_episodes' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th>Resim</th>
                    <th>
                        Sonraki Bölüm
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('next_episode_date', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'next_episode_date' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('next_episode_date', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'next_episode_date' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th style="text-align: center;">Eylem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($animes) > 0): ?>
                    <?php foreach ($animes as $anime): ?>
                        <tr>
                            <td><span class="list-anime-title" onclick="toggleAnimeTitle(this)" title="Tam ismi gormek icin tiklayin"><?php echo htmlspecialchars($anime['title']); ?></span></td>
                            <td><?php echo htmlspecialchars($anime['watch_status']); ?></td>
                            <td class="episode-count"><?php
                                // Episode display logic (v0.5+):
                                //  - total_episodes set  -> watched/total (finished or short series)
                                //  - total NULL, aired set -> watched/aired (yayında) (long ongoing series)
                                //  - everything NULL     -> watched/?
                                if (!empty($anime['total_episodes'])) {
                                    echo htmlspecialchars($anime['watched_episodes'] . '/' . $anime['total_episodes']);
                                } elseif (!empty($anime['aired_episodes'])) {
                                    echo htmlspecialchars($anime['watched_episodes'] . '/' . $anime['aired_episodes']) . ' <small>(yayında)</small>';
                                } else {
                                    echo htmlspecialchars($anime['watched_episodes']) . '/?';
                                }
                            ?></td>
                            <td><img src="<?php echo htmlspecialchars($anime['image_path']); ?>" alt="<?php echo htmlspecialchars($anime['title']); ?>" width="100"></td>
                            <td class="next-episode-cell">
<?php 
if ($anime['status'] == 'Yayın Tamamlandı') {
    echo "Yayın Tamamlandı";
} else if (!empty($anime['next_episode_date'])) {
    echo '<pre class="next-episode-info">' . getTimeUntilNextEpisode($anime['next_episode_date'], $anime['watched_episodes'], $anime['total_episodes'] ?? 0, $anime['aired_episodes'] ?? 0) . '</pre>';
} else {
    echo "-";
}
?>
</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="anime_details.php?id=<?php echo $anime['id']; ?>" class="more-button">Daha Fazla</a>
                                    <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button">Düzenle</a>
                                    <form method="POST" action="index.php"
                                          onsubmit="return confirm('Bu animeyi silmek istediğinize emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$anime['id']; ?>">
                                        <button type="submit" class="delete-button">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">Hiç anime bulunamadı.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
    // Anime ismini tikla-genislet. Uzun isimler CSS ile "..." seklinde
    // kirpiliyor, kullanici tiklayinca tam halini gosteriyoruz. Tekrar
    // tiklayinca yine kirpiliyor (toggle).
    function toggleAnimeTitle(element) {
        element.classList.toggle('expanded');
    }
    </script>
</body>
</html>