# Anime Tracker 0.7.7 - Degisiklikler

**Yayin tarihi:** Haziran 2026

## Tur ve cumle etiketlerinin Ingilizce adlari artik katalog senkronizasyonuyla sunucu uzerinden tasiniyor

Turlerin ve oneri cumlelerinin (etiketlerin) Ingilizce karsiligini
girebiliyordunuz, ama bu Ingilizce adlar yalnizca kendi kurulumunuzda
duruyordu; katalog senkronizasyonu sirasinda diger kurulumlara gitmiyordu.

Artik katalog, bir turun veya cumlenin Ingilizce adi girilmisse bunu da
tasir. Boylece ayni katalogdan beslenen kurulumlar, tur ve cumlelerin
Ingilizce karsiliklarini da paylasmis olur.

Davranis kurali soyle: katalog bir Ingilizce ad gonderdiginde yereldeki
deger onun lehine guncellenir. Katalog Ingilizce ad gondermiyorsa, daha
once kendiniz girdiginiz Ingilizce ad oldugu gibi kalir; senkronizasyon
girdiginiz bir adi asla silmez.

Tek kullanicili bir kurulumda gozle gorulur bir farki yoktur, cunku
zaten kendi adlarinizi girip goruyorsunuz. Fark, birden fazla kurulumun
ayni katalogdan beslendigi durumda ortaya cikar.

## Admin: sync hata mesajindaki dosya adi duzeltildi

Admin sync sayfasinda HMAC anahtari tanimli degilken cikan hata mesaji,
anahtarin `config.php` icinde aranmasi gerektigini soyluyordu. Anahtar
aslinda `admin_secret.php` dosyasindan okunur. Mesaj dogru dosya adini
gosterecek sekilde duzeltildi. Bu yalnizca admin tarafini ilgilendirir;
son kullanici arayuzunde bir degisiklik yoktur.

## Diger

### Sema

Bu surum sema degisikligi icermez. Kullanilan tur/cumle Ingilizce ad
sutunlari onceki bir surumde eklenmisti. `migration/0.7.7` yalnizca surum
numarasini ilerleten bos bir migration'dir; otomatik guncelleme sirasinda
kendiliginden gecer, elle bir sey yapmaniza gerek yoktur.

### Dosyalar

Degisen: `catalog.php`, `catalog_import.php`, `admin_sync_example.php`,
`lang/admin_tr.php`, `lang/admin_en.php`.
Yeni: `migration/0.7.7/upgrade.sql`.
