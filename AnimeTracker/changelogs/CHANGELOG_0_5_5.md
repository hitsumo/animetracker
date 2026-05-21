# Anime Tracker 0.5.5

**Sürüm tarihi:** 19 Mayıs 2026
**Tür:** Yeni özellik + arayüz düzeltmesi

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur.

## Yeni özellik

- **Liste üzerinden hızlı bölüm güncelleme:** Artık ana listede her
  animenin "İzlenen Bölüm" sütununda küçük bir `−` / `+` düğmesi var.
  Bir bölüm izlediğinizde "Düzenle" ekranını açmanıza gerek kalmadan,
  doğrudan listeden izlenen bölüm sayısını bir artırıp
  azaltabilirsiniz. Sayfa yenilenmeden anında güncellenir.

  - İzlenen bölüm 0'ın altına inemez (`−` o noktada pasifleşir).
  - İzlenen bölüm, toplam (veya henüz toplam girilmemişse yayınlanan)
    bölüm sayısının üzerine çıkamaz (`+` o noktada pasifleşir).
  - Toplam ve yayınlanan bölüm sayısının ikisi de bilinmiyorsa
    düğmeler gösterilmez; önce bölüm bilgisini girmeniz ya da
    senkronize etmeniz gerekir.

## Düzeltme

- **Liste tablosu artık taşmıyor:** Önceki sürümlerde, özellikle
  tarayıcı yakınlaştırması yüksek olduğunda (örneğin %125), liste
  tablosu sayfa kutusunun dışına taşabiliyordu. Bu sürümde tablo
  sayfa genişliğine sığacak şekilde düzeltildi; uzun metinler hücre
  içinde alt satıra kayar, tablo artık taşmaz.

## Notlar

Bu sürüm yalnızca arayüz ve kullanım kolaylığı içerir; veritabanınızda
herhangi bir değişiklik veya ek işlem gerekmez. Mevcut izleme
verileriniz olduğu gibi korunur.
