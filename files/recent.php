<?php

/**
 * Anime Tracker - Son Guncellenenler
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Three tabs. The first two (1.1.44) are catalog-level, five anime each:
 *
 *   episodes  the anime whose AIRED EPISODE COUNT changed most recently
 *             (daily AnimeSchedule sync, the edit form, a catalog
 *             import). Ordered by animes.episodes_updated_at, which
 *             only those writes stamp - and only when the number really
 *             changes. "Which show got a new episode?"
 *   content   the anime most recently ADDED or EDITED: add_anime inserts
 *             a row, edit_anime / catalog import rewrite one, both
 *             bumping animes.updated_at (MySQL ON UPDATE). "What changed
 *             in the catalog?"
 *
 * Before 1.1.44 there was one list on updated_at and the sync's episode
 * increments buried every real edit. Now the episode-only writers pin
 * updated_at (updated_at = updated_at), so the two questions no longer
 * share a column - see migration/1.1.44/upgrade.sql.
 *
 * The third tab (1.1.45) is PERSONAL:
 *
 *   watched   the anime whose watch progress THIS USER changed most
 *             recently - a "+1 watched", a status change - ordered by
 *             user_anime.updated_at, ten rows. "Where was I?" It lived
 *             on the statistics page ("Son Izlenenler") from 1.1.1 to
 *             1.1.44; the user asked for it here, next to the other two
 *             "what happened lately" lists, and statistics went back to
 *             being numbers only.
 *
 * The personal tab is the ONLY one ordered by user_anime: on the two
 * catalog tabs the user_anime JOIN is for the badge only, never for
 * ordering - a "+1 watched" must not move anything there. In online mode
 * a guest has no user_anime rows, so the watched tab shows its empty
 * state with a sign-in hint instead of a list.
 *
 * Which tab opens first: ?tab= in the URL wins; otherwise the per-user
 * default from list settings (user_pref 'recent_default_tab', shipped
 * default 'episodes', saved by set_recent_tab_pref.php).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Sayfa dilini baslat
lang_init($pdo);
// 1.1.45 - baslik dili tercihi: kartlar display_title() ile basilir (1.1.21
// dersi: bu cagri unutulursa onbellek Romaji'de kalir).
title_pref_init($pdo);

// Active tab: explicit ?tab= > saved per-user default > 'episodes'.
// Unknown values fall back rather than 404 - a stale link still shows
// something useful.
$tab = (string)($_GET['tab'] ?? '');
if (!in_array($tab, recent_tabs(), true)) {
    $tab = recent_default_tab($pdo);
}

// watch_status / watched_episodes are personal (user_anime, 1.0.1). The
// user_anime JOIN below is kept ONLY to display the personal badge and
// episode count - it is NOT used for ordering. A personal "+1 watched"
// (which bumps user_anime.updated_at, not animes.*) must not move the
// anime up on either tab.
//
// Episode tab: rows never stamped (NULL) are left out - on an upgraded
// install the column fills as the daily sync runs, and the empty state
// says so. Content tab: every row has updated_at, so no filter.
//
// Watched tab (1.1.45): the one place user_anime DOES order. Only rows
// with progress (watched_episodes > 0) - a status-only row ("planned")
// is not a watch event. Ten rows, as the statistics page showed.
// current_user_id() is NULL for a guest in online mode; the query then
// matches nothing and the empty state explains.
if ($tab === 'watched') {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.image_path,
               ua.watch_status,
               a.status,
               ua.watched_episodes,
               a.total_episodes, a.aired_episodes,
               ua.updated_at AS updated_at
        FROM user_anime ua
        JOIN animes a ON a.id = ua.anime_id
        WHERE ua.user_id = :uid AND ua.watched_episodes > 0
        ORDER BY ua.updated_at DESC
        LIMIT 10
    ");
} elseif ($tab === 'episodes') {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.image_path,
               ua.watch_status,
               a.status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               a.total_episodes, a.aired_episodes,
               a.episodes_updated_at AS updated_at
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = :uid
        WHERE a.episodes_updated_at IS NOT NULL
        ORDER BY a.episodes_updated_at DESC
        LIMIT 5
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.image_path,
               ua.watch_status,
               a.status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               a.total_episodes, a.aired_episodes,
               a.updated_at AS updated_at
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = :uid
        ORDER BY a.updated_at DESC
        LIMIT 5
    ");
}
// Guest in online mode: current_user_id() is NULL. PDO binds NULL fine
// (matches no row on the watched tab, no badge on the others).
$stmt->execute([':uid' => current_user_id()]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Per-tab texts (hint under the tabs, empty-state message). One map so
// adding a tab is one line here plus the lang keys.
$tabText = [
    'episodes' => ['hint' => 'recent.tab.episodes.hint', 'empty' => 'recent.empty_state.episodes'],
    'content'  => ['hint' => 'recent.tab.content.hint',  'empty' => 'recent.empty_state'],
    'watched'  => ['hint' => 'recent.tab.watched.hint',  'empty' => (MULTI_USER_MODE && !is_logged_in()) ? 'recent.empty_state.watched_guest' : 'recent.empty_state.watched'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('recent.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - noindex, follow. The list here is different on every
    // visit and answers no search query; what it IS good at is leading a
    // crawler to freshly changed detail pages, which "follow" preserves.
    // Crawl budget belongs to those pages, not to this one.
    // 1.1.44: canonical stays the bare address for both tabs - ?tab=
    // re-sorts the same kind of content, and the page is noindex anyway.
    echo seo_head([
        'title'       => t('recent.page_title'),
        'description' => t('seo.recent.description'),
        'canonical'   => 'recent.php',
        'noindex'     => true,
    ]);
    ?>
    <?php echo asset_styles(); ?>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
        .recent-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
        }
        .recent-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .recent-header h1 {
            margin: 0;
            font-size: 1.4em;
            color: #2c3e50;
        }
        .back-btn {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9em;
        }
        .back-btn:hover { background: #2980b9; }
        .recent-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 14px 18px;
            margin-bottom: 12px;
            transition: box-shadow 0.2s;
        }
        .recent-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        .recent-card img {
            width: 60px;
            height: 85px;
            object-fit: cover;
            border-radius: 6px;
            flex-shrink: 0;
        }
        .recent-card .no-img {
            width: 60px;
            height: 85px;
            background: #e0e0e0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 1.4em;
            flex-shrink: 0;
        }
        .recent-info {
            flex: 1;
            min-width: 0;
        }
        .recent-info .title {
            font-weight: 600;
            font-size: 1.05em;
            color: #2c3e50;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .recent-info .title:hover { color: #3498db; }
        .recent-meta {
            display: flex;
            gap: 14px;
            margin-top: 6px;
            font-size: 0.85em;
            color: #777;
            flex-wrap: wrap;
        }
        .recent-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 500;
        }
        .badge-watching { background: #dbeafe; color: #1e40af; }
        .badge-watched { background: #d1fae5; color: #065f46; }
        .badge-plantowatch { background: #f3f4f6; color: #4b5563; }
        .badge-onhold { background: #fef3c7; color: #92400e; }
        .badge-dropped { background: #fee2e2; color: #991b1b; }
        .badge-unselected { background: #e5e7eb; color: #6b7280; }
        .recent-time {
            text-align: right;
            font-size: 0.8em;
            color: #999;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
            background: #fff;
            border-radius: 10px;
        }
        /* Tabs (1.1.44) - bolum / icerik; 1.1.45 + izlenen. series_timeline.php'nin
           .st-tabs kalibi: pill, aktif dolu, digerleri beyaz. */
        .recent-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }
        .recent-tabs a {
            padding: 7px 18px;
            border-radius: 18px;
            background: #fff;
            color: #666;
            text-decoration: none;
            font-size: 0.88em;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            transition: box-shadow 0.2s;
        }
        .recent-tabs a.active {
            background: #3498db;
            color: #fff;
        }
        .recent-tabs a:hover:not(.active) {
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }
        .recent-tabs-hint {
            font-size: 0.82em;
            color: #999;
            margin: 0 0 16px 4px;
        }
        /* Episode tab: the number that changed, in front. */
        .badge-latest-ep {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 500;
            background: #e0f2fe;
            color: #075985;
        }
    </style>
</head>
<body>
<div class="recent-container">
    <div class="recent-header">
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars(t('recent.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('recent.back_to_list'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <?php // 1.1.44 - sekmeler. Duz GET baglantilari; ?tab= o gorunum icin
          // kazanir, kalici varsayilan Liste Ayarlari'ndan (set_recent_tab_pref). ?>
    <div class="recent-tabs">
        <a href="recent.php?tab=episodes" class="<?php echo $tab === 'episodes' ? 'active' : ''; ?>"><i class="fas fa-tv"></i> <?php echo htmlspecialchars(t('recent.tab.episodes'), ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="recent.php?tab=content" class="<?php echo $tab === 'content' ? 'active' : ''; ?>"><i class="fas fa-pen"></i> <?php echo htmlspecialchars(t('recent.tab.content'), ENT_QUOTES, 'UTF-8'); ?></a>
        <?php // 1.1.45 - kisisel sekme: istatistiklerden tasinan "Son Izlenenler". ?>
        <a href="recent.php?tab=watched" class="<?php echo $tab === 'watched' ? 'active' : ''; ?>"><i class="fas fa-eye"></i> <?php echo htmlspecialchars(t('recent.tab.watched'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <p class="recent-tabs-hint"><?php echo htmlspecialchars(t($tabText[$tab]['hint']), ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px;"></i>
            <p><?php echo htmlspecialchars(t($tabText[$tab]['empty']), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    <?php else: ?>
        <?php foreach ($recent as $anime): ?>
            <?php
                // Episode display
                $ep = $anime['watched_episodes'] ?? 0;
                if ($anime['total_episodes']) {
                    $epDisplay = $ep . '/' . $anime['total_episodes'];
                } elseif ($anime['aired_episodes']) {
                    $epDisplay = $ep . '/' . $anime['aired_episodes'] . ' ' . t('index.row.ep_aired_badge');
                } else {
                    $epDisplay = $ep . '/?';
                }

                // Watch status badge
                $ws = $anime['watch_status'] ?? '';
                // 0.6: ASCII enum -> stable CSS suffix via central helper.
                // style.css (0.6 adim 8) targets badge-watched / badge-
                // watching / badge-plantowatch / badge-onhold uniformly.
                $badgeClass = 'badge-' . watch_status_css_class($ws);

                // Time ago
                $updatedTs = strtotime($anime['updated_at']);
                $diff = time() - $updatedTs;
                if ($diff < 60) {
                    $timeAgo = t('recent.time.now');
                } elseif ($diff < 3600) {
                    $timeAgo = sprintf(t('recent.time.minutes_ago'), floor($diff / 60));
                } elseif ($diff < 86400) {
                    $timeAgo = sprintf(t('recent.time.hours_ago'), floor($diff / 3600));
                } else {
                    $timeAgo = sprintf(t('recent.time.days_ago'), floor($diff / 86400));
                }
            ?>
            <?php // 1.1.45 - baslik dili tercihine uyar (istatistiklerdeki
                  // tablo 1.1.18'den beri uyuyordu, bu sayfa uymuyordu). ?>
            <?php $cardTitle = display_title($anime); ?>
            <div class="recent-card">
                <img src="<?php echo htmlspecialchars(poster_src($anime['image_path'] ?? '')); ?>"
                     alt="<?php echo htmlspecialchars($cardTitle); ?>">

                <div class="recent-info">
                    <a href="anime_details.php?id=<?php echo (int)$anime['id']; ?>" class="title">
                        <?php echo htmlspecialchars($cardTitle); ?>
                    </a>
                    <div class="recent-meta">
                        <?php if ($tab === 'episodes'): ?>
                            <?php // 1.1.44 - bolum sekmesinde degisen sayi one cikar. ?>
                            <span class="badge-latest-ep"><i class="fas fa-tv"></i> <?php
                                echo htmlspecialchars(sprintf(t('recent.latest_episode'), (int)$anime['aired_episodes']), ENT_QUOTES, 'UTF-8');
                            ?></span>
                        <?php endif; ?>
                        <span class="badge-status <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars(watch_status_label($ws)); ?>
                        </span>
                        <span><i class="fas fa-play-circle"></i> <?php echo $epDisplay; ?></span>
                        <span><i class="fas fa-broadcast-tower"></i> <?php
                            echo htmlspecialchars(broadcast_status_label($anime['status']));
                        ?></span>
                    </div>
                </div>

                <div class="recent-time">
                    <i class="far fa-clock"></i> <?php echo $timeAgo; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
