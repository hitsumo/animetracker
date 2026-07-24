# Anime Tracker 1.1.20

**Yayın tarihi:** 2026-07-24

## Değişti: "İngilizce Başlık" kutusu kaldırıldı, dil alternatif isimlere taşındı

- **Anime ekleme/düzenleme formundaki ayrı "İngilizce Başlık" alanı kaldırıldı.**
  Bir animenin bütün isimleri artık tek bir yerde girilir: alternatif isim
  listesinde, her satırın yanındaki dil kutusuyla.
- **Her alternatif ismin dili işaretlenebilir:**

  ```
  Anime İsmi        : Tonari no Totoro
  Alternatif İsimler: My Neighbor Totoro    [İngilizce]
                      となりのトトロ          [Japonca]
                      Totoro                [Dil belirtilmedi]
  ```

- **"İngilizce" işaretlediğiniz isim, eskiden o kutunun yaptığı işi yapar:**
  Liste Ayarları'nda "İngilizce başlıkları göster" açıkken listede Romaji
  başlık yerine o gösterilir. Yani davranış aynı, giriş yeri tek.
- **Dil seçmek zorunlu değil.** Bir isme dil atamak istemiyorsanız kutu
  "— Dil belirtilmedi"de kalır; alternatif isim eskisi gibi çalışır.
- **Seçilebilen diller:** İngilizce, Japonca, Türkçe, Çince, Korece, Fransızca.
- **Bonus — arama genişledi.** Ana listedeki arama kutusu anime ismi ve
  alternatif isimler içinde arıyor, İngilizce başlık kutusuna yazdıklarınızda
  ise aramıyordu. İngilizce isim artık listenin bir parçası olduğu için
  "My Neighbor Totoro" araması sonuç veriyor.

## Neden dil başına yeni bir kutu değil

- **Türkçe başlık istendiğinde eski yol tıkanıyordu.** "İngilizce Başlık"
  kutusu tek bir dili anlatan çıkmaz bir sokaktı; Türkçe için ikinci bir kutu,
  Japonca için üçüncüsü gerekirdi. Her biri veritabanında yeni bir kolon,
  başlık gösteren koda yeni bir dal, katalog senkronuna yeni bir alan ve
  merkez katalog sunucusunda elle bir müdahale demekti — dil başına bir kez.
- **Dil, listenin içine taşındı.** Yeni bir dil eklemek artık tek satırlık bir
  iş: `title_lang_helpers.php` içindeki haritaya bir satır, dil dosyalarına bir
  satır. Veritabanına dokunulmaz.

## Nasıl saklanıyor (teknik)

- Dil bilgisi mevcut `animes.alternative_titles` metnine, her ismin önüne
  konan isteğe bağlı bir `[xx]` etiketiyle yazılır:

  ```
  [en]My Neighbor Totoro|[ja]となりのトトロ|Totoro
  ```

- **Neden `en:` değil de `[en]`?** Yalın "iki harf + iki nokta" öneki gerçek
  başlıkları yanlış okurdu — `Re:Zero kara Hajimeru Isekai Seikatsu` tam olarak
  bu kalıpla başlıyor. Köşeli parantez artı beyaz liste kontrolü (yalnızca
  tanınan bir dil kodu etiket sayılır) etiketsiz bir başlığın asla etiketli
  sanılmamasını garanti eder. `[TV] Bleach` gibi isimler de olduğu gibi kalır.
- Yeni yardımcı dosya: `files/functions/title_lang_helpers.php`. Kolon değerini
  `build_alt_titles()` üretir, `parse_alt_titles()` forma geri çözer,
  `alt_title_for_lang()` bir dilin ismini okur.
- Elle yazılan `[en]` öneki kaydederken temizlenir (dil kutusu tek yetkilidir),
  isme yazılan `|` işareti boşluğa çevrilir (ayraç olduğu için ismi ikiye
  bölerdi).
- `animes.title_english` kolonu **duruyor** ama artık kullanıcı doldurmuyor:
  kaydetme anında listedeki `[en]` etiketli isimden türetiliyor. Böylece bu
  sürüm başlık gösteren tek bir sayfaya bile dokunmadı. Gösterimin doğrudan
  etiketlere bağlanması ve kolonun emekli edilmesi 1.1.21'e bırakıldı.
- Katalogdan gelip henüz etiket taşımayan (ama `title_english` dolu) kayıtlar
  düzenleme formunda otomatik olarak İngilizce işaretli görünür. Bu olmasaydı
  kullanıcı formu açıp kaydettiğinde İngilizce isim sessizce silinirdi.

## Şema / migration

- `migration/1.1.20/upgrade.sql` **şema değiştirmez** — yeni tablo, kolon veya
  ayar anahtarı yok. İçindeki iki `UPDATE`, mevcut kayıtlarda yalnızca
  `title_english`'te duran İngilizce ismi listeye `[en]` etiketli olarak ekler
  (aramanın hemen çalışması için). İkisi de kendi koşulunu sıfırladığından
  migration tekrar çalışsa bile ikinci bir kopya oluşmaz.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ.** `alternative_titles`
  zaten bir metin kolonu ve tipi değişmedi; etiketli metin mevcut senkron
  zincirinden olduğu gibi geçer. (1.1.3, 1.1.10 ve 1.1.17'nin aksine.)
- Tek gözlem: bu kurulum katalogu push ettikten sonra henüz 1.1.20'ye
  geçmemiş bir istemci `[en]` önekini kendi düzenleme formunda ham metin
  olarak görür. Arama ve gösterim etkilenmez, veri kaybı yoktur; istemci
  güncellenince önek etikete döner.

## Değişen / yeni dosyalar

- files/functions/title_lang_helpers.php (yeni; etiket ayrıştırma/kurma)
- files/functions.php (loader'a title_lang_helpers eklendi)
- files/add_anime.php ("İngilizce Başlık" alanı kaldırıldı; satır başına dil kutusu)
- files/edit_anime.php (aynısı + mevcut kayıtların etiketlerinin geri yüklenmesi)
- files/js/anime_form.js (yeni satır artık dil kutusuyla üretiliyor)
- files/css/list.css (dil kutusu genişliği + dar ekranda satır sarması)
- files/lang/tr.php, files/lang/en.php (dil adları; title_english anahtarları kaldırıldı)
- files/schema.sql (alternative_titles ve title_english açıklamaları güncellendi)
- files/migration/1.1.20/upgrade.sql (yeni)
- files/version.txt
