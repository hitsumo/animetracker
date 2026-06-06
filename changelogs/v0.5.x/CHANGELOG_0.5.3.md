# Anime Tracker 0.5.3

**Sürüm tarihi:** 16 Mayıs 2026
**Tür:** Veri koruma ve hata düzeltme

Bu sürüm otomatik güncelleme ile gelir; ekstra bir şey yapmanıza gerek
yoktur.

## Yenilikler

- **Kronoloji işareti koruması:** Bir animeye elle eklediğiniz "şu
  bölümden sonra şu anime izlenir" kronoloji işaretleri artık
  "Katalogdan İçe Aktar" yaptığınızda silinmiyor. Eskiden, kataloğa
  gönderilmemiş kendi işaretleriniz içe aktarma sırasında
  kaybolabiliyordu. Bu sürümle kendi eklediğiniz işaretler korunur,
  katalogdan gelen işaretler ise otomatik güncellenir.

- **İçe aktarma öncesi bilgilendirme:** "Katalogdan İçe Aktar"
  yapmadan önce, katalog ile senkronize olmayan kendi kronoloji
  işaretleriniz varsa artık bir bilgi notu ve onay uyarısı görürsünüz.
  İçe aktarma güvenlidir — kendi işaretleriniz silinmez; uyarı yalnızca
  bilgi amaçlıdır ve işlemi engellemez.

## Kendi sunucusunu çalıştıranlar için

Eğer katalog/senkronizasyon sistemini kendi sunucunuzda
çalıştırıyorsanız, bu sürümde sunucu tarafında da bir iyileştirme var:

- **Sunucu tarafı kronoloji koruması:** Sunucudaki katalog güncelleme
  mantığı da kendi kronoloji işaretlerini koruyacak şekilde
  güncellendi; istemci ile birebir simetrik çalışır. Bu sürümde sunucu
  veritabanına küçük bir alan **elle eklenmelidir** (kurulum/dağıtım
  notlarına bakın). (admin_push.php — yönetim tarafı dosyasıdır,
  otomatik güncellemeye dahil değildir, elle güncellenmelidir.)

## Notlar

Bu sürümde veritabanına küçük bir alan eklendi (kronoloji
işaretlerinin kaynağını ayırt etmek için). Otomatik güncelleme
sırasında veritabanınız otomatik olarak güncellenir; mevcut
verileriniz olduğu gibi korunur.

Güncelleme sonrası kataloğu bir kez içe aktarmanız önerilir: bu işlem,
güncellemeden önce var olan katalog işaretlerinin doğru şekilde
etiketlenmesini sağlar. Kendi eklediğiniz işaretler bu işlemden
etkilenmez.
