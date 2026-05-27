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
 * help.php - Kullanici yardim / nasil calisir sayfasi.
 *
 * Teknik olmayan dille sistemin nasil calistigini anlatir. Ozellikle
 * sync davranisi, kisisel alanlar (Notlar / Kisisel Konu), oneri
 * sistemi ve veri guvenligi konularinda kullaniciyi bilgilendirir.
 *
 * Statik icerik - 0.6.3 sonrasi i18n icin db.php + functions.php
 * yuklenir, lang_init() ile dil baslatilir. Aksi halde statik.
 * Menuden veya Hakkinda sayfasindan linklenebilir.
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
    <style>
        .help-container {
            max-width: 850px;
            margin: 40px auto;
            padding: 30px 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            font-family: 'Poppins', sans-serif;
            color: #333;
            line-height: 1.7;
        }
        .help-container h1 {
            font-size: 2em;
            color: #2c3e50;
            margin-top: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #4a90e2;
        }
        .help-container h2 {
            font-size: 1.4em;
            color: #2c3e50;
            margin-top: 35px;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #eee;
        }
        .help-container h3 {
            font-size: 1.1em;
            color: #4a90e2;
            margin-top: 22px;
            margin-bottom: 8px;
        }
        .help-container p {
            margin: 10px 0;
            color: #444;
        }
        .help-container ul {
            padding-left: 24px;
            margin: 10px 0;
        }
        .help-container li {
            margin: 6px 0;
        }
        .help-container code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
            color: #c7254e;
        }
        .help-container .box {
            background: #f8f9fa;
            border-left: 4px solid #4a90e2;
            padding: 12px 18px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .help-container .box.warning {
            background: #fff8e1;
            border-left-color: #ffc107;
        }
        .help-container .box.safe {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .help-container .box.danger {
            background: #ffebee;
            border-left-color: #e53935;
        }
        .help-container .box.info {
            background: #e3f2fd;
            border-left-color: #1976d2;
        }
        .help-container .icon-inline {
            color: #4a90e2;
            margin-right: 6px;
        }
        .help-container .toc {
            background: #f8f9fa;
            padding: 15px 25px;
            border-radius: 6px;
            margin-bottom: 25px;
        }
        .help-container .toc a {
            color: #4a90e2;
            text-decoration: none;
        }
        .help-container .toc a:hover {
            text-decoration: underline;
        }
        .help-container table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .help-container th, .help-container td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .help-container th {
            background: #f4f6f8;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin: 20px 0;
            color: #4a90e2;
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="help-container">
    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>

    <h1><i class="fas fa-question-circle icon-inline"></i> <?php echo htmlspecialchars(t('help.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <p>
        <?php echo t('help.intro'); ?>
    </p>

    <div class="toc">
        <strong><?php echo htmlspecialchars(t('help.toc.heading'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <ul style="margin: 8px 0 0;">
            <li><a href="#alanlar"><?php echo htmlspecialchars(t('help.toc.fields'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#izleme-durumlari"><?php echo htmlspecialchars(t('help.toc.statuses'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#hizli-butonlar"><?php echo htmlspecialchars(t('help.toc.quick_buttons'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#sync"><?php echo htmlspecialchars(t('help.toc.sync'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#kisisel-alanlar"><?php echo htmlspecialchars(t('help.toc.personal'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#oneri"><?php echo htmlspecialchars(t('help.toc.recommendations'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#kronoloji"><?php echo htmlspecialchars(t('help.toc.chronology'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#silme-uyarilari"><?php echo htmlspecialchars(t('help.toc.deletion'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#guncelleme"><?php echo htmlspecialchars(t('help.toc.updates'), ENT_QUOTES, 'UTF-8'); ?></a></li>
            <li><a href="#saat-dilimi"><?php echo htmlspecialchars(t('help.toc.timezone'), ENT_QUOTES, 'UTF-8'); ?></a></li>
        </ul>
    </div>

    <!-- =============================================================== -->
    <h2 id="alanlar"><?php echo htmlspecialchars(t('help.fields.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.fields.intro'); ?>
    </p>

    <h3><?php echo t('help.fields.catalog.h3'); ?></h3>
    <ul>
        <?php echo t('help.fields.catalog.list'); ?>
    </ul>
    <p>
        <?php echo t('help.fields.catalog.note'); ?>
    </p>

    <h3><?php echo t('help.fields.personal.h3'); ?></h3>
    <ul>
        <?php echo t('help.fields.personal.list'); ?>
    </ul>
    <p>
        <?php echo t('help.fields.personal.note'); ?>
    </p>

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

    <h3><?php echo htmlspecialchars(t('help.buttons.manual.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>

    <p>
        <?php echo t('help.buttons.manual.text'); ?>
    </p>

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

    <!-- =============================================================== -->
    <h2 id="kisisel-alanlar"><?php echo htmlspecialchars(t('help.personal.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <p>
        <?php echo t('help.personal.intro'); ?>
    </p>

    <table>
        <tr>
            <th><?php echo htmlspecialchars(t('help.personal.table.col_field'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(t('help.personal.table.col_purpose'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(t('help.personal.table.col_example'), ENT_QUOTES, 'UTF-8'); ?></th>
        </tr>
        <tr>
            <td><strong><?php echo htmlspecialchars(t('help.personal.table.row_notes_field'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td><?php echo htmlspecialchars(t('help.personal.table.row_notes_purpose'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(t('help.personal.table.row_notes_example'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td><strong><?php echo htmlspecialchars(t('help.personal.table.row_synopsis_field'), ENT_QUOTES, 'UTF-8'); ?></strong></td>
            <td><?php echo htmlspecialchars(t('help.personal.table.row_synopsis_purpose'), ENT_QUOTES, 'UTF-8'); ?></td>
            <td><?php echo htmlspecialchars(t('help.personal.table.row_synopsis_example'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </table>

    <h3><?php echo t('help.personal.howto.h3'); ?></h3>
    <p>
        <?php echo t('help.personal.howto.intro'); ?>
    </p>
    <ul>
        <?php echo t('help.personal.howto.list'); ?>
    </ul>

    <div class="box warning">
        <strong><?php echo t('help.personal.warning_title'); ?></strong>
        <?php echo t('help.personal.warning_body'); ?>
    </div>

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

    <div class="box warning">
        <strong><?php echo t('help.chrono.warning_title'); ?></strong>
        <?php echo t('help.chrono.warning_body'); ?>
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

    <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>
</div>
</body>
</html>
