<?php

/**
 * Anime Tracker - Turkish UI Translations
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * ---
 *
 * Turkish translation dictionary. Returned as an associative array
 * and consumed by the t() helper in functions.php.
 *
 * Key naming convention (dot notation, namespace-style):
 *   nav.*             Header navigation links shared across pages.
 *   common.*          Generic buttons / labels reused on many pages.
 *   index.*           Strings used only by index.php (the list page).
 *   anime_details.*   Strings used only by anime_details.php.
 *   edit_anime.*      Strings used only by edit_anime.php.
 *   lang.*            Strings used by the language switcher itself.
 *
 * Adding a new translation:
 *   1. Add the key + value here.
 *   2. Add the same key with the English value in lang/en.php.
 *   3. Replace the hard-coded UI string with t('your.key') in the
 *      template file.
 *
 * Missing keys are handled by the t() helper: it falls back to
 * Turkish, and if even Turkish is missing it returns the key itself
 * so the developer can spot the gap without a blank screen.
 */

return [

    // -----------------------------------------------------------------
    // Header navigation (index.php has all five; edit_anime.php uses
    // the about link; anime_details.php gains a header-section in this
    // release so the switcher can sit consistently in the same spot).
    // -----------------------------------------------------------------

    'nav.what_to_watch'   => 'Ne İzlesem?',
    'nav.recent_edits'    => 'Son Düzenlenenler',
    'nav.list_settings'   => 'Liste Ayarları',
    'nav.statistics'      => 'İstatistikler',
    'nav.help'            => 'Yardım',
    'nav.about'           => 'Hakkında',

    // -----------------------------------------------------------------
    // Language switcher itself.
    // The switcher shows two short labels in the header. The aria_label
    // is read by screen readers and never displayed; it still gets
    // translated because assistive tech respects the active language.
    // -----------------------------------------------------------------

    'lang.tr_label'       => 'TR',
    'lang.en_label'       => 'EN',
    'lang.aria_label'     => 'Dili degistir',

    // -----------------------------------------------------------------
    // index.php - the main anime list page
    // -----------------------------------------------------------------

    // Page title and the header above the table
    'index.page_title'              => 'Anime İzleme Listesi',
    'index.list_title'              => 'Anime İzleme Listesi',

    // Search box
    'index.search.placeholder'      => 'Anime ara...',
    'index.search.submit'           => 'Ara',
    'index.search.clear'            => 'Temizle',

    // Filter labels
    'index.filter.genre'            => 'Türe Göre Filtrele:',
    'index.filter.watch_status'     => 'İzleme Durumuna Göre Filtrele:',
    'index.filter.broadcast'        => 'Yayın Durumuna Göre Filtrele:',
    'index.filter.letter'           => 'Harfe Göre Filtrele',
    'index.filter.per_page'         => 'Sayfada Göster:',
    'index.filter.all'              => 'Tümü',
    'index.filter.show_all'         => 'Hepsi',
    'index.filter.submit'           => 'Filtrele',

    // Broadcast status values (free text in animes.status, kept as
    // Turkish constants in the DB for now)
    'index.broadcast.ongoing'       => 'Yayın Devam Ediyor',
    'index.broadcast.finished'      => 'Yayın Tamamlandı',

    // Add button above the list
    'index.add_anime'               => 'Yeni Anime Ekle',

    // Table column headers
    'index.col.anime'               => 'Anime',
    'index.col.status'              => 'Durum',
    'index.col.watched_episodes'    => 'İzlenen Bölüm',
    'index.col.image'               => 'Resim',
    'index.col.next_episode'        => 'Sonraki Bölüm',
    'index.col.action'              => 'Eylem',

    // Row contents
    'index.row.title_tooltip'       => 'Tam ismi gormek icin tiklayin',
    'index.row.ep_minus_tooltip'    => 'Bir bolum geri',
    'index.row.ep_plus_tooltip'     => 'Bir bolum ileri',
    'index.row.ep_aired_badge'      => '(yayında)',
    'index.row.more_button'         => 'Daha Fazla',
    'index.row.edit_button'         => 'Düzenle',
    'index.row.delete_button'       => 'Sil',
    'index.row.delete_confirm'      => 'Bu animeyi silmek istediğinize emin misiniz?',
    'index.row.no_results'          => 'Hiç anime bulunamadı.',

    // Pagination
    'index.pagination.info'         => '%d anime, sayfa %d/%d (%d-%d)',
    'index.pagination.prev'         => '&laquo; Önceki',
    'index.pagination.next'         => 'Sonraki &raquo;',

    // JS alerts
    'index.js.update_failed'        => 'Bolum guncellenemedi. Sayfayi yenileyip tekrar deneyin.',
    'index.js.network_error'        => 'Sunucuya ulasilamadi. Internet baglantinizi kontrol edin.',

    // -----------------------------------------------------------------
    // anime_details.php - per-anime detail page
    // -----------------------------------------------------------------

    // Page title - "{anime title} - Detaylar"
    'anime_details.title_suffix'         => 'Detaylar',

    // Detail rows (label : value pairs)
    'anime_details.label.status'         => 'Durum:',
    'anime_details.label.total_episodes' => 'Toplam Bölüm:',
    'anime_details.label.unknown'        => 'Bilinmiyor',
    'anime_details.label.aired_episodes' => 'Yayınlanan Bölüm:',
    'anime_details.label.release_date'   => 'Yayın Tarihi:',
    'anime_details.label.end_date'       => 'Yayın Bitiş Tarihi:',
    'anime_details.label.unset'          => 'Belirtilmemiş',
    'anime_details.label.broadcast_attribution' => 'Saat bilgisi %s\'den alınmıştır',
    'anime_details.label.watched_episodes' => 'İzlenen Bölüm:',
    'anime_details.label.synopsis'       => 'Konu:',
    'anime_details.label.user_synopsis'  => 'Kişisel Konu:',
    'anime_details.label.genres'         => 'Türler:',
    'anime_details.label.watch_status'   => 'İzleme Durumu:',
    'anime_details.label.emotion'        => 'Duygu:',
    'anime_details.label.broadcast_day'  => 'Yayın Günü:',
    'anime_details.label.broadcast_time' => 'Yayın Saati:',
    'anime_details.label.next_episode'   => 'Sonraki Bölüm:',
    'anime_details.label.notes'          => 'Notlar:',

    // Buttons / navigation
    'anime_details.btn.chronology'       => 'Kronoloji',
    'anime_details.btn.series_chronology' => 'Seri Kronolojisi',
    'anime_details.btn.edit'             => 'Düzenle',
    'anime_details.btn.back'             => 'Geri Dön',

    // Sections
    'anime_details.section.external_sites' => 'Anime Siteleri',
    'anime_details.section.next_up'      => 'Sıradaki',
    'anime_details.section.related'      => 'Bağlantılı Animeler',
    'anime_details.section.related_other_type' => 'Diğer',
    'anime_details.section.chronology'   => 'Kronoloji Notları',

    // Chronology alert
    'anime_details.alert.watch_after'    => '%d. bölümden sonra izlenmeli:',

    // Chronology marker list
    'anime_details.marker.after_episode' => '%d. bölümden sonra',
    'anime_details.marker.delete_tooltip' => 'Sil',
    'anime_details.marker.delete_confirm' => 'Bu kronoloji notunu silmek istediğinize emin misiniz?',

    // Chronology marker add form
    'anime_details.marker_form.title'    => 'Yeni Kronoloji Notu Ekle',
    'anime_details.marker_form.after_episode' => 'Bölümden sonra:',
    'anime_details.marker_form.after_episode_placeholder' => 'Örn: 23',
    'anime_details.marker_form.target_anime' => 'İzlenecek anime:',
    'anime_details.marker_form.choose'   => 'Seçiniz',
    'anime_details.marker_form.note'     => 'Not (opsiyonel):',
    'anime_details.marker_form.note_placeholder' => 'Örn: Kanonik kronoloji',
    'anime_details.marker_form.submit'   => 'Ekle',

    // JS alerts on the emotion toolbar
    'anime_details.js.operation_failed'  => 'İşlem başarısız oldu.',
    'anime_details.js.connection_error'  => 'Bağlantı hatası. Lütfen tekrar deneyin.',

    // Error path (anime ID not in DB)
    'anime_details.error.not_found'      => 'Anime bulunamadı.',

    // -----------------------------------------------------------------
    // edit_anime.php - the anime edit form
    // -----------------------------------------------------------------

    // Page title + headers
    'edit_anime.page_title'              => 'Anime Düzenle',
    'edit_anime.back_to_list'            => 'Anime İzleme Listesi',

    // Validation error messages (server-side)
    'edit_anime.validation.anidb_required' => 'AniDB linki zorunludur.',
    'edit_anime.validation.anidb_invalid'  => 'AniDB linki geçersiz. anidb.net adresi olmalı. Örnek: https://anidb.net/anime/12345 veya https://anidb.net/episode/212772',
    'edit_anime.validation.release_date_format' => 'Yayın tarihi geçersiz format. Doğru format: YYYY-MM-DD (örn: 2026-04-08)',
    'edit_anime.validation.end_date_format'     => 'Bitiş tarihi geçersiz format. Doğru format: YYYY-MM-DD (örn: 2026-09-15)',
    'edit_anime.validation.next_date_format'    => 'Sonraki bölüm tarihi geçersiz format.',

    // Error page chrome (form validation + image upload + duplicate)
    'edit_anime.error_page.form_title'   => 'Form Hatası',
    'edit_anime.error_page.upload_title' => 'Yükleme Hatası',
    'edit_anime.error_page.upload_h1'    => 'Resim Yükleme Hatası',
    'edit_anime.error_page.go_back_fix'  => 'Geri dön ve düzelt',
    'edit_anime.error_page.go_back_retry' => 'Geri dön ve tekrar dene',
    'edit_anime.error_page.duplicate_title' => 'Tekrarlanan Veri',
    'edit_anime.error_page.duplicate_h1' => 'Tekrarlanan Veri Hatası',
    'edit_anime.error_page.dup_used_in_another' => 'başka bir kayıtta kullanılıyor.',
    'edit_anime.error_page.dup_conflicting_record' => 'Tekrarlanan veri hatasını oluşturan kayıt:',
    'edit_anime.error_page.dup_go_to_record' => 'Çakışma olan kayda git',
    'edit_anime.error_page.dup_go_to_list' => 'Anime listesine git',
    'edit_anime.error_page.dup_field_catalog_uuid' => 'Katalog UUID',
    'edit_anime.error_page.dup_field_undefined'    => 'tanımsız UNIQUE alan',

    // Form labels (top section)
    'edit_anime.label.anime_name'        => 'Anime İsmi:',
    'edit_anime.label.alt_titles'        => 'Alternatif İsimler:',
    'edit_anime.btn.add_alt_title'       => 'Alternatif İsim Ekle',
    'edit_anime.placeholder.alt_title'   => 'Alternatif isim',
    'edit_anime.label.synopsis'          => 'Konu:',
    'edit_anime.placeholder.synopsis'    => 'Animenin konusunu yazın',
    'edit_anime.help.synopsis_readonly'  => 'sunucudan gelir, sync ile güncellenir',
    'edit_anime.label.user_synopsis'     => 'Kişisel Konu:',
    'edit_anime.placeholder.user_synopsis' => 'Kendi yorumunuz, çevirisi, özeti',
    'edit_anime.help.user_synopsis'      => 'kullanıcı konu bölümü - silinirse sync ile geri gelmez',

    // Episode counts
    'edit_anime.label.total_episodes'    => 'Toplam Bölüm Sayısı:',
    'edit_anime.placeholder.total_unknown' => 'Bilinmiyorsa boş bırakın',
    'edit_anime.label.aired_episodes'    => 'Yayınlanan Bölüm Sayısı:',
    'edit_anime.placeholder.aired'       => 'Şu ana kadar yayınlanan bölüm',
    'edit_anime.btn.sync_aired'          => 'Senkronize Et',

    // Dates
    'edit_anime.label.release_date'      => 'Yayın Tarihi:',
    'edit_anime.label.end_date'          => 'Yayın Bitiş Tarihi:',

    // Broadcast status / details
    'edit_anime.label.broadcast_status'  => 'Yayın Durumu:',
    'edit_anime.help.status_locked'      => 'Bu anime yayını tamamlandığı için durum değiştirilemez.',
    'edit_anime.option.choose'           => 'Seçiniz',
    'edit_anime.label.episode_interval'  => 'Bölümler Arası Süre (Gün):',
    'edit_anime.label.broadcast_day'     => 'Yayın Günü:',
    'edit_anime.label.broadcast_time'    => 'Yayın Saati:',
    'edit_anime.label.broadcast_timezone' => 'Yayın Saat Dilimi:',

    // Broadcast day labels (DB value stays Turkish - "Pazartesi" etc.;
    // only the visible label can be translated)
    'edit_anime.day.monday'              => 'Pazartesi',
    'edit_anime.day.tuesday'             => 'Salı',
    'edit_anime.day.wednesday'           => 'Çarşamba',
    'edit_anime.day.thursday'            => 'Perşembe',
    'edit_anime.day.friday'              => 'Cuma',
    'edit_anime.day.saturday'            => 'Cumartesi',
    'edit_anime.day.sunday'              => 'Pazar',

    // Timezone option labels (DB values 'Asia/Tokyo' etc. stay as-is)
    'edit_anime.tz.jp'                   => 'Japonya (Tokyo) - JST',
    'edit_anime.tz.tr'                   => 'Türkiye (Istanbul) - TRT',
    'edit_anime.tz.utc'                  => 'UTC',
    'edit_anime.tz.us_east'              => 'ABD Doğu (New York) - ET',
    'edit_anime.tz.us_west'              => 'ABD Batı (Los Angeles) - PT',
    'edit_anime.tz.uk'                   => 'Birleşik Krallık (London)',

    // Watch status section
    'edit_anime.label.watch_status'      => 'İzleme Durumu:',
    'edit_anime.label.watched_episodes'  => 'İzlenen Bölüm Sayısı:',

    // Genres section
    'edit_anime.label.genres'            => 'Türler:',
    'edit_anime.option.pick_existing_genre' => 'Mevcut Türlerden Seç',
    'edit_anime.placeholder.new_genre'   => 'Yeni tür ekle',
    'edit_anime.btn.add'                 => 'Ekle',

    // Tags ("Cumleler") section
    'edit_anime.label.tags'              => 'Cümleler:',
    'edit_anime.placeholder.tag'         => 'Cümle ekle (örn: Okulda geçsin, Spor teması olsun)...',
    'edit_anime.help.tags'               => 'Yazınca eşleşenler görünür. Eşleşme yoksa Enter ile yeni cümle oluşturulur.',
    'edit_anime.link.manage_tags'        => 'Cümleleri yönet',

    // Notes
    'edit_anime.label.notes'             => 'Notlar:',
    'edit_anime.help.notes'              => 'notlar bölümü silinirse sync ile geri gelmez',

    // Series fields
    'edit_anime.label.series_name'       => 'Seri Adı (opsiyonel):',
    'edit_anime.placeholder.series_name' => 'Örn: Detective Conan, Spy x Family',
    'edit_anime.help.series_name'        => 'Aynı seriye ait animeler bu adı paylaşır.',
    'edit_anime.label.media_type'        => 'Medya Türü (opsiyonel):',
    'edit_anime.label.next_in_series'    => 'Sıradaki Anime (opsiyonel):',
    'edit_anime.help.next_in_series'     => 'Bu animeyi bitirdikten sonra izlenecek anime. ★ = aynı seri.',

    // External links
    'edit_anime.label.anidb_link'        => 'AniDB Linki:',
    'edit_anime.placeholder.anidb_link'  => 'https://anidb.net/anime/12345 veya /episode/12345',
    'edit_anime.label.mal_link'          => 'MyAnimeList Linki:',
    'edit_anime.placeholder.mal_link'    => 'https://myanimelist.net/anime/12345',
    'edit_anime.label.schedule_link'     => 'AnimeSchedule Linki:',
    'edit_anime.placeholder.schedule_link' => 'https://animeschedule.net/anime/...',
    'edit_anime.btn.auto_fill'           => 'Otomatik Doldur',

    // Image upload
    'edit_anime.label.upload_image'      => 'Resim Yükle:',
    'edit_anime.btn.choose_file'         => 'Dosya Seç',
    'edit_anime.placeholder.no_file'     => 'Dosya seçilmedi',

    // Submit row
    'edit_anime.btn.submit'              => 'Güncelle',
    'edit_anime.btn.cancel'              => 'Vazgeç',

    // JS alerts
    'edit_anime.js.genre_add_failed'     => 'Tür eklenirken bir hata oluştu',
    'edit_anime.js.url_required'         => 'Önce AnimeSchedule URL ini girin.',
    'edit_anime.js.fetching_schedule'    => 'AnimeSchedule den veri çekiliyor...',
    'edit_anime.js.fetching_aired'       => 'AnimeSchedule den bölüm sayısı çekiliyor...',
    'edit_anime.js.unknown_error'        => 'Bilinmeyen hata.',
    'edit_anime.js.field_not_found'      => '%s (alan bulunamadı)',
    'edit_anime.js.no_empty_fields'      => 'Doldurulacak boş alan bulunamadı (tüm alanlar dolu).',
    'edit_anime.js.fields_filled'        => '%d alan dolduruldu: %s.',
    'edit_anime.js.request_failed'       => 'İstek başarısız: %s',
    'edit_anime.js.this_week'            => ' (bu hafta)',
    'edit_anime.js.last_week'            => ' (geçen hafta)',
    'edit_anime.js.weeks_ago'            => ' (%d hafta önce)',
    'edit_anime.js.updated_value'        => 'Güncellendi: %s -> %s%s',
    'edit_anime.js.already_up_to_date'   => 'Mevcut değer zaten güncel: %s%s',

];
