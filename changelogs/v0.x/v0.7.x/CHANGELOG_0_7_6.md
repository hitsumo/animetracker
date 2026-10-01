# Anime Tracker 0.7.6 - Degisiklikler

**Yayin tarihi:** Haziran 2026

## Oneri ekranindaki etiket adlari artik dile duyarli

Oneri sayfasinda, eslesen her anime kartinin altinda hangi cumlelerin
(etiketlerin) eslestigini gosteren kucuk cipler vardir. Bu cipler simdiye
kadar her zaman Turkce cumle adini gosteriyordu.

Artik Ingilizce arayuzde, bir cumlenin Ingilizce karsiligi girilmisse, cip
o Ingilizce adi gosterir. Ingilizce karsiligi girilmemisse ya da arayuz
Turkce ise eskisi gibi Turkce ad gosterilir; degisen bir davranis yoktur.

Boylece anime ana basliklari, iliskili anime basliklari ve tur/cumle
etiketlerinin tamami ayni dil tercihine uyumlu hale gelmis olur.

## Diger

### Sema

Bu surum sema degisikligi icermez. `migration/0.7.6` yalnizca surum
numarasini ilerleten bos bir migration'dir; otomatik guncelleme sirasinda
kendiliginden gecer, elle bir sey yapmaniza gerek yoktur.

### Dosyalar

Degisen: `recommendations.php`.
Yeni: `migration/0.7.6/upgrade.sql`.
