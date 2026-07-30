# Anime Tracker 1.1.23

**Yayın tarihi:** 2026-07-26

## Yeni: Seri Kronolojisi'ne "Yayın Tarihi" sekmesi

- **Seri kronolojisi sayfası artık iki sekmeli.** "Zincir Sırası" bugüne
  kadarki görünümün kendisi: Sıradaki Anime bağlantılarını halka halka izler.
  Yeni "Yayın Tarihi" sekmesi ise aynı seri adını taşıyan **bütün** animeleri
  ilk gösterim tarihine göre dizer — TV dizilerinde başlangıç–bitiş aralığı
  (gün.ay.yıl), film ve özel bölümlerde tek gösterim tarihi görünür.
- **Neden?** Zincir görünümü elle kurulan bağlantılara muhtaçtır: tek bir
  eksik halka listeyi ikiye böler ve katalogdan içe aktarılan anime hep
  bağlantısız doğar. Üstelik doğrusal bir zincir, iç içe geçen yayın
  dönemlerini gösteremez — bir dizinin yayını sürerken çıkan filmler bunun
  tipik örneğidir. Yayın Tarihi sekmesi zincire hiç bakmadığı için bu
  sorunların ikisinden de etkilenmez: eksik halka onu bölemez, tarih
  aralıkları çakışmaları olduğu gibi gösterir.
- **Tarihi girilmemiş kayıtlar** listenin sonuna düşer ve "tarih yok"
  etiketiyle görünür — serinin başlangıcıymış gibi öne geçmezler.
- **Sekme seçimi oturum boyunca hatırlanır:** bir seride Yayın Tarihi'ne
  geçtiyseniz başka bir serinin kronolojisi de o sekmeyle açılır; kalıcı
  varsayılanınız değişmez.

## Yeni: Liste Ayarları'nda kalıcı varsayılan

- Liste Ayarları → Genel Ayarlar'a "Seri Kronolojisi Görünümü" tercihi
  eklendi: sayfa varsayılan olarak hangi sekmeyle açılsın — Zincir Sırası
  (varsayılan, eski davranış) ya da Yayın Tarihi. Kronoloji Görünümü
  (1.1.15) tercihinin birebir kalıbı: sayfadaki sekmeler bu varsayılanı
  ezmeden geçici geçiş yapar. Bu tercih yalnızca sizi etkiler.

## Yeni: Buton artık seri adından da açılıyor

- Detay sayfasındaki "Seri Kronolojisi" butonu eskiden yalnızca zincire
  girmiş (Sıradaki Anime bağlantısı olan) animelerde çıkıyordu. Artık
  **zincire hiç girmemiş ama aynı seri adını taşıyan başka kayıtları olan**
  animede de çıkar — katalogdan içe aktarılıp henüz bağlanmamış bir anime,
  Yayın Tarihi sekmesi sayesinde serisinin çizelgesine hemen girer.

## Düzeltildi: +18 başlıklar seri kronolojisinde maskesizdi

- +18 içerik tercihi kapalı bir kullanıcı, zincirde +18 damgalı bir üye
  varsa başlığını seri kronolojisinde açık açık görüyordu — uygulamanın
  diğer ilişkili-anime kartları (1.1.2'den beri) bu başlığı nötr yer
  tutucuyla maskelerken bu sayfa istisna kalmıştı. Artık iki sekmede de
  aynı maske uygulanır: kart kalır, başlık sızmaz; tercihi açık kullanıcı
  gerçek başlığı görmeye devam eder.

## Nasıl çalışıyor (teknik)

- Yayın Tarihi sekmesi `series_name` üzerinden tek sorguyla beslenir;
  sıralama `release_date` (NULL'lar sona). Zincir yürüyüşü, kart şablonu ve
  ilerleme/durum rozetleri iki sekmede ortaktır.
- Sekme seçimi 1.1.15'teki kronoloji modu kalıbını izler: oturumdaki geçici
  seçim > kayıtlı kişisel varsayılan > Zincir Sırası. Kalıcı tercih
  `user_pref` tablosuna `series_timeline_mode` adıyla yazılır; sekmeler
  sayfa içi bağlantıdır, kalıcı kayıt yalnızca Liste Ayarları'ndan yapılır.
- Seri adı boş animede Yayın Tarihi sekmesi gösterilmez; sayfa eski
  davranışıyla yalnızca zinciri çizer.

## Şema / migration

- **Şema değişikliği yok.** Yeni tercih, var olan `user_pref` anahtar-değer
  tablosuna satır olarak girer; kullanılan tüm kolonlar zaten mevcuttu.
  `migration/1.1.23/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — değişiklik tamamen
  gösterim katmanındadır.

## Değişen / yeni dosyalar

- files/series_timeline.php (sekmeler, yayın tarihi görünümü, +18 maskesi)
- files/anime_details.php (Seri Kronolojisi butonu seri adından da açılır)
- files/list_settings.php (Seri Kronolojisi Görünümü tercihi)
- files/set_series_timeline_mode.php (yeni, kalıcı tercihi yazan uç nokta)
- files/functions/series_helpers.php (mod çözümü + yayın tarihi sorgusu)
- files/lang/tr.php, files/lang/en.php (yeni metinler)
- files/migration/1.1.23/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt
