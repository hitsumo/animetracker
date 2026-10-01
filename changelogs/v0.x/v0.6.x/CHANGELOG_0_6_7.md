# Anime Tracker 0.6.7 - Degisiklikler

**Yayin tarihi:** Mayis 2026 (0.6.6 sonrasi)

Bu surum bir ic kod duzenlemesi surumudur. Gorunur bir ozellik veya
arayuz degisikligi YOKTUR; uygulama birebir 0.6.6 gibi calisir ve
gorunur. Amac, kod tabanini gelecek gelistirmeler icin daha bakimli
hale getirmek.

## Kod modulerlestirildi

Iki buyuk dosya, islevlerine gore daha kucuk parcalara bolundu:

- `functions.php` (2131 satir) artik ince bir yukleyici. Tum yardimci
  fonksiyonlar islevlerine gore `functions/` klasoru altinda 8 modulde
  toplandi (ceviri, izleme durumu, duygu, anime verisi, guvenlik, seri/
  kronoloji, tur/etiket, AnimeSchedule).
- `style.css` (1635 satir) artik ince bir yukleyici. Tum stiller
  `css/` klasoru altinda 6 modulde toplandi (temel, bilesenler, liste/
  tablo, seri/kronoloji, duygu, dil secici).

Her iki yukleyici de eski dosya adini korur, dolayisiyla tum sayfalar
hicbir degisiklik olmadan calismaya devam eder. Fonksiyonlarin davranisi
ve stillerin gorunumu birebir aynidir.

## Diger

### Migration

Bu surumde sema degisikligi yok. `migration/0.6.7/upgrade.sql` sadece
surum numarasini ilerletmek icin bos halka.

### i18n

Sozluk degismedi. Bu surumde yeni metin/anahtar eklenmedi.
