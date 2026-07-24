<?php

/**
 * Anime Tracker - Pending additions (public list)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth). Public, read-only list of user-submitted anime
 * additions that are awaiting moderator approval (source='local'). Anyone -
 * anonymous included - can view this page; it is the place those pending
 * entries live, since they are kept out of the main catalog list (index.php)
 * until a moderator promotes them to source='catalog' via admin_pending.php.
 *
 * A moderator/admin add never appears here: those go straight into the catalog
 * (source='catalog') at add time.
 *
 * Multi-user only. In self-host there is no approval flow (the owner's adds are
 * visible directly in the catalog), so this page redirects to the home page.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);
title_pref_init($pdo);

if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

$rows = $pdo->query(
    "SELECT id, title, alternative_titles, status, media_type, image_path
     FROM animes WHERE source = 'local' ORDER BY id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('pending.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .pending-wrap { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .pending-wrap h1 { font-size: 1.5em; color: #333; display: flex; align-items: center; gap: 10px; }
        .pending-intro { color: #666; font-size: 0.95em; margin-bottom: 20px; }
        .pending-list { list-style: none; padding: 0; margin: 0; }
        .pending-item {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 14px; border: 1px solid #e0e0e0; border-radius: 6px;
            margin-bottom: 10px; background: #fff;
        }
        .pending-item img { width: 52px; height: 74px; object-fit: cover; border-radius: 4px; flex: none; background: #f0f0f0; }
        .pending-item .pi-main { flex: 1; min-width: 0; }
        .pending-item .pi-title { font-weight: 500; color: #007bff; text-decoration: none; }
        .pending-item .pi-title:hover { text-decoration: underline; }
        .pending-item .pi-meta { color: #888; font-size: 0.85em; margin-top: 3px; }
        .pending-badge { background: #fff3cd; color: #856404; padding: 2px 9px; border-radius: 10px; font-size: 0.78em; flex: none; }
        .pending-empty { color: #666; padding: 20px 0; }
        .pending-back { display: inline-block; margin-top: 20px; color: #666; text-decoration: none; }
        .pending-back:hover { color: #333; }
    </style>
</head>
<body>
    <div class="pending-wrap">
        <h1><i class="fas fa-clock"></i> <?php echo htmlspecialchars(t('pending.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="pending-intro"><?php echo htmlspecialchars(t('pending.intro'), ENT_QUOTES, 'UTF-8'); ?></p>

        <?php if (empty($rows)): ?>
            <p class="pending-empty"><?php echo htmlspecialchars(t('pending.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
            <ul class="pending-list">
                <?php foreach ($rows as $r): ?>
                    <li class="pending-item">
                        <img src="<?php echo htmlspecialchars(poster_src($r['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="">
                        <div class="pi-main">
                            <a class="pi-title" href="anime_details.php?id=<?php echo (int)$r['id']; ?>"><?php echo htmlspecialchars(display_title($r), ENT_QUOTES, 'UTF-8'); ?></a>
                            <div class="pi-meta">
                                <?php
                                $bits = [];
                                if (!empty($r['media_type'])) { $bits[] = $r['media_type']; }
                                if (!empty($r['status']))     { $bits[] = broadcast_status_label($r['status']); }
                                echo htmlspecialchars(implode(' - ', $bits), ENT_QUOTES, 'UTF-8');
                                ?>
                            </div>
                        </div>
                        <span class="pending-badge"><?php echo htmlspecialchars(t('pending.badge'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <a href="index.php" class="pending-back"><i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('pending.back'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
</body>
</html>
