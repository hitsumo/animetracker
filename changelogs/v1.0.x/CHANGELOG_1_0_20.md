# Anime Tracker 1.0.20

**Yayin tarihi:** 27.06.2026

## Yenilikler

- Davet koduyla kayit acik oldugunda, daveti olmayan ziyaretciler artik
  davetiye talep edebilir. Kayit sayfasinda yeni bir "Davetiye Talep Et" baglantisi
  var; ziyaretci e-posta adresini ve neden davetiye istedigini yazar. Talep,
  davet yonetimi sayfasinda yeni bir "Davetiye Talepleri" sekmesinde toplanir
  ve buradan tek tikla o talebe davet kodu uretebilirsiniz.
- Bir talep geldiginde, tanimladiginiz bildirim adresine kisa bir e-posta
  gonderilir (talep edenin e-postasi + nedeni). Bildirim adresi davet yonetimi
  sayfasindan ayarlanir; bos birakilirsa e-posta gonderilmez, talepler yine
  sekmede gorunur.

## Notlar

- Davetiye talebi yalnizca cok kullanicili kurulumda ve kayit modu "davetli"
  iken calisir. Tek kullanicili (self-host) kurulumda kayit/davet yoktur, bu
  ozellik gorunmez.
- Talep formu istenmeyen gonderimlere karsi korunur (gizli alan + IP basina
  saatlik gonderim siniri).
- Bildirim e-postasi, talep zaten sekmede kayitli oldugu icin "en iyi caba"
  ile gonderilir; gonderim basarisiz olsa bile talep kaybolmaz.
- Bildirim e-postasinin teslimi icin sunucunuzun e-posta gonderimi (yerel posta
  servisi ve alan adi DNS kayitlari) calisir durumda olmalidir.
- Bu surum veritabanina yeni bir tablo ekler (davetiye talepleri).
