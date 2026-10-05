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
 * privacy.php - Gizlilik ve kullanim kosullari (1.2.4).
 *
 * Metin MODA GORE degisir, cunku iki modun tuttugu veri farklidir:
 *   - Online (MULTI_USER_MODE): uyelik, davetiye talebi, oneri - kisisel
 *     veri var; sayfa ne tutuldugunu, nedenini, suresini ve kime
 *     basvurulacagini soyler. Iletisim adresi koda gomulu DEGIL:
 *     privacy_contact_email() (Yonetici Yetenekleri > Iletisim, yani
 *     admin/admin_capabilities.php'de girilir; davet bildirim adresine
 *     DUSMEZ) - her kurulumun
 *     veri sorumlusu onu isleten kisidir, uygulama onun yerine adres yazmaz.
 *   - Self-host (tek kullanici): hesap/e-posta/IP yok; sayfa uygulamanin
 *     disariya baglandigi yerleri listeler.
 * "Kendi sunucusuna kurulan kopyalar" bolumu iki modda da gorunur: canli
 * sitede "baskalarinin kurulumundan sorumlu degiliz", baskasinin
 * kurulumunda "bu kurulumdan gelistirici sorumlu degil" anlamina gelir -
 * ayni cumle iki yerde de dogru.
 *
 * Govde metinleri dil dosyasindan HTML olarak gelir (yardim sayfalariyla
 * ayni kural: dil dosyasi guvenilir kaynak); disaridan gelen tek deger
 * iletisim adresi, o da kacirilarak basilir.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

$isOnline = defined('MULTI_USER_MODE') && MULTI_USER_MODE;
$contact  = $isOnline ? privacy_contact_email($pdo) : '';
$contactHtml = '';
if ($contact !== '') {
    $c = htmlspecialchars($contact, ENT_QUOTES, 'UTF-8');
    $contactHtml = '<a href="mailto:' . $c . '">' . $c . '</a>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('privacy.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php
    echo seo_head([
        'title'       => t('privacy.page_title'),
        'description' => t('seo.privacy.description'),
        'canonical'   => 'privacy.php',
    ]);
    ?>
    <?php echo asset_styles(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
<div class="help-container">
    <?php echo guest_lang_links(); ?>
    <a href="about.php" class="back-link"><?php echo htmlspecialchars(t('privacy.back_to_about'), ENT_QUOTES, 'UTF-8'); ?></a>

    <h1><i class="fas fa-user-shield icon-inline"></i> <?php echo htmlspecialchars(t('privacy.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

    <?php if ($isOnline): ?>
    <p><?php echo t('privacy.intro'); ?></p>

    <div class="box info">
        <strong><?php echo htmlspecialchars(t('privacy.operator.title'), ENT_QUOTES, 'UTF-8'); ?></strong>
        <?php echo t('privacy.operator.text'); ?>
        <?php if ($contactHtml !== ''): ?>
            <?php echo sprintf(t('privacy.contact_fmt'), $contactHtml); ?>
        <?php else: ?>
            <?php echo t('privacy.contact_none'); ?>
        <?php endif; ?>
    </div>

    <!-- =============================================================== -->
    <h2 id="gizlilik"><?php echo htmlspecialchars(t('privacy.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <h3><?php echo htmlspecialchars(t('privacy.data.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul>
        <?php echo t('privacy.data.list'); ?>
    </ul>

    <h3><?php echo htmlspecialchars(t('privacy.purpose.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo t('privacy.purpose.text'); ?></p>

    <h3><?php echo htmlspecialchars(t('privacy.sharing.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo t('privacy.sharing.text'); ?></p>

    <h3><?php echo htmlspecialchars(t('privacy.cookies.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo t('privacy.cookies.text'); ?></p>

    <h3><?php echo htmlspecialchars(t('privacy.external.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo t('privacy.external.text'); ?></p>

    <h3><?php echo htmlspecialchars(t('privacy.retention.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo sprintf(t('privacy.retention.text_fmt'), (int)PRIVACY_IP_RETENTION_DAYS); ?></p>

    <h3><?php echo htmlspecialchars(t('privacy.rights.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <p><?php echo t('privacy.rights.text'); ?></p>

    <?php else: ?>
    <p><?php echo t('privacy.single.intro'); ?></p>

    <!-- =============================================================== -->
    <h2 id="gizlilik"><?php echo htmlspecialchars(t('privacy.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>

    <h3><?php echo htmlspecialchars(t('privacy.single.outbound.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <ul>
        <?php echo t('privacy.single.outbound.list'); ?>
    </ul>
    <?php endif; ?>

    <!-- =============================================================== -->
    <h2 id="kosullar"><?php echo htmlspecialchars(t('privacy.terms.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>
    <ul>
        <?php echo t($isOnline ? 'privacy.terms.list' : 'privacy.single.terms.list'); ?>
    </ul>

    <h3 id="kendi-sunucusu"><?php echo htmlspecialchars(t('privacy.selfhost.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
    <div class="box warning">
        <?php echo t('privacy.selfhost.text'); ?>
    </div>

    <a href="about.php" class="back-link"><?php echo htmlspecialchars(t('privacy.back_to_about'), ENT_QUOTES, 'UTF-8'); ?></a>
</div>
</body>
</html>
