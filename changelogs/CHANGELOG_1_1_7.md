# Anime Tracker 1.1.7

**Yayin tarihi:** 2026-07-12

## Duzeltme

- **Ice aktarilan yetiskin (+18) basliklar artik dogru isaretlenir.** AniList
  listenizi ice aktarirken, AniList'te yetiskin olarak isaretli basliklar artik
  katalogda da +18 olarak isaretlenir. Boylece bu basliklar +18 icerigi gizleme
  tercihine ve +18 filtresine uyar.
  - Onceki surumde (1.1.6) AniList ice aktarmasi bu bilgiyi almiyordu; yetiskin
    basliklar isaretsiz (gorunur) olarak kataloga girebiliyordu. 1.1.7 bunu
    kapatir.
  - Hem kisisel (self-host) dogrudan eklemede hem de online katalog onerisi ->
    moderator onayi yolunda +18 isareti tasinir.

- **Buyuk AniList listeleri artik guvenilir ice aktarilir.** AniList'in dakika
  basi istek siniri nedeniyle cok animeli (yuzlerce) listelerde ice aktarma
  ortada "istek sinirina ulasildi" hatasiyla kesilebiliyordu. Artik sayfalar
  arasi bekleme sinirin altinda tutulur ve sinir gelirse islem durmak yerine
  kisa bir sure bekleyip ayni sayfayi yeniden dener; boylece buyuk listeler
  tamamlanir. (Bu nedenle cok buyuk listelerin ice aktarilmasi biraz daha uzun
  surebilir.)

## Daha once ice aktarilan basliklari duzeltme (istege bagli)

- 1.1.7'den ONCE AniList ile ice aktardiginiz basliklar isaretsiz kalmis
  olabilir. Bunlari tek seferde duzeltmek icin komut satiri araci:

  ```
  php anilist_isadult_backfill.php > isadult_backfill.sql
  ```

  Arac veritabaninda hicbir sey degistirmez; yalnizca AniList'e sorup yetiskin
  basliklar icin bir `UPDATE` SQL'i uretir. Uretilen dosyayi gozden gecirip
  veritabani yoneticinizde (ornegin phpMyAdmin) calistirirsiniz.

## Notlar

- Bu surumde gercek bir sema degisikligi vardir: online katalog oneri kuyruguna
  +18 isaret kolonu eklenir (migration/1.1.7). Migration otomatik uygulanir;
  merkez katalog sunucusunda elle islem gerekmez.
- Anime tablosundaki +18 isareti zaten mevcuttu; degisen yalnizca ice aktarma
  yolunun bu bilgiyi doldurmasidir.

## Degisen dosyalar

- functions/anilist_import_helpers.php
- list_settings.php
- admin/admin_catalog_requests.php
- anilist_isadult_backfill.php (yeni, tek seferlik arac)
- schema.sql
- migration/1.1.7/upgrade.sql (yeni)
- version.txt
