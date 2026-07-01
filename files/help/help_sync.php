<?php
/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]

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

/**
 * help/help_sync.php - Yardim alt sayfasi (gruplu konu).
 *
 * Tek buyuk help.php 1.0.22'de bir indexe (help.php = sadece icindekiler)
 * ve gruplu alt sayfalara bolundu. Ortak stiller css/help.css
 * (style.css icinden @import edilir). Icerik help.* i18n anahtarlarindan
 * gelir; index ../help.php uzerinden
 * her bolume #anchor ile baglanir.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
lang_init($pdo);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('help.group.sync.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.sync.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="sync"><?php echo htmlspecialchars(t('help.sync.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.sync.intro'); ?>
    </p>

    <div class="box safe">
        <strong><?php echo t('help.sync.safe_title'); ?></strong>
        <?php echo t('help.sync.safe_body'); ?>
    </div>

    <div class="box warning">
        <strong><?php echo t('help.sync.warning_title'); ?></strong>
        <?php echo t('help.sync.warning_body'); ?>
    </div>

    <h3><?php echo htmlspecialchars(t('help.sync.own_added.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.sync.own_added.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.sync.when.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.sync.when.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.sync.aired.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.sync.aired.text'); ?>
    </p>
    <div class="box safe">
        <strong><?php echo t('help.sync.aired.box_title'); ?></strong>
        <?php echo t('help.sync.aired.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="silme-uyarilari"><?php echo htmlspecialchars(t('help.delete.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <div class="box danger">
        <strong><?php echo t('help.delete.danger_title'); ?></strong>
        <ul style="margin: 8px 0 0;">
            <?php echo t('help.delete.danger_list'); ?>
        </ul>
    </div>

    <div class="box safe">
        <strong><?php echo t('help.delete.safe_title'); ?></strong>
        <ul style="margin: 8px 0 0;">
            <?php echo t('help.delete.safe_list'); ?>
        </ul>
    </div>
    <!-- =============================================================== -->
    <h2 id="guncelleme"><?php echo htmlspecialchars(t('help.update.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.update.intro'); ?>
    </p>

    <p>
        <?php echo t('help.update.flow_intro'); ?>
    </p>
    <ul>
        <?php echo t('help.update.flow_list'); ?>
    </ul>

    <div class="box safe">
        <strong><?php echo t('help.update.safe_title'); ?></strong>
        <?php echo t('help.update.safe_body'); ?>
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
