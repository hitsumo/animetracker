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
 * help/help_basics.php - Yardim alt sayfasi (gruplu konu).
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
    <title><?php echo htmlspecialchars(t('help.group.basics.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
</head>
<body>
<div class="help-container">
    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.group.basics.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <!-- =============================================================== -->
    <h2 id="izleme-durumlari"><?php echo htmlspecialchars(t('help.statuses.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.statuses.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.statuses.list'); ?>
    </ul>

    <p>
        <?php echo t('help.statuses.when_postponed'); ?>
    </p>
    <!-- =============================================================== -->
    <h2 id="hizli-butonlar"><?php echo htmlspecialchars(t('help.buttons.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.buttons.intro'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.buttons.transitions.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.transitions.intro'); ?>
    </p>

    <table>
        <tr>
            <th><?php echo htmlspecialchars(t('help.buttons.transitions.col_current'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(t('help.buttons.transitions.col_action'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(t('help.buttons.transitions.col_new'), ENT_QUOTES, 'UTF-8'); ?></th>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row1_curr'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><code>+</code></td>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row1_new'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row2_curr'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><code>+</code></td>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row2_new'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row3_curr'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><code>-</code></td>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row3_new'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row4_curr'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><code>-</code></td>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row4_new'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row5_curr'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><code>+</code></td>
            <td><?php echo htmlspecialchars(t('help.buttons.transitions.row5_new'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </table>

    <p>
        <?php echo t('help.buttons.transitions.note'); ?>
    </p>

    <h3><?php echo htmlspecialchars(t('help.buttons.two_step.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.two_step.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.buttons.two_step.list'); ?>
    </ul>

    <h3><?php echo htmlspecialchars(t('help.buttons.untouched.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <div class="box safe">
        <strong><?php echo t('help.buttons.untouched.box_title'); ?></strong>
        <ul style="margin: 8px 0 0;">
            <?php echo t('help.buttons.untouched.list'); ?>
        </ul>
    </div>

    <h3><?php echo htmlspecialchars(t('help.buttons.unknown_count.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.unknown_count.intro'); ?>
    </p>

    <ul>
        <?php echo t('help.buttons.unknown_count.list'); ?>
    </ul>

    <h3><?php echo htmlspecialchars(t('help.buttons.airing_unknown.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.airing_unknown.intro'); ?>
    </p>

    <div class="box info">
        <strong><?php echo t('help.buttons.airing_unknown.box_title'); ?></strong>
        <?php echo t('help.buttons.airing_unknown.box_body'); ?>
    </div>

    <h3><?php echo htmlspecialchars(t('help.buttons.manual.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.manual.text'); ?>
    </p>

    <p style="margin-top: 40px; color: #888; font-size: 0.9em;">
        <?php echo t('help.footer'); ?>
    </p>

    <a href="../help.php" class="back-link"><?php echo t('help.back_to_index'); ?></a>
</div>
</body>
</html>
