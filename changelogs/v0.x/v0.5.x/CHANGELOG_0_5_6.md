# Anime Tracker 0.5.6

**Sürüm tarihi:** 21 Mayıs 2026
**Tür:** Yeni özellik (otomasyon)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur.

## Yeni özellik

- **İzleme durumu otomatik güncellenir:** Artık ana listede `+` / `−`
  düğmelerine basarken izleme durumu da otomatik olarak güncellenir.
  "Düzenle" ekranını açıp manuel olarak durum değiştirmeniz
  gerekmiyor.

  - **"İzlenme Planlandı" durumundayken `+` basarsanız** durum
    otomatik "İzleniyor"a geçer. Bu kural hem yeni başladığınız
    animeler için (0/12'den ilk `+`) hem de izlemeye ara verip daha
    sonra "Düzenle"den manuel olarak "İzlenme Planlandı"ya geri
    çektiğiniz animeler için geçerlidir (örn. 5/12'de bıraktınız,
    aylar sonra `+` basınca tekrar "İzleniyor" olur).

  - **Toplam (veya yayınlanan) bölüm sayısına ulaştığınızda** durum
    otomatik "İzlendi"ye geçer. Bunu son `+` basışınızda görürsünüz
    (örn. 11/12 → `+` → 12/12 ve aynı anda "İzleniyor" → "İzlendi").

  - **Tek aksiyon, iki adım da olabilir:** Eğer bir animeyi
    "İzlenme Planlandı" bırakmışsanız ve son bölümün hemen öncesine
    geldiyseniz (örn. 11/12), tek `+` basışıyla "Planlandı" →
    "İzlendi" geçişi tek seferde yapılır.

## Notlar

- **Bu sürümde sadece ileri yön çalışır.** `−` ile izlenen bölüm
  sayısını düşürdüğünüzde durum "İzlendi" olarak kalır, otomatik
  olarak "İzleniyor"a geri dönmez. Bu davranış bir sonraki sürümde
  (0.5.7) eklenecektir.

- Otomatik geçiş yalnızca toplam veya yayınlanan bölüm sayısı
  bilinen animelerde tetiklenir. Bölüm bilgisi olmayan animelerde
  `+` zaten kullanılamadığı için durum da otomatik değişmez.

- Bu sürüm yalnızca arayüz ve kullanım kolaylığı içerir; veritabanı
  yapısında herhangi bir değişiklik veya ek işlem gerekmez. Mevcut
  izleme verileriniz olduğu gibi korunur.
