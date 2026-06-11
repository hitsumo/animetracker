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

// Initialise the i18n layer. Reads display_language from settings on
// the first call of the request and caches the dictionary, so later
// t() calls in this file are pure array lookups.
lang_init($pdo);

// English-title display preference (0.7.2). display_title() uses this for
// the row titles below.
title_pref_init($pdo);

// Master genre list for the filter dropdown. Fetched via the helper
// so the rest of the page does not have to know which table the data
// lives in.
$genres = getAllGenres($pdo);

// Delete operation - POST + CSRF token
// GET kullanmiyoruz cunku (a) HTTP standartina aykiri, (b) tarayici pre-fetch
// veya <img> tag injection ile kazara/niyetli silinebilir, (c) CSRF saldirisi
// icin ideal yuzey. Offline single-user app icin risk dusuk ama disiplin onemli.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Authorization (KORU): deleting a catalog anime mutates shared data, so
    // it must be gated server-side, not just hidden in the UI. Online: only
    // moderator+ may delete; anonymous/regular users are bounced by
    // require_role. Self-host: no-op (owner passes), behaviour unchanged.
    // CSRF alone is not authorization - an anonymous visitor on this page
    // holds a valid token, so the role check is what actually protects it.
    require_role($pdo, 'moderator');

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

// Get sort parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'title';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Define valid sort columns
$valid_sort_columns = ['title', 'watch_status', 'watched_episodes', 'next_episode_date'];
if (!in_array($sort_column, $valid_sort_columns)) {
    $sort_column = 'title';
}

// Define valid sort directions
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
$genre_filter_clause = " AND a.id IN (
    SELECT ag.anime_id
    FROM anime_genres ag
    INNER JOIN genres g ON g.id = ag.genre_id
    WHERE g.name = :genre
)";

// Build the SQL query.
//
// Personal columns (watch_status, watched_episodes, notes,
// user_synopsis/_en) moved to user_anime in 1.0.1. We LEFT JOIN the
// current user's row and expose those values under their original names
// via COALESCE (defaults match the old animes defaults), so the rest of
// this file (filter, sort, render, checkIfAnimeCompleted) keeps reading
// $anime['watch_status'] etc. unchanged. a.* still carries the vestigial
// animes columns; the aliased ua.* values are listed AFTER a.* so PDO's
// associative fetch keeps the user_anime version (last column wins).
// WHERE/ORDER reference ua.* / COALESCE explicitly to avoid ambiguity
// with those vestigial columns (which disappear at the 1.0.3 drop).
$uid = current_user_id();

// Personal-capability flag for UI gating. can('personal') is true for any
// logged-in user and ALWAYS true in self-host (MULTI_USER_MODE off), so the
// self-host list looks exactly as before. Online anonymous visitors get
// false: they have no personal watched state, so the list shows only the
// total episode count and no +/- editing controls.
$canPersonal = can($pdo, 'personal');

// Catalog-curation capability (moderator+). Controls who may edit or delete
// a catalog anime. True in self-host (owner), true online for moderator/admin,
// false for regular/anonymous visitors. Matches edit_anime.php's role gate.
$canModerate = can($pdo, 'moderate');
$select_from = "SELECT a.*,
        COALESCE(ua.watch_status, 'PlanToWatch') AS watch_status,
        ua.watch_status     AS watch_status_raw,
        COALESCE(ua.watched_episodes, 0)         AS watched_episodes,
        ua.notes            AS notes,
        ua.user_synopsis    AS user_synopsis,
        ua.user_synopsis_en AS user_synopsis_en
    FROM animes a
    LEFT JOIN user_anime ua
           ON ua.anime_id = a.id AND ua.user_id = :uid
    WHERE 1=1";

// Multi-user mode: the main catalog list shows only approved entries
// (source='catalog'). User-submitted additions stay as source='local' and are
// listed on pending.php until a moderator promotes them, so they do not appear
// here. Self-host is unfiltered (single owner sees their own local adds), so
// the catalog looks exactly as before.
if (MULTI_USER_MODE) {
    $select_from .= " AND a.source = 'catalog'";
}
$sql = $select_from;

if ($search_query !== '') {
    $sql .= " AND (title LIKE :search1 OR alternative_titles LIKE :search2)";
}

if ($genre_filter) {
    $sql .= $genre_filter_clause;
}

if ($watch_status_filter) {
    if ($watch_status_filter === '__unselected__') {
        // user_anime satiri olmayan (hic secim yapilmamis) animeler
        $sql .= " AND ua.watch_status IS NULL";
    } else {
        $sql .= " AND ua.watch_status = :status";
    }
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

// Add sorting.
// Map the (already validated) sort column to a SQL expression.
// watch_status sorts by the LOCALIZED label alphabet via
// watch_status_sort_expr() (1.0.10) - the order follows the active UI
// language and the "not selected" state (NULL, no user_anime row) takes
// its own alphabetical place instead of being folded into PlanToWatch.
// watched_episodes is user_anime-backed and sorts on the COALESCE
// expression; title / next_episode_date are catalog columns on animes
// (a). This also avoids the bare-column ambiguity between a.* and the
// user_anime join.
$sort_expr_map = [
    'title'             => 'a.title',
    'watch_status'      => watch_status_sort_expr(),
    'watched_episodes'  => 'COALESCE(ua.watched_episodes, 0)',
    'next_episode_date' => 'a.next_episode_date',
];
$sort_expr = $sort_expr_map[$sort_column] ?? 'a.title';
$sql .= " ORDER BY " . $sort_expr . " " . strtoupper($sort_order);

// Special sort cases
if ($sort_column == 'watched_episodes') {
    $sql = $select_from;
    
    if ($search_query !== '') {
        $sql .= " AND (title LIKE :search1 OR alternative_titles LIKE :search2)";
    }
    
    if ($genre_filter) {
        $sql .= $genre_filter_clause;
    }
    
    if ($watch_status_filter) {
        if ($watch_status_filter === '__unselected__') {
            $sql .= " AND ua.watch_status IS NULL";
        } else {
            $sql .= " AND ua.watch_status = :status";
        }
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
    
    $sql .= " ORDER BY COALESCE(ua.watched_episodes, 0) " . strtoupper($sort_order) . ", a.total_episodes " . strtoupper($sort_order);
}

$stmt = $pdo->prepare($sql);

// The user_anime LEFT JOIN is present in both query branches, so :uid is
// always bound.
$stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

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
if ($watch_status_filter && $watch_status_filter !== '__unselected__') {
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
    echo '<span class="pagination-info">' . htmlspecialchars(sprintf(t('index.pagination.info'), $total_results, $current_page, $total_pages, $start, $end)) . '</span>';
    echo '<div class="pagination-links">';

    // Onceki
    if ($current_page > 1) {
        // t() returns the label with HTML entities (&laquo;) preserved -
        // do NOT htmlspecialchars it or the chevron will render as text.
        echo '<a href="' . buildPaginationUrl($current_page - 1) . '" class="page-link">' . t('index.pagination.prev') . '</a>';
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
        echo '<a href="' . buildPaginationUrl($current_page + 1) . '" class="page-link">' . t('index.pagination.next') . '</a>';
    }
    
    echo '</div></div>';
}

// Function to build the sort link
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
<html lang="<?php echo htmlspecialchars(current_lang(), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(t('index.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
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
        
        /* Liste tablosu tasma duzeltmesi (0.5.5 ek).
           style.css'te "table { width:auto }" + her hucre
           "min-width:100px" var; genis tablo, container'i
           (width:70%) asip ozellikle zoom'da disari tasiyordu.
           Eski bir layout hatasiydi, 0.5.5 regresyonu degil.

           Cozum: liste tablosunu container genisligine
           sabitle (width:100%, table-layout:fixed) ve
           sutunlara oransal genislik ver. Boylece icerik ne
           olursa olsun tablo container icinde kalir, kaydirma
           gerekmez. Kurallar SADECE .list-table-wrap altindaki
           tabloyu hedefler; anime_details / statistics gibi
           diger sayfalardaki global "th,td" kurallari
           etkilenmez (07 disiplini: minimum, izole mudahale). */
        .list-table-wrap {
            max-width: 100%;
        }

        .list-table-wrap table {
            width: 100%;
            table-layout: fixed;
        }

        .list-table-wrap th,
        .list-table-wrap td {
            min-width: 0;
            max-width: none;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        /* Sutun genislikleri (6 sutun):
           Anime | Durum | Izlenen Bolum | Resim | Sonraki Bolum | Eylem
           Yuzdeler toplami 100. Resim ve Eylem icerik
           genisligine gore biraz daha genis; digerleri dar
           metin sutunu. */
        .list-table-wrap th:nth-child(1),
        .list-table-wrap td:nth-child(1) { width: 22%; }  /* Anime */
        .list-table-wrap th:nth-child(2),
        .list-table-wrap td:nth-child(2) { width: 12%; }  /* Durum */
        .list-table-wrap th:nth-child(3),
        .list-table-wrap td:nth-child(3) { width: 14%; }  /* Izlenen Bolum */
        .list-table-wrap th:nth-child(4),
        .list-table-wrap td:nth-child(4) { width: 16%; }  /* Resim */
        .list-table-wrap th:nth-child(5),
        .list-table-wrap td:nth-child(5) { width: 22%; }  /* Sonraki Bolum */
        .list-table-wrap th:nth-child(6),
        .list-table-wrap td:nth-child(6) { width: 14%; }  /* Eylem */

        /* Resim sutunu: tablo daralinca poster tasmasin. */
        .list-table-wrap td:nth-child(4) img {
            max-width: 100%;
            height: auto;
        }

        /* 0.5.5 - liste ici hizli bolum guncelleme (+/-) */
        .ep-quick {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }

        .ep-quick .ep-text {
            text-align: center;
        }

        .ep-badge {
            font-size: 13px;
            color: #888780;
            line-height: 1;
        }

        .ep-controls {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .ep-sep {
            color: #999;
            font-size: 12px;
        }

        .ep-step {
            width: 22px;
            height: 22px;
            line-height: 20px;
            padding: 0;
            border: 1px solid #D85A30;
            background: #D85A30;
            color: #fff;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }

        .ep-step:hover:not(:disabled) {
            background: #993C1D;
            border-color: #993C1D;
            color: #fff;
        }

        .ep-step:disabled {
            border-color: #D3D1C7;
            background: #F1EFE8;
            color: #B4B2A9;
            opacity: 1;
            cursor: not-allowed;
        }

        .ep-step:disabled:hover {
            background: #F1EFE8;
            color: #B4B2A9;
        }

        .ep-quick.busy .ep-step {
            pointer-events: none;
            opacity: 0.5;
        }

        .ep-quick.flash .ep-text {
            transition: color 0.15s ease;
            color: #2e7d32;
            font-weight: 600;
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

        /* Mobil tasma duzeltmesi: dar ekranda 6 sutunlu liste tablosu
           container'a sigamayip sutunlar eziliyor; baslik (.list-anime-title
           max-width:170px) ve Durum metni yan sutuna tasip ust uste biniyordu.
           Cozum: telefonda tabloyu yatay kaydirilabilir yap ve sutunlara
           gercek genislik birak (min-width). Hucre padding'i de kucultulur
           (global th,td 12px dar sutunu eziyordu). Masaustu layout degismez. */
        @media (max-width: 768px) {
            .list-table-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .list-table-wrap table {
                min-width: 560px;
            }
            .list-table-wrap th,
            .list-table-wrap td {
                padding: 8px 6px;
            }
            .list-anime-title {
                max-width: 110px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="recommendations.php" class="about-link"><?php echo htmlspecialchars(t('nav.what_to_watch'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="recent.php" class="about-link"><?php echo htmlspecialchars(t('nav.recent_edits'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="list_settings.php" class="about-link"><?php echo htmlspecialchars(t('nav.list_settings'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="statistics.php" class="about-link"><?php echo htmlspecialchars(t('nav.statistics'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="help.php" class="about-link"><?php echo htmlspecialchars(t('nav.help'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php // SECTION: Language switcher (snippet copy - see _lang_switcher_reference.php) ?>
            <?php echo auth_nav_links(); ?>
            <div class="lang-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('lang.aria_label'), ENT_QUOTES, 'UTF-8'); ?>">
                <form action="set_language.php" method="post" class="lang-switch-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="lang" value="tr">
                    <button type="submit" class="lang-switch<?php echo current_lang() === 'tr' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.tr_label'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
                <form action="set_language.php" method="post" class="lang-switch-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="lang" value="en">
                    <button type="submit" class="lang-switch<?php echo current_lang() === 'en' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en_label'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>
        </div>
        <div class="list-page-title">
            <?php echo htmlspecialchars(t('index.list_title'), ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <div style="max-width: 380px; margin: 15px auto; background: #e9ecef; padding: 15px 20px; border-radius: 8px;">
            <form method="GET" action="" style="display: flex; gap: 8px;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="<?php echo htmlspecialchars(t('index.search.placeholder'), ENT_QUOTES, 'UTF-8'); ?>" style="flex: 1; padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px;">
                <button type="submit" style="padding: 10px 18px; background: #4a90e2; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px;"><?php echo htmlspecialchars(t('index.search.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
                <?php if ($search_query !== ''): ?>
                    <a href="index.php" style="padding: 10px 14px; background: #e0e0e0; color: #333; border-radius: 6px; text-decoration: none; font-size: 14px; display: flex; align-items: center;"><?php echo htmlspecialchars(t('index.search.clear'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="filter-container">
            <form method="GET" action="" onsubmit="for(var i=0;i&lt;this.elements.length;i++){var el=this.elements[i];if(el.name&amp;&amp;el.value===''){el.disabled=true;}}">
                <div class="filter-group">
                    <label for="genre_filter"><?php echo htmlspecialchars(t('index.filter.genre'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="genre_filter" id="genre_filter">
                        <option value=""><?php echo htmlspecialchars(t('index.filter.all'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>" 
                                    <?php echo $genre_filter == $genre['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(genre_display_name($genre)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group">
                    <label for="watch_status_filter"><?php echo htmlspecialchars(t('index.filter.watch_status'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="watch_status_filter" id="watch_status_filter">
                        <option value=""><?php echo htmlspecialchars(t('index.filter.all'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach (watch_status_options() as $ws_value => $ws_label): ?>
                            <option value="<?php echo htmlspecialchars($ws_value); ?>" <?php echo $watch_status_filter === $ws_value ? 'selected' : ''; ?>><?php echo htmlspecialchars($ws_label); ?></option>
                        <?php endforeach; ?>
                        <option value="__unselected__" <?php echo $watch_status_filter === '__unselected__' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('index.watch_status.unselected'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group">
                    <label for="broadcast_status_filter"><?php echo htmlspecialchars(t('index.filter.broadcast'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="broadcast_status_filter" id="broadcast_status_filter">
                        <option value=""><?php echo htmlspecialchars(t('index.filter.all'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Yayın Devam Ediyor" <?php echo $broadcast_status_filter == 'Yayın Devam Ediyor' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('index.broadcast.ongoing'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Yayın Tamamlandı" <?php echo $broadcast_status_filter == 'Yayın Tamamlandı' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('index.broadcast.finished'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                <div style="margin-top: 20px;"></div>
                <div class="filter-group filter-full">
                    <details class="letter-filter-details" <?php echo $letter_filter ? 'open' : ''; ?>>
                        <summary><?php echo htmlspecialchars(t('index.filter.letter'), ENT_QUOTES, 'UTF-8'); ?> <?php echo $letter_filter ? '(' . htmlspecialchars($letter_filter) . ')' : ''; ?></summary>
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
                    <label for="per_page"><?php echo htmlspecialchars(t('index.filter.per_page'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()">
                        <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                        <option value="30" <?php echo $per_page == 30 ? 'selected' : ''; ?>>30</option>
                        <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                        <option value="0" <?php echo $per_page == 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('index.filter.show_all'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
                
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_column); ?>">
                <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                <?php if ($search_query !== ''): ?>
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>
                
                <div class="form-actions filter-full">
                    <input type="submit" value="<?php echo htmlspecialchars(t('index.filter.submit'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </form>
        </div>

        <?php if (can($pdo, 'add_anime')): ?>
        <div class="button-container">
            <a href="add_anime.php" class="anime-list-button"><?php echo htmlspecialchars(t('index.add_anime'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <?php endif; ?>

        <?php if (MULTI_USER_MODE): ?>
            <?php $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM animes WHERE source = 'local'")->fetchColumn(); ?>
            <div class="button-container">
                <a href="pending.php" class="anime-list-button" style="background:#6c757d;">
                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars(sprintf(t('index.pending_link'), $pendingCount), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php renderPagination($current_page, $total_pages, $total_results, $per_page); ?>

        <div class="list-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>
                        <?php echo htmlspecialchars(t('index.col.anime'), ENT_QUOTES, 'UTF-8'); ?>
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('title', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'title' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('title', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'title' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th>
                        <?php echo htmlspecialchars(t('index.col.status'), ENT_QUOTES, 'UTF-8'); ?>
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('watch_status', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watch_status' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('watch_status', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watch_status' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th>
                        <?php echo htmlspecialchars(t($canPersonal ? 'index.col.watched_episodes' : 'index.col.episode_count'), ENT_QUOTES, 'UTF-8'); ?>
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('watched_episodes', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watched_episodes' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('watched_episodes', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'watched_episodes' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th><?php echo htmlspecialchars(t('index.col.image'), ENT_QUOTES, 'UTF-8'); ?></th>
                    <th>
                        <?php echo htmlspecialchars(t('index.col.next_episode'), ENT_QUOTES, 'UTF-8'); ?>
                        <div class="sort-buttons">
                            <a href="<?php echo getSortLink('next_episode_date', 'asc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'next_episode_date' && $sort_order == 'asc') ? 'active' : ''; ?>">↑</a>
                            <a href="<?php echo getSortLink('next_episode_date', 'desc', $genre_filter, $watch_status_filter); ?>" 
                               class="sort-button <?php echo ($sort_column == 'next_episode_date' && $sort_order == 'desc') ? 'active' : ''; ?>">↓</a>
                        </div>
                    </th>
                    <th style="text-align: center;"><?php echo htmlspecialchars(t('index.col.action'), ENT_QUOTES, 'UTF-8'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($animes) > 0): ?>
                    <?php foreach ($animes as $anime): ?>
                        <tr>
                            <td><span class="list-anime-title" onclick="toggleAnimeTitle(this)" title="<?php echo htmlspecialchars(t('index.row.title_tooltip'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(display_title($anime)); ?></span></td>
                            <td class="watch-status-cell"><?php
                                // user_anime satiri yoksa (watch_status_raw null) kullanici
                                // henuz secim yapmamistir -> "Secim Yapilmamis" goster.
                                // Satir varsa gercek durum etiketi. + ile satir olusunca
                                // JS hucreyi gercek etiketle gunceller (asagidaki updateWatched).
                                echo htmlspecialchars(
                                    $anime['watch_status_raw'] === null
                                        ? t('index.watch_status.unselected')
                                        : watch_status_label($anime['watch_status'])
                                );
                            ?></td>
                            <td class="episode-count"><?php
                                // Episode display logic (v0.5+):
                                //  - total_episodes set  -> watched/total (finished or short series)
                                //  - total NULL, aired set -> watched/aired (on air) (long ongoing series)
                                //  - everything NULL     -> watched/?
                                //
                                // 0.5.5: ceiling (tavan) = total if set, else aired,
                                // else null (unknown). The +/- buttons use this to
                                // decide which side to disable. The bound logic is
                                // duplicated server side in update_watched.php; that
                                // copy is authoritative, this one is for UX only.
                                // Match the pre-0.5.5 !empty() semantics
                                // exactly: a 0 (or NULL) total/aired counts
                                // as "not set" and falls through to the
                                // next display branch. update_watched.php
                                // uses the same rule so client and server
                                // agree on the ceiling.
                                $ec_watched = (int)$anime['watched_episodes'];
                                $ec_total   = !empty($anime['total_episodes'])
                                              ? (int)$anime['total_episodes'] : null;
                                $ec_aired   = !empty($anime['aired_episodes'])
                                              ? (int)$anime['aired_episodes'] : null;
                                $ec_ceiling = ($ec_total !== null) ? $ec_total
                                              : (($ec_aired !== null) ? $ec_aired : null);

                                // ep_text = pure count (no badge). The
                                // "(on air)" tag is now a separate line
                                // between the count and the +/- buttons,
                                // so it lives in its own variable.
                                $ec_badge = '';
                                if ($ec_total !== null) {
                                    $ec_text = htmlspecialchars($ec_watched . '/' . $ec_total);
                                } elseif ($ec_aired !== null) {
                                    $ec_text  = htmlspecialchars($ec_watched . '/' . $ec_aired);
                                    $ec_badge = t('index.row.ep_aired_badge');
                                } else {
                                    $ec_text = htmlspecialchars($ec_watched) . '/?';
                                }

                                // No known ceiling -> hide the controls entirely.
                                // "-" alone would be possible but the cell is
                                // cleaner with nothing until the user has episode
                                // data; the message belongs in Senkronize Et flow.
                                $ec_has_controls = ($ec_ceiling !== null);
                                $ec_at_min = ($ec_watched <= 0);
                                $ec_at_max = ($ec_ceiling !== null && $ec_watched >= $ec_ceiling);
                            ?><?php if (!$canPersonal): ?><?php
                                // Anonymous (online, not logged in): no personal
                                // watched state and no editing. Show only the total
                                // episode count - no "watched/" prefix, no +/- controls.
                                $ec_total_only = ($ec_total !== null) ? (string)$ec_total
                                               : (($ec_aired !== null) ? (string)$ec_aired : '?');
                                echo htmlspecialchars($ec_total_only);
                            ?><?php if ($ec_badge !== ''): ?> <small><?php echo htmlspecialchars($ec_badge); ?></small><?php endif; ?><?php elseif ($ec_has_controls): ?><div class="ep-quick" data-anime-id="<?php echo (int)$anime['id']; ?>" data-ceiling="<?php echo (int)$ec_ceiling; ?>">
                                    <span class="ep-text"><?php echo $ec_text; ?></span>
                                    <?php if ($ec_badge !== ''): ?><span class="ep-badge"><?php echo htmlspecialchars($ec_badge); ?></span><?php endif; ?>
                                    <div class="ep-controls">
                                        <button type="button" class="ep-step ep-minus" onclick="quickWatched(this, -1)"<?php echo $ec_at_min ? ' disabled' : ''; ?> title="<?php echo htmlspecialchars(t('index.row.ep_minus_tooltip'), ENT_QUOTES, 'UTF-8'); ?>">&minus;</button>
                                        <span class="ep-sep">/</span>
                                        <button type="button" class="ep-step ep-plus" onclick="quickWatched(this, 1)"<?php echo $ec_at_max ? ' disabled' : ''; ?> title="<?php echo htmlspecialchars(t('index.row.ep_plus_tooltip'), ENT_QUOTES, 'UTF-8'); ?>">+</button>
                                    </div>
                                </div><?php else: ?><?php echo $ec_text; ?><?php if ($ec_badge !== ''): ?> <small><?php echo htmlspecialchars($ec_badge); ?></small><?php endif; ?><?php endif; ?></td>
                            <td><img src="<?php echo htmlspecialchars($anime['image_path']); ?>" alt="<?php echo htmlspecialchars(display_title($anime)); ?>" width="100"></td>
                            <td class="next-episode-cell">
<?php 
if ($anime['status'] == 'Yayın Tamamlandı') {
    echo htmlspecialchars(t('index.broadcast.finished'));
} else if (!empty($anime['next_episode_date'])) {
    echo '<pre class="next-episode-info">' . getTimeUntilNextEpisode($anime['next_episode_date'], $anime['watched_episodes'], $anime['total_episodes'] ?? 0, $anime['aired_episodes'] ?? 0) . '</pre>';
} else {
    echo "-";
}
?>
</td>
                            <td>
                                <div class="action-buttons">
                                    <a href="anime_details.php?id=<?php echo $anime['id']; ?>" class="more-button"><?php echo htmlspecialchars(t('index.row.more_button'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php if ($canModerate): ?>
                                    <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button"><?php echo htmlspecialchars(t('index.row.edit_button'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <form method="POST" action="index.php"
                                          onsubmit="return confirm(<?php echo htmlspecialchars(json_encode(t('index.row.delete_confirm'), JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>);">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$anime['id']; ?>">
                                        <button type="submit" class="delete-button"><?php echo htmlspecialchars(t('index.row.delete_button'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;"><?php echo htmlspecialchars(t('index.row.no_results'), ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <?php renderPagination($current_page, $total_pages, $total_results, $per_page); ?>

    </div>

    <script>
    // CSRF token, JS fetch'leri icin. Form'lardaki hidden input ile
    // ayni session degeri; burada bir kez basiliyor.
    var CSRF_TOKEN = <?php echo json_encode(csrf_token()); ?>;

    // Anime ismini tikla-genislet. Uzun isimler CSS ile "..." seklinde
    // kirpiliyor, kullanici tiklayinca tam halini gosteriyoruz. Tekrar
    // tiklayinca yine kirpiliyor (toggle).
    function toggleAnimeTitle(element) {
        element.classList.toggle('expanded');
    }

    // 0.5.5 - liste ici hizli bolum guncelleme.
    // "+" / "-" butonuna basinca update_watched.php'ye AJAX POST atar,
    // watched_episodes +-1 olur, hucre yerinde guncellenir. Form
    // acmadan. Sinir kontrolu hem burada (UX) hem sunucuda (yetkili)
    // yapilir; sunucu son sozu soyler.
    function quickWatched(btn, delta) {
        var box = btn.closest('.ep-quick');
        if (!box || box.classList.contains('busy')) {
            return;
        }

        var animeId = parseInt(box.getAttribute('data-anime-id'), 10);
        var ceiling = parseInt(box.getAttribute('data-ceiling'), 10);
        var textEl  = box.querySelector('.ep-text');
        var minusEl = box.querySelector('.ep-minus');
        var plusEl  = box.querySelector('.ep-plus');

        box.classList.add('busy');

        var body = new URLSearchParams();
        body.set('csrf_token', CSRF_TOKEN);
        body.set('anime_id', String(animeId));
        body.set('delta', String(delta));

        fetch('update_watched.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            box.classList.remove('busy');

            if (!data || !data.success) {
                var msg = (data && data.error)
                    ? data.error
                    : <?php echo json_encode(t('index.js.update_failed'), JSON_UNESCAPED_UNICODE); ?>;
                alert(msg);
                return;
            }

            // Update the cell count. The "(on air)" badge is now separate
            // bir .ep-badge satiri; +/- sadece izlenen sayisini degistirir,
            // yayin durumunu degil. Bu yuzden sadece saf "izlenen/tavan"
            // metnini yaziyoruz, badge'e dokunmuyoruz.
            var base = data.watched_episodes + '/'
                + (data.ceiling !== null ? data.ceiling : '?');
            textEl.textContent = base;

            // Sinir butonlarini guncelle (sunucudan gelen at_min/at_max
            // yetkili kaynak).
            if (minusEl) { minusEl.disabled = !!data.at_min; }
            if (plusEl)  { plusEl.disabled  = !!data.at_max; }

            // 0.5.6 / 0.5.7 - watch_status otomatik gecisi (cift yon).
            // Sunucu mevcut watch_status'u + delta isareti ile karar
            // verdi:
            //   delta=+1: Planlandi -> Izleniyor (Kural 1) ve/veya
            //             watched==tavan -> Izlendi (Kural 2).
            //   delta=-1: Izlendi + new<tavan -> Izleniyor (Kural 3,
            //             Kural 2'nin simetrigi) ve/veya Izleniyor +
            //             new==0 -> Planlandi (Kural 4, Kural 1'in
            //             simetrigi). Ikisi de 0.5.7 ile eklendi.
            // JS yon bilmiyor: sadece watch_status_changed bayragina
            // bakar. Hangi kural fire ettiyse fire etti, sonuc string'i
            // watch_status_new'da gelir. Gereksiz DOM yazma yok - bayrak
            // false ise (kural fire etmedi) td'ye dokunulmaz. JS Yaklasim
            // 1 deseni (0.5.6'da tanimlandi) yeni kurallari eklemeyi JS
            // degisikligi olmadan mumkun kildi.
            if (data.watch_status_changed && data.watch_status_new) {
                var tr = box.closest('tr');
                if (tr) {
                    var statusTd = tr.querySelector('.watch-status-cell');
                    if (statusTd) {
                        // 0.6: the server returns both the ASCII internal value (watch_status_new)
                        // and the TR UI label (watch_status_label). If the helper
                        // exists, write that; for old server responses, fall back to
                        // writing the raw value. update_watched.php added the label field
                        // in 0.6 - this fallback is a defense against stale browser
                        // cache.
                        statusTd.textContent = data.watch_status_label || data.watch_status_new;
                    }
                }
            }

            // Kisa gorsel geri bildirim.
            box.classList.add('flash');
            setTimeout(function () { box.classList.remove('flash'); }, 350);

            // Not: satir yerinde kalir; siralama bir sonraki sayfa
            // yuklemesinde duzelir (proje_durumu_07 Bolum 3 karari).
        })
        .catch(function () {
            box.classList.remove('busy');
            alert(<?php echo json_encode(t('index.js.network_error'), JSON_UNESCAPED_UNICODE); ?>);
        });
    }
    </script>
</body>
</html>
