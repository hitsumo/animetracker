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
 * help/help_discovery.php - Yardim alt sayfasi (gruplu konu).
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
    <title><?php echo htmlspecialchars(t('help.group.discovery.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.discovery.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="oneri"><?php echo htmlspecialchars(t('help.recom.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.recom.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.recom.howto.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.recom.howto.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.recom.scoop.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.recom.scoop.text'); ?>
    </p>

    <div class="box safe">
        <strong><?php echo t('help.recom.scoop.box_title'); ?></strong>
        <?php echo t('help.recom.scoop.box_body'); ?>
    </div>

    <h3><?php echo htmlspecialchars(t('help.recom.surprise.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.recom.surprise.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.recom.search.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.recom.search.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="duygular"><?php echo htmlspecialchars(t('help.emotions.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.emotions.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.emotions.list'); ?>
    </ul>

    <div class="box info">
        <strong><?php echo t('help.emotions.cap_title'); ?></strong>
        <?php echo t('help.emotions.cap_body'); ?>
    </div>

    <p>
        <?php echo t('help.emotions.stats'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="istatistik"><?php echo htmlspecialchars(t('help.stats.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.stats.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.stats.user.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.stats.user.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.stats.recent.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.stats.recent.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.stats.global.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.stats.global.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="translation"><?php echo htmlspecialchars(t('help.translation.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.translation.intro'); ?>
    </p>
    <p>
        <?php echo t('help.translation.quality'); ?>
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
