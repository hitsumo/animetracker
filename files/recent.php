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
 * 1.1.46 - the watched tab reads the WATCH LOG (user_watch_log, see
 * functions/watch_log_helpers.php) and grows a period strip:
 *
 *   ?period=all    (default) one row per anime, newest movement first,
 *                  ten rows. An anime with log rows is placed by its
 *                  latest log entry; an anime whose progress predates
 *                  the log (no rows - there is NO SEED) is placed by
 *                  user_anime.updated_at as before and wears a
 *                  "pre-log" label so the two kinds are told apart.
 *   ?period=week   rolling 7 days, log only.
 *   ?period=month  rolling 30 days, log only.
 *   ?period=range  &from=YYYY-MM-DD&to=YYYY-MM-DD, log only.
 *
 * The three log-only views group the log per anime, keep the anime whose
 * net delta in the window is positive (an undone "+1" nets to zero), and
 * print "N anime, M episodes" above the list plus a "+M" badge per row.
 * The filter lives in the URL and is never saved; the per-user default
 * TAB setting is untouched.
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
//
// 1.1.46: the watched tab has a period filter (see the header). 'all'
// merges the log with the pre-log rows; the other three read the log
// only and carry a per-anime net delta. $period holds the resolved
// bounds; $summary the "N anime, M episodes" line for log-only views.
$period  = ['period' => 'all', 'since' => null, 'until' => null, 'from' => null, 'to' => null];
$summary = null;
$extraParams = [];
if ($tab === 'watched') {
    $period = watch_log_period_bounds(
        (string)($_GET['period'] ?? 'all'),
        $_GET['from'] ?? null,
        $_GET['to'] ?? null
    );
}
if ($tab === 'watched' && $period['period'] !== 'all') {
    // Log-only window. One row per anime: the sum of its deltas in the
    // window and the time of its latest entry. HAVING keeps the anime
    // that were actually watched (net > 0). user_anime is LEFT-joined
    // for the badge and the current count; the log is the driver.
    // Fifty rows: a window is bounded by time, not by count, but a bulk
    // import can drop hundreds of rows into one minute.
    // The aggregation sits in a derived table so the outer SELECT has no
    // GROUP BY (no ONLY_FULL_GROUP_BY exposure on MySQL 8).
    $untilSql = ($period['until'] !== null) ? " AND logged_at < :until" : "";
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.image_path,
               ua.watch_status,
               a.status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               a.total_episodes, a.aired_episodes,
               wl.last_at AS updated_at,
               wl.net_delta,
               0 AS pre_log
        FROM (
            SELECT anime_id,
                   MAX(logged_at) AS last_at,
                   SUM(episode_to - episode_from) AS net_delta
              FROM user_watch_log
             WHERE user_id = :uid AND logged_at >= :since" . $untilSql . "
             GROUP BY anime_id
            HAVING net_delta > 0
        ) wl
        JOIN animes a ON a.id = wl.anime_id
        LEFT JOIN user_anime ua
               ON ua.anime_id = wl.anime_id AND ua.user_id = :uid2
        ORDER BY wl.last_at DESC
        LIMIT 50
    ");
    // Native prepares refuse a reused named parameter (1.1.44 lesson).
    $extraParams[':uid2']  = current_user_id();
    $extraParams[':since'] = $period['since'];
    if ($period['until'] !== null) {
        $extraParams[':until'] = $period['until'];
    }
    $summary = watch_log_summary($pdo, current_user_id(), $period['since'], $period['until']);
} elseif ($tab === 'watched') {
    // 'all': every anime with progress, placed by its latest log entry
    // when it has one, else by user_anime.updated_at (the 1.1.45 order)
    // - those are the pre-log rows and pre_log = 1 labels them. An anime
    // whose log exists but whose count is back at 0 still shows (it was
    // watched, then reset); a status-only row with no log never does.
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.image_path,
               ua.watch_status,
               a.status,
               ua.watched_episodes,
               a.total_episodes, a.aired_episodes,
               COALESCE(wl.last_at, ua.updated_at) AS updated_at,
               NULL AS net_delta,
               (wl.last_at IS NULL) AS pre_log
        FROM user_anime ua
        JOIN animes a ON a.id = ua.anime_id
        LEFT JOIN (
            SELECT anime_id, MAX(logged_at) AS last_at
              FROM user_watch_log
             WHERE user_id = :uid
             GROUP BY anime_id
        ) wl ON wl.anime_id = ua.anime_id
        WHERE ua.user_id = :uid2
          AND (ua.watched_episodes > 0 OR wl.last_at IS NOT NULL)
        ORDER BY updated_at DESC
        LIMIT 10
    ");
    // Native prepares refuse a reused named parameter (1.1.44 lesson).
    $extraParams[':uid2'] = current_user_id();
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
$stmt->execute(array_merge([':uid' => current_user_id()], $extraParams));
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 1.1.46 - a log-only window that caught nothing gets its own empty
// text ("nothing in this period"), not the tab's "no activity yet".
$emptyKey = null;
if ($tab === 'watched' && $period['period'] !== 'all' && empty($recent)) {
    $emptyKey = (MULTI_USER_MODE && !is_logged_in())
        ? 'recent.empty_state.watched_guest'
        : 'recent.period.empty';
}

// Query-string helper for the period pills: keeps tab, drops from/to
// unless the range form re-sends them.
function recent_period_url($p) {
    return 'recent.php?tab=watched&period=' . rawurlencode($p);
}

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
        /* 1.1.46 - period strip under the watched tab: smaller pills in
           the .recent-tabs family, an inline date-range form, a summary
           line, and two row badges (net delta / pre-log). */
        .recent-period {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin: -6px 0 12px 0;
        }
        .recent-period a {
            padding: 5px 13px;
            border-radius: 14px;
            background: #fff;
            color: #666;
            text-decoration: none;
            font-size: 0.8em;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .recent-period a.active {
            background: #2c3e50;
            color: #fff;
        }
        .recent-period a:hover:not(.active) {
            box-shadow: 0 3px 12px rgba(0,0,0,0.12);
        }
        .recent-period form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-left: 6px;
            font-size: 0.8em;
            color: #666;
        }
        .recent-period form input[type="date"] {
            font-family: inherit;
            font-size: 0.95em;
            padding: 3px 6px;
            border: 1px solid #d0d5db;
            border-radius: 8px;
            background: #fff;
            color: #2c3e50;
        }
        .recent-period form button {
            font-family: inherit;
            font-size: 0.95em;
            padding: 4px 12px;
            border: none;
            border-radius: 14px;
            background: #3498db;
            color: #fff;
            cursor: pointer;
        }
        .recent-period form button:hover { background: #2980b9; }
        .recent-period-summary {
            font-size: 0.88em;
            color: #2c3e50;
            margin: 0 0 14px 4px;
        }
        .recent-period-summary strong { color: #1e40af; }
        .badge-net-eps {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: 500;
            background: #dcfce7;
            color: #166534;
        }
        .badge-pre-log {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.78em;
            background: #f3f4f6;
            color: #6b7280;
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

    <?php if ($tab === 'watched'): ?>
        <?php // 1.1.46 - donem seridi. Duz GET; secim adreste yasar, kaydedilmez.
              // Aralik formu iki tarih ister; eksik/bozuk tarih 'hepsi'ye duser
              // (watch_log_period_bounds). ?>
        <div class="recent-period">
            <a href="<?php echo recent_period_url('week'); ?>"  class="<?php echo $period['period'] === 'week'  ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('recent.period.week'),  ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="<?php echo recent_period_url('month'); ?>" class="<?php echo $period['period'] === 'month' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('recent.period.month'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="<?php echo recent_period_url('all'); ?>"   class="<?php echo $period['period'] === 'all'   ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('recent.period.all'),   ENT_QUOTES, 'UTF-8'); ?></a>
            <form method="get" action="recent.php">
                <input type="hidden" name="tab" value="watched">
                <input type="hidden" name="period" value="range">
                <label><?php echo htmlspecialchars(t('recent.period.from'), ENT_QUOTES, 'UTF-8'); ?>
                    <input type="date" name="from" value="<?php echo htmlspecialchars((string)($period['from'] ?? ($_GET['from'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required></label>
                <label><?php echo htmlspecialchars(t('recent.period.to'), ENT_QUOTES, 'UTF-8'); ?>
                    <input type="date" name="to" value="<?php echo htmlspecialchars((string)($period['to'] ?? ($_GET['to'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" required></label>
                <button type="submit"><?php echo htmlspecialchars(t('recent.period.show'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </div>
        <?php if ($summary !== null && $summary['animes'] > 0): ?>
            <p class="recent-period-summary"><?php
                // "N animede M bolum" - donem basligi (hafta / ay / aralik) + ozet.
                $label = ($period['period'] === 'range')
                    ? sprintf(t('recent.period.range_label'), $period['from'], $period['to'])
                    : t('recent.period.' . $period['period'] . '_label');
                echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ': ';
                echo sprintf(t('recent.period.summary'),
                    '<strong>' . (int)$summary['animes'] . '</strong>',
                    '<strong>' . (int)$summary['episodes'] . '</strong>');
            ?></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px;"></i>
            <p><?php echo htmlspecialchars(t($emptyKey ?? $tabText[$tab]['empty']), ENT_QUOTES, 'UTF-8'); ?></p>
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
                        <?php if ($tab === 'watched' && isset($anime['net_delta']) && $anime['net_delta'] !== null): ?>
                            <?php // 1.1.46 - donem gorunumunde bu pencerede izlenen net bolum. ?>
                            <span class="badge-net-eps"><i class="fas fa-plus"></i> <?php
                                echo htmlspecialchars(sprintf(t('recent.period.net_episodes'), (int)$anime['net_delta']), ENT_QUOTES, 'UTF-8');
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
                    <?php if ($tab === 'watched' && !empty($anime['pre_log'])): ?>
                        <?php // 1.1.46 - gunlugu olmayan eski satir: zaman user_anime.updated_at'ten. ?>
                        <br><span class="badge-pre-log" title="<?php echo htmlspecialchars(t('recent.period.pre_log.title'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(t('recent.period.pre_log'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
