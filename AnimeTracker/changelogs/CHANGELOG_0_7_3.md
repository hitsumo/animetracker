# Anime Tracker 0.7.3 - Degisiklikler

**Yayin tarihi:** Mayis 2026

## Kisisel Konu artik Turkce ve Ingilizce ayri

Daha once tek bir "Kisisel Konu" alani vardi. Artik Kisisel Konu Turkce
ve Ingilizce icin ayri ayri tutulur: **Kisisel Konu (TR)** ve **Kisisel
Konu (EN)**. Boylece bir animenin Turkce kisisel notu ile Ingilizce
kisisel notu birbirinden bagimsiz olur; birini doldurup digerini bos
birakabilirsiniz.

Anime detay sayfasinda, arayuz dili neyse o dilin kisisel konusu
katalog konusunun **altinda** ayri bir satirda gosterilir. Katalog konusu
(resmi ozet) her zaman ustte gorunmeye devam eder; kisisel notunuz onun
yerini almaz, yanina eklenir.

## Duzenlediginiz konu artik kaybolmuyor

Onemli bir duzeltme: bir animenin katalog konusunu (Konu TR veya Konu EN)
kendiniz duzenlediginizde, eskiden bir sonraki katalog senkronizasyonunda
bu degisiklik sessizce kaybolurdu. Artik kaybolmuyor.

Senkronizasyon sirasinda sistem her dil icin ayri ayri bakar: o dilin
katalog konusunu siz degistirmisseniz, **degistirdiginiz metin Kisisel
Konu alanina tasinir** (Turkce degisiklik Kisisel Konu TR'ye, Ingilizce
degisiklik Kisisel Konu EN'e), sonra katalog konusu sunucudaki guncel
haliyle geri gelir. Yani hem emeginiz korunur hem katalog guncel kalir.

Tasima yalnizca o dili degistirdiyseniz olur. Sadece Turkce'yi
degistirdiyseniz yalnizca Turkce tasinir; Ingilizce katalog konusu
oldugu gibi kalir ve duzenlenebilir olmaya devam eder.

Bir dilin konusu Kisisel Konu'ya tasindiktan sonra, o dilin katalog
konusu kilitlenir (salt-okunur olur). Bu, "Konu sunucudan gelir"
ilkesini korur. (Katalog sahibi/kurator bu kilidi Yonetici Yetenekleri
sayfasindan acabilir.)

## Diger

### Sema

Bu surumde gercek sema degisikligi var: yeni `animes.user_synopsis_en`
kolonu eklenir (opsiyonel, bos baslar). `migration/0.7.3` otomatik
guncelleme sirasinda kendiliginden gecer.

### Dosyalar

Yeni: `migration/0.7.3/upgrade.sql`.

Degisen: `schema.sql`, `catalog_import.php`, `edit_anime.php`,
`anime_details.php`, `catalog.php`, `tr.php`, `en.php`.
