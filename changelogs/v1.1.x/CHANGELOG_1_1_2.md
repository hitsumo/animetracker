# Anime Tracker 1.1.2

**Yayin tarihi:** 05.07.2026

## Yeni

- **Yetiskin (+18) icerik gizleme.** Katalogdaki animeler artik +18 olarak
  isaretlenebilir ve bu animeler VARSAYILAN OLARAK GIZLENIR; yalnizca izleyici
  Liste Ayarlari'ndan acarsa gorunur.
  - **Isaretleme:** Anime Ekle / Duzenle formunda "+18 / Yetiskin icerik"
    isaret kutusu. Isaretlenen anime katalogta yetiskin olarak damgalanir.
  - **Ac/kapa:** Liste Ayarlari'nda "Yetiskin icerigi goster" secenegi.
    Varsayilan KAPALI. Kapaliyken +18 animeler liste, arama, oneriler,
    istatistikler ve detay sayfasinda gorunmez; acilinca gorunur.
  - **Kisi bazli:** Tercih her kullaniciya ozeldir. Cok-kullanicili kurulumda
    bir kullanicinin ayari digerini etkilemez; tek-kullanicili kurulumda sahibin
    kendi tercihidir. Varsayilan gizleme, istemeyen kullaniciyi (veya paylasimli
    ekrandaki bir konugu) korur.
  - **Sirali iliskiler:** Kronoloji zaman cizelgesi ve seri zincirinde bir +18
    dugum, yapiyi bozmadan notr bir yer tutucuyla ("Gizli icerik") gosterilir;
    basligi sizmaz.
  - **Detay sayfasi:** Gizli bir +18 animenin dogrudan linkine girilirse sayfa
    sizdirilmaz; nasil acilacagini soyleyen notr bir uyari gosterilir.
  - **Katalog senkronu:** +18 bayragi katalog itme/ice aktarma ile tasinir, boylece
    kurulumlar arasinda tutarli kalir. Bayragi tasimayan eski bir taraftan gelen
    kayit guvenli tarafa (yetiskin degil) duser.

## Notlar

- Sema degisti: animes tablosuna is_adult kolonu eklendi (yama surumu, gercek
  migration). Mevcut animeler etkilenmez; yeni kolon 0 (yetiskin degil) baslar.
- Varsayilan davranis gizlemedir; gosterim bilincli bir tercihtir.
- Yetiskin isaretini moderator/admin (cok-kullanicili) veya operator (tek
  kullanicili) acip kapatabilir.

## Degisen dosyalar

- schema.sql
- migration/1.1.2/upgrade.sql (yeni)
- set_adult_pref.php (yeni)
- functions/anime_helpers.php
- functions/series_helpers.php
- add_anime.php, edit_anime.php
- index.php, recent.php, recommendations.php, statistics.php, anime_details.php
- chronology.php, series_timeline.php
- list_settings.php
- catalog.php, catalog_import.php, catalog_push.php, admin_push.php, admin_sync_example.php
- lang/tr.php, lang/en.php
- version.txt
