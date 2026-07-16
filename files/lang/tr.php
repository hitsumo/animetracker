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
    'nav.recent_edits'    => 'Son Güncellenenler',
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
    'index.watch_status.unselected' => 'Seçim Yapılmamış',
    'index.warn.catalog_push_failed' => 'Kayıt yapıldı ancak merkez kataloğa gönderilemedi. Değişiklik bu kurulumda saklı; animeyi yeniden kaydederek göndermeyi tekrar deneyebilirsiniz.',
    'index.filter.broadcast'        => 'Yayın Durumuna Göre Filtrele:',
    'index.filter.letter'           => 'Harfe Göre Filtrele',
    'index.filter.per_page'         => 'Sayfada Göster:',
    'index.filter.all'              => 'Tümü',
    'index.filter.show_all'         => 'Hepsi',
    'index.filter.submit'           => 'Filtrele',
    // 1.1.5: aktif duygu filtresi rozeti (istatistik duygu rozetinden gelince)
    'index.filter.emotion_active'   => 'Duygu filtresi: %s',
    'index.filter.emotion_clear'    => 'Filtreyi temizle',

    // 1.1.13: Genel / Kisisel liste sekmeleri (pagination ile tablo arasinda).
    // Kisisel = kullanicinin bir izleme durumu sectigi animeler.
    'index.tab.all'                 => 'Genel Liste',
    'index.tab.personal'            => 'Kişisel Liste',

    // Broadcast status values (free text in animes.status, kept as
    // Turkish constants in the DB for now). 1.1.10: three new values
    // (not_started / unselected / cancelled) alongside the original two.
    'index.broadcast.ongoing'       => 'Yayın Devam Ediyor',
    'index.broadcast.finished'      => 'Yayın Tamamlandı',
    'index.broadcast.not_started'   => 'Yayın Başlamadı',
    'index.broadcast.unselected'    => 'Seçim Yapılmadı',
    'index.broadcast.cancelled'     => 'Yayın İptal Edildi',

    // Add button above the list
    'index.add_anime'               => 'Yeni Anime Ekle',
    'index.pending_link'            => 'Onay bekleyenler (%d)',
    'pending.page_title'            => 'Onay Bekleyenler',
    'pending.heading'               => 'Onay Bekleyenler',
    'pending.intro'                 => 'Kullanıcıların eklediği, moderatör onayı bekleyen animeler. Onaylandıklarında katalogda görünür olurlar.',
    'pending.badge'                 => 'Onay bekliyor',
    'pending.empty'                 => 'Şu anda onay bekleyen ekleme yok.',
    'pending.back'                  => 'Katalog listesine dön',

    // Table column headers
    'index.col.anime'               => 'Anime',
    'index.col.status'              => 'Durum',
    'index.col.watched_episodes'    => 'İzlenen Bölüm',
    'index.col.episode_count'       => 'Bölüm Sayısı',
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
    'anime_details.label.watch_start_date' => 'İzlemeye Başlama:',
    'anime_details.label.watch_finish_date' => 'İzlemeyi Bitirme:',
    'anime_details.label.synopsis'       => 'Konu:',
    'anime_details.synopsis.auto_translated' => 'Türkçeden otomatik çevrildi',
    'anime_details.synopsis.en_unavailable'  => 'İngilizce konu mevcut değil — Türkçe orijinali gösteriliyor.',
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
    'anime_details.suggest.title'        => 'Düzeltme Öner',
    'anime_details.suggest.intro'        => 'Bu animede hatalı veya eksik bir bilgi mi var? Aşağıya yazın; bir moderatör inceleyecek.',
    'anime_details.suggest.placeholder'  => 'Önerinizi yazın (ör. yayın tarihi yanlış, tür eksik...).',
    'anime_details.suggest.submit'       => 'Öneri Gönder',
    'anime_details.suggest.ok'           => 'Öneriniz alındı, teşekkürler. Moderatör incelemesine düştü.',
    'anime_details.suggest.rate'         => 'Çok fazla öneri gönderdiniz. Lütfen bir süre sonra tekrar deneyin.',
    'anime_details.suggest.err'          => 'Öneri gönderilemedi. Lütfen tekrar deneyin.',

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
    // 1.1.2 - yetiskin (+18) icerik notr gizleme uyarisi (detay sayfasi kapisi)
    'anime_details.adult.hidden'         => 'Bu içerik gizli. Görmek için Liste Ayarları\'ndan "Yetişkin içeriği göster" seçeneğini açabilirsiniz.',
    // 1.1.2 - sirali iliskilerde (kronoloji/seri) +18 dugum icin notr yer tutucu baslik
    'adult.hidden_node_title'            => 'Gizli içerik',

    // -----------------------------------------------------------------
    // add_anime.php - new anime entry form
    // -----------------------------------------------------------------

    // Page meta
    'add_anime.page_title'                   => 'Listeye Anime Ekleme',
    'add_anime.heading'                      => 'Listeye Anime Ekleme',

    // Form field labels
    'add_anime.label.title'                  => 'Anime İsmi:',
    'add_anime.label.alternative_titles'     => 'Alternatif İsimler:',
    'add_anime.label.synopsis'               => 'Konu (TR):',
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
    'add_anime.label.watch_dates'            => 'İzleme Tarihleri (opsiyonel):',
    'add_anime.label.watch_start_date'       => 'Başlangıç:',
    'add_anime.label.watch_finish_date'      => 'Bitiş:',
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
    'add_anime.label.title_english'          => 'İngilizce Başlık:',
    'add_anime.ph.title_english'             => 'İngilizce başlık (opsiyonel)',
    'add_anime.hint.title_english'           => 'İsteğe bağlı. Doldurulduğunda, Liste Ayarları\'nda "İngilizce başlıkları göster" açıksa Romaji başlık yerine bu gösterilir.',
    'add_anime.ph.synopsis'                  => 'Animenin konusunu yazın',
    'add_anime.label.synopsis_en'            => 'Konu (EN):',
    'add_anime.ph.synopsis_en'               => 'Animenin İngilizce konusu (AI ile çevrilmiş)',
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
    'add_anime.hint.watch_dates'             => 'Elle girilir, boş bırakılabilir. Kişiseldir; katalog senkronuyla paylaşılmaz.',
    'add_anime.warn.date_order'              => 'Bitiş tarihi başlangıçtan önce. Yine de kaydedilir.',
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
    'add_anime.error.release_date_invalid'   => 'Yayin tarihi gecersiz format. Dogru format: YYYY-MM-DD (orn: 2026-04-08)',
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
    // edit_anime.php - mevcut anime duzenleme sayfasi
    // -----------------------------------------------------------------
    //
    // edit_anime form yapisi add_anime ile buyuk olcude paraleldir, bu
    // yuzden tum label/placeholder/buton/gun/timezone/option/hint
    // anahtarlari add_anime.* uzerinden yeniden kullanilir (KARARLAR
    // Bolum 7 tek-kaynak prensibi). Asagidaki anahtarlar SADECE
    // edit_anime'a ozgu olanlar: sayfa basligi, Guncelle butonu,
    // kilitli durum uyarisi, Kisisel Konu (Mode 2) alanlari, Siradaki
    // Anime alani, duplicate hatasinin edit-tarafi metinleri ve aired
    // episodes sync (Senkronize Et) butonu icin JS string'leri.

    // Page meta
    'edit_anime.page_title'                  => 'Anime Düzenle',
    'edit_anime.heading'                     => 'Anime Düzenle',

    // Submit button (add_anime "Ekle" yerine "Guncelle")
    'edit_anime.btn.submit'                  => 'Güncelle',
    // 1.1.5: guncelleme/ekleme sonrasi ayni sayfada kalinca gosterilen basari bandi
    'edit_anime.notice.saved'                => 'Değişiklikler kaydedildi.',
    // 1.1.5: duzenleme sayfasindan ilgili animenin detay sayfasina giden buton
    'edit_anime.btn.view_detail'             => 'Anime Detayı',
    // 1.1.8: admin-only tam-katalog push butonu + onay + sonuc bandlari.
    // Normal "Guncelle" artik yalniz o animenin serisini gonderir; bu buton
    // tum katalogu yeniden gonderir (admin_sync'in online karsiligi).
    'edit_anime.btn.full_push'               => 'Tüm Kataloğu Gönder',
    'edit_anime.confirm.full_push'           => 'Tüm katalog merkeze yeniden gönderilecek. Devam edilsin mi?',
    'edit_anime.notice.full_pushed'          => 'Tüm katalog merkeze gönderildi (%d anime).',
    'edit_anime.notice.full_push_failed'     => 'Tüm katalog gönderimi başarısız oldu. Ayrıntı sunucu günlüğünde.',

    // Status field - locked hint shown when anime status is "Yayin
    // Tamamlandi" (the select is replaced with a readonly input).
    'edit_anime.status.locked_hint'          => 'Bu anime yayını tamamlandığı için durum değiştirilemez.',

    // Synopsis Mode 2 - user_synopsis is set, "Konu" becomes readonly
    // (server text) and "Kisisel Konu" is the editable personal field.
    // The label "Konu:" itself is shared with add_anime.label.synopsis.
    'edit_anime.hint.synopsis_readonly'      => "server'dan gelir, sync ile guncellenir",
    'edit_anime.btn.copy_synopsis_tr'        => 'Kopyala',
    'edit_anime.hint.synopsis_en'            => 'İngilizce metni bir AI aracıyla çevirip buraya yapıştırın. Detay sayfasında "Auto-translated from Turkish" etiketiyle gösterilir.',
    'edit_anime.label.mark_reviewed'         => 'Onaylandı olarak işaretle',
    'edit_anime.hint.mark_reviewed'          => 'İngilizce çeviriyi okuyup doğruladıysanız işaretleyin. Türkçe metni değiştirirseniz otomatik kalkar.',
    'edit_anime.label.user_synopsis'         => 'Kişisel Konu (TR):',
    'edit_anime.ph.user_synopsis'            => 'Kendi yorumunuz, cevirisi, ozeti',
    'edit_anime.hint.user_synopsis'          => 'kullanici konu bolumu - silinirse sync ile geri gelmez',
    'edit_anime.label.user_synopsis_en'      => 'Kişisel Konu (EN):',
    'edit_anime.ph.user_synopsis_en'         => 'Your own comment / translation / summary',

    // Next-in-series field (only on edit, not on add)
    'edit_anime.label.next_in_series'        => 'Sıradaki Anime (opsiyonel):',
    'edit_anime.hint.next_in_series'         => 'Bu animeyi bitirdikten sonra izlenecek anime. ★ = aynı seri.',

    // Duplicate detection - edit-side wording differs from add (a value
    // is "used by another record" rather than "already exists").
    'edit_anime.duplicate.already_used_suffix'   => 'baska bir kayitta kullaniliyor.',
    'edit_anime.duplicate.conflicting_record_prefix' => 'Tekrarlanan veri hatasini olusturan kayit:',
    'edit_anime.error_page.go_to_conflicting'    => 'Cakisma olan kayda git',

    // Aired episodes sync button (only on edit) - JS LANG bloku
    'edit_anime.js.aired_sync.fetching'      => 'AnimeSchedule den bolum sayisi cekiliyor...',
    'edit_anime.js.aired_sync.this_week'     => ' (bu hafta)',
    'edit_anime.js.aired_sync.last_week'     => ' (gecen hafta)',
    'edit_anime.js.aired_sync.weeks_ago_fmt' => ' (%d hafta once)',
    'edit_anime.js.aired_sync.updated_prefix'   => 'Guncellendi:',
    'edit_anime.js.aired_sync.no_change_prefix' => 'Mevcut deger zaten guncel:',

    // -----------------------------------------------------------------
    // help.php - kullanici yardim / nasil calisir sayfasi
    // -----------------------------------------------------------------

    // Page meta
    'help.page_title'                        => 'Yardım - Anime Tracker',
    'help.heading'                           => 'Yardım',
    'help.back_to_home'                      => '&larr; Ana Sayfaya Dön',
    'help.back_to_index'                     => '&larr; Yardım İçindekiler',

    // Help sub-page group titles (1.0.22 split)
    'help.group.basics.heading'              => 'Temel İzleme — Durumlar ve Butonlar',
    'help.group.basics.page_title'           => 'Temel İzleme - Anime Tracker',
    'help.group.fields.heading'              => 'Alanlar ve Kişisel Veri',
    'help.group.fields.page_title'           => 'Alanlar ve Kişisel Veri - Anime Tracker',
    'help.group.sync.heading'                => 'Senkronizasyon, Silme ve Güncelleme',
    'help.group.sync.page_title'             => 'Senkronizasyon, Silme ve Güncelleme - Anime Tracker',
    'help.group.discovery.heading'           => 'Keşif ve Etkileşim',
    'help.group.discovery.page_title'        => 'Keşif ve Etkileşim - Anime Tracker',
    'help.group.series.heading'              => 'Seriler ve Bölüm Bilgisi',
    'help.group.series.page_title'           => 'Seriler ve Bölüm Bilgisi - Anime Tracker',
    'help.group.timezone.heading'            => 'Saat Dilimi',
    'help.group.timezone.page_title'         => 'Saat Dilimi - Anime Tracker',
    'help.intro'                             => 'Anime Tracker\'ın nasıl çalıştığı, hangi alanların neye yaradığı ve neye dikkat etmeniz gerektiği burada. Bir özelliği merak ediyorsanız ilgili bölümü okuyun.',
    'help.contact'                           => 'İletişim için: <a href="mailto:at@animetracker.uzakdiyarlar.com">at@animetracker.uzakdiyarlar.com</a>',

    // Table of contents
    'help.toc.heading'                       => 'İçindekiler:',
    'help.toc.fields'                        => 'Anime Alanları — Hangisi Ne Yapar?',
    'help.toc.statuses'                      => 'İzleme Durumları — Beş Seçenek',
    'help.toc.quick_buttons'                 => 'Hızlı İzleme Butonları (+/-)',
    'help.toc.sync'                          => 'Katalog Sync — Nasıl Çalışır?',
    'help.toc.personal'                      => 'Kişisel Alanlar — Notlar ve Kişisel Konu',
    'help.toc.emotions'                      => 'Duygular — Animeye Tepki Ver',
    'help.toc.filler'                        => 'Dolgu ve Canon Bölümler',
    'help.toc.statistics'                    => 'İstatistikler',
    'help.toc.title_lang'                    => 'Başlık Dili (İngilizce / Romaji)',
    'help.toc.translation'                   => 'Çeviri durumu',
    'help.toc.recommendations'               => 'Ne İzlesem? — Öneri Sistemi',
    'help.toc.chronology'                    => 'Seriler ve Kronoloji',
    'help.toc.deletion'                      => 'Silme Uyarıları',
    'help.toc.updates'                       => 'Güncelleme Sistemi',
    'help.toc.timezone'                      => 'Saat Dilimi (TZ)',

    // Section: Anime fields (catalog vs personal)
    'help.fields.h2'                         => 'Anime Alanları — Hangisi Ne Yapar?',
    'help.fields.intro'                      => 'Anime ekleme ve düzenleme ekranındaki alanlar iki gruba ayrılır: <strong>katalog alanları</strong> (sunucudan gelir, sync ile güncellenir) ve <strong>kişisel alanlar</strong> (size özel, hiçbir zaman sunucuya gitmez).',
    'help.fields.catalog.h3'                 => '<i class="fas fa-cloud icon-inline"></i> Katalog Alanları (sync edilir)',
    'help.fields.catalog.list' => '<li><strong>Anime İsmi, Alternatif İsimler</strong></li>
        <li><strong>Konu</strong> — Animenin resmi özeti</li>
        <li><strong>Türler</strong> — Aksiyon, Komedi, vb.</li>
        <li><strong>Cümleler (Etiketler)</strong> — "Ne İzlesem?" sistemi için</li>
        <li><strong>Yayın durumu, bölüm sayısı, yayın gün/saati</strong></li>
        <li><strong>MAL / AniDB / AnimeSchedule linkleri</strong></li>
        <li><strong>Seri bilgileri</strong> (seri adı, medya türü, sonraki seri)</li>',
    'help.fields.catalog.note'               => 'Bu alanları elle değiştirirseniz, bir sonraki sync\'te <strong>üzerine yazılır</strong> (sunucunun dediği geçer).',
    'help.fields.personal.h3'                => '<i class="fas fa-user icon-inline"></i> Kişisel Alanlar (sync edilmez)',
    'help.fields.personal.list' => '<li><strong>İzlenen Bölüm sayısı</strong></li>
        <li><strong>İzleme Durumu</strong> (İzlendi / İzleniyor / İzlenme Planlandı / İzleme Ertelendi / İzleme Bırakıldı) — listedeki <a href="#hizli-butonlar"><code>+/-</code> butonlarıyla otomatik değişebilir</a></li>
        <li><strong>Notlar</strong> — Size özel hatırlatmalar, yorumlar</li>
        <li><strong>Kişisel Konu</strong> — Kendi yorumunuz / açıklamanız</li>
        <li><strong>Poster (kendi yüklediyseniz)</strong></li>
        <li><strong>Sonraki bölüm tarihi</strong> (lokal hesap)</li>',
    'help.fields.personal.note'              => 'Bu alanlara sunucu <strong>dokunmaz</strong>. İstediğiniz kadar yazabilir, değiştirebilirsiniz.',

    // Section: Watch statuses
    'help.statuses.h2'                       => 'İzleme Durumları',
    'help.statuses.intro'                    => 'Her animenin bir <strong>İzleme Durumu</strong> vardır. Beş seçenek farklı izleme aşamalarını karşılar, artı henüz hiçbir seçim yapmadığınız bir başlangıç durumu:',
    'help.statuses.list' => '<li><strong>İzlenme Planlandı</strong> — Henüz başlamadınız, ileride izlemek istiyorsunuz. İzlenen bölüm: 0.</li>
        <li><strong>İzleniyor</strong> — Aktif olarak izliyorsunuz. İzlenen bölüm tavan ile sıfır arasında bir yerde.</li>
        <li><strong>İzlendi</strong> — Bitirdiğiniz animeler. İzlenen bölüm = toplam bölüm (ya da yayını tamamlanmış bir dizinin tüm bölümleri).</li>
        <li><strong>İzleme Ertelendi</strong> — İzlemeye başladınız ama ara verdiniz, ilerlemeniz korunsun. <em>Planlandı\'dan farkı:</em> Planlandı "henüz başlamadım" demektir (izlenen=0), Ertelendi "biraz izledim, şu anda ara veriyorum" demektir (izlenen>0).</li>
        <li><strong>İzleme Bırakıldı</strong> — Bu animeyi izlemeyi tamamen bırakmaya karar verdiniz; geri dönmeyi planlamıyorsunuz. Ertelendi\'den farkı: Ertelendi "sonra devam edeceğim", Bırakıldı "bittim, devam etmeyeceğim" demektir.</li>
        <li><strong>Seçim Yapılmamış</strong> — Henüz bu anime için bir durum seçmediniz. Listenizde görünür ama hiçbir izleme grubuna girmez; ilk <code>+</code> ya da Düzenle ile bir durum atayınca bu başlangıç durumundan çıkar.</li>',
    'help.statuses.when_postponed'           => '<strong>Ne zaman Ertelendi kullanmalı?</strong> Bir animeyi 6 ay sonra geri dönmek üzere yarıda bırakırsanız, durumu Ertelendi yapın. Böylece "İzleniyor" listenizdeki aktif izleme akışı kalabalıklaşmaz, ama Planlandı\'ya da düşmez (çünkü ilerlemeniz var). Hazır olduğunuzda <code>+</code> basarsınız, sistem otomatik olarak İzleniyor\'a geri çeker.',

    // Section: Quick watch buttons (+/-)
    'help.buttons.h2'                        => 'Hızlı İzleme Butonları (+/-)',
    'help.buttons.intro'                     => 'Listede her animenin yanında <code>+</code> ve <code>-</code> butonları var. Bu butonlarla "Düzenle" ekranına gitmeden İzlenen Bölüm sayısını bir artırıp azaltabilirsiniz. Sayım değişirken belirli koşullarda <strong>İzleme Durumu da otomatik olarak güncellenir</strong>.',
    'help.buttons.transitions.h3'            => 'Otomatik Durum Geçişleri',
    'help.buttons.transitions.intro'         => 'Aşağıdaki tablo beş temel durumu özetler:',
    'help.buttons.transitions.col_current'   => 'Şu anki durum',
    'help.buttons.transitions.col_action'    => 'Aksiyon',
    'help.buttons.transitions.col_new'       => 'Yeni durum',
    'help.buttons.transitions.row1_curr'     => 'İzlenme Planlandı + 0/12',
    'help.buttons.transitions.row1_new'      => 'İzleniyor + 1/12',
    'help.buttons.transitions.row2_curr'     => 'İzleniyor + 11/12',
    'help.buttons.transitions.row2_new'      => 'İzlendi + 12/12',
    'help.buttons.transitions.row3_curr'     => 'İzlendi + 12/12',
    'help.buttons.transitions.row3_new'      => 'İzleniyor + 11/12',
    'help.buttons.transitions.row4_curr'     => 'İzleniyor + 1/12',
    'help.buttons.transitions.row4_new'      => 'İzlenme Planlandı + 0/12',
    'help.buttons.transitions.row5_curr'     => 'İzleme Ertelendi + 5/12',
    'help.buttons.transitions.row5_new'      => 'İzleniyor + 6/12',
    'help.buttons.transitions.note'          => 'Mantık basit: durum sınır geçişlerinde (başa dönüş, sona ulaşma) otomatik değişir, ara değerlerde dokunulmaz. Not: tablodaki "İzlendi"ye otomatik geçiş, toplam bölümü bilinen ya da yayını tamamlanmış diziler içindir; yayını devam eden ve toplamı bilinmeyen dizilerde aşağıdaki kural geçerlidir.',
    'help.buttons.two_step.h3'               => 'Tek Tıkla İki Adım',
    'help.buttons.two_step.intro'            => 'Bazen tek bir <code>+</code> veya <code>-</code> basışı iki geçişi birden tetikleyebilir:',
    'help.buttons.two_step.list' => '<li><strong>Planlandı + 11/12 → <code>+</code> → İzlendi + 12/12.</strong> Önce Planlandı\'dan İzleniyor\'a, sonra tavana ulaştığı için İzleniyor\'dan İzlendi\'ye tek tıkla geçer.</li>
        <li><strong>İzlendi + 1/12 → <code>-</code> → İzlenme Planlandı + 0/12.</strong> Yukarıdakinin aynadaki yansıması: önce İzleniyor\'a, sonra 0\'a indiği için Planlandı\'ya tek tıkla döner.</li>
        <li><strong>İzleme Ertelendi + 11/12 → <code>+</code> → İzlendi + 12/12.</strong> Ertelendiğin animede son bölüme varınca, aynı mantık çalışır: önce İzleniyor\'a, sonra tavana ulaştığı için İzlendi\'ye tek tıkla geçer.</li>',
    'help.buttons.untouched.h3'              => 'Ne Zaman Tetiklenmez?',
    'help.buttons.untouched.box_title'       => '<i class="fas fa-info-circle"></i> Otomasyon dokunmaz:',
    'help.buttons.untouched.list' => '<li><strong>İzleniyor + ara değer</strong> (örnek: 7/12) — <code>+</code> veya <code>-</code> basıldığında durum İzleniyor olarak kalır, sadece sayım değişir.</li>
            <li><strong>İzlenme Planlandı + <code>-</code></strong> — Planlandı durumunda <code>-</code> basmak ne sayımı ne durumu değiştirir (zaten 0).</li>
            <li><strong>İzlendi + tavan altında + <code>+</code></strong> — manuel olarak anormal duruma getirilmiş bir kayda <code>+</code> basınca durum İzlendi olarak kalır; otomasyon zorla düzeltmez, manuel niyetiniz korunur.</li>
            <li><strong>İzleme Ertelendi + <code>-</code></strong> — Ertelenmiş bir animede <code>-</code> basıldığında durum İzleme Ertelendi olarak kalır, sadece sayım 1 azalır. "Ara verdim ama bir bölümü unutmuştum" gibi nadir durumlar içindir. Devam etmek istediğinizde <code>+</code> basın (yukarıdaki 5. kural devreye girer) veya Düzenle\'den durumu manuel değiştirin.</li>',
    'help.buttons.unknown_count.h3'          => 'Bölüm Sayısı Bilinmeyen Animeler',
    'help.buttons.unknown_count.intro'       => 'Toplam veya yayınlanan bölüm sayısı bilinmiyorsa (tavansız eski OVA\'lar, programı belirsiz seriler gibi):',
    'help.buttons.unknown_count.list' => '<li><strong>Tavana ulaşma kontrolü yapılamaz</strong> — bu yüzden <code>+</code> ile otomatik "İzlendi" geçişi çalışmaz. Manuel olarak Düzenle\'den işaretlemeniz gerekir.</li>
        <li><strong>0\'a iniş kontrolü tavandan bağımsız çalışır</strong> — İzleniyor + 1/? üzerinde <code>-</code> basıldığında durum yine İzlenme Planlandı + 0/?\'e otomatik döner.</li>
        <li><strong>Manuel İzlendi yapılmış tavansız animede <code>-</code></strong> basıldığında durum İzlendi olarak kalır — sistem güvenli bir geçiş yapamadığı için manuel duruma karışmaz.</li>',
    'help.buttons.airing_unknown.h3'         => 'Yayını Devam Eden, Toplamı Bilinmeyen Diziler',
    'help.buttons.airing_unknown.intro'      => 'Bir dizi hâlâ yayınlanıyorsa ve toplam bölüm sayısı henüz bilinmiyorsa (örn. şu an 11 bölüm yayınlanmış, dizi devam ediyor), son yayınlanan bölüme yetişmek sizi "bitirdiniz" saymaz.',
    'help.buttons.airing_unknown.box_title'  => '<i class="fas fa-info-circle"></i> Yetiştim, izledim demek değil:',
    'help.buttons.airing_unknown.box_body'   => '<code>+</code> ile son yayınlanan bölüme (örn. 11/11) ulaşsanız bile durum <strong>"İzleniyor" olarak kalır</strong>, yanlışlıkla "İzlendi" olmaz. Yeni bir bölüm yayınlandığında da "İzleniyor" kalmaya devam eder. Durum yalnızca dizi gerçekten bittiğinde "İzlendi" olur: bilinen toplam bölüme ulaşıldığında ya da yayını tamamlanmış bir dizinin tüm bölümleri izlendiğinde. (Bu davranış 1.0.21 ile geldi; daha önceki sürümlerde yanlışlıkla "İzlendi"de takılan bir kayıt, bir kez <code>-</code> basınca kendiliğinden "İzleniyor"a döner.)',
    'help.buttons.manual.h3'                 => 'Manuel Düzenleme Her Zaman Serbest',
    'help.buttons.manual.text'               => 'Otomatik durum geçişleri sadece <code>+</code> ve <code>-</code> butonlarına basarken devreye girer. "Düzenle" formundan istediğiniz durumu manuel olarak <strong>her zaman</strong> seçebilirsiniz; otomasyon ona karışmaz.',

    // Section: Catalog sync
    'help.sync.h2'                           => 'Katalog Sync — Nasıl Çalışır?',
    'help.sync.intro'                        => 'Liste Ayarları sayfasında "Katalogdan İçe Aktar" düğmesine bastığınızda, sunucudaki katalog lokal veritabanınızla birleştirilir.',
    'help.sync.safe_title'                   => '<i class="fas fa-shield-alt"></i> Kaybolmaz:',
    'help.sync.safe_body'                    => 'İzleme verileriniz, notlarınız, Kişisel Konu, kendi yüklediğiniz poster — bunlar size özeldir ve sync\'te asla dokunulmaz.',
    'help.sync.warning_title'                => '<i class="fas fa-exclamation-triangle"></i> Üzerine Yazılır:',
    'help.sync.warning_body'                 => 'Anime ismi, konu, türler, yayın bilgileri gibi katalog alanları her sync\'te sunucunun son haline göre güncellenir. Elle değiştirdiyseniz kaybolur.',
    'help.sync.own_added.h3'                 => 'Kendi Eklediğim Animeler Ne Olur?',
    'help.sync.own_added.text'               => 'Siz bir anime ekledikten sonra admin tarafından kataloğa alınmamışsa (yani sizin özel kayıtlarınız), bu animeler sync\'te <strong>hiç dokunulmaz</strong>. Tüm alanları korunur.',
    'help.sync.when.h3'                      => 'Sync Ne Zaman Çalışır?',
    'help.sync.when.text'                    => 'Otomatik değil — sadece siz istedikçe. Liste Ayarları → "Katalogdan İçe Aktar" düğmesine basınca bir defa çalışır.',
    'help.sync.aired.h3'                      => 'Bölüm Sayısı Senkronizasyonu (yayınlanan bölüm)',
    'help.sync.aired.text'                    => 'Katalog sync\'inden ayrı olarak, yayını devam eden animelerin "kaç bölümü yayınlandı" bilgisi AnimeSchedule\'dan güncellenir. Bu, Liste Ayarları sayfasını her açtığınızda günde bir kez arka planda kendiliğinden çalışır; "Şimdi Senkronize Et" ile elle de tetikleyebilirsiniz.',
    'help.sync.aired.box_title'               => '<i class="fas fa-shield-alt"></i> Kişisel duruma dokunmaz:',
    'help.sync.aired.box_body'                => 'Bu işlem yalnızca katalogdaki "yayınlanan bölüm sayısı" alanını günceller. Sizin izleme durumunuza, izlenen bölüm sayınıza ya da notlarınıza dokunmaz. Yeni bir bölüm yayınlandığında izleme durumunuz kendiliğinden değişmez; ilerlemeyi siz <code>+</code> ile işlersiniz.',

    // Section: Personal fields (Notes + Personal Synopsis)
    'help.personal.h2'                       => 'Kişisel Alanlar — Notlar ve Kişisel Konu',
    'help.personal.intro'                    => 'İki farklı kişisel metin alanınız var. Farkları:',
    'help.personal.table.col_field'          => 'Alan',
    'help.personal.table.col_purpose'        => 'Amaç',
    'help.personal.table.col_example'        => 'Örnek',
    'help.personal.table.row_notes_field'    => 'Notlar',
    'help.personal.table.row_notes_purpose'  => 'Kısa hatırlatmalar',
    'help.personal.table.row_notes_example'  => '"Arkadaşla beraber izle", "ilk 3 bölümden sonra hızlı izle"',
    'help.personal.table.row_synopsis_field' => 'Kişisel Konu',
    'help.personal.table.row_synopsis_purpose' => 'Uzun yorumlar, kendi özetiniz',
    'help.personal.table.row_synopsis_example' => 'Kendi çevirisiniz, kendi yorumunuz, kendi özetiniz',
    'help.personal.howto.h3'                 => '<i class="fas fa-sync icon-inline"></i> Kişisel Konu Nasıl Oluşur?',
    'help.personal.howto.intro'              => '<strong>İlk durumda tek "Konu" alanı vardır.</strong> Kendiniz yazarsanız veya sunucudan gelen konu orada durur. Eğer katalogdan gelen yeni bir şey varsa ve siz o alana kendi yazınızı yazmışsanız, ilk sync sırasında:',
    'help.personal.howto.list' => '<li>Sizin yazdığınız metin otomatik olarak <strong>"Kişisel Konu"</strong> alanına taşınır</li>
        <li>Sunucudan gelen metin "Konu" alanına yazılır (düzenleyemezsiniz, salt okunur olur)</li>
        <li>Artık iki alan göreceksiniz, düzenlediğiniz her şey "Kişisel Konu"ya gider</li>',
    'help.personal.warning_title'            => '<i class="fas fa-exclamation-triangle"></i> Dikkat:',
    'help.personal.warning_body'             => 'Kişisel Konu\'yu silerseniz <strong>sync ile geri gelmez</strong>. Aynı şekilde Notlar alanını silerseniz o da geri gelmez. Bu iki alan size özel ve kalıcı olarak sizin kontrolünüzde.',

    // Section: Emotions
    'help.emotions.h2'                       => 'Duygular — Animeye Tepki Ver',
    'help.emotions.intro'                    => 'Bir animenin detay sayfasında, o anime size ne hissettirdiyse işaretleyebilirsiniz. Dokuz duygu seçeneği var:',
    'help.emotions.list' => '<li><strong>Hüzünlendirdi</strong></li>
        <li><strong>Heyecanlandırdı</strong></li>
        <li><strong>Sıktı</strong></li>
        <li><strong>Güldürdü</strong></li>
        <li><strong>Korkuttu</strong></li>
        <li><strong>Düşündürdü</strong></li>
        <li><strong>Şaşırttı</strong></li>
        <li><strong>Dinlendirdi</strong></li>
        <li><strong>Motive Etti</strong></li>',
    'help.emotions.cap_title'                => '<i class="fas fa-info-circle"></i> Anime başına en fazla 3:',
    'help.emotions.cap_body'                 => 'Bir animede aynı anda en çok 3 duygu işaretli olabilir; böylece işaretler anlamlı kalır. Bir duyguya tekrar basmak işareti kaldırır (aç/kapa). İşaret kaldırmak her zaman serbesttir, 3 sınırına takılmaz.',
    'help.emotions.stats'                    => 'Duygu işaretleriniz kişiseldir ve İstatistikler sayfasında "Duygulara Göre" dağılım olarak özetlenir — en çok hangi duyguyu işaretlediğinizi orada görebilirsiniz.',

    // Section: Filler / canon episodes
    'help.filler.h2'                         => 'Dolgu ve Canon Bölümler',
    'help.filler.intro'                      => 'Bir animenin bölümleri kaynak materyale göre sınıflandırılabilir. Bu bilgi, "hangi bölümleri atlayabilirim" diye merak edenler içindir. Dört tür vardır:',
    'help.filler.list' => '<li><strong>Manga Canon</strong> — Kaynak mangaya dayanan, ana hikayenin parçası bölümler.</li>
        <li><strong>Anime Canon</strong> — Mangada olmayan ama yapım tarafından hikayeye dahil edilmiş, canon sayılan bölümler.</li>
        <li><strong>Karışık</strong> — Aynı bölüm içinde hem canon hem dolgu kısımlar var.</li>
        <li><strong>Dolgu</strong> — Ana hikayeyi etkilemeyen, atlanabilen dolgu bölümler.</li>',
    'help.filler.unmarked'                   => 'İşaretlenmemiş bir bölüm "canon varsay" anlamına gelir — yani bir bölümde tür etiketi yoksa, ana hikayenin parçası kabul edilir.',
    'help.filler.warning_title'              => '<i class="fas fa-exclamation-triangle"></i> Katalog verisi:',
    'help.filler.warning_body'               => 'Bölüm sınıflandırması katalog tarafından tutulur; sync\'te sunucunun hali esas alınır. Yani kendiniz değiştirirseniz bir sonraki sync\'te üzerine yazılabilir.',

    // Section: Statistics
    'help.stats.h2'                          => 'İstatistikler',
    'help.stats.intro'                       => 'İstatistikler sayfası listenizi özetleyen sayılar sunar; üç sekmeye ayrılır:',
    'help.stats.user.h3'                     => 'Kullanıcı İstatistiği',
    'help.stats.user.text'                   => 'Size özel özet: toplam anime, toplam izlediğiniz bölüm, toplam bölüm; medya türüne göre dağılım (TV / Film / OVA vb.), yayın durumuna göre, izleme durumuna göre (İzleniyor / İzlendi / Planlandı / Ertelendi / Bırakıldı / Seçim Yapılmamış) ve duygulara göre dağılım.',
    'help.stats.recent.h3'                   => 'Son İzlenenler',
    'help.stats.recent.text'                 => 'En son izleme işlemi yaptığınız animeler, en yeni en üstte olacak şekilde listelenir. "Neredeydim" diye bakmak için pratiktir.',
    'help.stats.global.h3'                   => 'Global İstatistik',
    'help.stats.global.text'                 => 'Kişisel listenizden bağımsız olarak, kataloğun genel dağılımını gösterir (kaç anime, hangi medya türleri vb.). Sizin izleme durumunuzu değil, kataloğun bütününü yansıtır.',

    // Section: Title language
    'help.title_lang.h2'                     => 'Başlık Dili (İngilizce / Romaji)',
    'help.title_lang.intro'                  => 'Anime başlıkları varsayılan olarak Romaji (örn. "Shingeki no Kyojin") gösterilir. Liste Ayarları → "Başlık Dili" bölümünden, İngilizce karşılığı olan animeleri İngilizce başlığıyla (örn. "Attack on Titan") gösterebilirsiniz.',
    'help.title_lang.box_title'              => '<i class="fas fa-info-circle"></i> Arayüz dilinden bağımsız:',
    'help.title_lang.box_body'               => 'Bu tercih size özeldir ve sitenin dilinden (Türkçe/İngilizce) bağımsız çalışır — arayüzü Türkçe kullanıp başlıkları İngilizce görmeyi tercih edebilirsiniz. İngilizce karşılığı olmayan animeler Romaji başlığıyla kalır.',

    // Section: Recommendation system
    'help.translation.h2'                    => 'Çeviri Durumu',
    'help.translation.intro'                 => 'Bu sitedeki anime konuları aslen site küratörü tarafından Türkçe yazılır. İngilizce sürümler harici araçlarla AI çevirisi yapılıp elle eklenir. Konunun altında "Auto-translated from Turkish" etiketiyle gösterilir.',
    'help.translation.quality'               => 'Çeviri kalitesi değişebilir; Türkçe orijinal her zaman esas alınan sürümdür. Dili istediğiniz zaman dil seçicisinden değiştirebilirsiniz.',
    'help.recom.h2'                          => 'Ne İzlesem? — Öneri Sistemi',
    'help.recom.intro'                       => 'Menüdeki "Ne İzlesem?" linki, listenizden size uygun anime önermesi için tasarlanmış bir araçtır.',
    'help.recom.howto.h3'                    => 'Nasıl Çalışır?',
    'help.recom.howto.text'                  => 'Yönetici (admin) her animeye birkaç <strong>cümle etiketi</strong> atar: "Okulda geçsin", "Spor olsun", "Büyü olsun" gibi. Siz bu cümlelerden istediğinizi seç, "Öner" butonuna basın.',
    'help.recom.scoop.h3'                    => 'Kepçe Mantığı',
    'help.recom.scoop.text'                  => 'Her seçilen cümle bir kepçe gibi düşünün. Kepçe kendi eşleşmesini listeden çeker. Birden fazla kepçe seçerseniz, en çok kepçeye uyan anime üst sırada gözükür.',
    'help.recom.scoop.box_title'             => '<i class="fas fa-check"></i> Önemli:',
    'help.recom.scoop.box_body'              => 'Çok cümle seçerseniz sonuçlar azalmaz, aksine sıralama netleşir. Sistem AND yerine OR + puan mantığı kullanır.',
    'help.recom.surprise.h3'                 => 'Sürpriz Seç',
    'help.recom.surprise.text'               => 'Hiç cümle seçmeden "Sürpriz Seç" derseniz, sistem size izlememiş olduğunuz bir anime rastgele seçer. Kararsız kaldığınızda hızlı bir çözüm.',
    'help.recom.search.h3'                   => 'Arama Kutusu',
    'help.recom.search.text'                 => 'Cümle listesi uzadığında arama kutusuna yazabilirsiniz. Yazdığınız harflerle <strong>başlayan</strong> cümleler liste halinde görülür. Türkçe karakterler ayırt edilir — "u" yazarsanız "U" ile başlayanlar, "ü" yazarsanız "Ü" ile başlayanlar gelir.',

    // Section: Series and Chronology
    'help.chrono.h2'                         => 'Seriler ve Kronoloji',
    'help.chrono.intro'                      => 'Birbirine bağlı animeler için iki tür ilişki sistemi var:',
    'help.chrono.series.h3'                  => 'Seri Bilgisi',
    'help.chrono.series.text'                => 'Bir anime\'nin hangi seriye ait olduğu <strong>seri adı</strong> ve <strong>medya türü</strong> (TV / Film / OVA / Special / ONA) ile belirlenir. Aynı seri adını paylaşan animeler anime detayında "Bağlı Animeler" bölümünde gözükür.',
    'help.chrono.next.h3'                    => 'Sonraki Seri (next_in_series)',
    'help.chrono.next.text'                  => 'Bir animeyi bitirince hangi animeyi izlemeniz gerektiği. Detay sayfasında "Sırada" kutusunda gözükür.',
    'help.chrono.markers.h3'                 => 'Kronoloji İşaretleri',
    'help.chrono.markers.text'               => 'Detective Conan gibi seriler için: "54. bölümden sonra 1. filmi izle" gibi bölüm seviyesinde işaretler tutulur. Detay sayfasında aktif uyarı olarak görülür, ayrı bir "Kronoloji" sayfasında da timeline halinde listelenir.',
    'help.chrono.warning_title'              => '<i class="fas fa-exclamation-triangle"></i> Dikkat:',
    'help.chrono.warning_body'               => 'Kronoloji işaretleri de sync\'te katalog otoritedir. Kendiniz marker eklediyseniz sync sonrası kaybolur.',

    // Section: Deletion warnings
    'help.delete.h2'                         => 'Silme Uyarıları',
    'help.delete.danger_title'               => '<i class="fas fa-trash-alt"></i> Geri Alınamaz Silmeler:',
    'help.delete.danger_list' => '<li><strong>Notlar</strong> alanını boşaltmak → sync geri getirmez</li>
            <li><strong>Kişisel Konu</strong> alanını boşaltmak → sync geri getirmez</li>
            <li>Anime silmek → kalıcı, izleme verisi dahil her şey gider</li>
            <li>Poster dosyası silmek → sync sırasında katalog posteri tekrar indirilir (ancak kendi yüklediğiniz poster geri gelmez)</li>',
    'help.delete.safe_title'                 => '<i class="fas fa-undo"></i> Geri Alınabilir (sync ile):',
    'help.delete.safe_list' => '<li>Konu alanını değiştirmek / boşaltmak → bir sonraki sync\'te katalog konusu geri gelir</li>
            <li>Anime ismi değiştirmek → sync\'te düzelir</li>
            <li>Tür listesi / yayın bilgisi değiştirmek → sync\'te düzelir</li>',

    // Section: Update system
    'help.update.h2'                         => 'Güncelleme Sistemi',
    'help.update.intro'                      => 'Anime Tracker\'ın kendisi zaman zaman yeni sürümlerle gelir. Liste Ayarları → "Güncelleme Kontrolü" düğmesi ile yeni sürüm olup olmadığını kontrol edebilirsiniz.',
    'help.update.flow_intro'                 => 'Yeni sürüm varsa, tek tıkla otomatik güncelleme yapılır:',
    'help.update.flow_list' => '<li>Sunucudan yeni sürüm indirilir</li>
        <li>Dosyalar yerinde güncellenir (<code>config.php</code>, <code>uploads/</code> ve izleme verileriniz korunur)</li>
        <li>Veritabanı gerekirse otomatik güncellenir</li>
        <li>Sayfa yenilenir, yeni sürüm aktif</li>',
    'help.update.safe_title'                 => '<i class="fas fa-shield-alt"></i> Güncelleme Sırasında Kaybolmaz:',
    'help.update.safe_body'                  => 'Animeleriniz, izleme verileriniz, notlarınız, poster\'leriniz, DB kimlik bilgileriniz — hiçbiri etkilenmez.',

    // Section: Timezone
    'help.tz.h2'                             => 'Saat Dilimi — Yayın Saati Nasıl Gösterilir?',
    'help.tz.intro'                          => 'Anime Tracker tüm tarih ve saatleri veritabanında <strong>UTC</strong> olarak saklar. Gösterirken her animenin kendi yayın saat dilimine çevirir.',
    'help.tz.bc_tz.h3'                       => 'Yayın Saat Dilimi (animenin TZ\'i)',
    'help.tz.bc_tz.text'                     => 'Anime ekleme/düzenleme formundaki "Yayın Saat Dilimi" alanı. Listede 6 sabit seçenek var: Asia/Tokyo (JST), Europe/Istanbul (TRT), UTC, America/New_York (ET), America/Los_Angeles (PT), Europe/London. Çoğu Japon animesi için <code>Asia/Tokyo</code> doğru seçimdir.',
    'help.tz.autofill_title'                 => '<i class="fas fa-magic"></i> Hızlı Yol — Otomatik Doldur:',
    'help.tz.autofill_body'                  => 'AnimeSchedule URL\'sini girip "Otomatik Doldur" (Senkronize Et) butonuna basarsanız <strong>broadcast_day, broadcast_time ve broadcast_timezone alanları otomatik olarak Asia/Tokyo + Tokyo saati ile dolar</strong>. AnimeSchedule API\'si Japon animesi verilerini doğru şekilde Tokyo TZ\'de dönderir. Elle giriş yapmanıza gerek kalmaz.',
    'help.tz.workflows.h3'                   => 'İki Geçerli Workflow',
    'help.tz.workflows.intro'                => 'TZ alanı ve saat alanı aynı saat dilimini yansıtmalı. İki yol da geçerli:',
    'help.tz.workflows.list' => '<li><strong>Animenin yayın yeri:</strong> "JST" seç + Tokyo saatini gir (örn. 23:30). Anime detay sayfası "23:30 (JST)" gösterir, kendi saatinizi manuel hesaplarsınız. AnimeSchedule Otomatik Doldur bu yöntemi kullanır.</li>
        <li><strong>Kendi yerel saatiniz:</strong> "TRT" seç + Türkiye saatini 24 saat formatında gir (örn. 17:30). Anime detay sayfası "17:30 (TRT)" gösterir, doğrudan okunabilir. AnimeSchedule sitesinden manuel okuyorsanız site zaten Türkiye saatini gösteriyor; sadece am/pm\'i 24 saate çevirip yazın.</li>',
    'help.tz.consistency'                    => 'Önemli: TZ seçimi ile saat alanı <strong>tutarlı</strong> olmalı. "JST" seçip Türkiye saatini girerseniz veya "TRT" seçip Tokyo saatini girerseniz, "sonraki bölüm ne zaman" hesabı yanlış olur.',
    'help.tz.box_animeschedule_title'        => '<i class="fas fa-info-circle"></i> AnimeSchedule sitesi ne gösterir:',
    'help.tz.box_animeschedule_body'         => 'AnimeSchedule sitesini tarayıcıdan açarsanız saatleri <strong>am/pm (12 saat) formatında</strong>, <strong>sizin lokal TZ\'inizde</strong> gösterir (Türkiye\'den ziyaret edenlere Türkiye saati, başka ülkeden ziyaret edenlere o ülkenin saati). Manuel doldurma yapıyorsanız: formda lokal TZ\'inizi seçin (Türkiye için TRT), am/pm\'i 24 saat formatına çevirin (örn. "5:30 PM" -> 17:30), "Yayın Saati" alanına yazın. Site Tokyo TZ\'ini göstermez — Tokyo TZ verisi sadece "Otomatik Doldur" butonu ile AnimeSchedule API\'sinden doğrudan çekilir.',
    'help.tz.box_dst_title'                  => '<i class="fas fa-info-circle"></i> Yaz/Kış Saati (DST):',
    'help.tz.box_dst_body'                   => 'Animenin yayınlandığı TZ yaz/kış saati kullanıyorsa (Avrupa, ABD gibi), yayın saati yılda 2 kez 1 saat kayar (Mart sonu ve Ekim sonu). Asia/Tokyo DST kullanmadığı için Japon anime saatleri yıl boyu sabittir.',
    'help.tz.upgrade.h3'                     => 'Eski v0.5 Kurulumlardan Yükseltme',
    'help.tz.upgrade.text'                   => 'v0.5.1\'e geçtikten sonra hiçbir veriniz kaybolmaz. Yayın saatleri aynı görünür (Asia/Tokyo varsayılan TZ\'de eklenmiş kayıtlar hâlâ Asia/Tokyo\'da). Anime detay sayfasında yayın saatinin yanında TZ etiketi (JST, vs.) gözükür.',

    // Footer
    'help.footer'                            => 'Daha fazla sorunuz için: daha fazla ayrıntılı teknik bilgi proje <a href="https://github.com/hitsumo/animetracker" target="_blank" rel="noopener">GitHub sayfasında</a> bulunur.',

    // -----------------------------------------------------------------
    // statistics.php
    // -----------------------------------------------------------------
    'statistics.page_title'                  => 'İstatistikler - Anime Tracker',
    'statistics.heading'                     => 'İstatistikler',
    'statistics.tab.user'                    => 'Kullanıcı İstatistiği',
    'statistics.tab.global'                  => 'Global İstatistik',
    'statistics.tab.recent_watched'          => 'Son İzlenenler',
    'statistics.label.total_anime'           => 'Toplam Anime',
    'statistics.label.total_watched'         => 'Toplam İzlenen Bölüm',
    'statistics.label.total_episodes'        => 'Toplam Bölüm',
    'statistics.section.by_media'            => 'Medya Türüne Göre',
    'statistics.section.by_broadcast'        => 'Yayın Durumuna Göre',
    'statistics.section.by_watch'            => 'İzleme Durumuna Göre',
    'statistics.col.type'                    => 'Tür',
    'statistics.col.status'                  => 'Durum',
    'statistics.col.count'                   => 'Adet',
    'statistics.col.last_watched'            => 'Son İzleme',
    'statistics.value.unspecified'           => 'Belirtilmemiş',
    'statistics.section.by_emotion'          => 'Duygulara Göre',
    'statistics.col.emotion'                 => 'Duygu',
    'statistics.emotion.summary'             => 'Toplam %d işaret, %d anime.',
    'statistics.emotion.empty'               => 'Henüz hiçbir animeye duygu işareti koymamışsın. Anime detay sayfasındaki duygu butonlarıyla işaret ekleyebilirsin.',
    'statistics.emotion.empty_global'        => 'Henüz hiçbir animeye duygu işareti konmamış.',
    // 1.1.5: kisisel duygu rozetine tiklama ipucu (o duygudaki animeleri listeler)
    'statistics.emotion.filter_hint'         => 'Bu duyguyla işaretlediğin animeleri listele',
    'statistics.recent_watched.empty'        => 'Henüz izleme aktiviten yok. Bir animenin bölümünü izlediğinde burada görünür.',

    // -----------------------------------------------------------------
    // recent.php - son duzenlenen 5 anime
    // -----------------------------------------------------------------
    'recent.page_title'                      => 'Son Güncellenenler - Anime Tracker',
    'recent.heading'                         => 'Son Güncellenenler',
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

    // 0.6.5 - emotion filter integration (KARARLAR Bolum 8 devir borc kapanisi).
    // tag (cumle) + emotion bucket'lari paralel calisir, OR mantigi:
    // score = tag_score + emo_score. Eski tag-only anahtarlar bozulmaz;
    // emotion seciliyse alttaki _combined varyantlar devreye girer.
    'recommendations.emotion.toggle.show'           => 'Duyguları Göster',
    'recommendations.emotion.toggle.hide'           => 'Duyguları Gizle',
    'recommendations.emotion.toggle.count_selected' => '(%d duygu seçili)',
    'recommendations.emotion.empty_marks'           => 'Henüz hiçbir animeye duygu işareti koymamışsın. Anime detay sayfasında duygu butonlarıyla işaret ekleyebilirsin.',
    'recommendations.matched.emotion_prefix'        => 'Eşleşen duygular:',
    'recommendations.no_match_combined'             => 'Seçtiğin cümle ve duygularla eşleşen anime bulunamadı. Daha az kriter seçip tekrar dene.',
    'recommendations.result.count_combined'         => '<strong>%d</strong> anime bulundu (%d cümle, %d duygu seçildi).',
    'recommendations.group.matched_combined'        => '%d kriter eşleşti',

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
    // 1.1.13 - Liste Ayarlari sekme etiketleri
    'list_settings.tab.import_export'        => 'İçe/Dışa Aktar',
    'list_settings.tab.general_settings'     => 'Genel Ayarlar',
    'list_settings.tab.management'           => 'Yönetim Ayarları',
    'list_settings.tab.clear'                => 'Temizleme',
    'list_settings.csrf.invalid'             => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.',
    'list_settings.version.unknown'          => 'bilinmiyor',
    'list_settings.aired.cancelled_prefix'   => 'Senkronizasyon iptal edildi:',
    'list_settings.aired.no_api_key'         => 'AnimeSchedule API anahtari config.php icinde tanimli degil.',
    'list_settings.aired.rate_limit'         => 'API istek limiti asildi. Birkac dakika sonra tekrar deneyin.',
    'list_settings.aired.invalid_key'        => 'API anahtari gecersiz. config.php yi kontrol edin.',
    'list_settings.aired.result.updated'     => '%d anime guncellendi',
    'list_settings.aired.result.unchanged'   => '%d degismedi',
    'list_settings.aired.result.started'     => '%d yayina basladi',
    'list_settings.aired.result.finished'    => '%d tamamlandi',
    'list_settings.aired.result.not_in_table' => '%d takvimde bulunamadi',
    'list_settings.aired.result.no_slug'     => '%d AnimeSchedule URL si yok',
    'list_settings.aired.result.errors'      => '%d hata',
    'list_settings.import.result'            => '%d anime içe aktarıldı, %d atlandı.',
    'list_settings.import.markers'           => '%d kronoloji notu bağlandı, %d atlandı.',
    'list_settings.import.invalid_format'    => 'Lütfen geçerli bir JSON dosyası yükleyin!',
    'list_settings.import.online_result'     => 'İçe aktarma tamamlandı: %d anime listenize eklendi, %d yeni katalog önerisi oluşturuldu, %d zaten önerilmişti.',
    'list_settings.import.upload_error'      => 'Dosya yüklenemedi (hata kodu: %d). Lütfen tekrar deneyin.',
    'list_settings.import.read_failed'       => 'Yüklenen dosya okunamadı. Dosya boyutu veya sunucu ayarları engelliyor olabilir.',
    'list_settings.clear.success'            => 'Liste başarıyla temizlendi!',
    'list_settings.section.export'           => 'Listeyi Dışa Aktar',
    'list_settings.section.export.desc'      => 'Mevcut anime listenizi JSON formatında dışa aktarın.',
    'list_settings.btn.export'               => 'Listeyi Dışa Aktar',
    'list_settings.section.import'           => 'Listeyi İçe Aktar',
    'list_settings.section.import.desc'      => 'Önceden dışa aktarılmış bir listeyi içe aktarın.',
    'list_settings.btn.choose_file'          => 'Dosya Seç',
    'list_settings.btn.import'               => 'Listeyi İçe Aktar',

    // MAL liste ice aktarma (1.1.1)
    'list_settings.section.mal_import'       => 'MyAnimeList Listesini İçe Aktar',
    'list_settings.section.mal_import.desc'  => 'MyAnimeList dışa aktarma dosyanızı (XML veya .gz) yükleyin. Önce bir önizleme gösterilir; onaylamadan hiçbir şey kaydedilmez.',
    'list_settings.mal.btn.choose_file'      => 'MAL Dosyası Seç',
    'list_settings.mal.btn.preview'          => 'Önizle',
    'list_settings.mal.btn.commit'           => 'İçe Aktar',
    'list_settings.mal.btn.cancel'           => 'İptal',
    'list_settings.mal.err.upload'           => 'Dosya yüklenemedi (hata kodu %d).',
    'list_settings.mal.err.read'             => 'Dosya okunamadı.',
    'list_settings.mal.err.parse'            => 'MAL dosyası çözümlenemedi. Geçerli bir MyAnimeList dışa aktarma dosyası seçin.',
    'list_settings.mal.err.empty'            => 'Dosyada içe aktarılacak anime bulunamadı.',
    'list_settings.mal.err.session'          => 'Önizleme süresi doldu. Lütfen dosyayı tekrar yükleyin.',
    'list_settings.mal.preview.summary'      => 'Toplam %d kayıt okundu: %d katalogda eşleşti, %d zaten listenizde, %d katalogda yok.',
    'list_settings.mal.preview.status_filter' => 'İçe aktarılacak durumlar:',
    'list_settings.mal.preview.overwrite'    => 'Listemde zaten olan kayıtların üzerine yaz',
    'list_settings.mal.preview.unmatched_note.online'   => 'Katalogda olmayanlar katalog önerisi olarak gönderilecek.',
    'list_settings.mal.preview.unmatched_note.selfhost' => 'Katalogda olmayanlar yerel olarak eklenecek.',
    'list_settings.mal.result'               => 'İçe aktarma tamamlandı: %d yazıldı, %d atlandı (zaten listede), %d öneri/eklendi.',
    'list_settings.section.anilist_import'      => 'AniList Listesini İçe Aktar',
    'list_settings.section.anilist_import.desc' => 'AniList kullanıcı adınızı girin; herkese açık anime listeniz AniList üzerinden çekilir. Önce bir önizleme gösterilir; onaylamadan hiçbir şey kaydedilmez. (Listenizin herkese açık olması gerekir.)',
    'list_settings.anilist.username_label'      => 'AniList kullanıcı adı',
    'list_settings.anilist.username_placeholder' => 'örn. kullaniciadi',
    'list_settings.anilist.btn.preview'         => 'Önizle',
    'list_settings.anilist.btn.commit'          => 'İçe Aktar',
    'list_settings.anilist.btn.cancel'          => 'İptal',
    'list_settings.anilist.err.bad_username'    => 'Geçersiz kullanıcı adı. Yalnızca harf, rakam, alt çizgi ve tire kullanın.',
    'list_settings.anilist.err.network'         => 'AniList sunucusuna ulaşılamadı. İnternet bağlantınızı kontrol edip tekrar deneyin.',
    'list_settings.anilist.err.rate_limit'      => 'AniList istek sınırına ulaşıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
    'list_settings.anilist.err.notfound'        => 'AniList kullanıcısı bulunamadı. Kullanıcı adını kontrol edin.',
    'list_settings.anilist.err.http'            => 'AniList’ten beklenmeyen bir yanıt alındı. Lütfen sonra tekrar deneyin.',
    'list_settings.anilist.err.parse'           => 'AniList yanıtı çözümlenemedi. Lütfen sonra tekrar deneyin.',
    'list_settings.anilist.err.empty'           => 'Bu kullanıcının içe aktarılacak (herkese açık) anime listesi bulunamadı.',
    'list_settings.anilist.err.session'         => 'Önizleme süresi doldu. Lütfen kullanıcı adını tekrar girin.',
    'list_settings.anilist.err.source_limit'    => 'En fazla %d farklı AniList hesabından içe aktarabilirsin. Daha önce aktardığın hesapları sınırsız yeniden senkronlayabilirsin; yeni bir hesap için yöneticiden sıfırlama iste.',
    'list_settings.anilist.preview.summary'     => 'Toplam %d kayıt okundu: %d katalogda eşleşti, %d zaten listenizde, %d katalogda yok.',
    'list_settings.anilist.preview.mode'        => 'İçe aktarma türü:',
    'list_settings.anilist.preview.mode.list'   => 'Listeyi izleme durumlarıyla aktar (durum, bölüm, tarih, not)',
    'list_settings.anilist.preview.mode.content' => 'Sadece içeriği aktar (kişisel izleme durumları alınmaz)',
    'list_settings.anilist.preview.status_filter' => 'İçe aktarılacak durumlar:',
    'list_settings.anilist.preview.overwrite'   => 'Listemde zaten olan kayıtların üzerine yaz',
    'list_settings.anilist.preview.overwrite_hint' => 'Yalnızca "durumlarıyla aktar" türünde geçerlidir; "sadece içerik" türünde yok sayılır.',
    'list_settings.anilist.preview.unmatched_note.online'   => 'Katalogda olmayanlar katalog önerisi olarak gönderilecek.',
    'list_settings.anilist.preview.unmatched_note.selfhost' => 'Katalogda olmayanlar yerel olarak eklenecek.',
    'list_settings.anilist.result'              => 'İçe aktarma tamamlandı: %d yazıldı, %d atlandı (zaten listede), %d öneri/eklendi.',
    'list_settings.anilist.result_content'      => 'İçerik aktarımı tamamlandı: %d yeni katalog kaydı eklendi/önerildi, %d zaten katalogda mevcuttu.',

    'list_settings.section.clear'            => 'Listeyi Temizle',
    'list_settings.section.clear.desc'       => 'DİKKAT: Bu işlem geri alınamaz!',
    'list_settings.btn.clear'                => 'Listeyi Temizle',
    'list_settings.section.language'         => 'Arayüz Dili',
    'list_settings.section.language.desc'    => 'Menülerin, etiketlerin ve butonların dili. Bu tercih başlık dilinden bağımsızdır.',
    'list_settings.language.option_tr'       => 'Türkçe',
    'list_settings.language.option_en'       => 'English',
    'list_settings.language.save'            => 'Kaydet',

    'list_settings.section.title_lang'       => 'Başlık Dili',
    'list_settings.section.title_lang.desc'  => 'Anime başlıklarının İngilizce karşılığı varsa, liste ve detay sayfalarında Romaji başlık yerine İngilizce başlığı göster. Bu tercih arayüz dilinden bağımsızdır.',
    'list_settings.title_lang.checkbox'      => 'İngilizce başlıkları göster',
    'list_settings.title_lang.save'          => 'Kaydet',

    // 1.1.13 - varsayilan liste sekmesi tercihi (Genel / Kisisel)
    'list_settings.section.list_view'        => 'Varsayılan Liste',
    'list_settings.section.list_view.desc'   => 'Anime listesi sayfası açıldığında hangi sekmenin seçili geleceğini belirler. Genel Liste tüm kataloğu, Kişisel Liste ise yalnızca bir izleme durumu seçtiğiniz animeleri gösterir. Bu tercih yalnızca sizi etkiler.',
    'list_settings.list_view.option_all'     => 'Genel Liste',
    'list_settings.list_view.option_personal' => 'Kişisel Liste',
    'list_settings.list_view.save'           => 'Kaydet',

    // 1.1.2 - yetiskin (+18) icerik gorunurluk toggle (list_settings)
    'list_settings.section.adult'            => 'Yetişkin İçerik',
    'list_settings.section.adult.desc'       => 'Varsayılan olarak kapalıdır. Açılırsa +18 işaretli animeler liste, arama, öneri ve istatistiklerde görünür. Bu tercih yalnızca sizi etkiler.',
    'list_settings.adult.checkbox'           => 'Yetişkin içeriği göster',
    'list_settings.adult.save'               => 'Kaydet',
    'list_settings.section.genres'           => 'Tür Yönetimi',
    'list_settings.section.genres.desc'      => 'Yanlış yazılan veya kullanılmayan türleri yönetin.',
    'list_settings.btn.manage_genres'        => 'Türleri Yönet',
    'list_settings.section.tags'             => 'Cümle Yönetimi',
    'list_settings.section.tags.desc'        => 'Yanlış yazılan veya kullanılmayan cümleleri yönetin.',
    'list_settings.btn.manage_tags'          => 'Cümleleri Yönet',
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
    'list_settings.update.online_note'       => 'Online (çok kullanıcılı) kurulum, uygulama içi buton ile değil, kaynak depodan (git / Docker) güncellenir.',
    'list_settings.update.github_link'       => 'GitHub deposu',
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

    // -----------------------------------------------------------------
    // manage_tags.php - sentence (tag) library management
    // -----------------------------------------------------------------
    'manage_tags.title'                      => 'Cümle Yönetimi',
    'manage_tags.intro'                      => 'Cümleler öneri sisteminde kullanıcılara gösterilir. Anime ekleme/düzenleme ekranında yeni cümle yazınca otomatik oluşur. Buradan yazım hatalarını düzeltebilir veya gereksiz cümleleri silebilirsin. Cümleyi tam olarak kullanıcının göreceği şekilde yaz (örn: "Okulda geçsin", "Spor teması olsun").',
    'manage_tags.placeholder'                => 'Yeni cümle (örn: Okulda geçsin, Spor teması olsun)',
    'manage_tags.ph.name_en'                 => 'İngilizce karşılığı',
    'manage_tags.btn.add'                    => 'Ekle',
    'manage_tags.th.tag'                     => 'Cümle',
    'manage_tags.th.usage'                   => 'Kullanım',
    'manage_tags.th.rename'                  => 'Yeniden Yaz',
    'manage_tags.adult.label'                => '+18',
    'manage_tags.th.delete'                  => 'Sil',
    'manage_tags.usage_suffix'               => 'anime',
    'manage_tags.empty'                      => 'Henüz cümle yok. Yukarıdaki formdan ilk cümleni ekleyebilirsin.',
    'manage_tags.btn.delete'                 => 'Sil',
    'manage_tags.confirm_delete'             => '"%s" cümlesini silmek istediğinize emin misiniz? %d animeden kaldırılacak.',
    'manage_tags.back_to_list'               => 'Anime Listesine Dön',
    'manage_tags.csrf.invalid'               => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.',
    'manage_tags.err.empty'                  => 'Cümle boş olamaz.',
    'manage_tags.msg.added'                  => 'Cümle eklendi (veya zaten mevcuttu): %s',
    'manage_tags.err.rename_missing'         => 'Eksik bilgi: cümle ID veya yeni metin boş.',
    'manage_tags.msg.renamed'                => 'Cümle güncellendi.',
    'manage_tags.err.invalid_id'             => 'Geçersiz cümle ID.',
    'manage_tags.msg.deleted'                => 'Cümle silindi.',
    'manage_tags.err.unknown_action'         => 'Bilinmeyen işlem.',
    'manage_tags.err.duplicate'              => 'Bu cümle zaten var.',
    'manage_tags.err.db'                     => 'Veritabanı hatası oluştu.',

    // -----------------------------------------------------------------
    // manage_genres.php - master genre list management
    // -----------------------------------------------------------------
    'manage_genres.title'                    => 'Tür Yönetimi',
    'manage_genres.th.name'                  => 'Tür Adı',
    'manage_genres.th.name_en'               => 'İngilizce Adı',
    'manage_genres.ph.name_en'               => 'İngilizce adı',
    'manage_genres.btn.save_en'              => 'Kaydet',
    'manage_genres.adult.label'              => '+18',
    'manage_genres.th.action'                => 'İşlem',
    'manage_genres.confirm_delete'           => 'Bu türü silmek istediğinize emin misiniz? Bu türü kullanan animelerden de otomatik olarak kaldırılacaktır.',
    'manage_genres.btn.delete'               => 'Sil',
    'manage_genres.back_to_list'             => 'Anime Listesine Dön',
    'manage_genres.csrf.invalid'             => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.',


    // -----------------------------------------------------------------
    // filler_edit.php - per-episode filler / canon grid editor (0.7)
    // Type labels (Manga Canon / Anime Canon / Karisik / Dolgu) come from
    // functions.php filler_type_label(), NOT from here. Only the page
    // chrome + JS strings live in the dictionary.
    // -----------------------------------------------------------------
    'filler.title_suffix'      => 'Dolgu Bölümleri',
    'filler.subtitle'          => 'Bölüm bazında dolgu / canon işaretleme',
    'filler.instructions'      => 'Bir bölüme tıklayarak tipini değiştirin: işaretsiz → Manga Canon → Anime Canon → Karışık → Dolgu. Sadece istisnaları işaretlemeniz yeterli; işaretsiz bölümler canon sayılır.',
    'filler.type.unmarked'     => 'İşaretsiz',
    'filler.guard.no_count'    => 'Bu anime için bölüm sayısı tanımlı değil. Grid oluşturmak için önce toplam veya yayınlanan bölüm sayısını girin.',
    'filler.guard.set_count'   => 'Bölüm sayısını düzenle',
    'filler.save'              => 'Kaydet',
    'filler.back_to_details'   => 'Detaylara dön',
    'filler.js.saving'         => 'Kaydediliyor...',
    'filler.js.saved'          => 'Kaydedildi.',
    'filler.js.save_error'     => 'Kaydetme başarısız oldu. Lütfen tekrar deneyin.',
    'filler.js.marked_count'   => '%d bölüm işaretli',

    // anime_details.php - filler ozet satiri (0.7)
    'anime_details.label.filler'    => 'Bölüm detayları:',
    'anime_details.btn.filler_edit' => 'Düzenle',
    'anime_details.filler_empty'    => 'Henüz bölüm işaretlenmedi.',

    // add_anime.php / edit_anime.php - filler izleme toggle (0.7)
    'add_anime.label.filler_tracking'     => 'Filler bölüm izleme:',
    'add_anime.hint.filler_tracking'  => 'Bölüm bazında dolgu/canon işaretlemeyi etkinleştirir (sonradan da açıp kapatabilirsiniz).',

    // add_anime.php / edit_anime.php - yetiskin (+18) icerik bayragi (1.1.2)
    'add_anime.label.is_adult'     => '+18 / Yetişkin içerik:',
    'add_anime.hint.is_adult'  => 'İşaretlenirse bu anime varsayılan olarak gizlenir; göstermek için Liste Ayarları\'ndan "Yetişkin içeriği göster" seçeneğini açın.',


    // Filler import (AnimeFillerList) - 0.7
    'filler.import.placeholder'  => 'AnimeFillerList show adresi (ör. .../shows/detective-conan)',
    'filler.import.button'       => 'AnimeFillerList\'ten içe aktar',
    'filler.js.importing'        => 'İçe aktarılıyor...',
    'filler.js.imported_count'   => '%d bölüm yüklendi.',
    'filler.js.import_skipped'   => '%d bölüm, bölüm sayısı dışında kaldı (atlandı).',
    'filler.js.import_review'    => 'Gözden geçirip Kaydet\'e basın.',
    'filler.js.import_need_url'  => 'Lütfen bir AnimeFillerList adresi girin.',
    'filler.js.import_error'     => 'İçe aktarma başarısız oldu.',

    // =====================================================================
    // Auth (Faz 2 / Milestone 2). Login, logout, account pages + nav links.
    // =====================================================================
    'nav.login'   => 'Giriş',
    'nav.logout'  => 'Çıkış',
    'nav.account' => 'Hesap',
    'nav.register' => 'Kayıt ol',

    'auth.login.page_title' => 'Giriş',
    'auth.login.heading'    => 'Giriş Yap',
    'auth.login.username'   => 'Kullanıcı adı',
    'auth.login.password'   => 'Şifre',
    'auth.login.submit'     => 'Giriş yap',
    'auth.login.error'      => 'Kullanıcı adı veya şifre hatalı.',
    'auth.login.empty'      => 'Kullanıcı adı ve şifre gerekli.',

    'auth.logout.page_title' => 'Çıkış',
    'auth.logout.heading'    => 'Çıkış Yap',
    'auth.logout.confirm'    => 'Oturumu kapatmak istiyor musunuz?',
    'auth.logout.submit'     => 'Çıkış yap',

    'auth.account.page_title'           => 'Hesap',
    'auth.account.heading'              => 'Hesap Ayarları',
    'auth.account.username_label'       => 'Kullanıcı adı',
    'auth.account.email_label'          => 'E-posta',
    'auth.account.role_label'           => 'Rol',
    'auth.account.email_empty'          => '(belirtilmemiş)',
    'auth.account.change_password'      => 'Şifre Değiştir',
    'auth.account.current_password'     => 'Mevcut şifre',
    'auth.account.new_password'         => 'Yeni şifre',
    'auth.account.new_password_confirm' => 'Yeni şifre (tekrar)',
    'auth.account.submit'               => 'Şifreyi güncelle',
    'auth.account.success'              => 'Şifre güncellendi.',
    'auth.account.err_empty'            => 'Tüm alanlar gerekli.',
    'auth.account.err_current'          => 'Mevcut şifre hatalı.',
    'auth.account.err_short'            => 'Yeni şifre en az 8 karakter olmalı.',
    'auth.account.err_mismatch'         => 'Yeni şifreler eşleşmiyor.',

    'auth.register.page_title'          => 'Kayıt',
    'auth.register.heading'             => 'Hesap Oluştur',
    'auth.register.intro_invite'        => 'Kayıt davetlidir. Hesap oluşturmak için davet kodunuzu girin.',
    'auth.register.token'               => 'Davet kodu',
    'auth.register.username'            => 'Kullanıcı adı',
    'auth.register.email'               => 'E-posta (isteğe bağlı)',
    'auth.register.password'            => 'Şifre',
    'auth.register.password_confirm'    => 'Şifre (tekrar)',
    'auth.register.submit'              => 'Hesap oluştur',
    'auth.register.have_account'        => 'Zaten hesabınız var mı? Giriş yapın',
    'auth.register.err_generic'         => 'Kayıt tamamlanamadı. Lütfen tekrar deneyin.',
    'auth.register.err_token_required'  => 'Davet kodu gerekli.',
    'auth.register.err_token_invalid'   => 'Davet kodu geçersiz veya kullanılmış.',
    'auth.register.err_username_invalid' => 'Kullanıcı adı 3-32 karakter olmalı (harf, rakam, alt çizgi).',
    'auth.register.err_username_taken'  => 'Bu kullanıcı adı kullanılıyor.',
    'auth.register.err_email_invalid'   => 'E-posta adresi geçersiz.',
    'auth.register.err_email_taken'     => 'Bu e-posta adresi kullanılıyor.',
    'auth.register.err_password_short'  => 'Şifre en az 8 karakter olmalı.',
    'auth.register.err_password_mismatch' => 'Şifreler eşleşmiyor.',
    'auth.register.request_invite'      => 'Davetin yok mu? Davetiye talep et',
    'invite_request.page_title'         => 'Davetiye Talebi',
    'invite_request.heading'            => 'Davetiye Talep Et',
    'invite_request.intro'              => 'Bu site davetlidir. Hesap açabilmek için bir davetiye gerekir. E-posta adresini ve neden davetiye istediğini yaz; talebin yöneticiye iletilir.',
    'invite_request.email_label'        => 'E-posta adresin',
    'invite_request.reason_label'       => 'Neden davetiye istiyorsun?',
    'invite_request.reason_hint'        => 'Kısaca kendinden ve siteyi nasıl bulduğundan bahset. Bu, istenmeyen taleplerin ayıklanmasına yardımcı olur.',
    'invite_request.submit'             => 'Talebi gönder',
    'invite_request.back_to_register'   => 'Davet kodun var mı? Kayıt sayfasına dön',
    'invite_request.ok'                 => 'Talebin alındı. Uygun görülürse sana bir davet kodu iletilecek.',
    'invite_request.err'                => 'Talep gönderilemedi. Lütfen e-posta ve neden alanlarını kontrol edip tekrar dene.',
    'invite_request.rate'               => 'Çok fazla talep gönderdin. Lütfen bir süre sonra tekrar dene.',
    'invite_request.full'               => 'Şu anda yeni davetiye talebi alınmıyor. Kontenjan dolu; lütfen daha sonra tekrar dene.',
    'invite_request.mail.subject'       => 'Yeni davetiye talebi',
    'invite_request.mail.line_email'    => 'E-posta:',
    'invite_request.mail.line_reason'   => 'Neden:',

];
