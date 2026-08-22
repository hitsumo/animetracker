# Anime Tracker 1.1.28

**Yayın tarihi:** 2026-08-08

## Düzeltme: "Yayın Başlamadı" durumunda yayın bilgileri bölümü açılmıyordu

- **Sorun:** anime ekleme ve düzenleme formundaki **Yayın Bilgileri** bölümü
  (bölüm aralığı, yayın günü, yayın saati, yayın saat dilimi) yalnızca "Yayın
  Devam Ediyor" durumunda açılıyordu. Durumu "Yayın Başlamadı" olan bir animede
  bu dört alanı ne görebiliyor ne de doldurabiliyordunuz.
- **Oysa henüz başlamamış bir dizinin yayın günü ve saati bellidir.** Bir anime
  sezon başlamadan haftalar önce "cumartesi 23:30" diye duyurulur; bu bilgi
  yayın başladığı gün ortaya çıkmaz. AnimeSchedule da bu alanları henüz
  başlamamış animeler için döndürür.
- **Bu yüzden "Otomatik Doldur" görünmeyen bir yere yazıyordu.** Henüz
  başlamamış bir animede düğmeye bastığınızda yayın günü ve saati gerçekten
  yazılıyor, mesaj da bunu dürüstçe "broadcast_day *(gizli bölümde)*" diye
  bildiriyordu — ama alan ekranda olmadığı için ne kontrol edebiliyor ne de
  düzeltebiliyordunuz.
- **Artık bölüm "Yayın Başlamadı" durumunda da açılıyor.** Alanları elle
  doldurabilir, otomatik doldurmanın getirdiğini görebilir ve düzeltebilirsiniz.
  Anime yayına başlayıp durumu "Yayın Devam Ediyor" olduğunda bu bilgiler
  zaten hazır olur.
- **Anime detay sayfası da artık gösteriyor.** Henüz başlamamış bir animenin
  detayında Yayın Günü ve Yayın Saati satırları görünüyor — kaynak notuyla
  birlikte. Yoksa formda girdiğiniz saati hiçbir yerde göremezdiniz.
- **"Sonraki Bölüm" satırı bilinçli olarak dışarıda.** Henüz başlamamış bir
  animenin "sonraki bölüm"ü ilk bölümüdür ve onu zaten Yayın Tarihi satırı
  taşır.
- **"(gizli bölümde)" notu kaldırılmadı** — hâlâ gerçekten kapalı olan durumlar
  (örneğin tamamlanmış animede bölüm sayısı dışındaki alanlar) için yerinde
  duruyor.

## Düzeltme: kişisel notlarınız detay sayfasında görünmüyordu

- **Sorun:** detay sayfasındaki **Notlar** satırı, yayın bilgileri bloğunun
  *içinde* duruyordu — yani "yalnızca Yayın Devam Ediyor" koşuluna takılıydı.
  Sonuç: **tamamlanmış, henüz başlamamış veya iptal edilmiş** bir animeye
  yazdığınız not detay sayfasında **hiç görünmüyordu.**
- **Notunuz kaybolmuş değildi.** Kaydediliyor, düzenleme formunda duruyor ve
  orada değiştirilebiliyordu; yalnızca detay sayfasında basılmıyordu.
- **Etkisi sanılandan büyüktü:** bir katalogun büyük çoğunluğu tamamlanmış
  animedir, yani notların çoğu görünmüyordu.
- **Artık her durumda görünüyor.** Not kişisel bir veridir ve animenin yayın
  durumuyla hiçbir ilgisi yoktur. Yayını devam eden animelerde notun yeri
  değişmedi.
- **Kronoloji düğmesi her durumda tam bir kez çıkıyor** (bu düzeltme sırasında
  ayrıca sayılarak doğrulandı).

## Düzeltme: "Dış Siteler" bölümündeki üç kusur

- **AnimeSchedule düğmesi, MyAnimeList bağlantısına bağlıydı.** AnimeSchedule
  adresini girdiğiniz ama MAL kutusunu boş bıraktığınız bir animede düğme hiç
  çıkmıyordu. 1.1.27'den beri "yalnızca AnimeSchedule bağlantısı girmek"
  desteklenen bir kullanım olduğu için bu iyice görünür olmuştu. Artık her
  düğmenin kendi koşulu var.
- **Adres yokken düğme sitenin ana sayfasına gidiyordu** (üstelik bunu yalnızca
  MAL bağlantısı varsa yapıyordu). Bu bölüm *bu animeye ait* bağlantıları
  listeler; sizi animenin sayfasına götürmeyen bir düğme bilgi değil gürültü.
  Adres yoksa düğme artık hiç basılmıyor.
- **Hiç bağlantısı olmayan animede boş bir "Dış Siteler" başlığı çıkıyordu.**
  Bölümün koşulu her zaman doğru sonuç veriyordu. Artık en az bir bağlantı
  varsa görünüyor.
- **Yayın saatinin altındaki kaynak notu değişmedi** — orası "saat bilgisi
  nereden geldi" diyen bir kaynak belirtmesi, animenin sayfasına giden bir
  düğme değil; adres yoksa servisin ana sayfasına gitmesi doğru davranış.

## Değişmeyen kurallar

Bu sürüm yalnızca **yayın bilgileri** bölümünün görünürlük kuralını düzeltir.
Ona komşu bölümler bilerek olduğu gibi bırakıldı:

- **"Yayınlanan Bölüm Sayısı" hâlâ yalnızca "Yayın Devam Ediyor" durumunda
  görünür.** Henüz başlamamış bir animede yayınlanan bölüm sayısı tanımı gereği
  sıfırdır; sunucu bu alanı devam eden olmayan her durumda zaten temizler.
- **"Yayın Bitiş Tarihi" hâlâ yalnızca tamamlanmış ve tek bölümlük olmayan
  yapımlarda görünür.**
- **Sonraki bölüm tarihi hâlâ yalnızca devam eden animede hesaplanır.**
- **Tamamlanmış animede yayın günü/saati hâlâ hiç gösterilmiyor ve otomatik
  doldurmada hiç getirilmiyor** (1.1.27'de gelen kural). Orada bu alanlar
  gerçekten anlamsızdır.

## Veriniz etkilenmez

Yayın günü, saati, saat dilimi ve bölüm aralığı **zaten her durumda
kaydediliyordu** — gizli bir form bölümü de değerlerini gönderir. Notlarınız da
öteden beri kaydediliyordu; değişen, **gösterilmeleri.** Bu sürüm hiçbir veriyi
taşımaz, silmez veya dönüştürmez.

## Ayrıca: detay sayfası temizliği

Yukarıdaki işler sırasında `anime_details.php` baştan sona gözden geçirildi.
Görünürde bir şey değiştirmeyen ama dosyayı sağlamlaştıran düzeltmeler:

- **Adres çubuğuna `anime_details.php` (id'siz) yazıldığında** sayfa artık
  düzgünce "anime bulunamadı" diyor; eskiden önce PHP uyarısı üretiyordu.
  Var olmayan bir id ile girildiğinde de aynı şey oluyordu.
- **Girinti düzeltildi.** Detay satırlarının bir bölümü satır başından
  başlıyordu, kardeşlerinin hizasına çekildi. Üretilen sayfa birebir aynı —
  boşluk yok sayılarak alınan farkla doğrulandı.

## Şema / migration

- **Şema değişikliği yok.** Yeni tablo, kolon veya tercih eklenmedi.
  `migration/1.1.28/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — katalog teline
  dokunulmadı.

## Değişen / yeni dosyalar

- files/js/anime_form.js (görünürlük kuralı: yayın bilgileri bölümü artık
  "Yayın Başlamadı" durumunda da açılır)
- files/edit_anime.php (bölümün sayfa açılışındaki ilk hâli)
- files/anime_details.php (yayın bilgileri başlamamış animede de gösterilir;
  **notlar satırı bloğun dışına taşındı** — her durumda görünür; "Dış Siteler"
  bölümü düzeltildi; girinti ve küçük sağlamlaştırmalar)
- files/functions/animeschedule_helpers.php (**yalnızca yorum güncellemesi** —
  eski açıklama artık geçerli olmayan form kuralını anlatıyordu; davranış aynı)
- files/migration/1.1.28/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt

`files/add_anime.php` **değişmedi**: o formda bölüm daima kapalı başlar
(varsayılan durum "Seçim Yapılmadı") ve görünürlüğünü tamamen betik yönetir.

## Dağıtım notu

- Yeni dosya yok. `files/js/anime_form.js` ile `files/edit_anime.php`
  **birlikte** yüklenmelidir; biri eski kalırsa düzenleme formu sayfa
  açılışında betikle çelişir (bölüm açık başlayıp ilk durum değişikliğinde
  kapanır ya da tersi). Göze çarpmayan, teşhisi can sıkıcı bir tutarsızlık olur.
- `files/anime_details.php` bağımsızdır, tek başına da yüklenebilir; ama formla
  aynı sürümde olması anlamlıdır — form saati girmeyi açar, detay onu gösterir.
- Tarayıcı önbelleğini 1.1.24'ten beri kullanılan sürüm damgası (`?v=1.1.28`)
  kendisi tazeler; elle bir şey yapmanız gerekmez.
