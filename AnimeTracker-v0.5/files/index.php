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

// Master genre list for the filter dropdown. Fetched via the helper
// so the rest of the page does not have to know which table the data
// lives in.
$genres = getAllGenres($pdo);

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
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Sayfa basina gosterilecek anime sayisi
$allowed_per_page = [10, 20, 30, 50, 100, 0]; // 0 = hepsi
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, $allowed_per_page, true)) {
    $per_page = 10;
}

// Genre filter clause used in both the main SELECT and the special
// watched_episodes branch below. Defined once so the two stay in sync.
// Uses an IN-subquery against the anime_genres join table so the outer
// SELECT * does not need to be rewritten as a JOIN. MySQL 5.6+ rewrites
// this as a semi-join internally, so there is no performance penalty.
// Match is by exact genre name (no LIKE wildcards) - this fixes the
// false-positive bug where the old "genres LIKE %Komedi%" matched
// "Romantik Komedi" too.
$genre_filter_clause = " AND id IN (
    SELECT ag.anime_id
    FROM anime_genres ag
    INNER JOIN genres g ON g.id = ag.genre_id
    WHERE g.name = :genre
)";

// SQL sorgusunu oluştur
$sql = "SELECT * FROM animes WHERE 1=1";

if ($search_query !== '') {
    $sql .= " AND (title LIKE :search1 OR alternative_titles LIKE :search2)";
}

if ($genre_filter) {
    $sql .= $genre_filter_clause;
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
    
    if ($search_query !== '') {
        $sql .= " AND (title LIKE :search1 OR alternative_titles LIKE :search2)";
    }
    
    if ($genre_filter) {
        $sql .= $genre_filter_clause;
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

if ($search_query !== '') {
    $stmt->bindValue(':search1', '%' . $search_query . '%');
    $stmt->bindValue(':search2', '%' . $search_query . '%');
}
if ($genre_filter) {
    // Exact match against genres.name (no wildcards). The old code
    // wrapped the value in % to use LIKE which produced false positives
    // (e.g. "Komedi" matched "Romantik Komedi"). The relational schema
    // makes those collisions impossible.
    $stmt->bindValue(':genre', $genre_filter);
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

// Her anime icin kontrollerimizi yapalim
foreach ($animes as $key => $anime) {
    // Anime tamamlanmis mi kontrol et ve guncelle
    $animes[$key] = checkIfAnimeCompleted($pdo, $animes[$key]);
    
    // Sonraki bolum tarihini kontrol et ve aired_episodes guncelle.
    // Pass by reference: fonksiyon anime array'ini yerinde gunceller,
    // boylece ayni sayfa yuklemesinde guncel veri gosterilir.
    if (!empty($animes[$key]['next_episode_date'])) {
        updateNextEpisodeDate($pdo, $animes[$key]);
    }
}

// Toplam sayiyi sakla, sayfa bazli kesim yap
$total_results = count($animes);
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

if ($per_page > 0 && $total_results > $per_page) {
    $total_pages = (int)ceil($total_results / $per_page);
    if ($current_page > $total_pages) $current_page = $total_pages;
    $offset = ($current_page - 1) * $per_page;
    $animes = array_slice($animes, $offset, $per_page);
} else {
    $total_pages = 1;
    $current_page = 1;
}

// Sayfalama linklerini olusturan yardimci fonksiyon
function buildPaginationUrl($page) {
    $params = $_GET;
    if ($page <= 1) {
        unset($params['page']);
    } else {
        $params['page'] = $page;
    }
    // Bos parametreleri temizle
    foreach ($params as $k => $v) {
        if ($v === '') unset($params[$k]);
    }
    return '?' . http_build_query($params);
}

function renderPagination($current_page, $total_pages, $total_results, $per_page) {
    if ($total_pages <= 1) return;
    
    $start = ($current_page - 1) * $per_page + 1;
    $end = min($current_page * $per_page, $total_results);
    
    echo '<div class="pagination-bar">';
    echo '<span class="pagination-info">' . $total_results . ' anime, sayfa ' . $current_page . '/' . $total_pages . ' (' . $start . '-' . $end . ')</span>';
    echo '<div class="pagination-links">';
    
    // Onceki
    if ($current_page > 1) {
        echo '<a href="' . buildPaginationUrl($current_page - 1) . '" class="page-link">&laquo; Onceki</a>';
    }
    
    // Sayfa numaralari
    $range = 2; // aktif sayfanin iki yaninda kac sayfa gosterilsin
    $show_start = max(1, $current_page - $range);
    $show_end = min($total_pages, $current_page + $range);
    
    if ($show_start > 1) {
        echo '<a href="' . buildPaginationUrl(1) . '" class="page-link">1</a>';
        if ($show_start > 2) echo '<span class="page-dots">...</span>';
    }
    
    for ($i = $show_start; $i <= $show_end; $i++) {
        if ($i == $current_page) {
            echo '<span class="page-link active">' . $i . '</span>';
        } else {
            echo '<a href="' . buildPaginationUrl($i) . '" class="page-link">' . $i . '</a>';
        }
    }
    
    if ($show_end < $total_pages) {
        if ($show_end < $total_pages - 1) echo '<span class="page-dots">...</span>';
        echo '<a href="' . buildPaginationUrl($total_pages) . '" class="page-link">' . $total_pages . '</a>';
    }
    
    // Sonraki
    if ($current_page < $total_pages) {
        echo '<a href="' . buildPaginationUrl($current_page + 1) . '" class="page-link">Sonraki &raquo;</a>';
    }
    
    echo '</div></div>';
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
    
    global $search_query;
    if ($search_query !== '') {
        $params['q'] = $search_query;
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
        .pagination-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pagination-info {
            color: #666;
            font-size: 0.85em;
        }
        .pagination-links {
            display: flex;
            gap: 4px;
            align-items: center;
            flex-wrap: wrap;
        }
        .page-link {
            display: inline-block;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #4a90e2;
            font-size: 0.9em;
            background: #fff;
        }
        .page-link:hover { background: #f0f0f0; }
        .page-link.active {
            background: #4a90e2;
            color: #fff;
            border-color: #4a90e2;
        }
        .page-dots { color: #999; padding: 0 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="recommendations.php" class="about-link">Ne İzlesem?</a>
            <a href="recent.php" class="about-link">Son Düzenlenenler</a>
            <a href="list_settings.php" class="about-link">Liste Ayarları</a>
            <a href="statistics.php" class="about-link">İstatistikler</a>
            <a href="help.php" class="about-link">Yardım</a>
        </div>
        <div class="page-title">
            ANİME İZLEME LİSTESİ
        </div>

        <div style="max-width: 500px; margin: 15px auto; background: #e9ecef; padding: 15px 20px; border-radius: 8px;">
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Anime ara..." style="flex: 1; padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                <button type="submit" style="padding: 10px 18px; background: #4a90e2; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;">Ara</button>
                <?php if ($search_query !== ''): ?>
                    <a href="index.php" style="padding: 10px 14px; background: #e0e0e0; color: #333; border-radius: 6px; text-decoration: none; font-size: 14px; display: flex; align-items: center;">Temizle</a>
                <?php endif; ?>
            </form>
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
                        if ($search_query !== '') $preserve['q'] = $search_query;
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
                <?php if ($search_query !== ''): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>
                
                <div class="form-actions filter-full">
                    <input type="submit" value="Filtrele">
                </div>
            </form>
        </div>

        <div class="button-container">
            <a href="add_anime.php" class="anime-list-button">Yeni Anime Ekle</a>
        </div>

        <?php renderPagination($current_page, $total_pages, $total_results, $per_page); ?>

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

        <?php renderPagination($current_page, $total_pages, $total_results, $per_page); ?>

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
