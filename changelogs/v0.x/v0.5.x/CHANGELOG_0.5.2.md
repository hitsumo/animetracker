# Anime Tracker 0.5.2

**Sürüm tarihi:** 12 Mayıs 2026
**Tür:** Hata düzeltme ve iyileştirme

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza gerek yoktur.

## Yenilikler

- **Tekrarlanan veri uyarısı:** Aynı MAL veya AniDB bağlantısına sahip
  bir animeyi ikinci kez eklemeye çalışırsanız artık anlaşılır bir
  "Tekrarlanan veri hatası" uyarısı görürsünüz ve mevcut kayda tek
  tıkla gidebilirsiniz. Eskiden teknik bir hata ekranı çıkıyordu.

- **Resim güvenliği:** Anime düzenlerken yeni bir kapak resmi
  yüklediğinizde, kayıt başarısız olursa eski resminiz korunur. Artık
  resim kayıpları yaşanmıyor.

- **Yardım sayfası yenilendi:** Saat dilimi ayarı daha net anlatıldı.
  AnimeSchedule bağlantısı girip "Otomatik Doldur" tuşuna basınca yayın
  günü, saati ve saat diliminin otomatik dolduğu açıklandı — artık elle
  giriş yapmanıza gerek yok.

- **Güncelleme sistemi iyileştirildi:** Sunucuya ulaşılamadığında artık
  "güncel" yerine açık bir hata mesajı gösterilir. Güncelleme süreci
  daha güvenilir.

## Kendi sunucusunu çalıştıranlar için

Eğer katalog/senkronizasyon sistemini kendi sunucunuzda
çalıştırıyorsanız, bu sürümde yönetim panelinde bir iyileştirme var:

- **Senkronizasyon koruması:** Yönetim panelindeki "Sync sayfasını aç"
  butonu, henüz kataloğa alınmamış (bekleyen) anime varken artık pasif
  olur ve uyarı gösterir. Eskiden bu durumda push yapılırsa bekleyen
  animeler sessizce atlanıyordu. Artık önce bekleyenleri kataloğa
  almanız, sonra push yapmanız için yönlendiriliyorsunuz. (admin.php —
  yönetim tarafı dosyası, otomatik güncellemeye dahil değildir, elle
  güncellenmelidir.)

## Notlar

Bu sürümde veritabanı yapısı değişmedi; mevcut verileriniz olduğu gibi
korunur.
