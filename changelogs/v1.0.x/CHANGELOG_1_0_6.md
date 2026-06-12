# Anime Tracker 1.0.6 - Degisiklikler

**Yayin tarihi:** Haziran 2026 (internal milestone)

> Not: Bu surum yalnizca online (cok-kullanici) modu etkiler. Self-host (tek
> kullanici) kurulumlar icin gozle gorulur bir degisiklik YOKTUR; ice aktarma
> ve her sey eskisi gibi calisir. Surum numaralari internal gelistirme
> adimlaridir.

## Ozet

Online uyeler kendi anime listelerini "Listeyi Ice Aktar" ile yuklediklerinde,
listede olup da paylasimli katalogda HENUZ bulunmayan animeler artik sessizce
kayboluyor ya da katalogu kirletecek sekilde cogaltilmiyor. Bunlar bir "katalog
onerisi" kuyruguna dusuyor; yonetici/moderator bu kuyrugu inceleyip uygun
olanlari kataloga ekliyor.

## Online ice aktarma artik moda duyarli

Onceden ice aktarma her iki modda da animeleri dogrudan `animes` tablosuna
yaziyordu. Bu, online'da iki sorun cikariyordu: paylasimli katalogda olmayan
bir anime ya cogaltiliyor ya da kisisel izleme durumu yanlis satira
baglaniyordu. Bu surumde:

- **Online (cok-kullanici):** Ice aktarilan liste, MAL / AniDB kimligine gore
  mevcut katalogla eslestirilir. Eslesenler icin yalnizca senin kisisel izleme
  durumun yazilir (katalog satirina dokunulmaz). Eslesmeyenler `catalog_requests`
  tablosuna `pending` (bekliyor) durumunda kaydedilir - kataloga DOGRUDAN
  eklenmez. Ayni kullanici ayni animeyi tekrar onerirse cogaltilmaz.
- **Self-host (tek kullanici):** Onceki davranis korunur - ice aktarma tam
  yedek geri-yuklemesidir; sahip dogrudan katalogu duzenler, oneri kuyrugu yok.

## Yeni: Katalog Onerileri moderasyon ekrani

Yonetici panelinde (`admin.php`) yeni bir kart: **Katalog Onerileri**. Bekleyen
oneri sayisini rozet olarak gosterir ve `admin_catalog_requests.php` sayfasina
goturur. Bu sayfada moderator/yonetici:

- Bekleyen onerileri toplu secip **Onayla** diyebilir - secilen animeler
  kataloga `source='local'` satir olarak eklenir (sonra `admin_pending.php` /
  `admin_sync.php` ile sunucuya tasinabilir; eksik gorseli/alanlari duzenleme
  ekranindan tamamlanabilir).
- Veya **Reddet** diyebilir - oneri reddedilmis olarak isaretlenir, denetim
  icin kayitlarda durur.

Erisim online'da moderator+; self-host'ta sayfa yalnizca localhost'tan acilir.

## Daha net ice aktarma hata mesajlari

Ice aktarma basarisiz olunca eskiden her durumda "gecerli bir JSON dosyasi
yukleyin" deniyordu - dosya hic okunamadiginda bile. Artik yukleme hatasi
(boyut/sunucu limiti) ile gecersiz icerik ayri ayri raporlanir.

## Veritabani

Bu surum tek bir tablo ekler: `catalog_requests` (online oneri/pending
kuyrugu). Migration otomatik uygulanir (`migration/1.0.6/upgrade.sql`,
idempotent). Mevcut hicbir tabloya dokunulmaz; `animes`, kisisel izleme
tablolari ve istatistikler etkilenmez. Self-host'ta tablo bos kalir.

## Self-host kullanicilar icin ne degisti

Pratikte hicbir sey. Ice aktarma, listen, izleme durumlarin - hepsi eskisi gibi
calisir. Bu surumdeki ozellik tamamen online moda ozeldir.
