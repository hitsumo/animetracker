# Anime Tracker 0.7.1 - Degisiklikler

**Yayin tarihi:** Mayis 2026

## Liste Ayarlari'na Cumle Yonetimi butonu

Liste Ayarlari sayfasinda, Tur Yonetimi'nin yaninda artik bir
**"Cumleleri Yonet"** butonu var. Daha once cumle (etiket) yonetimine
yalnizca anime ekleme/duzenleme ekranindaki baglantidan ulasilabiliyordu;
artik dogrudan Liste Ayarlari'ndan da acabilirsiniz.

## Cumle ve Tur Yonetimi sayfalari artik Ingilizce

**Cumle Yonetimi** ve **Tur Yonetimi** sayfalari secili dile gore TR/EN
gosterilir. Daha once bu iki sayfa yalnizca Turkce idi; arayuz dilini
Ingilizce sectiginizde sayfa basliklari, tablo basliklari, butonlar ve
tum uyari/onay mesajlari da cevrilir.

Dil secimi (Ana Sayfa veya Liste Ayarlari'ndaki dil dugmesi) oturum
boyunca gecerli oldugu icin bu iki sayfa da otomatik olarak o dilde acilir.

## Konu (synopsis) artik Turkce + Ingilizce

Anime Konu (synopsis) alani artik iki dillidir. Her anime icin ayri bir
**Konu (TR)** ve **Konu (EN)** tutulur; detay sayfasinda arayuz diline
gore uygun olani gosterilir.

- Ingilizce Konu, bir AI araci ile cevrilip elle yapistirilir (sistemde
  AI entegrasyonu yoktur). Anime ekleme/duzenleme ekraninda Turkce metnin
  altindaki "Kopyala" butonu bu islemi hizlandirir.
- Ingilizce metin gosterilirken altinda kucuk gri bir
  "Auto-translated from Turkish" etiketi cikar ve Yardim sayfasindaki
  Ceviri Durumu bolumune baglanir.
- Ingilizce Konu henuz girilmemisse, Ingilizce arayuzde Turkce orijinal
  gosterilir ve kisa bir bilgi notu eklenir.
- Duzenleme ekraninda bir "Onaylandi olarak isaretle" secenegi vardir;
  Turkce metni degistirdiginizde bu isaret otomatik kalkar (Ingilizce
  metin silinmez, yalnizca durum isareti degisir).

Yardim sayfasina, bu cevirilerin nasil olustugunu anlatan bir
**Ceviri Durumu** bolumu eklendi.

## Diger

### i18n

Bu surumde toplam **45 yeni metin anahtari** eklendi (Cumle Yonetimi,
Tur Yonetimi, Liste Ayarlari butonu ve Konu TR/EN ile ceviri durumu
metinleri). Toplam sozluk boyutu: 490 -> 535 anahtar (TR/EN paralel).

### Sema

Bu surum bir **sema degisikligi** icerir: `animes` tablosuna `synopsis_tr`,
`synopsis_en` ve `translation_status` kolonlari eklenir; eski tek
`synopsis` kolonu silinmez, verisi `synopsis_tr`'ye kopyalanir.
`migration/0.7.1` artik bu degisikligi yapan gercek bir migration'dir;
otomatik guncelleme sirasinda kendiliginden gecer, elle bir sey
yapmaniza gerek yoktur (islem idempotenttir, tekrar calissa bile zarar
vermez).

### Dosyalar

Degisen: `list_settings.php`, `manage_tags.php`, `manage_genres.php`,
`add_anime.php`, `edit_anime.php`, `anime_details.php`,
`recommendations.php`, `help.php`, `catalog.php`, `catalog_import.php`,
`admin_push.php`, `schema.sql`, `css/base.css`, `tr.php`, `en.php`.
Yeni: `migration/0.7.1/upgrade.sql`.
