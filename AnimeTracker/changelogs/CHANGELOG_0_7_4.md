# Anime Tracker 0.7.4 - Degisiklikler

**Yayin tarihi:** Mayis 2026

## Liste yedekleme (disa/ice aktarma) artik dogru calisiyor

Liste Ayarlari sayfasindaki JSON disa/ice aktarma onarildi.

- **Disa aktarilan yedek artik turleri ve cumleleri (etiketleri) de iceriyor.**
  Eskiden disa aktarilan JSON yalniz temel anime bilgilerini tasiyordu; tur ve
  cumle baglantilari yedege hic girmiyordu. Artik her anime'nin turleri ve
  cumleleri de yedege yazilir.
- **Ice aktarma artik calisiyor.** Eskiden bir yedegi geri yuklemeye
  calistiginizda islem hata verip yarida kaliyordu. Artik yedek sorunsuz geri
  yuklenir; turler ve cumleler de yeniden olusturulur.
- **Ice aktarma sonunda ozet.** Islem bitince "X anime ice aktarildi, Y atlandi"
  seklinde bir bilgi mesaji gosterilir.

Ice aktarma bir **geri yukleme** olarak calisir: yedeginizi listenize geri
ekler. Ayni yedegi ikinci kez geri yuklemeden once "Listeyi Temizle" ile
listeyi bosaltmaniz onerilir; aksi halde ayni animeler tekrar eklenebilir.

## "Listeyi Temizle" duzeltildi

"Listeyi Temizle" islemi de onarildi - eskiden listeyi temizlemeyip oldugu gibi
birakabiliyordu. Artik listeyi ve ona bagli tur/cumle baglantilarini duzgunce
temizler. Tur ve cumle adlarinin ana listesi korunur.

## Istatistikler - Toplam Bolum sayisi

Istatistikler sayfasinda, "Toplam Izlenen Bolum"un yaninda artik **Toplam Bolum**
sayisi da gosterilir: listenizdeki tum animelerin bilinen bolum sayilarinin
toplami. Bolum sayisi henuz bilinmeyen (belirsiz / devam eden) animeler bu
toplama dahil edilmez.

## Diger

### Sema

Bu surum sema degisikligi icermez. `migration/0.7.4` yalnizca surum numarasini
ilerleten bos bir migration'dir; otomatik guncelleme sirasinda kendiliginden
gecer, elle bir sey yapmaniza gerek yoktur.

### Dosyalar

Degisen: `list_settings.php`, `statistics.php`, `tr.php`, `en.php`.
Yeni: `migration/0.7.4/upgrade.sql`.
