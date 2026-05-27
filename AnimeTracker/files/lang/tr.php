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
    // add_anime.php - new anime entry form
    // -----------------------------------------------------------------

    // Page meta
    'add_anime.page_title'                   => 'Listeye Anime Ekleme',
    'add_anime.heading'                      => 'Listeye Anime Ekleme',

    // Form field labels
    'add_anime.label.title'                  => 'Anime İsmi:',
    'add_anime.label.alternative_titles'     => 'Alternatif İsimler:',
    'add_anime.label.synopsis'               => 'Konu:',
    'add_anime.label.total_episodes'         => 'Toplam Bölüm Sayısı:',
    'add_anime.label.aired_episodes'         => 'Yayınlanan Bölüm Sayısı:',
    'add_anime.label.release_date'           => 'Yayın Tarihi:',
    'add_anime.label.end_date'               => 'Yayın Bitiş Tarihi:',
    'add_anime.label.status'                 => 'Yayın Durumu:',
    'add_anime.label.episode_interval'       => 'Bölümler Arası Süre (Gün):',
    'add_anime.label.broadcast_day'          => 'Yayın Günü:',
    'add_anime.label.broadcast_time'         => 'Yayın Saati:',
    'add_anime.label.broadcast_timezone'     => 'Yayın Saat Dilimi:',
    'add_anime.label.watch_status'           => 'İzleme Durumu:',
    'add_anime.label.watched_episodes'       => 'İzlenen Bölüm Sayısı:',
    'add_anime.label.genres'                 => 'Türler:',
    'add_anime.label.tags'                   => 'Cumleler:',
    'add_anime.label.notes'                  => 'Notlar:',
    'add_anime.label.series_name'            => 'Seri Adı (opsiyonel):',
    'add_anime.label.media_type'             => 'Medya Türü (opsiyonel):',
    'add_anime.label.anidb_link'             => 'AniDB Linki:',
    'add_anime.label.mal_link'               => 'MyAnimeList Linki:',
    'add_anime.label.animeschedule_link'     => 'AnimeSchedule Linki:',
    'add_anime.label.image'                  => 'Resim Yükle:',

    // Input placeholders
    'add_anime.ph.alternative_title'         => 'Alternatif isim',
    'add_anime.ph.synopsis'                  => 'Animenin konusunu yazın',
    'add_anime.ph.total_episodes'            => 'Bilinmiyorsa boş bırakın',
    'add_anime.ph.aired_episodes'            => 'Şu ana kadar yayınlanan bölüm',
    'add_anime.ph.new_genre'                 => 'Yeni tür ekle',
    'add_anime.ph.tag_input'                 => 'Cumle ekle (orn: Okulda gecsin, Spor temasi olsun)...',
    'add_anime.ph.series_name'               => 'Orn: Detective Conan, Spy x Family',
    'add_anime.ph.anidb_link'                => 'https://anidb.net/anime/12345 veya /episode/12345',
    'add_anime.ph.mal_link'                  => 'https://myanimelist.net/anime/12345',
    'add_anime.ph.animeschedule_link'        => 'https://animeschedule.net/anime/...',

    // Buttons
    'add_anime.btn.add_alternative_title'    => 'Alternatif İsim Ekle',
    'add_anime.btn.add_genre'                => 'Ekle',
    'add_anime.btn.animeschedule_fetch'      => 'Otomatik Doldur',
    'add_anime.btn.choose_file'              => 'Dosya Seç',
    'add_anime.btn.submit'                   => 'Ekle',
    'add_anime.btn.cancel'                   => 'Vazgeç',

    // Generic select options
    'add_anime.option.choose'                => 'Seçiniz',
    'add_anime.option.choose_from_existing'  => 'Mevcut Türlerden Seç',

    // Broadcast day labels. The DB stores Turkish day names as
    // values (broadcast_day column), so the values stay TR and only
    // the displayed label is translated.
    'add_anime.day.monday'                   => 'Pazartesi',
    'add_anime.day.tuesday'                  => 'Salı',
    'add_anime.day.wednesday'                => 'Çarşamba',
    'add_anime.day.thursday'                 => 'Perşembe',
    'add_anime.day.friday'                   => 'Cuma',
    'add_anime.day.saturday'                 => 'Cumartesi',
    'add_anime.day.sunday'                   => 'Pazar',

    // Broadcast timezone labels (value attribute stays as IANA tz id)
    'add_anime.tz.tokyo'                     => 'Japonya (Tokyo) - JST',
    'add_anime.tz.istanbul'                  => 'Türkiye (Istanbul) - TRT',
    'add_anime.tz.utc'                       => 'UTC',
    'add_anime.tz.new_york'                  => 'ABD Dogu (New York) - ET',
    'add_anime.tz.los_angeles'               => 'ABD Bati (Los Angeles) - PT',
    'add_anime.tz.london'                    => 'Birlesik Krallik (London)',

    // File upload UI
    'add_anime.file.no_file'                 => 'Dosya seçilmedi',

    // Form hints (small.form-text)
    'add_anime.hint.notes'                   => 'notlar bolumu silinirse sync ile geri gelmez',
    'add_anime.hint.series_name'             => 'Aynı seriye ait animeler bu adı paylaşır. Mevcut seriler otomatik önerilir.',
    'add_anime.hint.tags'                    => 'Yazinca eslesenler gozukur. Eslesme yoksa Enter ile yeni cumle olusturulur.',
    'add_anime.link.manage_tags'             => 'Cumleleri yonet',

    // CSRF rejection
    'add_anime.csrf.invalid'                 => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.',

    // Server-side validation errors (shown on the form error page)
    'add_anime.error.mal_link_required'      => 'MyAnimeList linki zorunludur.',
    'add_anime.error.mal_link_invalid'       => 'MyAnimeList linki gecersiz format. Ornek: https://myanimelist.net/anime/12345',
    'add_anime.error.anidb_link_required'    => 'AniDB linki zorunludur.',
    'add_anime.error.anidb_link_invalid'     => 'AniDB linki gecersiz. anidb.net adresi olmali. Ornek: https://anidb.net/anime/12345 veya https://anidb.net/episode/212772',
    'add_anime.error.end_date_invalid'       => 'Bitis tarihi gecersiz format. Dogru format: YYYY-MM-DD (orn: 2026-09-15)',
    'add_anime.error.next_episode_date_invalid' => 'Sonraki bolum tarihi gecersiz format.',

    // Error pages (validation / image upload / duplicate)
    'add_anime.error_page.form_error_title'  => 'Form Hatasi',
    'add_anime.error_page.image_error_title' => 'Resim Yukleme Hatasi',
    'add_anime.error_page.duplicate_title'   => 'Tekrarlanan Veri Hatasi',
    'add_anime.error_page.duplicate_heading' => 'Bu anime zaten listenizde mevcut',
    'add_anime.error_page.go_back_and_fix'   => 'Geri don ve duzelt',
    'add_anime.error_page.go_back_and_retry' => 'Geri don ve tekrar dene',
    'add_anime.error_page.go_to_existing'    => 'Mevcut kayda git',
    'add_anime.error_page.go_to_list'        => 'Anime listesine git',

    // Duplicate detection - field labels and detail message fragments
    'add_anime.duplicate.field_mal_id'       => 'MAL ID',
    'add_anime.duplicate.field_anidb_id'     => 'AniDB ID',
    'add_anime.duplicate.field_catalog_uuid' => 'Katalog UUID',
    'add_anime.duplicate.field_unknown'      => 'tanimsiz UNIQUE alan',
    'add_anime.duplicate.already_exists_suffix' => 'zaten kayitli.',
    'add_anime.duplicate.existing_record_prefix' => 'Mevcut kayit:',

    // JS-side messages (injected via LANG constant in <script>)
    'add_anime.js.genre_add_failed'          => 'Tür eklenirken bir hata oluştu',
    'add_anime.js.create_new_tag_prefix'     => '+ Yeni cumle olustur:',
    'add_anime.js.enter_animeschedule_url'   => 'Once AnimeSchedule URL ini girin.',
    'add_anime.js.fetching'                  => 'AnimeSchedule den veri cekiliyor...',
    'add_anime.js.unknown_error'             => 'Bilinmeyen hata.',
    'add_anime.js.field_not_found_suffix'    => '(alan bulunamadi)',
    'add_anime.js.no_empty_fields'           => 'Doldurulacak bos alan bulunamadi (tum alanlar dolu).',
    'add_anime.js.fields_filled_prefix'      => 'Alan dolduruldu:',
    'add_anime.js.request_failed_prefix'     => 'Istek basarisiz:',

    // -----------------------------------------------------------------
    // help.php - kullanici yardim / nasil calisir sayfasi
    // -----------------------------------------------------------------

    // Page meta
    'help.page_title'                        => 'Yardım - Anime Tracker',
    'help.heading'                           => 'Yardım',
    'help.back_to_home'                      => '&larr; Ana Sayfaya Dön',
    'help.intro'                             => 'Anime Tracker\'in nasil calistigi, hangi alanlarin neye yaradigi ve neye dikkat etmeniz gerektigi burada. Bir ozelligi merak ediyorsaniz ilgili bolumu okuyun.',

    // Table of contents
    'help.toc.heading'                       => 'Icindekiler:',
    'help.toc.fields'                        => 'Anime Alanlari — Hangisi Ne Yapar?',
    'help.toc.statuses'                      => 'Izleme Durumlari — Dort Secenek',
    'help.toc.quick_buttons'                 => 'Hizli Izleme Butonlari (+/-)',
    'help.toc.sync'                          => 'Katalog Sync — Nasil Calisir?',
    'help.toc.personal'                      => 'Kisisel Alanlar — Notlar ve Kisisel Konu',
    'help.toc.recommendations'               => 'Ne İzlesem? — Öneri Sistemi',
    'help.toc.chronology'                    => 'Seriler ve Kronoloji',
    'help.toc.deletion'                      => 'Silme Uyarilari',
    'help.toc.updates'                       => 'Guncelleme Sistemi',
    'help.toc.timezone'                      => 'Saat Dilimi (TZ)',

    // Section: Anime fields (catalog vs personal)
    'help.fields.h2'                         => 'Anime Alanlari — Hangisi Ne Yapar?',
    'help.fields.intro'                      => 'Anime ekleme ve duzenleme ekranindaki alanlar iki gruba ayrilir: <strong>katalog alanlari</strong> (sunucudan gelir, sync ile guncellenir) ve <strong>kisisel alanlar</strong> (size ozel, hicbir zaman sunucuya gitmez).',
    'help.fields.catalog.h3'                 => '<i class="fas fa-cloud icon-inline"></i> Katalog Alanlari (sync edilir)',
    'help.fields.catalog.list' => '<li><strong>Anime Ismi, Alternatif Isimler</strong></li>
        <li><strong>Konu</strong> — Animenin resmi ozeti</li>
        <li><strong>Turler</strong> — Aksiyon, Komedi, vb.</li>
        <li><strong>Cumleler (Etiketler)</strong> — "Ne İzlesem?" sistemi icin</li>
        <li><strong>Yayin durumu, bolum sayisi, yayin gun/saati</strong></li>
        <li><strong>MAL / AniDB / AnimeSchedule linkleri</strong></li>
        <li><strong>Seri bilgileri</strong> (seri adi, medya turu, sonraki seri)</li>',
    'help.fields.catalog.note'               => 'Bu alanlari elle degistirirseniz, bir sonraki sync\'te <strong>uzerine yazilir</strong> (sunucunun dedigi gecer).',
    'help.fields.personal.h3'                => '<i class="fas fa-user icon-inline"></i> Kisisel Alanlar (sync edilmez)',
    'help.fields.personal.list' => '<li><strong>Izlenen Bolum sayisi</strong></li>
        <li><strong>Izleme Durumu</strong> (Izlendi / Izleniyor / Izlenme Planlandi / Izleme Ertelendi) — listedeki <a href="#hizli-butonlar"><code>+/-</code> butonlariyla otomatik degisebilir</a></li>
        <li><strong>Notlar</strong> — Size ozel hatirlatmalar, yorumlar</li>
        <li><strong>Kisisel Konu</strong> — Kendi yorumunuz / aciklamaniz</li>
        <li><strong>Poster (kendi yuklediyseniz)</strong></li>
        <li><strong>Sonraki bolum tarihi</strong> (lokal hesap)</li>',
    'help.fields.personal.note'              => 'Bu alanlara sunucu <strong>dokunmaz</strong>. Istediginiz kadar yazabilir, degistirebilirsiniz.',

    // Section: Watch statuses
    'help.statuses.h2'                       => 'Izleme Durumlari',
    'help.statuses.intro'                    => 'Her animenin bir <strong>Izleme Durumu</strong> vardir. Dort secenek farkli izleme asamalarini karsilar:',
    'help.statuses.list' => '<li><strong>Izlenme Planlandi</strong> — Henuz baslamadiniz, ileride izlemek istiyorsunuz. Izlenen bolum: 0.</strong></li>
        <li><strong>Izleniyor</strong> — Aktif olarak izliyorsunuz. Izlenen bolum tavan ile sifir arasinda bir yerde.</li>
        <li><strong>Izlendi</strong> — Bittirdiginiz animeler. Izlenen bolum = toplam bolum.</li>
        <li><strong>Izleme Ertelendi</strong> — Izlemeye basladiniz ama ara verdiniz, ilerlemeniz korunsun. <em>Planlandi\'dan farki:</em> Planlandi "henuz baslamadim" demektir (izlenen=0), Ertelendi "biraz izledim, suanda ara veriyorum" demektir (izlenen>0).</li>',
    'help.statuses.when_postponed'           => '<strong>Ne zaman Ertelendi kullanmali?</strong> Bir animeyi 6 ay sonra geri donmek uzere yarida birakirsaniz, durumu Ertelendi yapin. Boylece "Izleniyor" listenizdeki aktif izleme akisi kalabaliklasmaz, ama Planlandi\'ya da dusmez (cunku ilerlemeniz var). Hazir oldugunuzda <code>+</code> basarsiniz, sistem otomatik olarak Izleniyor\'a geri ceker.',

    // Section: Quick watch buttons (+/-)
    'help.buttons.h2'                        => 'Hizli Izleme Butonlari (+/-)',
    'help.buttons.intro'                     => 'Listede her animenin yaninda <code>+</code> ve <code>-</code> butonlari var. Bu butonlarla "Duzenle" ekranina gitmeden Izlenen Bolum sayisini bir artirip azaltabilirsiniz. Sayim degisirken belirli kosullarda <strong>Izleme Durumu da otomatik olarak guncellenir</strong>.',
    'help.buttons.transitions.h3'            => 'Otomatik Durum Gecisleri',
    'help.buttons.transitions.intro'         => 'Asagidaki tablo bes temel durumu ozetler:',
    'help.buttons.transitions.col_current'   => 'Su anki durum',
    'help.buttons.transitions.col_action'    => 'Aksiyon',
    'help.buttons.transitions.col_new'       => 'Yeni durum',
    'help.buttons.transitions.row1_curr'     => 'Izlenme Planlandi + 0/12',
    'help.buttons.transitions.row1_new'      => 'Izleniyor + 1/12',
    'help.buttons.transitions.row2_curr'     => 'Izleniyor + 11/12',
    'help.buttons.transitions.row2_new'      => 'Izlendi + 12/12',
    'help.buttons.transitions.row3_curr'     => 'Izlendi + 12/12',
    'help.buttons.transitions.row3_new'      => 'Izleniyor + 11/12',
    'help.buttons.transitions.row4_curr'     => 'Izleniyor + 1/12',
    'help.buttons.transitions.row4_new'      => 'Izlenme Planlandi + 0/12',
    'help.buttons.transitions.row5_curr'     => 'Izleme Ertelendi + 5/12',
    'help.buttons.transitions.row5_new'      => 'Izleniyor + 6/12',
    'help.buttons.transitions.note'          => 'Mantik basit: durum sinir gecislerinde (basa donus, sona ulasma) otomatik degisir, ara degerlerde dokunulmaz.',
    'help.buttons.two_step.h3'               => 'Tek Tikla Iki Adim',
    'help.buttons.two_step.intro'            => 'Bazen tek bir <code>+</code> veya <code>-</code> basisi iki gecisi birden tetikleyebilir:',
    'help.buttons.two_step.list' => '<li><strong>Planlandi + 11/12 → <code>+</code> → Izlendi + 12/12.</strong> Once Planlandi\'dan Izleniyor\'a, sonra tavana ulastigi icin Izleniyor\'dan Izlendi\'ye tek tikla gecer.</li>
        <li><strong>Izlendi + 1/12 → <code>-</code> → Izlenme Planlandi + 0/12.</strong> Yukaridakinin aynadaki yansimasi: once Izleniyor\'a, sonra 0\'a indigi icin Planlandi\'ya tek tikla doner.</li>
        <li><strong>Izleme Ertelendi + 11/12 → <code>+</code> → Izlendi + 12/12.</strong> Ertelendigin animede son bolume varinca, ayni mantik calisir: once Izleniyor\'a, sonra tavana ulastigi icin Izlendi\'ye tek tikla gecer.</li>',
    'help.buttons.untouched.h3'              => 'Ne Zaman Tetiklenmez?',
    'help.buttons.untouched.box_title'       => '<i class="fas fa-info-circle"></i> Otomasyon dokunmaz:',
    'help.buttons.untouched.list' => '<li><strong>Izleniyor + ara deger</strong> (ornek: 7/12) — <code>+</code> veya <code>-</code> basildiginda durum Izleniyor olarak kalir, sadece sayim degisir.</li>
            <li><strong>Izlenme Planlandi + <code>-</code></strong> — Planlandi durumunda <code>-</code> basmak ne sayimi ne durumu degistirir (zaten 0).</li>
            <li><strong>Izlendi + tavan altinda + <code>+</code></strong> — manuel olarak anormal duruma getirilmis bir kayda <code>+</code> basinca durum Izlendi olarak kalir; otomasyon zorla duzeltmez, manuel niyetiniz korunur.</li>
            <li><strong>Izleme Ertelendi + <code>-</code></strong> — Ertelenmis bir animede <code>-</code> basildiginda durum Izleme Ertelendi olarak kalir, sadece sayim 1 azalir. "Ara verdim ama bir bolumu unutmustum" gibi nadir durumlar icindir. Devam etmek istediginizde <code>+</code> basin (yukaridaki 5. kural devreye girer) veya Duzenle\'den durumu manuel degistirin.</li>',
    'help.buttons.unknown_count.h3'          => 'Bolum Sayisi Bilinmeyen Animeler',
    'help.buttons.unknown_count.intro'       => 'Toplam veya yayinlanan bolum sayisi bilinmiyorsa (tavansiz eski OVA\'lar, programi belirsiz seriler gibi):',
    'help.buttons.unknown_count.list' => '<li><strong>Tavana ulasma kontrolu yapilamaz</strong> — bu yuzden <code>+</code> ile otomatik "Izlendi" gecisi calismaz. Manuel olarak Duzenle\'den isaretlemeniz gerekir.</li>
        <li><strong>0\'a inis kontrolu tavandan bagimsiz calisir</strong> — Izleniyor + 1/? uzerinde <code>-</code> basildiginda durum yine Izlenme Planlandi + 0/?\'e otomatik doner.</li>
        <li><strong>Manuel Izlendi yapilmis tavansiz animede <code>-</code></strong> basildiginda durum Izlendi olarak kalir — sistem guvenli bir gecis yapamadigi icin manuel duruma karismaz.</li>',
    'help.buttons.manual.h3'                 => 'Manuel Duzenleme Her Zaman Serbest',
    'help.buttons.manual.text'               => 'Otomatik durum gecisleri sadece <code>+</code> ve <code>-</code> butonlarina basarken devreye girer. "Duzenle" formundan istediginiz durumu manuel olarak <strong>her zaman</strong> secebilirsiniz; otomasyon ona karismaz.',

    // Section: Catalog sync
    'help.sync.h2'                           => 'Katalog Sync — Nasil Calisir?',
    'help.sync.intro'                        => 'Liste Ayarlari sayfasinda "Katalogdan Ice Aktar" dugmesine bastiginizda, sunucudaki katalog lokal veritabaninizla birlestirilir.',
    'help.sync.safe_title'                   => '<i class="fas fa-shield-alt"></i> Kaybolmaz:',
    'help.sync.safe_body'                    => 'Izleme verileriniz, notlariniz, Kisisel Konu, kendi yukleginiz poster — bunlar size ozeldir ve sync\'te asla dokunulmaz.',
    'help.sync.warning_title'                => '<i class="fas fa-exclamation-triangle"></i> Uzerine Yazilir:',
    'help.sync.warning_body'                 => 'Anime ismi, konu, turler, yayin bilgileri gibi katalog alanlari her sync\'te sunucunun son haline gore guncellenir. Elle degistirdiyseniz kaybolur.',
    'help.sync.own_added.h3'                 => 'Kendi Ekledigim Animeler Ne Olur?',
    'help.sync.own_added.text'               => 'Siz bir anime ekledikten sonra admin tarafindan kataloga alinmamissa (yani sizin ozel kayitlariniz), bu animeler sync\'te <strong>hic dokunulmaz</strong>. Tum alanlari korunur.',
    'help.sync.when.h3'                      => 'Sync Ne Zaman Calisir?',
    'help.sync.when.text'                    => 'Otomatik degil — sadece siz istedikce. Liste Ayarlari → "Katalogdan Ice Aktar" dugmesine basinca bir defa calisir.',

    // Section: Personal fields (Notes + Personal Synopsis)
    'help.personal.h2'                       => 'Kisisel Alanlar — Notlar ve Kisisel Konu',
    'help.personal.intro'                    => 'Iki farkli kisisel metin alaniniz var. Farklari:',
    'help.personal.table.col_field'          => 'Alan',
    'help.personal.table.col_purpose'        => 'Amac',
    'help.personal.table.col_example'        => 'Ornek',
    'help.personal.table.row_notes_field'    => 'Notlar',
    'help.personal.table.row_notes_purpose'  => 'Kisa hatirlatmalar',
    'help.personal.table.row_notes_example'  => '"Arkadasla beraber izle", "ilk 3 bolumden sonra hizli izle"',
    'help.personal.table.row_synopsis_field' => 'Kisisel Konu',
    'help.personal.table.row_synopsis_purpose' => 'Uzun yorumlar, kendi ozetiniz',
    'help.personal.table.row_synopsis_example' => 'Kendi cevirisiniz, kendi yorumunuz, kendi ozetiniz',
    'help.personal.howto.h3'                 => '<i class="fas fa-sync icon-inline"></i> Kisisel Konu Nasil Olusur?',
    'help.personal.howto.intro'              => '<strong>Ilk durumda tek "Konu" alani vardir.</strong> Kendiniz yazarsaniz veya sunucudan gelen konu orada durur. Eger katalogdan gelen yeni bir sey varsa ve siz o alana kendi yazinizi yazmissaniz, ilk sync sirasinda:',
    'help.personal.howto.list' => '<li>Sizin yazdiginiz metin otomatik olarak <strong>"Kisisel Konu"</strong> alanina tasinir</li>
        <li>Sunucudan gelen metin "Konu" alanina yazilir (duzenleyemezsiniz, salt okunur olur)</li>
        <li>Artik iki alan goreceksiniz, duzenlediginiz her sey "Kisisel Konu"ya gider</li>',
    'help.personal.warning_title'            => '<i class="fas fa-exclamation-triangle"></i> Dikkat:',
    'help.personal.warning_body'             => 'Kisisel Konu\'yu silerseniz <strong>sync ile geri gelmez</strong>. Ayni sekilde Notlar alanini silerseniz o da geri gelmez. Bu iki alan size ozel ve kalici olarak sizin kontrolunuzde.',

    // Section: Recommendation system
    'help.recom.h2'                          => 'Ne İzlesem? — Öneri Sistemi',
    'help.recom.intro'                       => 'Menudeki "Ne İzlesem?" linki, listenizden size uygun anime onermesi icin tasarlanmis bir aractir.',
    'help.recom.howto.h3'                    => 'Nasil Calisir?',
    'help.recom.howto.text'                  => 'Yonetici (admin) her animeye birkac <strong>cumle etiketi</strong> atar: "Okulda gecsin", "Spor olsun", "Buyu olsun" gibi. Siz bu cumlelerden istediginizi sec, "Oner" butonuna basin.',
    'help.recom.scoop.h3'                    => 'Kepce Mantigi',
    'help.recom.scoop.text'                  => 'Her secilen cumle bir kepce gibi dusunun. Kepce kendi eslesmesini listeden ceker. Birden fazla kepce secerseniz, en cok kepceye uyan anime ust sirada gozukur.',
    'help.recom.scoop.box_title'             => '<i class="fas fa-check"></i> Onemli:',
    'help.recom.scoop.box_body'              => 'Cok cumle secerseniz sonuclar azalmaz, aksine siralama netlesir. Sistem AND yerine OR + puan mantigi kullanir.',
    'help.recom.surprise.h3'                 => 'Surpriz Sec',
    'help.recom.surprise.text'               => 'Hic cumle secmeden "Surpriz Sec" derseniz, sistem size izlememis oldugunuz bir anime rastgele secer. Kararsiz kaldiginizda hizli bir cozum.',
    'help.recom.search.h3'                   => 'Arama Kutusu',
    'help.recom.search.text'                 => 'Cumle listesi uzadiginda arama kutusuna yazabilirsiniz. Yazdiginiz harflerle <strong>baslayan</strong> cumleler liste halinde gorulur. Turkce karakterler ayirt edilir — "u" yazarsaniz "U" ile baslayanlar, "ü" yazarsaniz "Ü" ile baslayanlar gelir.',

    // Section: Series and Chronology
    'help.chrono.h2'                         => 'Seriler ve Kronoloji',
    'help.chrono.intro'                      => 'Birbirine bagli animeler icin iki tur iliski sistemi var:',
    'help.chrono.series.h3'                  => 'Seri Bilgisi',
    'help.chrono.series.text'                => 'Bir anime\'nin hangi seriye ait oldugu <strong>seri adi</strong> ve <strong>medya turu</strong> (TV / Film / OVA / Special / ONA) ile belirlenir. Ayni seri adini paylasan animeler anime detayinda "Bagli Animeler" bolumunde gozukur.',
    'help.chrono.next.h3'                    => 'Sonraki Seri (next_in_series)',
    'help.chrono.next.text'                  => 'Bir animeyi bitirince hangi animeyi izlemeniz gerektigi. Detay sayfasinda "Sirada" kutusunda gozukur.',
    'help.chrono.markers.h3'                 => 'Kronoloji Isaretleri',
    'help.chrono.markers.text'               => 'Detective Conan gibi seriler icin: "54. bolumden sonra 1. filmi izle" gibi bolum seviyesinde isaretler tutulur. Detay sayfasinda aktif uyari olarak gorulur, ayri bir "Kronoloji" sayfasinda da timeline halinde listelenir.',
    'help.chrono.warning_title'              => '<i class="fas fa-exclamation-triangle"></i> Dikkat:',
    'help.chrono.warning_body'               => 'Kronoloji isaretleri de sync\'te katalog otoritedir. Kendiniz marker eklediyseniz sync sonrasi kaybolur.',

    // Section: Deletion warnings
    'help.delete.h2'                         => 'Silme Uyarilari',
    'help.delete.danger_title'               => '<i class="fas fa-trash-alt"></i> Geri Alinamaz Silmeler:',
    'help.delete.danger_list' => '<li><strong>Notlar</strong> alanini bossaltmak → sync geri getirmez</li>
            <li><strong>Kisisel Konu</strong> alanini bossaltmak → sync geri getirmez</li>
            <li>Anime silmek → kalici, izleme verisi dahil her sey gider</li>
            <li>Poster dosyasi silmek → sync sirasinda katalog posteri tekrar indirilir (ancak kendi yuklediginiz poster geri gelmez)</li>',
    'help.delete.safe_title'                 => '<i class="fas fa-undo"></i> Geri Alinabilir (sync ile):',
    'help.delete.safe_list' => '<li>Konu alanini degistirmek / bossaltmak → bir sonraki sync\'te katalog konusu geri gelir</li>
            <li>Anime ismi degistirmek → sync\'te duzelir</li>
            <li>Tur listesi / yayin bilgisi degistirmek → sync\'te duzelir</li>',

    // Section: Update system
    'help.update.h2'                         => 'Guncelleme Sistemi',
    'help.update.intro'                      => 'Anime Tracker\'in kendisi zaman zaman yeni surumlerle gelir. Liste Ayarlari → "Guncelleme Kontrolu" dugmesi ile yeni sürüm olup olmadigini kontrol edebilirsiniz.',
    'help.update.flow_intro'                 => 'Yeni surum varsa, tek tikla otomatik guncelleme yapilir:',
    'help.update.flow_list' => '<li>Sunucudan yeni surum indirilir</li>
        <li>Dosyalar yerinde guncellenir (<code>config.php</code>, <code>uploads/</code> ve izleme verileriniz korunur)</li>
        <li>Veritabani gerekirse otomatik guncellenir</li>
        <li>Sayfa yenilenir, yeni surum aktif</li>',
    'help.update.safe_title'                 => '<i class="fas fa-shield-alt"></i> Guncelleme Sirasinda Kaybolmaz:',
    'help.update.safe_body'                  => 'Animeleriniz, izleme verileriniz, notlariniz, poster\'leriniz, DB kimlik bilgileriniz — hic biri etkilenmez.',

    // Section: Timezone
    'help.tz.h2'                             => 'Saat Dilimi — Yayin Saati Nasil Gosterilir?',
    'help.tz.intro'                          => 'Anime Tracker tum tarih ve saatleri veritabaninda <strong>UTC</strong> olarak saklar. Gosterirken her animenin kendi yayin saat dilimine cevirir.',
    'help.tz.bc_tz.h3'                       => 'Yayin Saat Dilimi (animenin TZ\'i)',
    'help.tz.bc_tz.text'                     => 'Anime ekleme/duzenleme formundaki "Yayin Saat Dilimi" alani. Listede 6 sabit secenek var: Asia/Tokyo (JST), Europe/Istanbul (TRT), UTC, America/New_York (ET), America/Los_Angeles (PT), Europe/London. Cogu Japon animesi icin <code>Asia/Tokyo</code> dogru secimdir.',
    'help.tz.autofill_title'                 => '<i class="fas fa-magic"></i> Hizli Yol — Otomatik Doldur:',
    'help.tz.autofill_body'                  => 'AnimeSchedule URL\'sini girip "Otomatik Doldur" (Senkronize Et) butonuna basarsaniz <strong>broadcast_day, broadcast_time ve broadcast_timezone alanlari otomatik olarak Asia/Tokyo + Tokyo saati ile dolar</strong>. AnimeSchedule API\'si Japon animesi verilerini dogru sekilde Tokyo TZ\'de donderir. Elle giris yapmaniza gerek kalmaz.',
    'help.tz.workflows.h3'                   => 'Iki Gecerli Workflow',
    'help.tz.workflows.intro'                => 'TZ alani ve saat alani ayni saat dilimini yansitmali. Iki yol da gecerli:',
    'help.tz.workflows.list' => '<li><strong>Animenin yayin yeri:</strong> "JST" sec + Tokyo saatini gir (orn. 23:30). Anime detay sayfasi "23:30 (JST)" gosterir, kendi saatinizi manuel hesaplarsiniz. AnimeSchedule Otomatik Doldur bu yontemi kullanir.</li>
        <li><strong>Kendi yerel saatiniz:</strong> "TRT" sec + Turkiye saatini 24 saat formatinda gir (orn. 17:30). Anime detay sayfasi "17:30 (TRT)" gosterir, dogrudan okunabilir. AnimeSchedule sitesinden manuel okuyorsaniz site zaten Turkiye saatini gosteriyor; sadece am/pm\'i 24 saate cevirip yazin.</li>',
    'help.tz.consistency'                    => 'Onemli: TZ secimi ile saat alani <strong>tutarli</strong> olmali. "JST" secip Turkiye saatini girerseniz veya "TRT" secip Tokyo saatini girerseniz, "sonraki bolum ne zaman" hesabi yanlis olur.',
    'help.tz.box_animeschedule_title'        => '<i class="fas fa-info-circle"></i> AnimeSchedule sitesi ne gosterir:',
    'help.tz.box_animeschedule_body'         => 'AnimeSchedule sitesini tarayicidan acarsaniz saatleri <strong>am/pm (12 saat) formatinda</strong>, <strong>sizin lokal TZ\'inizde</strong> gosterir (Turkiye\'den ziyaret edenlere Turkiye saati, baska ulkeden ziyaret edenlere o ulkenin saati). Manuel doldurma yapiyorsaniz: formda lokal TZ\'inizi secin (Turkiye icin TRT), am/pm\'i 24 saat formatina cevirin (orn. "5:30 PM" -> 17:30), "Yayin Saati" alanina yazin. Site Tokyo TZ\'ini gostermez — Tokyo TZ verisi sadece "Otomatik Doldur" butonu ile AnimeSchedule API\'sinden dogrudan cekilir.',
    'help.tz.box_dst_title'                  => '<i class="fas fa-info-circle"></i> Yaz/Kis Saati (DST):',
    'help.tz.box_dst_body'                   => 'Animenin yayinlandigi TZ yaz/kis saati kullaniyorsa (Avrupa, ABD gibi), yayin saati yilda 2 kez 1 saat kayar (Mart sonu ve Ekim sonu). Asia/Tokyo DST kullanmadigi icin Japon anime saatleri yil boyu sabittir.',
    'help.tz.upgrade.h3'                     => 'Eski v0.5 Kurulumlardan Yukseltme',
    'help.tz.upgrade.text'                   => 'v0.5.1\'e gectikten sonra hicbir veriniz kaybolmaz. Yayin saatleri ayni gorunur (Asia/Tokyo varsayilan TZ\'de eklenmis kayitlar hala Asia/Tokyo\'da). Anime detay sayfasinda yayin saatinin yaninda TZ etiketi (JST, vs.) gozukur.',

    // Footer
    'help.footer'                            => 'Daha fazla sorunuz icin: daha fazla ayrintili teknik bilgi proje GitHub sayfasinda bulunur.',

    // -----------------------------------------------------------------
    // statistics.php
    // -----------------------------------------------------------------
    'statistics.page_title'                  => 'İstatistikler - Anime Tracker',
    'statistics.heading'                     => 'İstatistikler',
    'statistics.label.total_anime'           => 'Toplam Anime',
    'statistics.label.total_watched'         => 'Toplam İzlenen Bölüm',
    'statistics.section.by_media'            => 'Medya Türüne Göre',
    'statistics.section.by_broadcast'        => 'Yayın Durumuna Göre',
    'statistics.section.by_watch'            => 'İzleme Durumuna Göre',
    'statistics.col.type'                    => 'Tür',
    'statistics.col.status'                  => 'Durum',
    'statistics.col.count'                   => 'Adet',
    'statistics.value.unspecified'           => 'Belirtilmemiş',

    // -----------------------------------------------------------------
    // recent.php - son duzenlenen 5 anime
    // -----------------------------------------------------------------
    'recent.page_title'                      => 'Son Düzenlenenler - Anime Tracker',
    'recent.heading'                         => 'Son Düzenlenenler',
    'recent.back_to_list'                    => 'Listeye Dön',
    'recent.empty_state'                     => 'Henüz anime eklenmemiş.',
    'recent.time.now'                        => 'Az önce',
    'recent.time.minutes_ago'                => '%d dk önce',
    'recent.time.hours_ago'                  => '%d saat önce',
    'recent.time.days_ago'                   => '%d gün önce',

    // -----------------------------------------------------------------
    // recommendations.php - 'Ne Izlesem?' oneri sayfasi
    // -----------------------------------------------------------------
    'recommendations.page_title'             => 'Ne İzlesem? - Anime Tracker',
    'recommendations.heading'                => 'Ne İzlesem?',
    'recommendations.surprise.heading'       => 'Bugün bunu deneyelim:',
    'recommendations.surprise.try_another'   => 'Başka Bir Tane',
    'recommendations.surprise.choose_sentences' => 'Cümlelerden Seç',
    'recommendations.intro'                  => 'Sana uygun olabilecek cümleleri seçip <strong>Öner</strong>\'e bas. Çok cümle seçersin diye sonuç daralmaz - her cümle bir kepçe gibi kendi eşleşmesini çekiyor, en çok kepçeye düşen anime üst sırada.',
    'recommendations.no_tags_empty'          => 'Henüz cümle tanımlanmamış. Önce <a href="manage_tags.php">cümleleri yönet</a> sayfasından birkaç cümle ekle, sonra animelere atamak için <a href="add_anime.php">anime ekleme</a> veya düzenleme ekranını kullan.',
    'recommendations.search.placeholder'     => 'Cümle ara (yazınca daralır)...',
    'recommendations.toggle.show'            => 'Cümleleri Göster',
    'recommendations.toggle.hide'            => 'Cümleleri Gizle',
    'recommendations.toggle.count_selected'  => '(%d seçili)',
    'recommendations.search.empty_state'     => 'Bu metinle başlayan cümle bulunamadı.',
    'recommendations.btn.recommend'          => 'Öner',
    'recommendations.btn.surprise'           => 'Sürpriz Seç',
    'recommendations.btn.clear'              => 'Temizle',
    'recommendations.no_match'               => 'Seçtiğin cümlelerle eşleşen anime bulunamadı. Henüz hiçbir animeye bu cümleler atanmamış olabilir — anime düzenleme ekranından cümle eklemeyi unutma.',
    'recommendations.result.count'           => '<strong>%d</strong> anime bulundu (%d cümle seçildi).',
    'recommendations.group.matched'          => '%d / %d cümle eşleşti',
    'recommendations.group.count_suffix'     => '(%d anime)',

    // -----------------------------------------------------------------
    // about.php
    // -----------------------------------------------------------------
    'about.page_title'                       => 'Hakkında - Anime Tracker',
    'about.description'                      => 'Anime Tracker, AI araclari kullanilarak gelistirilmis bir anime liste olusturma ve yayin takip sistemidir.',
    'about.ai_notice_link'                   => 'AI Kullanım Beyanı / Notice',
    'about.back_to_list'                     => 'Anime Listesine Dön',

    // -----------------------------------------------------------------
    // chronology.php - per-anime kronoloji isaretleri timeline
    // -----------------------------------------------------------------
    'chronology.title_suffix'                => 'Kronoloji',
    'chronology.subtitle'                    => 'Kronolojik İzleme Sırası',
    'chronology.status.watched'              => 'İzlendi',
    'chronology.status.watching'             => 'İzleniyor',
    'chronology.status.upcoming'             => 'Sırada',
    'chronology.episode.range.watching'      => 'İzleniyor (%s/%s)',
    'chronology.episode.range.single'        => 'Bölüm %d',
    'chronology.episode.range.multi'         => 'Bölüm %d - %d',
    'chronology.episode.progress'            => '%d / %s bölüm izlendi',
    'chronology.back_to_details'             => 'Detaya Dön',

    // -----------------------------------------------------------------
    // series_timeline.php - seri zincir kronolojisi
    // -----------------------------------------------------------------
    'series_timeline.title_suffix'           => 'Seri Kronolojisi',
    'series_timeline.subtitle'               => 'Seri Kronolojisi',
    'series_timeline.count'                  => '%d anime',
    'series_timeline.back_to_details'        => 'Detaya Dön',

    // -----------------------------------------------------------------
    // list_settings.php - import/export/clear/sync/update
    // -----------------------------------------------------------------
    'list_settings.page_title'               => 'Liste Ayarları - Anime Tracker',
    'list_settings.heading'                  => 'Liste Ayarları',
    'list_settings.csrf.invalid'             => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.',
    'list_settings.version.unknown'          => 'bilinmiyor',
    'list_settings.aired.cancelled_prefix'   => 'Senkronizasyon iptal edildi:',
    'list_settings.aired.no_api_key'         => 'AnimeSchedule API anahtari config.php icinde tanimli degil.',
    'list_settings.aired.rate_limit'         => 'API istek limiti asildi. Birkac dakika sonra tekrar deneyin.',
    'list_settings.aired.invalid_key'        => 'API anahtari gecersiz. config.php yi kontrol edin.',
    'list_settings.aired.result.updated'     => '%d anime guncellendi',
    'list_settings.aired.result.unchanged'   => '%d degismedi',
    'list_settings.aired.result.not_in_table' => '%d takvimde bulunamadi',
    'list_settings.aired.result.no_slug'     => '%d AnimeSchedule URL si yok',
    'list_settings.aired.result.errors'      => '%d hata',
    'list_settings.import.success'           => 'Liste başarıyla içe aktarıldı!',
    'list_settings.import.invalid_format'    => 'Lütfen geçerli bir JSON dosyası yükleyin!',
    'list_settings.clear.success'            => 'Liste başarıyla temizlendi!',
    'list_settings.section.export'           => 'Listeyi Dışa Aktar',
    'list_settings.section.export.desc'      => 'Mevcut anime listenizi JSON formatında dışa aktarın.',
    'list_settings.btn.export'               => 'Listeyi Dışa Aktar',
    'list_settings.section.import'           => 'Listeyi İçe Aktar',
    'list_settings.section.import.desc'      => 'Önceden dışa aktarılmış bir listeyi içe aktarın.',
    'list_settings.btn.choose_file'          => 'Dosya Seç',
    'list_settings.btn.import'               => 'Listeyi İçe Aktar',
    'list_settings.section.clear'            => 'Listeyi Temizle',
    'list_settings.section.clear.desc'       => 'DİKKAT: Bu işlem geri alınamaz!',
    'list_settings.btn.clear'                => 'Listeyi Temizle',
    'list_settings.section.genres'           => 'Tür Yönetimi',
    'list_settings.section.genres.desc'      => 'Yanlış yazılan veya kullanılmayan türleri yönetin.',
    'list_settings.btn.manage_genres'        => 'Türleri Yönet',
    'list_settings.section.catalog'          => 'Katalog Senkronizasyonu',
    'list_settings.section.catalog.desc'     => 'Merkezi katalogdan en son anime bilgilerini cekin. Kendi izleme durumlariniz ve notlariniz korunur.',
    'list_settings.catalog.last_sync_prefix' => 'Son senkronizasyon:',
    'list_settings.catalog.never_synced'     => 'Henuz senkronize edilmedi.',
    'list_settings.catalog.unpushed_warning' => 'Katalog ile senkronize olmayan <strong>%d</strong> kronoloji isareti var. Ice aktarma bunlari <strong>silmez</strong> &mdash; kendi ekledikleriniz korunur, katalogdan gelenler otomatik eslestirilir. Evrensel kronolojinin eksiksiz kalmasi icin bunlari admin push ile kataloga gondermeniz onerilir.',
    'list_settings.btn.catalog_import'       => 'Katalogdan İçe Aktar',
    'list_settings.section.aired'            => 'Bölüm Sayısı Senkronizasyonu',
    'list_settings.section.aired.desc'       => 'Yayını devam eden animelerin "yayınlanan bölüm sayısı" bilgisi AnimeSchedule den otomatik olarak güncellenir. Bu sayfa her açıldığında günde bir kez arka planda çalışır; manuel çalıştırmak için aşağıdaki butonu kullanabilirsiniz.',
    'list_settings.btn.sync_now'             => 'Şimdi Senkronize Et',
    'list_settings.section.update'           => 'Güncelleme Kontrolü',
    'list_settings.section.update.desc'      => 'Yeni versiyon kontrolü yapın.',
    'list_settings.update.current_version'   => 'Mevcut versiyon:',
    'list_settings.btn.check_update'         => 'Güncelleme Kontrolü',
    'list_settings.back_to_list'             => 'Anime Listesine Dön',
    'list_settings.js.confirm_clear'         => 'Tüm liste silinecek. Bu işlem geri alınamaz! Devam etmek istiyor musunuz?',
    'list_settings.js.confirm_sync_intro'    => 'Katalogdan ice aktarilacak.',
    'list_settings.js.confirm_sync_safe'     => 'Kendi izleme durumlariniz ve notlariniz KORUNUR.',
    'list_settings.js.confirm_sync_overwrite' => 'Sadece anime bilgileri (baslik, synopsis, bolum sayisi vs.) guncellenir.',
    'list_settings.js.confirm_sync_unpushed' => 'NOT: Katalog ile senkronize olmayan %d kronoloji isareti var. Ice aktarma bunlari SILMEZ. Kendi ekledikleriniz korunur, katalogdan gelenler otomatik eslestirilir.',
    'list_settings.js.confirm_continue'      => 'Devam etmek istiyor musunuz?',
    'list_settings.js.checking'              => 'Kontrol ediliyor...',
    'list_settings.js.update_error'          => 'Guncelleme kontrolu sirasinda bir hata olustu.',
    'list_settings.js.up_to_date_suffix'     => '(güncel)',
    'list_settings.js.new_version_label'     => 'Yeni versiyon:',
    'list_settings.js.confirm_install' => 'Yeni versiyon mevcut: %s

Hemen guncellemek ister misiniz?',
    'list_settings.js.network_error'         => 'Güncelleme kontrolü sırasında bir hata oluştu:',
    'list_settings.js.installing'            => 'Guncelleme indiriliyor ve uygulaniyor...',
    'list_settings.js.installing_note'       => 'Bu islem birkac saniye surebilir. Sayfayi kapatmayin.',
    'list_settings.js.install_failed'        => 'Guncelleme basarisiz',
    'list_settings.js.install_failed_alert'  => 'Guncelleme basarisiz:',
    'list_settings.js.unknown_error'         => 'Bilinmeyen hata',
    'list_settings.js.install_success'       => 'Guncelleme tamamlandi!',
    'list_settings.js.install_previous'      => 'Eski versiyon:',
    'list_settings.js.install_new'           => 'Yeni versiyon:',
    'list_settings.js.reloading'             => 'Sayfa yenileniyor...',
    'list_settings.js.install_network_error' => 'Ag hatasi',
    'list_settings.js.install_network_error_alert' => 'Guncelleme sirasinda bir hata olustu:',

];
