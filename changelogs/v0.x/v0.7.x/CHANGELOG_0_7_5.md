# Anime Tracker 0.7.5 - Degisiklikler

**Yayin tarihi:** Haziran 2026

## Ingilizce basliklar artik iliskili animelerde de gosteriliyor

Daha once "Ingilizce basliklari goster" tercihi yalnizca ana anime
basliklarinda calisiyordu. Artik ayni tercih, bir animenin baska
animelerle iliskili oldugu yerlerdeki basliklara da uygulanir.

Ingilizce arayuz + "Ingilizce basliklari goster" acikken (ve o animenin
Ingilizce basligi girilmisse) su yerlerde de Ingilizce baslik gosterilir:

- **Anime detay sayfasi:** "Sonraki anime" linki, ayni serideki animeler
  kutusu, kronoloji uyarisi, izleme sirasi (marker) listesi ve marker
  ekleme acilir listesi.
- **Kronoloji sayfasi:** izleme sirasina serpistirilen film/OVA basliklari.
- **Seri zinciri (timeline) sayfasi:** zincirdeki her parcanin basligi.

Ingilizce baslik girilmemisse ya da tercih kapaliysa, hepsi eskisi gibi
orijinal (Romaji) basligi gosterir; degisen bir davranis yoktur.

Ek olarak, kronoloji sayfasinin kendi ust basligi da bu tercihe baglandi
(onceden bu sayfada ana baslik tercihten bagimsiz Romaji gosteriyordu).

## Diger

### Sema

Bu surum sema degisikligi icermez. `migration/0.7.5` yalnizca surum
numarasini ilerleten bos bir migration'dir; otomatik guncelleme sirasinda
kendiliginden gecer, elle bir sey yapmaniza gerek yoktur.

### Dosyalar

Degisen: `series_helpers.php`, `anime_details.php`, `chronology.php`,
`series_timeline.php`.
Yeni: `migration/0.7.5/upgrade.sql`.
