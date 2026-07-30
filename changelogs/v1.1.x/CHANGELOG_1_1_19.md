# Anime Tracker 1.1.19

**Yayın tarihi:** 2026-07-22

## Yeni: Konu içinde başka bir animeye tıklanabilir link

- **Bir animenin konusundan (synopsis) başka bir animeye tıklanabilir link
  verebilirsiniz.** Örneğin bir filmin konusuna "X animesinin film olarak
  derlenmiş hâli" yazıp "X animesi" kısmını, tıklandığında o animenin detay
  sayfasını açan bir bağlantı yapabilirsiniz.
- **Yazım biçimi bir kısa koddur:**

  ```
  [[anime:52991|Frieren]] animesinin film olarak derlenmiş hâli.
  ```

  Buradaki `52991` hedef animenin **MyAnimeList (MAL) numarasıdır**, `Frieren`
  ise ekranda görünecek bağlantı metnidir.
- **Metni yazmazsanız hedef animenin kendi başlığı otomatik kullanılır:**
  `[[anime:52991]]` yazmak, bağlantıyı animenin adıyla etiketler.
- **Hem katalog konusunda hem de kişisel notunuzda çalışır.**
- Bağlantı metni sitenin normal link rengiyle, alttan çizili gösterilir; yazının
  akışını bozmayacak kadar sade tutuldu.

## Neden ham HTML değil, kısa kod?

- **Konuya doğrudan `<a href="...">` yazmak mümkün değildir; bilerek böyle.**
  Konu metni katalog üzerinden bütün üyelere gider; ham HTML'e izin vermek
  depolanmış XSS ve rastgele dış bağlantı riski açardı. Kısa kod yalnızca bir
  MAL numarası taşır, geri kalan her şey güvenle kaçırılmaya devam eder ve
  bağlantının gittiği yer **her zaman sitenin kendi anime detay sayfasıdır**.
- **Numara olarak neden yerel id değil, MAL id?** Katalog metni her kuruluma
  aynı gider, ama animelerin yerel id'si her kurulumda ayrı atanır. Yerel id
  yazsaydınız, senkron sonrası başka kurulumda yanlış animeye giderdi. MAL
  numarası evrensel olduğu için bağlantı her kurulumda doğru animeyi bulur.
- **Aradığı anime o kurulumda yoksa link kırılmaz:** yazdığınız metin düz yazı
  olarak kalır, cümle yine okunur.

## Nasıl çalışır (teknik)

- Yeni bir yardımcı dosya eklendi: `files/functions/synopsis_helpers.php`.
  `render_synopsis()` konuyu güvenli HTML olarak basar ve `[[anime:..]]`
  kısa kodlarını yerel `anime_details.php` bağlantısına çevirir; `synopsis_plain()`
  ise önizleme/kısaltma yerleri için kısa kodu düz etikete indirger.
- Metnin tamamı önce `htmlspecialchars` ile kaçırılır, kısa kod ondan sonra
  bağlantıya dönüştürülür; yani önceki `nl2br(htmlspecialchars(...))` davranışı
  düz konularda birebir korunur.
- Kısa kod içindeki MAL numaraları tek bir toplu sorguyla (`WHERE mal_id IN (...)`)
  yerel satırlara çözülür; her bağlantı için ayrı sorgu açılmaz.
- Sürpriz öneri kartındaki 200 karakterlik konu özeti artık `synopsis_plain()`
  ile üretilir; böylece özet ham `[[...]]` göstermez ve kısaltma bir kısa kodu
  ortadan bölmez.

## Şema / migration

- `migration/1.1.19/upgrade.sql` yalnızca sürümü 1.1.19'a taşır; **şema
  değişikliği yoktur** (çalıştırılacak SQL ifadesi yok). Özellik tamamen render
  katmanındadır. Merkez katalog etkilenmez, sunucuda elle bir işlem **gerekmez**.

## Değişen / yeni dosyalar

- files/functions/synopsis_helpers.php (yeni; `render_synopsis` + `synopsis_plain`)
- files/functions.php (loader'a synopsis_helpers eklendi)
- files/anime_details.php (katalog ve kişisel konu artık `render_synopsis()` ile basılıyor)
- files/recommendations.php (sürpriz özeti kısaltmadan önce `synopsis_plain()`)
- files/css/base.css (`.synopsis-link` stili)
- files/migration/1.1.19/upgrade.sql (yeni)
- files/version.txt
