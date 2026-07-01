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
 * help/help_timezone.php - Yardim alt sayfasi (gruplu konu).
 *
 * Tek buyuk help.php 1.0.22'de bir indexe (help.php = sadece icindekiler)
 * ve gruplu alt sayfalara bolundu. Ortak stiller ../help.css icindedir.
 * Icerik help.* i18n anahtarlarindan gelir; index ../help.php uzerinden
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
    <title><?php echo htmlspecialchars(t('help.group.timezone.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../help.css">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.timezone.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="saat-dilimi"><?php echo htmlspecialchars(t('help.tz.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.tz.intro'); ?>
    </p>

    <h3><?php echo t('help.tz.bc_tz.h3'); ?></h3>
    <p>
        <?php echo t('help.tz.bc_tz.text'); ?>
    </p>

    <div class="box safe">
        <strong><?php echo t('help.tz.autofill_title'); ?></strong>
        <?php echo t('help.tz.autofill_body'); ?>
    </div>

    <h3><?php echo htmlspecialchars(t('help.tz.workflows.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.tz.workflows.intro'); ?>
    </p>
    <ul>
        <?php echo t('help.tz.workflows.list'); ?>
    </ul>
    <p>
        <?php echo t('help.tz.consistency'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.tz.box_animeschedule_title'); ?></strong>
        <?php echo t('help.tz.box_animeschedule_body'); ?>
    </div>

    <div class="box info">
        <strong><?php echo t('help.tz.box_dst_title'); ?></strong>
        <?php echo t('help.tz.box_dst_body'); ?>
    </div>

    <h3><?php echo htmlspecialchars(t('help.tz.upgrade.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.tz.upgrade.text'); ?>
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
