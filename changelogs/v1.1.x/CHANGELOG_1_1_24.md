# Anime Tracker 1.1.24

**Yayın tarihi:** 2026-07-30

## Düzeltildi: yükseltmeden sonra bozuk görünen sayfalar

- **Sürüm yükseltmesinden sonra sayfa düzeninin bozulması sona erdi.** Stil
  ve betik dosyaları bugüne kadar hiç değişmeyen adreslerle linkleniyordu
  (`style.css`, `js/select_enhance.js`). Adres aynı kaldığı için tarayıcı,
  yükseltmeden sonra bile **eski** dosyayı kendi önbelleğinden servis etmeye
  devam ediyordu: yeni HTML, eski CSS ile buluşuyordu. 1.1.20 dağıtımının
  hemen ardından görülen bozuk görünümün sebebi tam olarak buydu.
- **Artık her yerel stil ve betik adresinin sonunda sürüm damgası var**
  (`css/base.css?v=1.1.24`). Sürüm değiştiği anda adres de değiştiği için
  tarayıcı dosyayı yeniden indirmek zorunda kalır. Yükseltmeden sonra
  Ctrl+F5 yapmaya, önbelleği elle temizlemeye gerek yok: sayfanın kendisi
  önbelleklenmediği için yeni HTML ilk açılışta gelir, damgalı adresleri
  görür ve taze CSS'i çeker.
- **Bu değişiklik sayfaları yavaşlatmaz.** Aynı sürüm boyunca adresler
  sabittir, yani dosyalar eskiden olduğu gibi süresiz önbelleklenmeye devam
  eder — yalnızca sürüm atladığında tazelenirler.
- **Stil dosyaları tek tek linklenir hâle geldi.** `style.css` yalnızca bir
  yükleyicidir; gerçek kurallar `css/` altındaki sekiz modüldedir ve
  `@import` ile çekilir. Sadece yükleyiciyi damgalamak işe yaramazdı:
  tarayıcı `style.css`'i yeniden okur, içindeki damgasız `@import`
  adreslerini görür ve `css/components.css`'i yine önbellekten verirdi.
  Sayfalar artık modüllerin her biri için damgalı bir link yazıyor —
  yükleyicinin `@import` sırası birebir korunarak, yani görsel sonuç aynı.

## Düzeltildi: yardım sayfasında var olmayan bir stil dosyası isteniyordu

- Yardım → Saat Dilimi alt sayfası, 0.6.7'deki dosya ayrışmasından beri var
  olmayan `help.css` dosyasını linklemeye devam ediyordu; istek her açılışta
  404 dönüyordu. Link kaldırıldı. Sayfanın görünümü değişmez — o stiller
  zaten `css/help.css` modülünden geliyordu.

## Nasıl çalışıyor (teknik)

- Yeni `files/functions/asset_helpers.php`: `asset_version()` damgayı
  `files/version.txt` içinden okur (`settings.version` satırından **değil** —
  damgalanan şeyler dosya olduğu için dosya sürümüyle eşleşmeleri gerekir;
  ayrıca kurulum sayfaları veritabanı yokken de çalışır). Değer URL'ye
  gireceği için doğrulanır; beklenmeyen bir içerik "sürüm yok" sayılır.
- `asset_styles()` bir sayfanın bütün stil linklerini üretir, `asset_url()`
  tek bir varlığın adresini kurar. İkisi de sayfanın `files/` köküne olan
  yolunu parametre alır: kökteki sayfalar için boş, `admin/` ve `help/`
  altındakiler için `../`.
- **Modül listesi `style.css`'in `@import` satırlarından çalışma anında
  okunur.** Böylece ikinci bir liste tutulmaz: yeni bir modül eklemek için
  yine yalnızca `style.css`'e `@import` yazmak yeterli. `style.css` kendi
  başına çalışan bir stil dosyası olarak da duruyor.
- Damgasız kalma ihtimalleri kapatıldı: `version.txt` okunamazsa dosyanın
  kendi değişiklik zamanı damga olarak kullanılır (geliştirme kopyasında da
  taze dosya gelir), `style.css` okunamazsa sayfa eski davranışa düşer ve
  yükleyiciyi linkler — hiçbir durumda stilsiz sayfa çıkmaz.
- Yardımcı, `functions.php` yükleyicisine eklendi; onu yüklemeyen üç sayfa
  (`setup`, `install`, `ai_notice` ve İngilizce eşleri) yardımcıyı doğrudan
  çağırır. Dosya bilerek kendi kendine yeter: veritabanı, dil dosyası ya da
  yapılandırma gerektirmez, bu yüzden uygulama kurulmadan önce de çalışır.

## Şema / migration

- **Şema değişikliği yok.** Değişiklik tamamen gösterim katmanındadır: ne
  yeni tablo/kolon ne yeni tercih var. `migration/1.1.24/upgrade.sql`
  yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — katalog teline
  dokunulmadı.

## Değişen / yeni dosyalar

- files/functions/asset_helpers.php (**yeni** — damga, varlık adresleri,
  modül listesi)
- files/functions.php (yardımcıyı yükleyiciye ekler)
- files/style.css (yalnızca yorum: `@import` listesinin artık modül
  listesi olarak da okunduğu not edildi)
- files/help/help_timezone.php (404 dönen `../help.css` linki kaldırıldı)
- Baş kısmında stil/betik linki olan bütün sayfalar (41 dosya):
  about, account, add_anime, ai_notice, ai_notice_en, anime_details,
  chronology, edit_anime, filler_edit, help, index, install, install_en,
  list_settings, login, logout, manage_genres, manage_tags, pending,
  recent, recommendations, register, request_invite, series_timeline,
  setup, setup_en, statistics; admin/ altında admin, admin_capabilities,
  admin_catalog_requests, admin_invites, admin_pending, admin_suggestions,
  admin_sync_example, admin_users; help/ altında help_basics,
  help_discovery, help_fields, help_series, help_sync, help_timezone
- files/migration/1.1.24/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt

## Dağıtım notu

- `files/functions/asset_helpers.php` **yeni** bir dosyadır ve
  `functions.php` üzerinden her sayfada yüklenir. Sunucuya yüklenmezse
  sayfalar ilk yardımcı çağrısında ölür, o yüzden bu sürümde dosya
  yüklemesinin eksiksiz olduğu doğrulanmalı.
- `css/` ve `js/` klasörlerinin içeriği değişmedi; damga adreste taşındığı
  için o dosyalara dokunmak gerekmez.
