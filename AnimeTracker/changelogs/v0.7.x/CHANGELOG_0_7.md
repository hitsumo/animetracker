# Anime Tracker 0.7 - Degisiklikler

**Yayin tarihi:** Mayis 2026

## Yeni: Bolum bazinda filler / canon takibi

Artik her anime icin hangi bolumun **dolgu (filler)**, hangisinin **canon**
oldugunu bolum bolum isaretleyebilirsiniz. Isaretsiz birakilan bolumler
canon kabul edilir - yani sadece istisnalari isaretlemeniz yeterli, cogu
bolume hic dokunmaniza gerek yok.

### Acma / kapama

Anime ekleme ve duzenleme formlarina **"Filler bolum izleme"** secenegi
eklendi. Varsayilan olarak kapalidir; bir anime icin actiginizda o
animenin detay sayfasinda filler ozeti gorunur hale gelir. Kapatmak
isaretlerinizi **silmez**, yalnizca gizler - tekrar actiginizda veriler
geri gelir.

### Grid editor

Detay sayfasindaki **"Duzenle"** butonu bolum grid'ini acar. Her bolum
icin bir kutu vardir; kutuya tikladikca tip degisir:

- Isaretsiz (notr) -> Manga Canon -> Anime Canon -> Karisik -> Dolgu -> tekrar isaretsiz

Renkler trafik isigi mantigindadir: canon tipleri yesil, Karisik (yari
dolgu) amber, Dolgu kirmizi, isaretsiz notr. Tum isaretleri yaptiktan
sonra tek **"Kaydet"** butonuyla hepsi birlikte kaydedilir.

### Detay sayfasinda ozet

Filler izleme acik olan animelerde detay sayfasi "Bolum detaylari"
basligi altinda kompakt bir SAYI ozeti gosterir, ornegin:

> 635 Manga Canon, 1 Anime Canon, 567 Dolgu

Her tip icin yalnizca kac bolum oldugu yazilir - uzun seriler icin bolum
bolum aralik listesi cok uzun olurdu. Henuz hicbir bolum isaretlenmemisse
ozet yerine kisa bir bilgi notu gosterilir. Yanindaki "Duzenle" butonu
(her zaman yesil) grid editore goturur.

### Bolum sayisi gerekli

Grid'in olusabilmesi icin animede toplam veya yayinlanan bolum sayisinin
girili olmasi gerekir. Ikisi de boşsa editor, once bolum sayisini
girmeniz icin sizi uyarir.

## Yeni: AnimeFillerList'ten tek tikla ice aktarma

Grid editorde bir **AnimeFillerList adresi** yapistirip "Ice aktar"
butonuna basinca, o show'un tum filler/canon siniflamasi otomatik olarak
grid'e yuklenir. animefillerlist.com kategorileri tiplerimizle dogrudan
eslesir: Manga Canon, Anime Canon, Mixed Canon/Filler (Karisik) ve Filler.

- Adres ornegi: `https://www.animefillerlist.com/shows/detective-conan`
- Ice aktarma grid'i doldurur ama KAYDETMEZ - gozden gecirip "Kaydet"e
  basmaniz gerekir; kontrol sizde kalir
- Kayittaki bolum sayisini asan bolumler atlanir ve kac tanesinin
  atlandigi bildirilir (gerekirse once bolum sayisini artirin)
- Sayfa sunucu tarafindan cekilir (tarayici engellerine takilmadan);
  yalnizca bolum-tip eslemesi alinir, bolum basliklari degil

## Diger

### Migration

Bu surum gercek bir sema degisikligi icerir:

- Yeni tablo: `filler_episodes` (anime basina isaretli bolumler)
- Yeni kolon: `animes.filler_tracking` (gorunurluk bayragi)

`migration/0.7/upgrade.sql` bu degisiklikleri uygular. Otomatik
guncelleme sirasinda kendiliginden calisir; elle bir sey yapmaniza gerek
yoktur.

### i18n

`tr.php` + `en.php`'ye 25 yeni anahtar eklendi (filler editor + detay
ozeti + form toggle + AnimeFillerList ice aktarma). Toplam sozluk
boyutu: 465 -> 490 anahtar (TR/EN paralel).

### Dosyalar

Yeni: `functions/filler_helpers.php`, `update_filler.php`, `filler_edit.php`,
`fetch_filler.php`, `css/filler.css`. Degisen: `add_anime.php`,
`edit_anime.php`, `anime_details.php`, `schema.sql`, `functions.php`,
`style.css`, iki sozluk dosyasi.
