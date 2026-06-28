# Anime Tracker 1.0.11

**Yayın tarihi:** (deploy günü doldurulacak)

## Yenilikler

### Online kurulumda katalog değişiklikleri merkez sunucuya otomatik gönderilir
Önceden merkez kataloğa yazmanın tek yolu, bekleyen animenin "Kataloga Al"
ile onaylandığı andı; sonradan yapılan düzenlemeler (konu yazmak, başlık
veya bölüm bilgisi düzeltmek) yalnızca online kurulumun kendi veritabanında
kalıyordu. Artık:

- Bir **katalog animesi düzenlenip kaydedildiğinde** değişiklik merkez
  kataloğa otomatik gönderilir — konu, başlık, bölüm bilgisi, kronoloji,
  tür ve cümleler dahil.
- Moderatör veya adminin **doğrudan kataloğa eklediği** anime (onay
  kuyruğuna uğramayan) de ekleme anında otomatik gönderilir.
- Normal kullanıcı eklemeleri eskisi gibi onay kuyruğuna düşer ve onay
  anında gönderilir; bu davranış değişmedi.

### Gönderim başarısız olursa kullanıcı bilgilendirilir
Gönderim başarısız olsa bile kayıt geri alınmaz; değişiklik kurulumda
saklı kalır ve ana listenin üstünde uyarı gösterilir. Animeyi yeniden
kaydetmek gönderimi tekrar dener.

## Notlar
- Self-host (tek kullanıcı) kurulumlarda davranış değişikliği yoktur;
  yeni kod blokları bu kurulumlarda hiç çalışmaz.
- Anime görselleri gönderime dahil değildir; görsel merkez sunucuya
  elle yüklenmelidir.
- Kişisel Konu, not, duygu ve izleme durumu kişisel veridir; hiçbir
  gönderime dahil edilmez. Merkez kataloğa gitmesi istenen konu metni
  katalog konu alanına yazılmalıdır.
- Bu sürümde veritabanı şeması değişmemiştir.

## Değişen dosyalar
- `index.php`
- `add_anime.php`
- `edit_anime.php`
- `version.txt`
- `upgrade.sql`
- `lang/tr.php`
- `lang/en.php`

## Yeni dosyalar
- `migration/1.0.11/upgrade.sql`
