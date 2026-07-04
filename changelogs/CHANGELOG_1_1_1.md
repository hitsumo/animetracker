# Anime Tracker 1.1.1

**Yayin tarihi:** 03.07.2026

## Yeni

- **MyAnimeList liste ice aktarma (Faz 1).** Liste Ayarlari sayfasina
  gomulu yeni bir bolum: MyAnimeList disa aktarma dosyanizi (XML veya
  gzip'li .gz) yukleyip listenizi ice aktarirsiniz. Akis iki adimlidir:
  once bir onizleme (dry-run) gosterilir, siz onaylamadan hicbir sey
  yazilmaz.
  - Eslestirme MAL id uzerinden yapilir. Katalogda eslesen animelerin
    kisisel izleme durumu, izlenen bolum sayisi, baslangic/bitis tarihi
    ve notu size ait kayda yazilir.
  - Onizlemede kaynak durumlara gore secim kutulari bulunur (hepsi
    varsayilan isaretli); istemediginiz durumlari (ornegin buyuk bir
    "Izlenme Planlandi" yigini) disarida birakabilirsiniz.
  - Varsayilan davranis "listemde zaten olani atla"dir; istege bagli
    "uzerine yaz" secenegi vardir. Uzerine yazarken yalniz MAL'in verdigi
    alanlar yazilir; mevcut notunuz veya tarihiniz bosuna silinmez.
  - Katalogda olmayan animeler online'da katalog onerisi olarak
    gonderilir; self-host'ta yerel olarak eklenir.

## Notlar

- Durum eslemesi: Watching -> Izleniyor, Completed -> Izlendi,
  On-Hold -> Ertelendi, Dropped -> Birakildi, Plan to Watch -> Planlandi
  (sayisal 1/2/3/4/6 da desteklenir).
- Izleme puani ice aktarilmaz.
- Sema degismedi; bu bir yama surumudur. Mevcut izleme veriniz etkilenmez.

## Degisen dosyalar

- functions/mal_import_helpers.php (yeni)
- functions.php
- list_settings.php
- lang/tr.php, lang/en.php
- version.txt
- migration/1.1.1/upgrade.sql (yeni, no-op)
