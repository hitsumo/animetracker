<?php

/**
 * Anime Tracker - Admin UI Translations (Turkish)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * ---
 *
 * Admin tarafi sozluk dosyasi. Sadece admin sayfalarinda
 * (admin.php, admin_pending.php, admin_sync.php) yuklenir.
 * Helper: lang_init_admin($pdo) - hem user (lang/tr.php) hem
 * admin (lang/admin_tr.php) sozlugunu birlestirip yukler.
 *
 * Bu dosya AYRIK tutulur (user kurulumlarinda yuklenmez)
 * cunku:
 *   1. User'lar admin sayfalarini gormez, bu anahtarlar onlar
 *      icin "olu sermaye" olurdu (sebepsiz bandwidth + dosya
 *      sismesi).
 *   2. Admin sayfalarinin online yuku ayri tutulur - ozellikle
 *      Faz 2'de "online admin paneli" baslarsa zaten ayrik
 *      kalir.
 *
 * Sozluk yapisi user tarafindakiyle (lang/tr.php) ayni:
 * dot-notation namespace, t() helper'i ile cagrilir, EN'de
 * eksik anahtar TR'ye fallback eder, TR'de de yoksa anahtarin
 * kendisi doner (gorunur developer uyarisi).
 */

return [

    // -----------------------------------------------------------------
    // admin.php - admin dashboard (localhost-only)
    // -----------------------------------------------------------------
    'admin.page_title'                       => 'Admin Dashboard - Anime Tracker',
    'admin.heading'                          => 'Admin Dashboard',
    'admin.subtitle'                         => 'Sadece localhost - normal kullanıcılar erişemez',
    'admin.intro'                            => 'Bu sayfa katalog sahibi için admin araçlarını barındırır. Yalnızca localhost\'tan erişilebilir (uzaktan istekler reddedilir) ve hiçbir sayfadan link verilmemiştir. <code>.exe</code> installer\'a dahil edilmez, yani son kullanıcılar bu dosyayı almaz; ancak kaynak GitHub\'da herkese açıktır, bu yüzden gerçek sırları burada değil <code>admin_secret.php</code>\'de tutun.',
    'admin.tool.sync.h3'                     => 'Sunucuya Katalog Gönder',
    'admin.tool.sync.desc'                   => 'Local DB\'deki katalogu sunucuya gönderir. Yeni eklenen animeler ve kronoloji notları HMAC imzalı POST ile iletilir. Kişisel veriler (izleme durumu, notlar) gönderilmez.',
    'admin.tool.sync.link.disabled'          => 'Kurulum eksik',
    'admin.tool.sync.link.open'              => 'Sync sayfasını aç',
    'admin.tool.sync.missing_files'          => 'Eksik dosyalar:',
    'admin.tool.sync.pending_warning'        => '%d bekleyen anime var. Bunlar <code>source=\'local\'</code> olduğu için admin_sync payload\'ına dahil edilmez (sessizce atlanır). Önce <strong>Bekleyen Animeler</strong> kartından seçilenleri kataloga al, sonra buradan push yap.',
    'admin.tool.sync.status_ok'              => 'Kurulum tamam',
    'admin.tool.pending.h3'                  => 'Bekleyen Animeler',
    'admin.tool.pending.desc'                => 'Yeni eklenen animeler varsayılan olarak <code>source=\'local\'</code> durumunda oluşturulur ve sunucuya gitmez. Bu aracı kullanarak seçilen animeleri <code>source=\'catalog\'</code> durumuna al, sonra admin_sync ile sunucuya gönder.',
    'admin.tool.pending.link.count'          => '%d bekleyen anime',
    'admin.tool.pending.link.open'           => 'Listeyi aç',
    'admin.tool.pending.status_ok'           => 'Bekleyen yok',
    'admin.tool.pending.missing_file'        => 'Eksik dosya:',
    'admin.tool.capabilities.h3'             => 'Yönetici Yetenekleri',
    'admin.tool.capabilities.desc'           => 'Düzenleme kilidi gibi yönetici override anahtarları. Yalnızca bu (admin) kurulumda görünür, istemcilere gitmez.',
    'admin.tool.capabilities.link.open'      => 'Yetenekleri aç',
    'admin.back_to_home'                     => 'Ana sayfaya dön',

    // -----------------------------------------------------------------
    // admin_pending.php - source='local' anime promotion to catalog
    // -----------------------------------------------------------------
    'admin_pending.localhost_only'           => 'Bu sayfa sadece localhost üzerinden erişilebilir.',
    'admin_pending.error.csrf'               => 'Geçersiz CSRF tokeni.',
    'admin_pending.error.no_selection'       => 'Hiç anime seçilmedi.',
    'admin_pending.error.invalid_id'         => 'Geçersiz anime ID.',
    'admin_pending.error.unknown_action'     => 'Bilinmeyen işlem.',
    'admin_pending.success.promoted_some'    => '%d anime kataloğa alındı. Sunucuya göndermek için admin_sync.php sayfasını kullan.',
    'admin_pending.success.promoted_all'     => '%d anime kataloğa alındı.',
    'admin_pending.success.demoted'          => 'Anime katalogdan çıkarıldı (local yapıldı).',
    'admin_pending.page_title'               => 'Admin: Bekleyen Animeler - Anime Tracker',
    'admin_pending.heading'                  => 'Bekleyen Animeler',
    'admin_pending.subtitle'                 => 'source=\'local\' - kataloğa alınmamış, sunucuya gitmiyor',
    'admin_pending.badge.catalog'            => 'Katalog:',
    'admin_pending.badge.local'              => 'Local:',
    'admin_pending.empty'                    => 'Bekleyen anime yok. Tüm yerel kayıtlar zaten katalog durumunda.<br><br>Yeni bir anime ekledikten sonra burada gözükür. Seçip "Kataloğa Al" dedikten sonra admin_sync.php üzerinden sunucuya push et.',
    'admin_pending.btn.select_all'           => 'Tümünü Seç',
    'admin_pending.btn.clear_selection'      => 'Seçimi Temizle',
    'admin_pending.btn.promote_selected'     => 'Seçilenleri Kataloğa Al',
    'admin_pending.btn.promote_all'          => 'Hepsini Kataloğa Al',
    'admin_pending.confirm.promote_all'      => '%d animenin TÜMÜNÜ kataloğa almak istediğinize emin misiniz?',
    'admin_pending.col.title'                => 'Başlık',
    'admin_pending.col.broadcast_status'     => 'Yayın Durumu',
    'admin_pending.col.watch_status'         => 'İzleme Durumu',
    'admin_pending.col.external_ids'         => 'MAL / AniDB',
    'admin_pending.col.added'                => 'Eklenme',
    'admin_pending.back_to_dashboard'        => 'Admin dashboard',

    // -----------------------------------------------------------------
    // admin_sync.php (admin_sync_example.php sablonu) - sunucuya push
    // -----------------------------------------------------------------
    'admin_sync.error.csrf'                  => 'CSRF tokeni geçersiz.',
    'admin_sync.error.no_secret'             => 'ADMIN_PUSH_SECRET config.php içinde tanımlı değil.',
    'admin_sync.error.curl'                  => 'cURL hatası: %s',
    'admin_sync.error.invalid_response'      => 'Geçersiz sunucu yanıtı (HTTP %d): %s',
    'admin_sync.error.server'                => 'Sunucu hatası (HTTP %d): %s',
    'admin_sync.page_title'                  => 'Admin Sync - Anime Tracker',
    'admin_sync.heading'                     => 'Admin Sync',
    'admin_sync.intro'                       => 'Local katalogunuzu sunucuya gönderir. Sadece sizin (admin) tarafınızdan kullanılır. Kişisel izleme veriniz (watched, status, notes) GÖNDERİLMEZ - sadece katalog bilgileri (başlıklar, synopsis, linkler, kronoloji) aktarılır.',
    'admin_sync.pending.title'               => 'Bekleyen %d anime var',
    'admin_sync.pending.body'                => 'Local DB\'de %d anime hala <code>source=\'local\'</code> durumunda - bu push onları <strong>sunucuya göndermez</strong>. Önce <a href="admin_pending.php">Bekleyen Animeler</a> sayfasından kataloğa al, sonra buradan push yap.',
    'admin_sync.setup.title'                 => 'Kurulum gerekli',
    'admin_sync.setup.body'                  => 'Proje köküne <code>admin_secret.php</code> dosyası oluşturun (kesinlikle GitHub\'a commit etmeyin, <code>.gitignore</code>\'da tanımlı):',
    'admin_sync.setup.match_note'            => 'Aynı anahtar sunucunun <code>private/admin_push_config.php</code> dosyasındaki <code>ADMIN_SECRET</code> ile birebir aynı olmalı.',
    'admin_sync.box.error_title'             => 'Hata',
    'admin_sync.box.success_title'           => 'Sunucu güncellendi',
    'admin_sync.stat.inserted'               => 'yeni anime eklendi',
    'admin_sync.stat.updated'                => 'mevcut anime güncellendi',
    'admin_sync.stat.markers'                => 'kronoloji notu',
    'admin_sync.summary'                     => 'Gönderilen: %d anime, %d kronoloji notu.',
    'admin_sync.reminder'                    => '<strong>Hatırlatma:</strong> Yeni eklenen animeler için poster görsellerini sunucunun <code>uploads/</code> klasörüne FTP ile yüklemeyi unutmayın. Yoksa ilk kullanıcı sync\'te poster indirmeye çalışırken 404 alır.',
    'admin_sync.btn.push'                    => 'Sunucuya Gönder',
    'admin_sync.confirm.push'                => 'Local katalog sunucuya gönderilecek. Devam?',
    'admin_sync.back_to_settings'            => 'Liste Ayarlarına dön',

    // --- admin_capabilities.php ---
    'admin_cap.page_title'                   => 'Yönetici Yetenekleri',
    'admin_cap.heading'                      => 'Yönetici Yetenekleri',
    'admin_cap.subtitle'                     => 'Küratör override anahtarları',
    'admin_cap.intro'                        => 'Bu anahtarlar yalnızca bu kurulumu etkiler ve istemcilere gönderilmez.',
    'admin_cap.synopsis_override.h3'         => 'Konu düzenleme kilidini aç',
    'admin_cap.synopsis_override.desc'       => 'Açık olduğunda, Kişisel Konu girilmiş animelerde de katalog Konu (TR/EN) alanları düzenlenebilir kalır. Mod 1/Mod 2 mantığı değişmez; yalnızca salt-okunur kilidi kalkar.',
    'admin_cap.synopsis_override.checkbox'   => 'Tüm konular düzenlenebilir kalsın',
    'admin_cap.synopsis_override.status_on'  => 'Açık - katalog konuları her zaman düzenlenebilir',
    'admin_cap.synopsis_override.status_off' => 'Kapalı - Kişisel Konu dolu animelerde katalog konusu kilitli (varsayılan)',
    'admin_cap.save'                         => 'Kaydet',
    'admin_cap.back_to_admin'                => 'Yönetici paneline dön',
    'admin_cap.csrf_invalid'                 => 'Geçersiz güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.',

];
