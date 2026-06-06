# Anime Tracker 1.0.5 - Degisiklikler

**Yayin tarihi:** Haziran 2026 (internal milestone)

> Not: Bu surum cok-kullanici (online) modun devamidir. Iki sey ekler: DUZELTME
> ONERILERI (kullanicilarin katalog hatalarini bildirmesi + moderasyon) ve
> KULLANICI KATALOG EKLEME (giris yapmis kullanicilarin onaya dusen anime
> ekleyebilmesi). Self-host (tek kullanici) kurulumlar icin gozle gorulur bir
> degisiklik YOKTUR. Surum numaralari internal gelistirme adimlaridir.

## Duzeltme onerileri

Bir animede hatali veya eksik bilgi goren herkes - anonim ziyaretciler dahil -
artik duzeltme onerisi gonderebilir. Anime detay sayfasina bir **"Duzeltme
Oner"** formu eklendi (serbest metin not). Gonderilen her oneri moderasyon
kuyruguna "bekliyor" olarak duser.

Yonetici paneline **"Duzeltme Onerileri"** ekrani eklendi (moderator ve ustu).
Bekleyen / kabul / ret sekmeleri, her oneride kabul et / reddet / tekrar ac
butonlari ve ilgili animeye "duzenle" baglantisi vardir. Kabul edilen bir
oneriyi kataloga islemek MANUELDIR (moderator animeyi acip duzenler); otomatik
uygulama yoktur.

Anti-spam icin iki onlem var: forma gizli bir tuzak alani (insanlarin gormedigi,
botlarin doldurdugu) ve IP basina saatlik gonderim siniri. Sinir/kotuye-kullanim
takibi icin gonderenin IP'si saklanir; bu bilgi yalnizca moderasyon ekraninda
gorunur, sitede kullanicilara gosterilmez.

## Kullanici katalog ekleme

Online modda artik giris yapmis HER kullanici yeni anime ekleyebilir (eskiden
yalnizca moderator ve ustu ekleyebiliyordu). Ekleyenin rolune gore iki yol var:

- **Moderator / yonetici** eklediginde anime DOGRUDAN katalogda gorunur (onay
  beklemez).
- **Normal kullanici** eklediginde anime ONAYA duser: ana katalog listesinde
  GORUNMEZ. Bunun yerine herkese acik **"Onay Bekleyen Eklemeler"** sayfasinda
  listelenir (ana sayfada bu sayfaya "Onay bekleyen eklemeler (N)" baglantisi
  vardir). Bir moderator/yonetici onayladiginda anime ana kataloga gecer.

Anonim ziyaretciler ekleyemez; onlara "Ekle" baglantisi gosterilmez.

## Guncelleme

Uygulama ici "Guncelleme Kontrolu" (otomatik ZIP guncellemesi) artik yalnizca
self-host kurulumlar icindir. Online (cok-kullanici) kurulumda bu bolum yerine
kaynak depo (GitHub) baglantisi gosterilir; online kurulumlar git/Docker ile
guncellenir. Online'da otomatik ZIP guncellemesi sunucu tarafinda da reddedilir -
boylece eksik/karisik (yeni cekirdek + eski admin) bir guncelleme olusmaz.

## Self-host kullanicilar icin ne degisti

Hicbir sey. Oneri formu, moderasyon ekrani ve "onay bekliyor" akisi yalnizca
cok-kullanici modu acikken gorunur. Tek kullanicili kurulumda sahip animeyi
eskisi gibi dogrudan ekler (onay yok) ve oneri ozelligi gozukmez.

## Diger

### Sema

Bu surum sema degisikligi icerir, ancak otomatik guncelleme ile kendiliginden
uygulanir - elle bir sey yapmaniza gerek yoktur. Eklenen: `suggestions` tablosu
(duzeltme onerileri kuyrugu). `migration/1.0.5` otomatik, tekrar calismaya
dayanikli (idempotent) gecer. Cok-kullanici modu kapaliyken bu tablo bos kalir;
self-host'ta hicbir etkisi yoktur.

> sicakcikolata.com katalog sunucusu bu degisiklige DAHIL DEGILDIR.

### Dosyalar

Yeni: `suggest.php` (oneri gonderme), `admin_suggestions.php` (oneri moderasyon
kuyrugu), `pending.php` (onay bekleyen eklemeler - herkese acik liste). Degisen:
`anime_details.php` (oneri formu), `add_anime.php` (giris yapmis her kullaniciya
acildi; role gore dogrudan katalog ya da onaya), `index.php` (onay bekleyenleri
ana listeden ayirma + "Onay bekleyen eklemeler" baglantisi + "Ekle" gizleme),
`update.php` + `list_settings.php` (online'da ZIP guncellemesi yerine GitHub
baglantisi), `admin.php` (yonetici paneline yeni kart) ve dil dosyalari.
