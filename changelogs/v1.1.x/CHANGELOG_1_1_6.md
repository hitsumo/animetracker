# Anime Tracker 1.1.6

**Yayin tarihi:** 2026-07-11

## Yeni

- **AniList listesini ice aktarma.** Artik AniList anime listenizi uygulamaya
  aktarabilirsiniz. Liste Ayarlari sayfasindaki yeni "AniList Listesini Ice
  Aktar" bolumune AniList **kullanici adinizi** yazarsiniz; herkese acik anime
  listeniz AniList'ten cekilir.
  - **Onizleme once, yazma sonra:** Once bir onizleme gosterilir - kac kayit
    okundugu, kacinin katalogda eslestigi, kacinin zaten listenizde oldugu ve
    kacinin katalogda bulunmadigi. Onaylamadan hicbir sey kaydedilmez.
  - **Duruma gore secim:** Ice aktarilacak izleme durumlarini (Izleniyor,
    Tamamlandi, Beklemede, Birakildi, Izlenecek) onizlemeden isaretleyip
    secebilirsiniz. AniList "tekrar izleniyor" durumu Izleniyor, "duraklatildi"
    Beklemede olarak eslenir.
  - **Iki ice aktarma turu:** Onizlemede secersiniz - "listeyi izleme
    durumlariyla aktar" (durum, bolum, tarih, not sizin listenize yazilir) veya
    "sadece icerik" (animeler yalnizca katalogunuza/veritabaniniza eklenir;
    kisisel izleme durumlari alinmaz). "Sadece icerik", herkese acik bir listeyi
    izleme gecmisini almadan katalogu doldurmak icin kullanislidir. **Varsayilan
    tur "sadece icerik"tir**; kendi listenizi durumlariyla almak isterseniz
    "durumlariyla aktar"i secin.
  - **Uzerine yazma secenegi:** Varsayilan olarak listenizde zaten olan kayitlar
    atlanir; isterseniz "uzerine yaz" secenegiyle guncellenir. (Bu secenek
    yalnizca "durumlariyla aktar" turunde gecerlidir.)
  - **Katalogda olmayanlar:** Online surumde katalog onerisi olarak gonderilir
    (moderator onayina); kisisel (self-host) kurulumda dogrudan yerel olarak
    eklenir - MyAnimeList ice aktarmasindaki davranisin ayni.
  - Izleme durumu, izlenen bolum sayisi, baslangic/bitis tarihleri ve notlar
    aktarilir.
  - **Dogru yayin durumu:** Katalogda olmayan bir anime icin, animenin yayin
    durumu (tamamlandi / devam ediyor) AniList verisinden alinir - kisisel
    kurulumda dogrudan eklenirken, online'da katalog onerisine islenip onay
    sirasinda kullanilir. Boylece hala yayinda olan bir anime yanlislikla
    "tamamlandi" olarak gelmez. (MyAnimeList ice aktarmasinda bu bilgi dosyada
    bulunmadigi icin gecerli degildir.)

## Notlar

- Bu ozellik icin AniList listenizin **herkese acik** olmasi gerekir.
- Ice aktarma sirasinda sunucu AniList'e baglanir; internet baglantisi gerekir.
  Cok uzun listeler sayfa sayfa cekilir.
- Sema veya migration degisikligi yoktur (migration/1.1.6 no-op halka). Eslesme,
  MyAnimeList ice aktarmasiyla ayni kimlik (MAL kimligi) uzerinden yapilir; yeni
  tablo veya kolon eklenmez.

## Degisen dosyalar

- functions/anilist_import_helpers.php (yeni)
- functions.php
- list_settings.php
- lang/tr.php, lang/en.php
- css/components.css
- version.txt
- migration/1.1.6/upgrade.sql
