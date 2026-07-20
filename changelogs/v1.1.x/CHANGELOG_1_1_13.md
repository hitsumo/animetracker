# Anime Tracker 1.1.13

**Yayin tarihi:** 2026-07-16

## Yeni: Kisisel liste sekmesi

- **Ana liste sayfasina "Genel Liste" / "Kisisel Liste" sekmeleri eklendi.**
  Sayfalama cubugu ile liste tablosu arasinda duran ince bir sekme cubugu.
  - **Genel Liste** - katalogun tamami (onceki varsayilan davranis; hicbir
    sey degismedi).
  - **Kisisel Liste** - yalnizca izleme durumu SECTIGINIZ animeler. Yani
    "Secim Yapilmamis" DISINDAKI her durum burada gorunur: Izlendi,
    Izleniyor, Izlenme Planlandi, Izleme Ertelendi, Izleme Birakildi.
- **Durum sectiginiz an anime kisisel listenize girer.** Bir animenin
  durumunu "Secim Yapilmamis" disinda herhangi bir degere ayarlamak
  (liste ici +/- ile veya detay sayfasindan) onu Kisisel Liste sekmesinde
  gorunur kilar. Durumu tekrar "Secim Yapilmamis" yaparsaniz kisisel
  listeden cikar.
- **Sekme mevcut aramanizi, filtrelerinizi ve siralamanizi korur.** Kisisel
  Liste sekmesindeyken tur / durum / harf / duygu filtreleri ve arama aynen
  calisir; yalniz secili duruma sahip animeler uzerinde. Sekme degistirince
  ilk sayfaya donulur.
- **Sekmeler yalniz kisisel listesi olan kullaniciya gosterilir.** Kendi
  kurulumunuzda (self-host) her zaman gorunur. Cok kullanicili surumde giris
  yapmamis ziyaretcinin kisisel listesi olmadigindan sekme cubugu onlara hic
  cikmaz.

## Yeni: Varsayilan liste tercihi

- **Liste Ayarlari sayfasina "Varsayilan Liste" secimi eklendi.** Anime listesi
  sayfasi acildiginda hangi sekmenin secili gelecegini belirlersiniz: **Genel
  Liste** (onceki davranis) veya **Kisisel Liste**. Arayuz-dili secimindeki gibi
  bir acilir menudur; sectiginiz an kaydedilir.
- **Kisi bazlidir.** Tercih yalniz sizi etkiler (`user_pref` tablosunda
  saklanir), diger kullanicilara dokunmaz.
- **Sekmeye tiklamak tercihi ezer.** Varsayilaniniz Kisisel Liste olsa bile
  ustteki "Genel Liste" sekmesine tiklayarak o an genel listeye gecebilirsiniz;
  bir sonraki acilista yine varsayilaniniz gelir.

## Yeni: Liste Ayarlari sekmeleri

- **Liste Ayarlari sayfasi sekmelere ayrildi.** Uc sekme var:
  - **Ice/Disa Aktar** - Listeyi Disa Aktar, Listeyi Ice Aktar, MyAnimeList
    Listesini Ice Aktar, AniList Listesini Ice Aktar.
  - **Genel Ayarlar** - Arayuz Dili, Baslik Dili, Varsayilan Liste, Yetiskin
    Icerik.
  - **Yonetim Ayarlari** - Turler, Etiketler, Katalog Senkronizasyonu, Bolum
    Sayisi Senkronizasyonu, Guncelleme. Bu sekme
    (butonu ve paneli) yalnizca **moderator/admin** ve **self-host sahibi** icin
    gorunur; online normal uye bu sekmeyi hic gormez (zaten icindeki her bolum
    yetki kapisinin arkasindaydi, simdi sekmenin kendisi de gizli).
  - **Temizleme** - Listeyi Temizle. Yikici bir islem oldugu icin kendi
    sekmesine alindi ve yalnizca **admin** ile **self-host sahibi** icin
    gorunur (buton ve panel `canAdmin` ile sarili; online normal uye ve
    moderator gormez).
- **Sekme gecisi aninda, sayfa yenilenmeden olur.** Acik sekme tarayıcıda
  hatirlanir; bir ice/disa aktarma veya senkronizasyon sonrasi sayfa yenilense
  bile ayni sekme acik kalir.
- **JS kapaliyken hicbir sey kaybolmaz.** Sekme cubugu gizlenir ve tum bolumler
  eskisi gibi alt alta gorunur (progressive enhancement) - hicbir bolum
  tasinmadi, yalniz gruplandi.

## Nasil calisir (teknik)

- Sekme, `?view=personal` URL parametresiyle secilir ve tamamen sorgu-kapsamli
  bir gorunumdur: ana listenin mevcut `user_anime` LEFT JOIN'ine
  `AND ua.watch_status IS NOT NULL` kosulu eklenir. Yeni tablo, alan veya ayar
  gerekmez.
- Gecersiz veya yetkisiz `view` degeri sessizce "Genel Liste"ye duser
  (anonim online ziyaretci dahil), yani parametre ile zorlanamaz.
- Varsayilan liste tercihi per-user `user_pref` anahtari
  `list_view_default`'ta ('all' / 'personal') tutulur; yeni endpoint
  `set_list_view_pref.php` (CSRF korumali, POST) yazar. URL'deki acik `?view=`
  parametresi (sekme tiklamasi) her zaman tercihi ezer, bu yuzden sekmeler
  daima acik view yollar ve korunan siralama/filtre/arama baglantilari view'i
  yalniz tercihten farkli oldugunda tasir.

## Sema / migration

- `migration/1.1.13/upgrade.sql` yalnizca surumu 1.1.13'e tasir; **sema
  degisikligi yoktur** (calistirilacak SQL ifadesi yok). Merkez katalog
  etkilenmez, sunucuda elle bir islem GEREKMEZ.

## Degisen / yeni dosyalar

- index.php (Genel/Kisisel sekme cubugu, view kapsami, varsayilan tercih
  cascade'i, sekmenin filtre/arama/siralama/sayfalama baglantilarinda
  korunmasi, sekme CSS'i)
- list_settings.php (Varsayilan Liste secim bolumu + Ice/Disa Aktar & Genel
  sekmeleri: sekme cubugu, panel sarmalamalari, sekme CSS'i ve JS gecisi)
- set_list_view_pref.php (yeni - CSRF korumali tercih endpoint'i)
- lang/tr.php, lang/en.php (index.tab.* + list_settings.section.list_view.* +
  list_settings.tab.*)
- migration/1.1.13/upgrade.sql (yeni)
- version.txt
