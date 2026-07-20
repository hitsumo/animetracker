# Anime Tracker 1.1.18

**Yayın tarihi:** 2026-07-19

## Yeni: "Son İzlenenler" tablosunda anime posteri

- **İstatistik sayfasındaki "Son İzlenenler" tablosunda artık animenin posteri
  görünüyor.** Önceden yalnızca yazıyla anime adı vardı.
- **Her anime iki satır kaplar:** üst satırda poster ile birlikte durum, izlenen
  bölüm ve son izleme bilgileri; alt satırda animenin adı.
- **Anime adı tablonun tamamını kullanır.** Ad dar Anime sütununa
  sığdırılsaydı uzun başlıklar beş altı satıra sarardı; tam genişlikte bir iki
  satıra iner.
- **Postere de isme de tıklamak anime detayına götürür.**
- **Posteri olmayan animelerde kırık resim çıkmaz.** Poster girilmemişse
  arayüz dilinize göre "burada resim yok" / "image not here" görseli
  gösterilir; gerçek posteri eklediğinizde yerini kendiliğinden ona bırakır.
- Poster kutusu 80x120 piksel, yani tam poster oranındadır (2:3); görseller
  ezilmeden, oranı bozulmadan gösterilir.

## Düzeltme: "Son İzlenenler" tablosunda Başlık Dili çalışmıyordu

- **"Başlık Dili" tercihiniz bu tabloda dikkate alınmıyordu.** İngilizce başlık
  tercihini açmış olsanız bile "Son İzlenenler" tablosu animenin özgün adını
  gösteriyordu; ana liste ve anime detay sayfası ise doğru çalışıyordu. Artık
  bu tablo da tercihinize uyar.
- İstatistik sayfasındaki diğer tablolar (medya türü, yayın durumu, izleme
  durumu, duygu dağılımı) sayaç tablolarıdır ve anime adı göstermez; bu yüzden
  düzeltme tek tabloyu ilgilendirir.

## Temizlik: 1.1.15'ten kalan kullanılmayan iki kalem silindi

- 1.1.15'te kaldırılan mor "Hikaye: 35" rozetinden arta kalan `.marker-episode-story`
  CSS kuralı ve `anime_details.marker.story_after_episode` dil anahtarı (Türkçe
  ve İngilizce) kaldırıldı. İkisinin de projede hiçbir kullanımı kalmamıştı.
- Görünürde bir değişiklik yaratmaz; yalnızca ölü kod temizliğidir. Türkçe /
  İngilizce dil anahtarı sayısı eşit kaldı (ikisinden de aynı anahtar düştü).

## Nasıl çalışır (teknik)

- "Son İzlenenler" sorgusu artık `a.image_path` ve `a.title_english` alanlarını
  da çekiyor. İkisi de `animes` tablosunda zaten vardı, yalnızca bu sorguda
  seçilmiyordu - bu yüzden poster gösterilemiyor ve başlık dili
  uygulanamıyordu.
- Poster kaynağı mevcut `poster_src()` yardımcısıyla (1.1.9) belirlenir, başlık
  ise mevcut `display_title()` yardımcısıyla. Yeni yardımcı yazılmadı.
- Stil kuralları `statistics.php` içindeki `<style>` bloğuna eklendi; sayfanın
  mevcut deseni budur, ayrı bir CSS dosyası açılmadı.

## Şema / migration

- `migration/1.1.18/upgrade.sql` yalnızca sürümü 1.1.18'e taşır; **şema
  değişikliği yoktur** (çalıştırılacak SQL ifadesi yok). Merkez katalog
  etkilenmez, sunucuda elle bir işlem **gerekmez**.

## Değişen / yeni dosyalar

- files/statistics.php (sorguya image_path + title_english; Anime hücresinde
  poster + ad; stil bloğuna poster kuralları)
- files/css/series.css (kullanılmayan `.marker-episode-story` kuralı silindi)
- files/lang/tr.php, files/lang/en.php (kullanılmayan
  `anime_details.marker.story_after_episode` anahtarı silindi)
- files/migration/1.1.18/upgrade.sql (yeni)
- files/version.txt
