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
    'admin.tool.invites.h3'                  => 'Kayıt ve Davetler',
    'admin.tool.invites.desc'                => 'Davet kodu üret/listele, kayıt modunu (davetli/açık) yönet. Çok kullanıcılı modda anlamlıdır.',
    'admin.tool.invites.link.open'           => 'Davetleri yönet',
    'admin.tool.users.h3'                    => 'Kullanıcı Yönetimi',
    'admin.tool.users.desc'                  => 'Kullanıcıların rolünü (kullanıcı/güvenilir/moderatör/yönetici) ve durumunu (etkin/askıya alınmış) değiştir. Yalnızca yönetici.',
    'admin.tool.users.link.open'             => 'Kullanıcıları yönet',
    'admin.tool.suggestions.h3'              => 'Düzeltme Önerileri',
    'admin.tool.suggestions.desc'            => 'Kullanıcılardan gelen düzeltme önerilerini incele; kabul/ret işaretle. Çok kullanıcılı modda anlamlıdır.',
    'admin.tool.suggestions.link.open'       => 'Önerileri incele',
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
    'admin_pending.success.pushed'           => 'Sunucuya gönderildi: %d eklendi, %d güncellendi.',
    'admin_pending.warn.push_failed'         => 'Kataloğa alındı, ancak sunucuya gönderilemedi: %s',
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
    'admin_sync.error.no_secret'             => 'ADMIN_PUSH_SECRET admin_secret.php içinde tanımlı değil.',
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

    // --- admin_invites.php ---
    'admin_invites.page_title'               => 'Kayıt ve Davetler',
    'admin_invites.heading'                  => 'Kayıt ve Davetler',
    'admin_invites.subtitle'                 => 'Davet kodları ve kayıt modu',
    'admin_invites.intro'                    => 'Davet kodları tek kullanımlıktır. Davetli modda yeni hesap açmak için bir davet kodu gerekir; kullanıcı kodu kayıt sayfasında (register.php) girer.',
    'admin_invites.csrf_invalid'             => 'Geçersiz güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.',
    'admin_invites.mode.h3'                  => 'Kayıt Modu',
    'admin_invites.mode.desc'                => 'Davetli: yeni hesap yalnızca geçerli bir davet koduyla açılır. Açık: herkes davetsiz kayıt olabilir. (Yalnızca yönetici değiştirebilir.)',
    'admin_invites.mode.label'               => 'Mod:',
    'admin_invites.mode.invite'              => 'Davetli',
    'admin_invites.mode.open'                => 'Açık',
    'admin_invites.mode.save'                => 'Kaydet',
    'admin_invites.mode.current'             => 'Şu anki mod:',
    'admin_invites.generate.h3'              => 'Davet Üret',
    'admin_invites.generate.desc'            => 'Yeni bir tek kullanımlık davet kodu üretir. E-posta isteğe bağlıdır (yalnızca bilgi amaçlı, gönderilmez).',
    'admin_invites.generate.email_label'     => 'E-posta (isteğe bağlı)',
    'admin_invites.generate.submit'          => 'Kod üret',
    'admin_invites.list.h3'                  => 'Davetler',
    'admin_invites.list.empty'               => 'Henüz davet kodu yok.',
    'admin_invites.list.col_token'           => 'Kod',
    'admin_invites.list.col_email'           => 'E-posta',
    'admin_invites.list.col_status'          => 'Durum',
    'admin_invites.list.col_created'         => 'Oluşturuldu',
    'admin_invites.list.col_used'            => 'Kullanıldı',
    'admin_invites.list.col_action'          => 'İşlem',
    'admin_invites.list.email_none'          => '-',
    'admin_invites.list.status_unused'       => 'Kullanılmadı',
    'admin_invites.list.status_used'         => 'Kullanıldı',
    'admin_invites.list.revoke'              => 'İptal',
    'admin_invites.list.revoke_confirm'      => 'Bu davet kodu silinsin mi?',
    'admin_invites.back_to_admin'            => 'Yönetici paneline dön',

    // --- admin_users.php ---
    'admin_users.page_title'                 => 'Kullanıcı Yönetimi',
    'admin_users.heading'                    => 'Kullanıcı Yönetimi',
    'admin_users.subtitle'                   => 'Rol ve durum',
    'admin_users.intro'                      => 'Kullanıcıların rolünü ve durumunu buradan değiştirebilirsiniz. Kendi hesabınızı bu ekrandan değiştiremezsiniz (yönetici kilitlenmesini önlemek için).',
    'admin_users.csrf_invalid'               => 'Geçersiz güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.',
    'admin_users.col_username'               => 'Kullanıcı adı',
    'admin_users.col_email'                  => 'E-posta',
    'admin_users.col_role'                   => 'Rol',
    'admin_users.col_status'                 => 'Durum',
    'admin_users.col_created'                => 'Kayıt',
    'admin_users.col_action'                 => 'İşlem',
    'admin_users.role.admin'                 => 'Yönetici',
    'admin_users.role.moderator'             => 'Moderatör',
    'admin_users.role.trusted'               => 'Güvenilir',
    'admin_users.role.user'                  => 'Kullanıcı',
    'admin_users.status.active'              => 'Etkin',
    'admin_users.status.suspended'           => 'Askıda',
    'admin_users.status.pending'             => 'Beklemede',
    'admin_users.status.deleted'             => 'Silinmiş',
    'admin_users.you'                        => 'siz',
    'admin_users.self_locked'                => 'Kendi hesabınız (değiştirilemez)',
    'admin_users.email_none'                 => '-',
    'admin_users.save'                       => 'Kaydet',
    'admin_users.err_self'                   => 'Kendi hesabınızın rolünü veya durumunu bu ekrandan değiştiremezsiniz.',
    'admin_users.err_last_admin'             => 'Bu işlem son etkin yöneticiyi kaldırır. En az bir etkin yönetici kalmalı.',
    'admin_users.back_to_admin'              => 'Yönetici paneline dön',

    // --- admin_suggestions.php ---
    'admin_suggestions.page_title'           => 'Düzeltme Önerileri',
    'admin_suggestions.heading'              => 'Düzeltme Önerileri',
    'admin_suggestions.subtitle'             => 'Moderasyon kuyruğu',
    'admin_suggestions.intro'                => 'Kullanıcıların gönderdiği düzeltme önerileri. Kabul edilen bir öneriyi kataloğa uygulamak manueldir (animeyi açıp düzenleyin).',
    'admin_suggestions.csrf_invalid'         => 'Geçersiz güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.',
    'admin_suggestions.filter.pending'       => 'Bekleyen',
    'admin_suggestions.filter.accepted'      => 'Kabul',
    'admin_suggestions.filter.rejected'      => 'Ret',
    'admin_suggestions.filter.all'           => 'Tümü',
    'admin_suggestions.col_anime'            => 'Anime',
    'admin_suggestions.col_note'             => 'Öneri',
    'admin_suggestions.col_submitter'        => 'Gönderen',
    'admin_suggestions.col_ip'               => 'IP',
    'admin_suggestions.col_created'          => 'Tarih',
    'admin_suggestions.col_status'           => 'Durum',
    'admin_suggestions.col_action'           => 'İşlem',
    'admin_suggestions.submitter_anon'       => 'Anonim',
    'admin_suggestions.status.pending'       => 'Bekliyor',
    'admin_suggestions.status.accepted'      => 'Kabul edildi',
    'admin_suggestions.status.rejected'      => 'Reddedildi',
    'admin_suggestions.action.accept'        => 'Kabul et',
    'admin_suggestions.action.reject'        => 'Reddet',
    'admin_suggestions.action.reopen'        => 'Tekrar aç',
    'admin_suggestions.action.edit_anime'    => 'Animeyi düzenle',
    'admin_suggestions.empty'                => 'Bu kategoride öneri yok.',
    'admin_suggestions.back_to_admin'        => 'Yönetici paneline dön',


    // --- admin_catalog_requests.php (Katalog Onerileri) ---
    'admin_catalog_requests.page_title'          => 'Katalog Önerileri',
    'admin_catalog_requests.heading'             => 'Katalog Önerileri',
    'admin_catalog_requests.subtitle'            => 'İçe aktarımdan gelen, katalogda henüz olmayan animeler',
    'admin_catalog_requests.localhost_only'      => 'Bu sayfa sadece localhost üzerinden erişilebilir.',
    'admin_catalog_requests.empty'               => 'Bekleyen katalog önerisi yok.',
    'admin_catalog_requests.col.title'           => 'Anime',
    'admin_catalog_requests.col.external_ids'    => 'Dış kaynak ID',
    'admin_catalog_requests.col.broadcast_status'=> 'Durum / Yayın',
    'admin_catalog_requests.col.suggested_by'    => 'Öneren',
    'admin_catalog_requests.col.created'         => 'Tarih',
    'admin_catalog_requests.btn.approve'         => 'Onayla',
    'admin_catalog_requests.btn.reject'          => 'Reddet',
    'admin_catalog_requests.btn.select_all'      => 'Tümünü seç',
    'admin_catalog_requests.btn.clear_selection' => 'Seçimi temizle',
    'admin_catalog_requests.confirm.reject'      => 'Seçili öneriler reddedilsin mi?',
    'admin_catalog_requests.error.csrf'          => 'Geçersiz güvenlik anahtarı (CSRF). Sayfayı yenileyip tekrar deneyin.',
    'admin_catalog_requests.error.no_selection'  => 'Hiçbir öneri seçilmedi.',
    'admin_catalog_requests.error.unknown_action'=> 'Bilinmeyen işlem.',
    'admin_catalog_requests.success.approved'    => '%d öneri onaylandı ve kataloğa eklendi.',
    'admin_catalog_requests.success.rejected'    => '%d öneri reddedildi.',
    'admin_catalog_requests.back_to_dashboard'   => 'Yönetici paneline dön',

    // --- admin.php karti: Katalog Onerileri ---
    'admin.tool.catalog_requests.h3'             => 'Katalog Önerileri',
    'admin.tool.catalog_requests.desc'           => 'Üyelerin içe aktarımından gelen, katalogda olmayan animeler. Onaylarsanız kataloğa eklenir.',
    'admin.tool.catalog_requests.link.open'      => 'Önerileri incele',
    'admin.tool.catalog_requests.link.count'     => '%d öneriyi incele',
    'admin.tool.catalog_requests.missing_file'   => 'Eksik dosya:',
];
