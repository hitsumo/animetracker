# Anime Tracker 1.1.21

**Yayın tarihi:** 2026-07-24

## Yeni: Başlık Dili — artık sadece İngilizce değil

- **Liste Ayarları'ndaki "İngilizce başlıkları göster" onay kutusu, gerçek bir
  dil seçimine dönüştü.** Anime başlıklarını hangi dilde görmek istediğinizi
  seçersiniz: **Romaji** (varsayılan), İngilizce, Japonca, Türkçe, Çince,
  Korece, Fransızca.
- **Seçtiğiniz dilde başlığı olmayan anime Romaji'de kalır.** Yani katalogda
  henüz olmayan bir dili seçmek zararsızdır — hiçbir yer boş görünmez.
- **Başlıkların dili nereden geliyor?** 1.1.20'de eklediğimiz yerden: anime
  ekleme/düzenleme formunda alternatif isimlerin yanındaki dil kutusundan.
  Bir animeye Japonca isim ekleyip "Japonca" işaretlerseniz, Başlık Dili'ni
  Japonca yapan herkes onu görür.
- Tercih size özeldir ve **arayüz dilinden bağımsızdır** — arayüzü Türkçe
  kullanıp başlıkları Japonca görebilirsiniz.

## Düzeltildi: İstatistik sayfası başlık dilini yok sayıyordu

- **"Son İzlenenler" tablosu, tercihiniz ne olursa olsun başlıkları hep Romaji
  gösteriyordu.** 1.1.18'de bu hücre başlık-dili desteği alacak şekilde
  düzenlenmişti, ama sayfanın tercihi okuması için gereken çağrı eklenmemişti;
  sonuç olarak tablo sessizce varsayılana düşüyordu. Artık doğru çalışıyor.

## Kaldırıldı: title_english kolonu

- **1.1.20'de "geçici olarak duruyor" dediğimiz kolon emekli edildi.** Artık
  bir başlığın dili tek bir yerde yaşıyor: etiketli alternatif isim listesinde.
- **Neden duruyordu, neden şimdi gitti?** 1.1.20'de kolon, kaydetme anında
  `[en]` etiketinden türetilen bir "gösterim kısayolu"ydu; sayesinde o sürüm
  başlık basan hiçbir sayfaya dokunmak zorunda kalmadı. Ama o kolon yalnızca
  İngilizceyi anlatabildiği için Türkçe/Japonca başlıkların **saklanmasına**
  izin veriyor, **gösterilmesine** izin vermiyordu. Bu sürümde gösterim
  doğrudan etiketleri okuduğu için kolon gereksizleşti.
- **Veri kaybı yoktur.** İngilizce isimler zaten `[en]` etiketiyle alternatif
  isimler içinde duruyor; migration, kolonu düşürmeden önce gözden kaçmış
  olabilecek satırları da etiketler.

## Nasıl çalışıyor (teknik)

- Tercih artık bir dil kodu tutuyor (`display_title_lang`); eskiden bir
  aç/kapa değeriydi (`display_title_english`). Migration, "açık" olan
  kullanıcıları İngilizce'ye taşır.
- `display_title()` seçili dilin etiketini arar, bulamazsa Romaji başlığa
  düşer. Varsayılan Romaji tercihinde etiketli liste **hiç ayrıştırılmaz**,
  yani en sık karşılaşılan durumda ek bir maliyet yoktur.
- Başlık basan bütün sorgular artık `title_english` yerine
  `alternative_titles` çekiyor: anime detayı, istatistik, seri zaman çizelgesi,
  kronoloji, onay bekleyenler, konu içi anime linkleri.
- +18 gizleme güçlendi: eskiden yalnızca İngilizce başlık temizleniyordu, artık
  etiketli listenin tamamı temizleniyor — aksi halde adı gizlenen bir anime,
  başka bir dil seçili kullanıcıya adını sızdırabilirdi.

## Şema / migration

- `migration/1.1.21/upgrade.sql` **kolonu düşürür** (`animes` ve
  `catalog_requests`). Düşürmeden önce iki kurtarma adımı çalışır: İngilizce
  isim listede etiketsiz duruyorsa yerinde etiketlenir, hiç yoksa `[en]` olarak
  eklenir. Bu adım gereklidir çünkü 1.1.20'den sonra yapılan bir katalog
  senkronu etiketleri sunucudaki eski sürümle ezmiş olabilir.
- Tercih göçü aynı dosyada: `display_title_english = '1'` olan kullanıcılar
  `display_title_lang = 'en'` olur, eski satırlar silinir.

## Merkez katalog sunucusunda elle işlem GEREKİR

1.1.20 elle işlem gerektirmeyen istisnaydı; bu sürüm yeniden gerektiriyor.
**Sıra kritiktir:**

1. `catalog_server/` yeni sürümünü dağıtın (`catalog.php` + `admin_push.php`).
2. Sunucu veritabanında: `ALTER TABLE animes DROP COLUMN title_english;`
3. Uygulamayı dağıtın (migration kendiliğinden çalışır).
4. Tam katalog push.

Ters sırada (önce ALTER) eski `catalog.php` düşmüş bir kolonu okumaya çalışır
ve **bütün istemciler için senkron patlar**. Sunucuda `catalog_requests`
tablosu yoktur; orada yalnızca yukarıdaki tek `ALTER` çalıştırılır.

**Geride kalan kurulumlar:** 1.1.20 ve öncesinde kalan bir istemci katalogtan
artık İngilizce başlık alamaz ve o kurulumda başlıklar Romaji'ye düşer. Veri
kaybı değildir — isim etiketli listede gelmeye devam eder; istemci 1.1.21'e
geçtiğinde yeniden görünür.

## Değişen / yeni dosyalar

- files/functions/anime_helpers.php (tercih ailesi dil koduna geçti; display_title etiket okuyor; adult_mask_related etiketli listeyi temizliyor)
- files/functions/title_lang_helpers.php (alt_titles_for_form kaldırıldı — işi migration'a geçti)
- files/functions/series_helpers.php (üç sorgu + display_related_title köprüsü)
- files/functions/synopsis_helpers.php (konu içi link sorgusu)
- files/set_title_pref.php (boolean yerine dil kodu, beyaz listeli)
- files/list_settings.php (onay kutusu → dil seçici; içe/dışa aktarmadan kolon çıktı)
- files/statistics.php (eksik title_pref_init eklendi + sorgu)
- files/anime_details.php, files/chronology.php, files/series_timeline.php, files/pending.php (sorgular)
- files/add_anime.php, files/edit_anime.php (kolon artık yazılmıyor)
- files/catalog_import.php, files/admin/catalog_push.php, files/admin/admin_catalog_requests.php, files/admin/admin_sync_example.php (katalog telinden çıkarıldı)
- catalog_server/catalog.php, catalog_server/admin_push.php (sunucu tarafı tel)
- files/css/components.css (artık yanıltıcı olan sınıf yorumu)
- files/lang/tr.php, files/lang/en.php (dil seçici metinleri + yardım sayfası)
- files/schema.sql (kolon tanımları ve açıklamalar kaldırıldı)
- files/migration/1.1.21/upgrade.sql (yeni)
- files/version.txt
