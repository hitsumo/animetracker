<?php
/**
 * Anime Tracker - Anime izleme takip listesi
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

/**
 * help/help_transfer.php - Yardim alt sayfasi (gruplu konu). 1.1.33'te eklendi.
 *
 * Liste Ayarlari'nin "Ice/Disa Aktar" sekmesi: yedek alma (JSON disa
 * aktarma), yedegi geri yukleme, MyAnimeList ve AniList ice aktarma, arti
 * "Listeyi Temizle". Bu dort is 1.1.32'ye kadar yardimda hic anlatilmiyordu;
 * yardim yalnizca KATALOG sync'ini anlatiyordu ki o bambaska bir istir
 * (bkz. help_sync.php).
 *
 * Ortak stiller css/help.css. Icerik help.* i18n anahtarlarindan gelir.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
lang_init($pdo);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('help.group.transfer.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - SEO meta (see help_basics.php for the '../' note).
    echo seo_head([
        'title'       => t('help.group.transfer.page_title'),
        'description' => sprintf(t('seo.help.group.description_fmt'), t('help.group.transfer.heading')),
        'canonical'   => 'help/help_transfer.php',
        'base'        => '../',
    ]);
    ?>
    <?php echo asset_styles('../'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.transfer.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <p>
        <?php echo t('help.transfer.intro'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="disa-aktar"><?php echo htmlspecialchars(t('help.transfer.export.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.transfer.export.text'); ?>
    </p>

    <ul>
        <?php echo t('help.transfer.export.list'); ?>
    </ul>

    <div class="box safe">
        <strong><?php echo t('help.transfer.export.box_title'); ?></strong>
        <?php echo t('help.transfer.export.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="ice-aktar"><?php echo htmlspecialchars(t('help.transfer.import.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.transfer.import.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.transfer.import.online.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.transfer.import.online.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.transfer.import.selfhost.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.transfer.import.selfhost.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="mal"><?php echo htmlspecialchars(t('help.transfer.mal.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.transfer.mal.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.transfer.mal.steps'); ?>
    </ul>

    <div class="box info">
        <strong><?php echo t('help.transfer.mal.box_title'); ?></strong>
        <?php echo t('help.transfer.mal.box_body'); ?>
    </div>

    <p>
        <?php echo t('help.transfer.mal.note'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="anilist"><?php echo htmlspecialchars(t('help.transfer.anilist.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.transfer.anilist.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.transfer.anilist.modes.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul>
        <?php echo t('help.transfer.anilist.modes'); ?>
    </ul>

    <p>
        <?php echo t('help.transfer.anilist.overwrite'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.transfer.anilist.box_title'); ?></strong>
        <?php echo t('help.transfer.anilist.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="temizle"><?php echo htmlspecialchars(t('help.transfer.clear.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.transfer.clear.text'); ?>
    </p>

    <div class="box danger">
        <strong><?php echo t('help.transfer.clear.danger_title'); ?></strong>
        <?php echo t('help.transfer.clear.danger_body'); ?>
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
