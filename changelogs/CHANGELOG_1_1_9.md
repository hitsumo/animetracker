# Anime Tracker 1.1.9

**Yayin tarihi:** 2026-07-13

## Iyilestirme

- **Posteri olmayan animeler icin dile duyarli "resim yok" gorseli.** Posteri
  bulunmayan animeler liste ve detay sayfalarinda kirik bir resim yerine, arayuz
  diline uygun bir "resim yok" gorseli gosterir. Arayuz dilini degistirdiginizde
  bu gorsel de otomatik degisir: Turkce'de "BURADA RESIM YOK", Ingilizce'de
  "IMAGE NOT HERE".
  - Bir animeye gercek posteri eklediginizde placeholder kendiliginden kaybolur
    (gercek poster gosterilir).
  - Posterin gosterildigi tum yerler kapsanir: liste, anime detayi, son
    eklenenler, oneriler, seri zaman cizelgesi ve bekleyenler.

## Nasil calisir (teknik)

- Placeholder veritabanina YAZILMAZ; image_path bos kaldigi surece goruntuleme
  aninda dile gore secilir (yeni `poster_src()` yardimcisi). Yani anime basina
  dosya kopyasi veya veritabani yazimi YOKTUR - yalnizca iki statik gorsel.
- Dual-mode: katalog istemcisinde posteri olmayan bir anime, placeholder'i
  YEREL diline gore gosterir (placeholder katalog uzerinden gonderilmez).

## Kurulum notu

- Iki gorseli `img/` altina su adlarla koyun:
  - `img/no_poster_tr.png` (Turkce - "BURADA RESIM YOK")
  - `img/no_poster_en.png` (Ingilizce - "IMAGE NOT HERE")

## Duzeltme

- **Detay sayfasinda poster oranlari.** Anime detay sayfasindaki kapak alani,
  dikey posterleri yatay bir kutuya esnetip basik gosteriyordu (gecersiz bir
  `object-fit` degeri). Artik poster kendi oraninda, bozulmadan gosterilir
  (kapak kutusu dikey yapildi: 400x600). Bu, yeni "resim yok" placeholder'i icin
  de gecerli - o da dogru oranda gorunur.

## Notlar

- Bu surumde sema degisikligi yoktur (yalniz kod + iki gorsel).

## Bakim / duzen

- Tek-seferlik CLI araclar `files/tek_kullanimlik/` altinda toplandi
  (anilist_isadult_backfill.php buraya tasindi). Ana dosyalarla karismamasi
  icin.

## Degisen dosyalar

- functions/anime_helpers.php (yeni poster_src() yardimcisi)
- index.php, anime_details.php, recent.php, recommendations.php,
  series_timeline.php, pending.php (poster render'lari poster_src ile)
- css/base.css (.anime-cover object-fit duzeltmesi + dikey kutu)
- img/no_poster_tr.png, img/no_poster_en.png (yeni gorseller)
- tek_kullanimlik/anilist_isadult_backfill.php (buraya tasindi)
- version.txt
- migration/1.1.9/upgrade.sql (yeni, no-op)
