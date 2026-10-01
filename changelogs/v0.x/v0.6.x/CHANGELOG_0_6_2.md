# Anime Tracker 0.6.2

**Sürüm tarihi:** 26 Mayıs 2026
**Tür:** Özellik (İngilizce dil desteği)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur. Veritabanı şemasına dokunulmaz, mevcut verilerinize
dokunulmaz.

## Yeni

- **İngilizce dil desteği.** Anime listesi, detay ve düzenleme
  sayfaları artık İngilizce de görüntülenebilir. Sayfanın sağ üst
  köşesinde küçük bir TR/EN düğme çifti var: tıklayın, sayfa
  yenilenir, dil değişir. Seçim kalıcı - bir kez seçtikten sonra
  tarayıcıyı kapatıp açsanız da unutulmaz.

- **Statü etiketleri ve duygu etiketleri de çevriliyor.** EN
  modundayken "İzlendi / İzleniyor / İzlenme Planlandı / İzleme
  Ertelendi" yerine "Watched / Watching / Plan to Watch / On Hold"
  görürsünüz; aynı şekilde duygu etiketleri "Saddened / Excited /
  Bored / ..." olarak yazılır. Listede bir animenin sonraki
  bölümüne kalan süre de İngilizceye uyarlandı.

- **Form doğrulama mesajları da iki dilli.** Düzenleme sayfasında
  bir alanı yanlış doldurduğunuzda gelen hata mesajları (örneğin
  geçersiz tarih formatı, eksik AniDB linki) seçtiğiniz dilde
  görünür.

## Bilinen Davranışlar

- **Şimdilik üç sayfa İngilizce.** Bu sürümde ana liste, detay
  sayfası ve düzenleme formu çevrildi. Diğer sayfalar (Ne İzlesem,
  Son Düzenlenenler, Liste Ayarları, İstatistikler, Yardım, Hakkında
  ve Seri Kronolojisi) hâlâ Türkçe gösterilir. Bu kasıtlı: kapsamı
  dar tutarak hata riskini düşürdük. Sonraki sürümlerde diğer
  sayfalar da eklenecek.

- **Düğme ile dil değişimi tüm cihazlar için ortak.** Dil seçimi
  veritabanında saklanır; başka bir tarayıcıdan veya farklı bir
  oturumdan aynı kuruluma bağlandığınızda yine seçtiğiniz dilde
  açılır. Bu özellikle ileride çoklu kullanıcı modu geldiğinde
  yararlı olacak (her kullanıcı kendi tercihini saklar).

- **Yayın günü ve saat dilimi seçicilerinde değer Türkçe, etiket
  iki dilli.** "Pazartesi / Salı / ..." değerleri veritabanında
  Türkçe saklanır; sadece kullanıcıya görünen etiket çevrilir.
  Yani EN modunda da seçtiğiniz değer "Monday" görünür ama
  veritabanına "Pazartesi" yazılır - bu, eski kayıtlarınızla
  uyumluluğu garantiler.

- **"Yayın Tamamlandı" gibi yayın durumu da aynı şekilde.**
  Veritabanı değeri Türkçe kalır, sadece dropdown etiketleri ve
  sayfa metinleri İngilizceye çevrilir.

## Teknik Notlar

- Veritabanı şemasında değişiklik YOK. Dil tercihi, daha önce
  başka ayarlar için kullanılan genel ayarlar tablosuna bir
  satır olarak yazılır (ilk kez dil değiştirdiğinizde otomatik
  oluşur).

- Yeni bir `lang/` klasörü eklendi (`tr.php` ve `en.php`).
  İleride başka bir dil eklenmek istenirse aynı yapıya tek
  dosya eklenir, hiçbir kod değişmesi gerekmez.

- Dil değiştirme düğmeleri CSRF korumalı küçük form'lardır
  (link değil) - GET yerine POST kullanılır, böylece dış
  bağlantılar veya tarayıcı önbelleği kazara dilinizi
  değiştiremez.
