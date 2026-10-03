# Anime Tracker 1.2.3

**Yayın tarihi:** 2026-09-30

Ana iş: **telefon görünümü.** Herkese açık sayfaların çoğu telefonda
küçültülmüş bir masaüstü sayfası olarak açılıyordu; artık ekran
genişliğinde çiziliyor. Yanında küçük bir düzeltme: anime detay
sayfasındaki **saat kaynağı notu** artık saatin altında duruyor. Şema
değişikliği yok (boş migration); merkez katalogda yapılacak bir şey yok.

## 1. Neden

Arama Konsolu'nun son üç aylık verisinde tıklamaların üçte ikisi
telefondan geliyor (43 tıklamanın 29'u).

Oysa telefonun sayfayı kendi genişliğinde çizmesini sağlayan
`<meta name="viewport">` etiketi yalnızca ana listede ve anime detay
sayfasında vardı. Seri Kronolojisi, Kronoloji, Hakkında, İstatistikler,
Son Güncellenenler, Ne İzlesem ve bütün yardım sayfaları telefonda yaklaşık
980 piksellik masaüstü düzeninde, küçültülerek açılıyordu. Arama motorları
mobil sürümü esas aldığı için bu sayfalar "mobil uyumlu değil" sayılabilir.

## 2. Ne değişti

- **Viewport etiketi herkese açık her sayfada.** Etiket artık sayfaların
  ortak SEO başlığından geliyor. İleride eklenecek herkese açık sayfalar
  da onu kendiliğinden alır.
- **Seri Kronolojisi telefona oturuyor.** Uzun anime adları kartı
  genişletmiyor, "…" ile kısalıyor. Zaman çizgisi noktaları ekranın
  içinde kalıyor. Telefonda dış boşluklar daralıyor.
- **Yardım sayfaları telefona oturuyor.** Dar ekranda kenar boşlukları
  küçülüyor. Geniş tablolar sayfayı değil kendi kutusunu yana kaydırıyor.
- **Masaüstü görünümü değişmedi.** Sayfa genişlikleri piksel piksel
  aynı.

## 3. Detay sayfası: saat kaynağı notu yerinde

Devam eden ve yayını başlamamış animelerde "Saat bilgisi AnimeSchedule'den
alınmıştır" notu Yayın Tarihi'nin altında basılıyordu. Yapım Ülkesi satırı
eklenince not ülkenin altında, ait olduğu saatten çok uzakta yalnız
kalmıştı; Yayın Günü ve Yayın Saati sayfanın daha aşağısında.

- **Not artık yayın bilgilerinin içinde:** Yayın Saati'nin ve geri sayımın
  (Sonraki Bölüm / 1. bölüme kalan süre) hemen altında.
- **Not yalnız saat girilmişse görünür.** Saati "Belirtilmemiş" olan
  animede kaynak notu çıkmaz.

## 4. Nasıl ölçüldü

375 piksel genişlikte (telefon) herkese açık 21 sayfa açıldı ve yatay
taşma arandı. Önce üç sayfa taşıyordu (Seri Kronolojisi 550 px, iki
yardım sayfası ~400 px); düzeltmeden sonra hiçbiri taşmıyor. Masaüstü
genişliği (1280 px) sayfa kutularının eski ölçüleriyle karşılaştırıldı.

Saat notu dört durumda denendi: saatli başlamamış ve saatli devam eden
animede not saatin altında; saatsiz başlamamış ve bitmiş animede not yok.

## Dosyalar

**Yeni:**

```
files/migration/1.2.3/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/functions/seo_helpers.php          seo_head(): viewport etiketi (ilk etiket)
files/anime_details.php                  elle yazılmış viewport satırı kalktı (seo_head veriyor);
                                         saat kaynağı notu yayın bilgilerinin içine, yalnız saat doluyken
files/index.php                          aynı
files/series_timeline.php                kapsayıcı ekran genişliğinde; kart küçülebilir; telefonda boşluklar
files/css/help.css                       kapsayıcı ekran genişliğinde; telefonda boşluklar; tablolar kendi içinde kayar
files/version.txt
```

## Dağıtım notu

- **Merkez katalogda iş yok.**
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- 1.2.2 henüz canlıya çıkmadıysa bu sürümle birlikte çıkabilir.
  `seo_helpers.php` ve `series_timeline.php` iki sürümün değişikliklerini
  birlikte taşır.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.3'e çekilir ve `updates/1.2.3/anime-tracker-1.2.3.zip` paketi
  yayımlanır.
