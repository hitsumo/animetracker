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
 * help/help_series.php - Yardim alt sayfasi (gruplu konu).
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
    <title><?php echo htmlspecialchars(t('help.group.series.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.series.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="kronoloji"><?php echo htmlspecialchars(t('help.chrono.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.chrono.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.chrono.series.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.chrono.series.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.chrono.next.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.chrono.next.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.chrono.markers.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.chrono.markers.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.chrono.story.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.chrono.story.text'); ?>
    </p>

    <div class="box warning">
        <strong><?php echo t('help.chrono.warning_title'); ?></strong>
        <?php echo t('help.chrono.warning_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="dolgu"><?php echo htmlspecialchars(t('help.filler.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.filler.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.filler.list'); ?>
    </ul>

    <p>
        <?php echo t('help.filler.unmarked'); ?>
    </p>

    <div class="box warning">
        <strong><?php echo t('help.filler.warning_title'); ?></strong>
        <?php echo t('help.filler.warning_body'); ?>
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
