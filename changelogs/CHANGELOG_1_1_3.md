# Anime Tracker 1.1.3

**Yayin tarihi:** 07.07.2026

## Yeni

- **Tur ve cumle icin +18 isaretleme.** Katalogdaki turler (tur) ve oneri
  cumleleri (cumle) artik +18 olarak isaretlenebilir. Yetiskin icerik
  kapaliyken bu terimler arayuzden gizlenir.
  - **Isaretleme:** Tur Yonetimi ve Cumle Yonetimi sayfalarinda satir basina
    "+18" isaret kutusu. Kaydedince terim katalogta yetiskin olarak damgalanir.
  - **Gizleme:** Yetiskin icerik kapaliyken adult bir tur, listedeki tur
    filtresi acilir kutusundan; adult bir cumle, oneriler cumle seciciden;
    adult bir tur rozeti de detay sayfasindan duser.
  - **Anime satirlari etkilenmez:** Bir terimi +18 isaretlemek, o terimi
    tasiyan animeyi gizlemez. Animelerin gorunurlugu 1.1.2'deki yetiskin anime
    bayragiyla yonetilmeye devam eder. Kendi bayragi olmayan ama adult bir tur
    veya cumle tasiyan bir anime listede kalir; yalnizca o terim gizlenir.
  - **Tek ac/kapa:** Mevcut "Yetiskin icerigi goster" tercihi artik animeleri,
    turleri ve cumleleri birlikte yonetir. Ayri bir tercih eklenmedi.
  - **Katalog senkronu:** Tur/cumle +18 bayragi katalog itme/ice aktarma ile
    tasinir (isim bazli ayri harita). Yalnizca adult isaretli terimler gonderilir;
    bayragi tasimayan eski bir taraftan gelen sync yerel isareti TEMIZLEMEZ
    (bir kez adult, yerelde kaldirilana dek adult kalir).

## Notlar

- Sema degisti: genres ve tags tablolarina is_adult kolonu eklendi (yama surumu,
  gercek migration). Mevcut satirlar 0 (yetiskin degil) baslar.
- +18 isaretini moderator/admin (cok-kullanicili) veya operator (tek-kullanicili)
  Tur/Cumle Yonetimi sayfalarindan acip kapatabilir.
- Merkez katalog sunucusu, tur/cumle +18'ini kendi yonetim sayfalarindan
  yonetir; yukari-itme (push) bu metadatayi tasimaz.

## Degisen dosyalar

- schema.sql
- migration/1.1.3/upgrade.sql (yeni)
- functions/anime_helpers.php
- functions/taxonomy_helpers.php
- manage_genres.php, manage_tags.php
- index.php, recommendations.php, anime_details.php
- catalog.php, catalog_import.php, catalog_push.php, admin_sync_example.php
- lang/tr.php, lang/en.php
- version.txt
