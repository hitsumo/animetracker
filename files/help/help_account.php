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
 * help/help_account.php - Yardim alt sayfasi (gruplu konu). 1.1.33'te eklendi.
 *
 * Cok kullanicili (online) kurulumun tamami: giris/kayit/davet/hesap, dort
 * rolun ne yapabildigi, uyenin ekledigi animenin onay kuyruguna dusmesi ve
 * detay sayfasindaki duzeltme onerisi. 1.0.x'ten beri var olan bu yuzeyin
 * yardimda tek satiri yoktu - oysa sitenin herkese acik hali tam olarak bu
 * moda calisir.
 *
 * SELF-HOST NOTU icerigin basindadir: tek kullanicili kurulumda giris diye
 * bir sey yoktur ve bu sayfadaki hicbir sey gorunmez.
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
    <title><?php echo htmlspecialchars(t('help.group.account.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    // 1.1.30 - SEO meta (see help_basics.php for the '../' note).
    echo seo_head([
        'title'       => t('help.group.account.page_title'),
        'description' => sprintf(t('seo.help.group.description_fmt'), t('help.group.account.heading')),
        'canonical'   => 'help/help_account.php',
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

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.account.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <p>
        <?php echo t('help.account.intro'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="uyelik"><?php echo htmlspecialchars(t('help.account.membership.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.account.membership.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.account.register.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.account.register.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.account.invite.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.account.invite.text'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.account.account.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p>
        <?php echo t('help.account.account.text'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="roller"><?php echo htmlspecialchars(t('help.account.roles.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.account.roles.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.account.roles.list'); ?>
    </ul>
    <!-- =============================================================== -->
    <h2 id="anime-ekleme"><?php echo htmlspecialchars(t('help.account.add.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.account.add.text'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.account.add.box_title'); ?></strong>
        <?php echo t('help.account.add.box_body'); ?>
    </div>
    <!-- =============================================================== -->
    <h2 id="oneri"><?php echo htmlspecialchars(t('help.account.suggest.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.account.suggest.text'); ?>
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
