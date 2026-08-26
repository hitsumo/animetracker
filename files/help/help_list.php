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
 * help/help_list.php - Yardim alt sayfasi (gruplu konu). 1.1.33'te eklendi.
 *
 * Ana liste sayfasinin kendisi: iki sekme (Genel/Kisisel), arama, alti
 * filtre, duygu filtresi, sayfalama ve siralama, arti "Son Guncellenenler"
 * sayfasi. Bunlarin hicbiri 1.1.32'ye kadar yardimda yoktu.
 *
 * Ortak stiller css/help.css (style.css icinden @import edilir). Icerik
 * help.* i18n anahtarlarindan gelir; index ../help.php uzerinden her bolume
 * #anchor ile baglanir.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
lang_init($pdo);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('help.group.list.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - SEO meta (see help_basics.php for the '../' note).
    echo seo_head([
        'title'       => t('help.group.list.page_title'),
        'description' => sprintf(t('seo.help.group.description_fmt'), t('help.group.list.heading')),
        'canonical'   => 'help/help_list.php',
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

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.list.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="liste-sekmeleri"><?php echo htmlspecialchars(t('help.list.tabs.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.list.tabs.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.list.tabs.list'); ?>
    </ul>

    <p>
        <?php echo t('help.list.tabs.pref'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.list.tabs.box_title'); ?></strong>
        <?php echo t('help.list.tabs.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="arama"><?php echo htmlspecialchars(t('help.list.search.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.list.search.text'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.list.search.box_title'); ?></strong>
        <?php echo t('help.list.search.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="filtreler"><?php echo htmlspecialchars(t('help.list.filters.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.list.filters.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.list.filters.list'); ?>
    </ul>

    <p>
        <?php echo t('help.list.filters.combine'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.list.filters.emotion.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.list.filters.emotion.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.list.filters.per_page.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.list.filters.per_page.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.list.filters.sort.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.list.filters.sort.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="son-guncellenenler"><?php echo htmlspecialchars(t('help.list.recent.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.list.recent.text'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.list.recent.box_title'); ?></strong>
        <?php echo t('help.list.recent.box_body'); ?>
    </div>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
