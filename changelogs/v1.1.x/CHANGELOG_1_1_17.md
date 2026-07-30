# Anime Tracker 1.1.17

**Yayın tarihi:** 2026-07-19

## Yeni: Ülkeye göre filtreleme

- **Ana listede artık ülkeye göre filtreleme yapılabilir.** Filtre kutusunda,
  yayın durumu filtresinin hemen altında yeni bir "Ülkeye Göre Filtrele"
  açılır kutusu vardır. Seçim, diğer filtrelerle (tür, izleme durumu, yayın
  durumu, harf, yıl) birlikte çalışır.
- **Açılır kutu sabit bir ülke listesi değildir: yalnızca katalogda gerçekten
  girilmiş ülkeleri gösterir.** Katalogda hiç Kore yapımı yoksa "Güney Kore"
  seçeneği görünmez, dolayısıyla boş sonuç veren bir filtre seçmeniz mümkün
  olmaz. Siz animelere ülke girdikçe seçenekler kendiliğinden belirir.
- **Hiçbir animede ülke girilmemişse** açılır kutu yerine "Henüz kayıtlı ülke
  yok." bilgisi görünür.
- **Yetişkin içerik tercihi burada da geçerlidir.** Yetişkin içerik kapalıyken,
  yalnızca +18 animelerde geçen bir ülke listede görünmez - yıl filtresindeki
  davranışın aynısı.
- **Filtre, sıralama ve sayfalama gezinirken korunur.** Sütun başlığına
  tıklayıp sıralamayı değiştirdiğinizde, harf seçtiğinizde, sayfa
  değiştirdiğinizde veya arama yaptığınızda ülke seçiminiz düşmez. Bu,
  "İzlenen Bölüme Göre" sıralaması için de geçerlidir.

## Yeni: Anime kaydında yapım ülkesi

- **Anime ekleme ve düzenleme formlarına "Yapım Ülkesi" alanı eklendi**
  (opsiyonel). Medya Türü alanının hemen altındadır.
- **Ülke listeden seçilir, elle yazılmaz.** Başlangıç listesi: Amerika
  Birleşik Devletleri, Çin, Fransa, Güney Kore, Japonya, Tayvan.
- **Ülke adı arayüz diline göre görünür.** Türkçe arayüzde "Japonya",
  İngilizce arayüzde "Japan" yazar. Bunun sebebi, veritabanında ülke adının
  değil uluslararası ülke kodunun (JP, CN, KR ...) saklanmasıdır. Kodu ne
  girersiniz ne de görürsünüz; her yerde ülkenin adı gösterilir.
- **Serbest metin yerine liste kullanılmasının nedeni** katalogun ortak
  olmasıdır: serbest yazımda aynı ülke "Japonya", "Japan" ve "japonya" olarak
  üç ayrı filtre değeri üretir ve dil desteği mümkün olmazdı.
- **Anime detay sayfasında ülke, yayın tarihi bilgilerinin altında gösterilir.**
  Ülke girilmemişse satır hiç basılmaz - "Belirtilmemiş" yazan boş bir satır
  eklenmez.
- **Listeye yeni ülke eklemek tek satırlık bir iştir:**
  `functions/country_helpers.php` içindeki `country_codes()` haritasına bir
  satır ve `lang/tr.php` + `lang/en.php` dosyalarına ülkenin adı eklenir.

## Mevcut animeler

- **Mevcut animelerin hiçbirine ülke atanmaz.** Sürüm yükseltmesi yalnızca
  alanı ekler; hiçbir satır tahmin yoluyla damgalanmaz. Dolayısıyla ülke
  filtresi ilk açılışta boş görünür ve siz doldurdukça dolar.
- Bunun nedeni, katalogda Çin ve Kore yapımı animelerin de bulunabilmesidir;
  "hepsi Japonya" varsayımı gözden kaçan yapımları yanlış ülkede bırakırdı.

## Toplu doldurma aracı (tek seferlik, isteğe bağlı)

- **`tek_kullanimlik/anilist_country_backfill.php`**, ülkesi boş olan animeleri
  AniList'e sorup size çalıştırılacak `UPDATE` cümleleri üretir. Ülke bilgisi
  tahmin edilmez: AniList'in `countryOfOrigin` alanı zaten bizim sakladığımız
  biçimde (ISO 3166-1 alpha-2) yapısal veri verir.
- **Veritabanına dokunmaz.** Ürettiği SQL'i inceleyip phpMyAdmin'de siz
  çalıştırırsınız:
  `php anilist_country_backfill.php > country_backfill.sql`
- **Elle girdiğiniz ülkeleri ezmez** - yalnızca `country IS NULL` satırları
  sorgular, ürettiği `UPDATE` de aynı koşulu taşır.
- **Ülke listesinde olmayan bir kod dönerse yazılmaz**, ayrıca raporlanır.
  Tanımlı olmayan bir kod veritabanına girseydi o anime hiçbir filtrede
  görünmezdi. Raporu görüp o ülkeyi listeye eklemek isteyip istemediğinize
  siz karar verirsiniz.
- Sınır: yalnızca `mal_id` taşıyan animeler eşleşir. Eşleşmeyenler ve
  `mal_id`'si olmayanlar sayı olarak raporlanır, elle doldurulur.

## AniList içe aktarma artık ülkeyi kendisi getirir

- **AniList'ten içe aktarılan her anime ülkesiyle birlikte gelir.** AniList
  `countryOfOrigin` alanını zaten veriyor; içe aktarma sorgusu artık bu alanı
  da istiyor ve değeri kayda taşıyor. Yani içe aktardığınız bir donghua
  katalogda doğrudan "Çin" olarak doğar, sonradan doldurulmayı beklemez.
- Bu hem online (öneri kaydına yazılır, moderatör onayında kataloğa geçer) hem
  self-host (yerel kayda doğrudan yazılır) tarafında geçerlidir.
- **Ülke listesinde olmayan bir kod gelirse yazılmaz, alan boş bırakılır.**
  Tanımsız bir kod kaydedilseydi o anime hiçbir filtrede görünmezdi.
- MyAnimeList (XML) içe aktarmasında ülke bilgisi yoktur; o yoldan gelen
  animeler ülkesiz kalır ve elle ya da toplu doldurma aracıyla tamamlanır.

## Ülke bilgisi taşınan yollar

Ülke, animeye ait bir katalog bilgisidir ve verinin dolaştığı her yolda
korunur:

- **Merkez kataloğa gönderme ve merkez katalogdan çekme** (online mod).
- **Yedek alma / geri yükleme** (self-host). Yedek dosyası ülkeyi içerir;
  1.1.17 öncesinde alınmış yedeklerde alan bulunmadığı için o kayıtlar ülkesiz
  geri yüklenir.
- **Üye önerisi ve moderatör onayı.** Öneri kaydı ülkeyi taşıyabilir ve onayda
  kataloğa aktarılır.

## Şema / migration

- `migration/1.1.17/upgrade.sql`, `animes` ve `catalog_requests` tablolarına
  `country` kolonunu ekler (`char(2)`, NULL = ülke girilmemiş) ve sürümü
  1.1.17'ye yükseltir. Tekrar çalıştırmada yinelenen-kolon hatası yok sayılır.
- **Merkez katalog için elle adım (yalnızca online):** merkez katalog
  veritabanı ayrı bir kurulumdur ve otomatik migration çalıştırıcısı yoktur.
  Ülke bilgisini oraya göndermeden önce merkez katalog DB'sinde bir kez
  çalıştırın:
  `ALTER TABLE animes ADD COLUMN country CHAR(2) DEFAULT NULL AFTER media_type;`
  Bu yapılmadan gönderim denenirse istek hata verir. Sıra önemlidir: önce
  sunucuda bu komut, sonra uygulama güncellemesi, sonra gönderim. Self-host
  kurulumları etkilenmez (yerel migration kendiliğinden çalışır).

## Değişen / yeni dosyalar

- files/migration/1.1.17/upgrade.sql (yeni: country kolonu, iki tablo)
- files/schema.sql (yeni kurulum için kolon; animes + catalog_requests)
- files/functions/country_helpers.php (yeni: country_codes / country_label /
  country_options / country_sort_key / is_valid_country_code)
- files/functions.php (yeni yardımcı dosyayı yükle)
- files/tek_kullanimlik/anilist_country_backfill.php (yeni: tek seferlik toplu
  doldurma aracı; DB'ye yazmaz, SQL üretir)
- files/index.php (katalogda geçen ülkelerin listesi; ülke filtresi kutusu;
  filtre koşulu `select_from`'a eklendi - böylece "İzlenen Bölüme Göre"
  sıralama dalı da aynı koşulu alır; sıralama/harf/yıl/arama bağlantılarında
  seçimin korunması)
- files/add_anime.php, files/edit_anime.php (Yapım Ülkesi açılır kutusu; kayıt
  öncesi kod doğrulaması)
- files/anime_details.php (ülke satırı; boşsa gösterilmez)
- files/functions/anilist_import_helpers.php (içe aktarma sorgusuna
  countryOfOrigin; kayda country alanı - tanınmayan kod boş bırakılır)
- files/list_settings.php (yedek geri yüklemede, üye önerisi oluşturmada ve
  AniList içe aktarmanın her iki dalında ülkeyi taşı)
- files/catalog_import.php, files/admin/catalog_push.php,
  files/admin/admin_sync_example.php, files/admin/admin_catalog_requests.php,
  catalog_server/catalog.php, catalog_server/admin_push.php
  (katalog veri formatı boyunca country)
- files/lang/tr.php, files/lang/en.php (ülke adları; form ve filtre etiketleri;
  anime detay etiketi)
- files/version.txt

Not: ülke adlarının sıralaması arayüz diline göre yapılır. Türkçe arayüzde
"Çin", "C" ile "D" arasında doğru yerde çıkar; PHP'nin varsayılan
karşılaştırması bu harfi listenin sonuna atacağı için sıralama Türk
alfabesine göre ayrıca düzenlenmiştir.
