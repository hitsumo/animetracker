# Anime Tracker 1.1.4

**Yayin tarihi:** 09.07.2026

## Yeni

- **Arayuz dili artik Liste Ayarlari'ndan secilir.** Arayuz dili (Turkce /
  Ingilizce) onceden her sayfanin ust bolumundeki kucuk TR / EN switcher ile
  degisiyordu. Artik tek bir yerden secilir: Liste Ayarlari sayfasindaki
  "Arayuz Dili" acilir menusu.
  - **Tek nokta:** Dil secimi Liste Ayarlari'na tasindi; sayfa ust
    bolumlerindeki TR / EN switcher kaldirildi (alti sayfa: liste, anime ekle,
    anime duzenle, detay, oneriler, liste ayarlari).
  - **Aninda uygulanir:** Menuden secim yapildiginda dil hemen degisir;
    JavaScript kapaliysa bir "Kaydet" butonu gorunur.
  - **Baslik dilinden bagimsiz:** Arayuz dili, "Baslik Dili" tercihinden (anime
    basliklarini Romaji yerine Ingilizce gosterme) ayridir; ikisi bagimsiz
    secilir.

## Notlar

- Sema veya migration degisikligi yoktur. Dil tercihi (display_language) ayni
  kullanici tercihinde saklanmaya devam eder; degisen yalnizca secim noktasidir
  (arayuz), altyapi degil.
- Dil yazma islemi ayni endpoint uzerinden yurur; POST + CSRF korumasi ve
  ayni-host yonlendirme sertlestirmesi korunur.

## Degisen dosyalar

- list_settings.php
- index.php, add_anime.php, edit_anime.php, anime_details.php, recommendations.php
- lang/tr.php, lang/en.php
- css/lang.css
- version.txt
