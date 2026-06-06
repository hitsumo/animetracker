<?php

/**
 * Anime Tracker - Son Duzenlenen Animeler
 *
 * Shows the 5 most recently edited anime entries. Any change to an
 * anime record (watched_episodes, status, notes, etc.) updates the
 * updated_at timestamp automatically (MySQL ON UPDATE), so this page
 * always reflects the latest activity.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Sayfa dilini baslat
lang_init($pdo);

// watch_status / watched_episodes are personal (user_anime, 1.0.1), and so
// is the recency of personal edits: a "+1 watched" now bumps
// user_anime.updated_at, not animes.updated_at. To keep this page meaning
// "recently touched" (catalog OR personal edit), order by the most recent
// of the two timestamps for the current user. COALESCE handles animes that
// have no user_anime row yet (falls back to the catalog timestamp).
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.image_path,
           COALESCE(ua.watch_status, 'PlanToWatch') AS watch_status,
           a.status,
           COALESCE(ua.watched_episodes, 0) AS watched_episodes,
           a.total_episodes, a.aired_episodes,
           GREATEST(a.updated_at, COALESCE(ua.updated_at, a.updated_at)) AS updated_at
    FROM animes a
    LEFT JOIN user_anime ua
           ON ua.anime_id = a.id AND ua.user_id = :uid
    ORDER BY GREATEST(a.updated_at, COALESCE(ua.updated_at, a.updated_at)) DESC
    LIMIT 5
");
$stmt->execute([':uid' => current_user_id()]);
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('recent.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
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
    </style>
</head>
<body>
<div class="recent-container">
    <div class="recent-header">
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars(t('recent.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('recent.back_to_list'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>

    <?php if (empty($recent)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px;"></i>
            <p><?php echo htmlspecialchars(t('recent.empty_state'), ENT_QUOTES, 'UTF-8'); ?></p>
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
            <div class="recent-card">
                <?php if (!empty($anime['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($anime['image_path']); ?>"
                         alt="<?php echo htmlspecialchars($anime['title']); ?>">
                <?php else: ?>
                    <div class="no-img"><i class="fas fa-film"></i></div>
                <?php endif; ?>

                <div class="recent-info">
                    <a href="anime_details.php?id=<?php echo (int)$anime['id']; ?>" class="title">
                        <?php echo htmlspecialchars($anime['title']); ?>
                    </a>
                    <div class="recent-meta">
                        <span class="badge-status <?php echo $badgeClass; ?>">
                            <?php echo htmlspecialchars(watch_status_label($ws)); ?>
                        </span>
                        <span><i class="fas fa-play-circle"></i> <?php echo $epDisplay; ?></span>
                        <span><i class="fas fa-broadcast-tower"></i> <?php
                            $s = $anime['status'];
                            if ($s === 'Yayın Tamamlandı') {
                                echo htmlspecialchars(t('index.broadcast.finished'));
                            } elseif ($s === 'Yayın Devam Ediyor') {
                                echo htmlspecialchars(t('index.broadcast.ongoing'));
                            } else {
                                echo htmlspecialchars($s);
                            }
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
