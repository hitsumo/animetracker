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
 * help.php - Yardim index (sadece icindekiler).
 *
 * 1.0.22'de tek buyuk yardim sayfasi bir indexe ve gruplu alt sayfalara
 * bolundu. Bu dosya yalnizca gruplu icindekileri gosterir; her konu
 * help/ altindaki ilgili alt sayfaya (gerekli #anchor ile) baglanir.
 * Ortak stiller css/help.css icindedir (style.css icinden @import edilir).
 * Menuden veya Hakkinda sayfasindan
 * linklenebilir.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('help.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <p style="margin: 0 0 16px; color: #555;">
        <i class="fas fa-envelope icon-inline"></i> <?php echo t('help.contact'); ?>
    </p>

    <p>
        <?php echo t('help.intro'); ?>
    </p>

    <div class="toc">
        <strong><?php echo htmlspecialchars(t('help.toc.heading'), ENT_QUOTES, 'UTF-8'); ?></strong>

        <div class="toc-group">
            <strong><a href="help/help_basics.php"><?php echo htmlspecialchars(t('help.group.basics.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_basics.php#izleme-durumlari"><?php echo htmlspecialchars(t('help.toc.statuses'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_basics.php#hizli-butonlar"><?php echo htmlspecialchars(t('help.toc.quick_buttons'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>

        <div class="toc-group">
            <strong><a href="help/help_fields.php"><?php echo htmlspecialchars(t('help.group.fields.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_fields.php#alanlar"><?php echo htmlspecialchars(t('help.toc.fields'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_fields.php#kisisel-alanlar"><?php echo htmlspecialchars(t('help.toc.personal'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_fields.php#baslik-dili"><?php echo htmlspecialchars(t('help.toc.title_lang'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>

        <div class="toc-group">
            <strong><a href="help/help_sync.php"><?php echo htmlspecialchars(t('help.group.sync.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_sync.php#sync"><?php echo htmlspecialchars(t('help.toc.sync'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_sync.php#silme-uyarilari"><?php echo htmlspecialchars(t('help.toc.deletion'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_sync.php#guncelleme"><?php echo htmlspecialchars(t('help.toc.updates'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>

        <div class="toc-group">
            <strong><a href="help/help_discovery.php"><?php echo htmlspecialchars(t('help.group.discovery.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_discovery.php#oneri"><?php echo htmlspecialchars(t('help.toc.recommendations'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_discovery.php#duygular"><?php echo htmlspecialchars(t('help.toc.emotions'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_discovery.php#istatistik"><?php echo htmlspecialchars(t('help.toc.statistics'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_discovery.php#translation"><?php echo htmlspecialchars(t('help.toc.translation'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>

        <div class="toc-group">
            <strong><a href="help/help_series.php"><?php echo htmlspecialchars(t('help.group.series.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_series.php#kronoloji"><?php echo htmlspecialchars(t('help.toc.chronology'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                <li><a href="help/help_series.php#dolgu"><?php echo htmlspecialchars(t('help.toc.filler'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>

        <div class="toc-group">
            <strong><a href="help/help_timezone.php"><?php echo htmlspecialchars(t('help.group.timezone.heading'), ENT_QUOTES, 'UTF-8'); ?></a></strong>
            <ul>
                <li><a href="help/help_timezone.php#saat-dilimi"><?php echo htmlspecialchars(t('help.toc.timezone'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            </ul>
        </div>
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>
</div>
</body>
</html>
