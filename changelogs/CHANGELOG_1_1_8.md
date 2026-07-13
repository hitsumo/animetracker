# Anime Tracker 1.1.8

**Yayin tarihi:** 2026-07-12

## Iyilestirme

- **Anime kaydetmek artik cok daha hizli.** Bir animeyi eklediginizde veya
  duzenlediginizde uygulama eskiden tum katalogu merkez sunucuya yeniden
  gonderiyordu; katalog buyudukce (binlerce anime) bu islem kaydetmeyi
  bekletir hale gelmisti. Artik yalnizca ilgili animenin dahil oldugu **seri**
  (ayni seri adini paylasan kayitlar) gonderilir; seri adi bos ise yalnizca o
  anime gider. Boylece kaydetme aninda tum katalog yerine birkac kayit gonderilir.

## Yoneticiler icin

- **Duzenleme sayfasina "Tum Katalogu Gonder" butonu (yalnizca admin).**
  Normal "Guncelle" artik yalnizca ilgili seriyi gonderdiginden, tum katalogu
  merkeze bir kerede yeniden gondermek isteyen admin bu butonu kullanir.
  (Mevcut manuel gonderim araci yalnizca localhost'ta calisiyordu; bu buton
  online calisir.)

## Notlar

- Bu surumde sema degisikligi yoktur.
- Toplu onay (bekleyenleri terfi) ve kronoloji notu ekleme hala tum katalogu
  gonderir - bunlar seyrek islemlerdir ve tam esitleme gerektirir.

## Arayuz

- Liste/detay butonlari ("Anime Listesi", "Anime Detayi" vb.) mavi yerine teal
  renge alindi.

## Degisen dosyalar

- admin/catalog_push.php
- edit_anime.php
- add_anime.php
- lang/tr.php, lang/en.php
- css/list.css
- version.txt
- migration/1.1.8/upgrade.sql (yeni, no-op)
