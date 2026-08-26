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
 * help/help_prefs.php - Yardim alt sayfasi (gruplu konu). 1.1.33'te eklendi.
 *
 * Liste Ayarlari > Genel Ayarlar sekmesindeki KISISEL tercihler tek yerde:
 * yedi tercihin listesi, arti kendi bolumu olmayan ikisinin (arayuz dili,
 * yetiskin icerik) tam anlatimi. Baslik dili "Alanlar", kronoloji gorunumu
 * ve seri kronolojisi gorunumu "Seriler", spoiler korumasi yine "Seriler"
 * sayfasinda anlatilir - buradaki liste onlara isaret eder, metni
 * KOPYALAMAZ (iki kopya, biri degistigi gun ayrisir).
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
    <title><?php echo htmlspecialchars(t('help.group.prefs.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - SEO meta (see help_basics.php for the '../' note).
    echo seo_head([
        'title'       => t('help.group.prefs.page_title'),
        'description' => sprintf(t('seo.help.group.description_fmt'), t('help.group.prefs.heading')),
        'canonical'   => 'help/help_prefs.php',
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

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.prefs.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="tercihler"><?php echo htmlspecialchars(t('help.prefs.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.prefs.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.prefs.list'); ?>
    </ul>
    <!-- =============================================================== -->
    <h2 id="arayuz-dili"><?php echo htmlspecialchars(t('help.prefs.ui_lang.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.prefs.ui_lang.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="yetiskin"><?php echo htmlspecialchars(t('help.prefs.adult.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.prefs.adult.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.prefs.adult.list'); ?>
    </ul>

    <div class="box info">
        <strong><?php echo t('help.prefs.adult.box_title'); ?></strong>
        <?php echo t('help.prefs.adult.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="spoiler-tercihi"><?php echo htmlspecialchars(t('help.prefs.spoiler.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.prefs.spoiler.text'); ?>
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
