# Anime Tracker 0.5.8

**Sürüm tarihi:** 23 Mayıs 2026
**Tür:** İyileştirme (yardım belgesi + arayüz düzenleme)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur.

## Yeni

- **Yardım sayfasına "Hızlı İzleme Butonları (+/-)" bölümü
  eklendi.** 0.5.6 ve 0.5.7 ile gelen `+` / `−` otomatik durum
  geçişleri artık yardım sayfasında ayrıntılı olarak belgelendi:
  hangi durumda hangi otomatik geçiş çalışır, ne zaman çalışmaz,
  bölüm sayısı bilinmeyen animelerde nasıl davranır. Dört kuralın
  tamamı tablo ve örneklerle anlatıldı; tek tıkla iki adımlı
  geçişler (Planlandı → İzleniyor → İzlendi gibi) ayrıca
  vurgulandı.

## İyileştirmeler

- **Ana sayfa başlığı daha kompakt hâle geldi.** "Anime İzleme
  Listesi" başlığı önceden listenin önünde devasa duruyordu;
  artık daha küçük ve arama kutusuyla daha dengeli görünüyor.
  Diğer sayfaların başlıkları (Düzenle, Ekle, Liste Ayarları,
  Ne İzlesem?, vb.) aynı kaldı — sadece ana sayfa değişti.

- **Arama kutusu boyutu küçültüldü.** Ana sayfanın arama kutusu
  başlık ile daha uyumlu bir genişliğe getirildi.

- **Yardım sayfasındaki bilgi kutuları için renk eklendi.** Saat
  Dilimi bölümündeki bilgilendirme kutuları artık açık mavi
  tonunda; uyarı (sarı), güvenli (yeşil) ve tehlike (kırmızı)
  kutularıyla görsel tutarlılık sağlandı.

## Düzeltmeler

- Yardım sayfasında iki küçük yazım hatası düzeltildi:
  - Kronoloji bölümünde "boleumden" → "bolumden"
  - Silme Uyarıları bölümünde "size özel yukledilmiş poster" →
    "kendi yüklediğiniz poster"

## Notlar

- **Veritabanı yapısında değişiklik yok.** Mevcut izleme
  verileriniz, notlarınız, posterleriniz olduğu gibi korunur.

- **Sadece arayüz ve dokümantasyon değişiklikleri.** Listede
  görünen animeleriniz, `+` / `−` butonlarının davranışı, sync
  ve güncelleme mantığı aynı kaldı. Var olan davranış sadece
  yardım sayfasında daha iyi belgelendi.
