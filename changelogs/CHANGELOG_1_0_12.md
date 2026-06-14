# Anime Tracker 1.0.12

**Yayın tarihi:** (deploy günü doldurulacak)

## Yenilikler

### Otomatik bölüm takibi artık Japonca (raw) yayın takvimini esas alıyor
AnimeSchedule ile yapılan otomatik "yayınlanan bölüm" senkronizasyonu eskiden
altyazılı (sub) takvimi sorguluyordu; sonraki bölüm geri sayımı ise zaten
Japonca yayın gününe göre hesaplanıyordu. Bu ikisi farklı hatlar olduğu için
yayınlanan bölüm sayısı ile geri sayım birbirinden kopabiliyordu. Artık her
ikisi de aynı hattı — Japonca (raw) yayını — esas alır, böylece bölüm sayısı
ile geri sayım tutarlı kalır.

### Yayını biten animeler otomatik "Yayın Tamamlandı" olur
Bir animenin tüm bölümleri yayınlandığında, senkronizasyon sırasında durumu
otomatik olarak "Yayın Tamamlandı"ya çekilir ve yayınlanan bölüm sayısı toplam
bölüme sabitlenir. Böylece geri sayım haftalık dönmeyi bırakır; eskiden bunun
için animeyi elle düzenlemek gerekiyordu.

### Tamamlanan animede geri sayım durur
Yayınlanan bölüm sayısı toplam bölüme ulaşmış bir animede sonraki bölüm geri
sayımı artık ileri atılmaz; tamamlanma görünümü devreye girer.

## Notlar
- Otomatik senkronizasyon Liste Ayarları sayfası açıldığında (günde bir kez,
  veya "Güncelle" düğmesiyle elle) çalışır; bunun için bir AnimeSchedule API
  anahtarı gerekir.
- İlk senkronizasyondan sonra devam eden bazı animelerin bölüm sayısı raw
  yayına hizalanırken bir-iki bölüm sıçrayabilir (raw, altyazıdan önde
  olabilir). Bu beklenen bir davranıştır.
- Yalnızca altyazıyla yayınlanan ve raw takviminde bulunmayan animeler
  senkronizasyonda eşleşmeyebilir. Altyazı (sub) takibinin raw'ın yanında
  gösterilmesi sonraki bir sürümde planlanmaktadır.
- Bu sürümde veritabanı şeması değişmemiştir.

## Değişen dosyalar
- `animeschedule_helpers.php`
- `anime_helpers.php`
- `index.php`
- `version.txt`
- `upgrade.sql`

## Yeni dosyalar
- `migration/1.0.12/upgrade.sql`
