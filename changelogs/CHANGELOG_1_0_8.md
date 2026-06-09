# Anime Tracker 1.0.8 - Degisiklikler

**Yayin tarihi:** Haziran 2026 (internal milestone)

> Not: Bu surum cogunlukla online / cok-kullanici kurulumlardaki kucuk
> duzeltmeleri ve mobil gorunumu ilgilendirir. Self-host (tek kullanici)
> kurulumlar icin iki gozle gorulur degisiklik var: mobilde liste tablosu
> artik tasmadan goruntulenir ve liste ice aktarma artik dolu bir katalog
> uzerine de calisir (asagida). Surum numaralari internal gelistirme
> adimlaridir. Bu surum sema (veritabani yapisi) degisikligi icermez.

## Ozet

Bu surum, mobilde anime listesi tablosunun tasmasini giderir; giris yapmamis
ziyaretcilere bolum sutununu duzenlenemez bicimde (yalniz toplam sayi) gosterir;
katalogdan anime silmeyi yetkili kullaniciyla sinirlar; ve liste ice aktarmayi
mevcut katalogla eslestirip dolu bir veritabani uzerine de calisir hale getirir.

## Mobilde liste tablosu duzeltmesi

Dar ekranlarda (telefon) anime listesi tablosunun sutunlari sigmayip yazilar
ust uste biniyordu (baslik, durum ve bolum metinleri komsu sutunlara tasiyordu).
Artik mobilde tablo yatay kaydirilabilir; sutunlar okunabilir genislikte kalir
ve metinler birbirine binmez. Masaustu gorunum degismez.

## Giris yapmamis ziyaretciler icin bolum sutunu

Giris yapmamis bir ziyaretci icin "izlenen bolum" kavraminin karsiligi yoktur
(kisisel izleme durumu tutulmaz). Artik bu ziyaretciler bolum sutununda yalniz
toplam bolum sayisini gorur; sutun basligi "Bolum Sayisi" olur ve artirma/azaltma
(+/-) kontrolleri gosterilmez. Giris yapmis kullanicilarda ve self-host
kurulumlarda her sey eskisi gibidir (izlenen/toplam gosterimi ve +/- kontrolleri).

## Katalogdan silme artik yetki ister

Online (cok-kullanici) bir kurulumda, anime listesindeki "Sil" islemi yalniz istek
dogrulamasi (CSRF) ile korunuyordu; bu, giris yapmamis bir ziyaretcinin de
katalogdan anime silebilmesi anlamina geliyordu. Artik silme islemi sunucu
tarafinda yetki ister (moderator ve ustu); yetkisi olmayan ziyaretcilere "Sil" ve
"Duzenle" butonlari gosterilmez. Self-host kurulumda sahip zaten tum yetkilere
sahip oldugu icin davranis degismez.

## Liste ice aktarma dolu katalog uzerine calisir

Onceden self-host liste ice aktarma, listedeki her animeyi yeni kayit olarak
eklemeye calisiyordu. Katalog senkronu (sunucudan anime cekme) ayni animeleri zaten
eklemisse, ice aktarma bu kayitlarla cakisip basarisiz oluyor ve hicbir satir
alinamiyordu (kullaniciya "gecersiz dosya" gorunuyordu). Artik ice aktarma once
mevcut animeyi (MAL / AniDB kimligi veya katalog kimligiyle) eslestirir: anime
varsa o kayda izleme durumunu, notlari ve duygulari yazar; yoksa yeni ekler.
Boylece bir listeyi dolu bir veritabanina (ornegin katalogu cekmis bir kuruluma)
ice aktarmak sorunsuz calisir; bos bir veritabanina tam geri yukleme de eskisi
gibi calismaya devam eder.

## Self-host kullanicilar icin ne degisti

Iki sey: (1) mobilde liste tablosu artik tasmadan goruntulenir; (2) liste ice
aktarma, listedeki animeler veritabaninda zaten varsa bunlari eslestirip kisisel
verinizi (izleme durumu, notlar, duygular) uzerine yazar - eskiden cakisip bos
donerdi. Listen, izleme durumlarin, ekleme ve duzenleme aksi belirtilmedikce eskisi
gibi calisir.
