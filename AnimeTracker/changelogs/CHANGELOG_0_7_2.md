# Anime Tracker 0.7.2 - Degisiklikler

**Yayin tarihi:** Mayis 2026

## Ingilizce baslik destegi

Animelere artik istege bagli bir **Ingilizce baslik** girebilirsiniz.
Anime ekleme ve duzenleme ekraninda, alternatif isimlerin altinda yeni
bir "Ingilizce Baslik" alani var.

Liste Ayarlari sayfasindaki yeni **"Baslik Dili"** bolumunden
**"Ingilizce basliklari goster"** secenegini acarsaniz, Ingilizce basligi
girilmis animeler liste ve detay sayfalarinda Romaji baslik yerine
Ingilizce basligiyla gosterilir. Bu tercih arayuz dilinden bagimsizdir:
Turkce arayuzde bile Ingilizce baslik gosterebilir, ya da Ingilizce
arayuzde Romaji'de kalabilirsiniz. Ingilizce baslik bos olan animeler her
zaman Romaji basligina duser.

## Tur ve cumlelere Ingilizce karsilik

**Tur Yonetimi** ve **Cumle Yonetimi** sayfalarinda her tur ve her cumle
icin bir **Ingilizce karsilik** girebilirsiniz. Arayuz dili Ingilizce
oldugunda, Ingilizce karsiligi girilmis turler ve cumleler her yerde
(detay sayfasi tur rozetleri, liste tur filtresi, oneri sistemi cumle
listesi) Ingilizce gosterilir. Ingilizce karsiligi bos olanlar Turkce
adina duser.

Tur Yonetimi'nde her satira eklenen kucuk bir alandan Ingilizce adi
girip kaydedersiniz. Cumle Yonetimi'nde Ingilizce karsilik, mevcut
"Yeniden Yaz" formuna eklendi; tek kaydetmeyle hem Turkce cumleyi hem
Ingilizce karsiligini guncellersiniz.

## Diger

### i18n

Bu surumde **11 yeni metin anahtari** eklendi (Ingilizce baslik alani ve
ipucu, Tur Yonetimi Ingilizce ad alani, Cumle Yonetimi Ingilizce karsilik
alani, Liste Ayarlari baslik dili bolumu). Toplam sozluk boyutu:
535 -> 546 anahtar (TR/EN paralel).

### Sema

Bu surumde **gercek sema degisikligi var**. Uc yeni sutun eklenir:
`genres.name_en`, `tags.name_en`, `animes.title_english` (hepsi opsiyonel,
bos baslar). `migration/0.7.2` otomatik guncelleme sirasinda kendiliginden
gecer; sutunlar ilk acilista olusur, elle bir sey yapmaniza gerek yoktur.

Sunucu (katalog) ile veri alisverisinde yalnizca `title_english` tasinir.
Tur ve cumle Ingilizce karsiliklari su an yereldir; bunlarin sunucuyla
paylasilmasi sonraki bir asamaya birakilmistir (mevcut kurulumda gosterim
tam calisir, paylasim beklemededir).

### Dosyalar

Yeni: `migration/0.7.2/upgrade.sql`, `set_title_pref.php`.

Degisen: `schema.sql`, `functions/taxonomy_helpers.php`,
`functions/anime_helpers.php`, `catalog.php`, `admin_push.php`,
`catalog_import.php`, `add_anime.php`, `edit_anime.php`,
`anime_details.php`, `index.php`, `recommendations.php`,
`manage_genres.php`, `manage_tags.php`, `list_settings.php`,
`css/components.css`, `tr.php`, `en.php`.
