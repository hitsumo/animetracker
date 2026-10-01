# Anime Tracker 0.6

**Sürüm tarihi:** 24 Mayıs 2026
**Tür:** Özellik (yeni izleme durumu + altyapı temizliği)

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza
gerek yoktur. Güncelleme sırasında veritabanı bir kez geçişten
geçirilir (mevcut izleme durumlarınız korunur, içerikleri aynen
aktarılır).

## Yeni

- **Yeni izleme durumu: "İzleme Ertelendi".** Bir animeye başladınız,
  bir süre ara vermek istiyorsunuz, ama "Planlandı"ya çekmek
  ilerlemenizi sıfırlayacağı için doğru gelmiyor. İşte tam bu
  durumlar için "İzleme Ertelendi" eklendi. İzlenen bölüm sayınız
  korunur, anime "İzleniyor" listenizi kalabalıklaştırmaz, hazır
  olduğunuzda `+` butonuna basarsanız sistem sizi otomatik olarak
  "İzleniyor" durumuna geri alır.

- **5. otomatik kural.** Önceden dört otomatik geçiş kuralı vardı
  (Planlandı↔İzleniyor↔İzlendi). 0.6 ile beşinci kural eklendi:
  **"İzleme Ertelendi + `+` → İzleniyor"** (devam etme sinyali).
  Birinci kural ile aynı sonucu üretir, sadece farklı kaynaktan
  (ara verdikten sonra geri dönmek). Tek tıkla iki adımlı geçiş
  bu kuralla da çalışır: İzleme Ertelendi + 11/12 → `+` → İzlendi.

- **Yardım sayfasına yeni "İzleme Durumları" bölümü.** Dört durumu
  (Planlandı, İzleniyor, İzlendi, Ertelendi) ne zaman kullanacağınız,
  hangi durumun hangisinden farkı olduğu sade dille anlatılır.
  "Ne zaman Ertelendi kullanmalı?" sorusu doğrudan cevaplanır.
  Otomatik geçiş tablosu da güncellendi (4 satır → 5 satır).

## Değişiklikler

- **İstatistikler sayfası dört satır gösterir.** Önceden mevcut
  durumlar için "ORDER BY adet" ile sıralama yapılıyordu — yani
  henüz hiç anime atamadığınız durumlar görünmüyordu. Şimdi sabit
  sırada (İzlendi → İzleniyor → İzlenme Planlandı → İzleme Ertelendi)
  dört satır da görünür; sayım sıfır olsa bile satır görünür.

- **Listede ve diğer sayfalarda durum etiketleri tutarlı renkler
  aldı.** Önceki sürümlerde bazı sayfalarda izleme durumu rozetleri
  renksiz görünebiliyordu (sayfaya özel CSS sınıfı eksikti). Beş
  sayfada da artık aynı renk paleti çalışır: yeşil (İzlendi), mavi
  (İzleniyor), gri (İzlenme Planlandı), amber/altın (İzleme Ertelendi).

## Altyapı

- **Veritabanı şeması ASCII'ye geçirildi.** İzleme durumu sütununun
  iç değerleri artık ASCII (Watched / Watching / PlanToWatch /
  OnHold). Kullanıcıya görünen metin Türkçe kalır — bu değişiklik
  arayüze değil sadece veritabanına yansır. Türkçe karakter
  problemi bulunan eski izleme durumu CSS sınıfları temizlendi.

- **Tek kaynak prensibi.** İzleme durumu için üç yardımcı fonksiyon
  ailesi (`watch_status_label`, `watch_status_options`,
  `watch_status_css_class`) `functions.php`'de merkezi olarak
  tanımlandı. Sayfa-yerel kopyalar (eski "yerel haritalar") bu
  fonksiyonlara bağlandı. İleride yeni bir izleme durumu eklemek
  ya da İngilizce arayüz dili eklemek için tek dosya değişir.

## Bilinen Davranışlar

- **"İzleme Ertelendi + `−`" durumu değiştirmez.** Ara verdiğiniz
  bir animede `−` basmak izlenen bölümü 1 azaltır ama durumu
  "İzleme Ertelendi" olarak korur. "Tamamen sıfıra inip Planlandı
  yapmak isteseydim" diye düşünüyorsanız: Düzenle ekranından
  manuel olarak değiştirebilirsiniz. Otomatik geçişe gerek görmedik
  çünkü bu çok nadir bir senaryo.

- **"İzleme Ertelendi + 0/X" anormal görünüm.** Bir anime'yi
  Düzenle ekranından "İzleme Ertelendi" yaparken izlenen bölümü
  0'da bırakırsanız, sistem itiraz etmez ama semantik olarak
  garip durur (henüz hiç izlemediğiniz bir anime'yi "ertelemek"
  Planlandı ile aynı). Genelde Ertelendi durumu izlenen > 0
  iken anlamlıdır.

- **`İzleme Ertelendi` mevcut animelerinizde otomatik olarak
  oluşmaz.** Yeni durum, manuel olarak Düzenle ekranından
  atadığınız (veya `+` ile devam ettiğiniz) anime'lerde aktif olur.
  Mevcut anime listenizdeki tüm kayıtlar güncellemeden sonra da
  aynı durumda kalır.

## Teknik Notlar

- DB geçişi 3 adımlı (`migration/0.6/upgrade.sql`): önce enum
  genişler (TR + ASCII karışık), sonra mevcut TR değerler ASCII
  karşılıklarına UPDATE edilir, son olarak enum sadece ASCII'ye
  daralır. İdempotent — auto-update yarıda kalırsa yeniden
  çalıştırılabilir.

- Eski TR enum değerleri (`İzlendi`, `İzleniyor`, `İzlenme Planlandı`)
  ile veritabanına doğrudan yazılmış üçüncü taraf scriptleriniz
  varsa, 0.6 sonrası ASCII değerleri kullanmaları gerekir.
  Anime Tracker'ın kendi kodu otomatik olarak güncellendi.
