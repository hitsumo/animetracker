# Anime Tracker 1.0.7 - Degisiklikler

**Yayin tarihi:** Haziran 2026 (internal milestone)

> Not: Bu surum cogunlukla online / cok-kullanici (Docker) operatorlerini ve yeni
> kurulumlari ilgilendirir. Self-host (tek kullanici) kurulumlar icin tek gozle
> gorulur degisiklik, liste disa/ice aktarmanin artik duygu isaretlerini de
> tasimasidir (asagida); bunun disinda her sey eskisi gibi calisir. Surum
> numaralari internal gelistirme adimlaridir. Bu surum sema (veritabani yapisi)
> degisikligi icermez.

## Ozet

Bu surum, Docker ile online (cok-kullanici) kurulumu mumkun kilar, online'da
eklenen katalog girdilerinin merkez sunucuya otomatik gitmesini saglar, yonetici
sayfalarini ayri bir `admin/` klasorunde toplar, guncellemede tasinan dosyalarin
eski kopyalarini otomatik temizler, anime ekleme/duzenleme sayfalarinin ortak kodunu
sadelestirir ve liste disa/ice aktarmaya duygu destegi ekler.

## Docker ile online (cok-kullanici) kurulum

Onceden Docker imaji yalnizca tek-kullanici (self-host) modunda aciliyordu, cunku
kurulum sihirbazindaki mod secimi Docker akisinda atlaniyordu. Bu surumde mod,
ortam degiskeniyle secilir:

- `.env` dosyasinda `MULTI_USER_MODE=true` yapin, `ADMIN_USER` ve `ADMIN_PASS`
  (en az 8 karakter) degerlerini verin, sonra `docker compose up -d`.
- Ilk acilista veritabani hazirlanir, uygulama cok-kullanici modunda yapilandirilir
  ve verdiginiz bilgilerle ilk yonetici hesabi olusturulur.
- `MULTI_USER_MODE` belirtilmezse varsayilan self-host'tur (giris yok) - onceki
  Docker davranisi korunur.

Notlar: Mod yalnizca ilk kurulumda config'e yazilir; sonradan degistirmek icin
container icindeki config.php elle guncellenmelidir. Ilk yonetici olustuktan sonra
`ADMIN_PASS` degerini `.env`'den silebilirsiniz (admin zaten olustugu icin tekrar
kullanilmaz).

## Online katalog eklemeleri artik merkez sunucuya otomatik gidiyor

Onceden online (cok-kullanici) bir kurulumda eklenen animeler yalnizca o kurulumun
veritabaninda kaliyordu: merkez katalog sunucusuna gonderilmediginden offline /
self-host kullanicilar bu animeleri hic gormuyordu. Manuel gonderim araci yalnizca
makinenin kendisinden (localhost) calistigi icin uzak bir online sunucuda
kullanilamiyordu.

Bu surumde, bir yonetici "Bekleyen Animeler" sayfasindan bir animeyi kataloga
aldiginda (onayladiginda), online sunucu kataloga aldigi kayitlari merkez katalog
sunucusuna otomatik gonderir (sunucudan sunucuya, imzali). Boylece offline /
self-host kullanicilar bir sonraki katalog ice aktarmalarinda bu animeleri de alir.
Ayri bir butona basmak veya manuel gonderim gerekmez.

Kurulum (yalnizca online instance): `admin/admin_secret.php` icine, sunucudaki gizli
anahtarla ayni `ADMIN_PUSH_SECRET` ve merkez sunucunun adresini iceren
`CATALOG_PUSH_URL` tanimlanmalidir. Gonderim basarisiz olursa yerel onay yine de
gecerli kalir; durum mesaji sonucu bildirir.

Notlar: Su an yalnizca onaydan gecen kayitlar otomatik gonderilir; yoneticinin
dogrudan ekledigi (onaya ugramayan) animeler bu kapsamda degildir. Self-host
kurulumlar etkilenmez - onlar onceki gibi elle gonderim yapar.

## Yonetici sayfalari ayri klasorde

Yonetici arayuzu sayfalari (yonetim paneli, bekleyen animeler, oneri/davet/kullanici
yonetimi, yetenekler) ile yerel operator arac ornekleri artik `admin/` alt
klasorunde toplanir. Online / Docker kurulumlarinda bu sayfalara
`.../admin/admin.php` adresinden erisilir. Self-host `.exe` kurulumu yonetici
sayfalarini icermez, bu yuzden o kullanicilar icin bir degisiklik yoktur.

## Guncelleme artik eski dosya kopyalarini temizler

Bir surumde tasinan veya kaldirilan dosyalar guncelleme sirasinda otomatik islenir:
dosya yeni konumuna alinir, eski kopya temizlenir. Onceden guncelleyici yalnizca yeni
dosyalari kopyalar, eski konumdaki kopyalari oldugu gibi birakirdi - bu surumde
yonetici sayfalari `admin/` klasorune tasindigi icin kok dizinde eski admin
dosyalarinin kalmasina yol acardi. Artik bu kalintilar kalmaz. Kisisel ayar
dosyalariniz (orn. yapilandirma) silinmez; yeni konumuna tasinarak korunur. Uygulama
ici guncelleme, elle kopyalama ve Docker icin gecerlidir.

## Yeni kurulumlarda Katalog Onerileri tablosu

Sifirdan kurulan veritabanlari artik `catalog_requests` (online ice aktarmada
katalogda olmayan animeler icin oneri kuyrugu) tablosunu da olusturur. Mevcut
kurulumlar bu tabloyu zaten otomatik guncellemeyle almisti; bu, yalnizca temiz
kurulum yolunu tamamlayan bir duzeltmedir. Self-host'ta tablo bos kalir.

## Ic iyilestirme

Yeni anime ekleme ve duzenleme sayfalarinin ortak form kodu tek bir dosyada
(`js/anime_form.js`) toplandi - davranis aynidir. Kucuk bir gorunur degisiklik:
AnimeSchedule "Otomatik Doldur" sonucu artik her iki sayfada da doldurulan alan
sayisini gosterir (onceden yalnizca ekleme sayfasinda gosteriliyordu).

## Liste yedegi artik duygulari da kapsiyor

Liste disa/ice aktarma (Liste Ayarlari), animelere koydugunuz duygu isaretlerini de
tasir. Onceden yalnizca izleme durumu, izlenen bolum ve notlar disa aktariliyordu;
duygular disarida kaliyordu. Bir listeyi baska bir kuruluma (ornegin online
hesabiniza) tasidiginizda duygular ice aktaran kullaniciya baglanir; kanonik olmayan
degerler atlanir, anime basina en fazla 3 isaret korunur ve ayni dosyayi tekrar ice
aktarmak kopya olusturmaz. Eski (duygu alani olmayan) yedek dosyalari sorunsuz
calismaya devam eder.

## Self-host kullanicilar icin ne degisti

Neredeyse hicbir sey - listen, izleme durumlarin, ekleme ve duzenleme eskisi gibi
calisir. Tek fark: liste disa/ice aktarma artik duygu isaretlerini de tasir (ustteki
bolum), boylece yedek tam olur ve listeni duygularinla birlikte tasiyabilirsin.
