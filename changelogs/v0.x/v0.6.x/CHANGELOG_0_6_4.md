# Anime Tracker 0.6.4

**Sürüm tarihi:** 27 Mayıs 2026
**Tür:** Özellik tamamlama (i18n döngüsü kapanış — tüm sayfalar EN'e açıldı)

Bu sürüm otomatik güncelleme ile gelir. Veritabanı şemasına dokunulmaz,
sadece kod ve sözlük dosyaları yenilenir. Mevcut izleme verileriniz,
notlarınız ve dil tercihiniz korunur — sürümden önce hangi dilde
çalışıyorduysanız sonra da öyle açılır.

## Özet

0.6.2 ile birlikte gelen i18n altyapısı (lang_init() / t() helper'ları
ve lang/tr.php / lang/en.php sözlükleri) o sürümde sadece üç sayfaya
uygulanmıştı (index, anime_details, edit_anime). Bu sürüm kalan **dokuz
sayfayı** aynı sözlüğe bağlar, böylece uygulamanın **tüm UI'ı** İngilizce
seçildiğinde gerçekten İngilizce'ye geçer.

Dil değiştirici (TR/EN düğmeleri) üst köşede zaten vardı; eskiden bazı
sayfalarda etiketler hâlâ Türkçe görünüyordu çünkü o sayfalar t()
çağrılarına henüz dönüştürülmemişti. Artık yapıldı.

**Admin tarafı da çevrildi.** GitHub'dan projeyi indiren yabancı bir
geliştiricinin admin sayfalarını Türkçe görmesi tutarsızlık olurdu;
admin paneli (`admin.php`), bekleyen anime listesi (`admin_pending.php`)
ve sunucu push aracı (`admin_sync.php`) de aynı i18n altyapısına bağlandı.
Sözlük tarafında yeni bir desen tanıtıldı: admin anahtarları ayrı bir
sözlük dosyasında tutulur (`lang/admin_tr.php` + `lang/admin_en.php`),
böylece normal kullanıcı kurulumlarında bu anahtarlar yüklenmez. Detay
aşağıda "Plan B - admin/user ayrık sözlük" başlığında.

## Çevrilen sayfalar

| Sayfa | Açıklama | Yeni anahtar |
|---|---|---|
| add_anime.php | Anime ekleme formu | 91 |
| help.php | Yardım sayfası (Nasıl çalışır?) | 132 |
| statistics.php | İstatistik tabloları | 11 |
| recent.php | Son düzenlenen 5 anime | 8 |
| recommendations.php | "Ne İzlesem?" öneri sayfası | 19 |
| about.php | Hakkında | 4 |
| chronology.php | Bölüm seviyeli kronoloji | 10 |
| series_timeline.php | Seri zinciri kronolojisi | 4 |
| list_settings.php | Liste Ayarları (import / export / sync / update) | 66 |
| admin.php | Admin paneli (localhost-only) | 18 |
| admin_pending.php | Bekleyen anime promotion | 25 |
| admin_sync.php | Sunucuya katalog push aracı (`admin_sync_example.php` şablonu üzerinden) | 23 |
| **Toplam** | | **411** |

Önceki 88 anahtar (0.6.2) + 411 yeni = **499 anahtar**. TR ve EN
sözlükleri birebir paraleldir. Sözlükte olmayan bir anahtar için t()
helper'ı önce TR'ye düşer, oraya da yoksa anahtarın kendisini döndürür
(geliştirici için görünür uyarı).

## Eklenen / Değişen davranışlar

- **Dil değiştirici 4 sayfada daha eksikti, eklendi.** add_anime.php,
  recommendations.php ve list_settings.php artık üst başlıkta TR/EN
  butonlarını gösterir. help.php, statistics.php, recent.php, about.php,
  chronology.php ve series_timeline.php bilinçli olarak switcher
  taşımıyor (kullanıcı ana sayfa veya anime detayından dil seçer,
  ayar oturum boyu kalıcıdır).

- **JavaScript mesajları artık dil ile gelir.** add_anime'deki AnimeSchedule
  "Otomatik Doldur" durum mesajları, recommendations'taki "(N seçili)"
  sayacı ve list_settings'in "Güncelleme Kontrolü" akışındaki tüm
  uyarılar PHP'den `LANG` JS sabiti üzerinden geçer; istemci tarafında
  hard-coded TR string kalmadı.

- **Veritabanı enum değerleri hâlâ TR (kasten).** `animes.status` kolonu
  hâlâ "Yayın Tamamlandı" / "Yayın Devam Ediyor" değerlerini tutar
  (legacy uyumluluk). Sadece **gösterilen etiket** çevrilir: index.php,
  statistics.php, recent.php sayfalarında PHP-side lookup ile
  `index.broadcast.finished` / `index.broadcast.ongoing` anahtarları
  üretilir. `broadcast_day` kolonu da aynı mantıkla "Pazartesi" / "Salı"
  vb. saklar; ekranda "Monday" / "Tuesday" gösterilir.

## Davranışsal değişiklik olmayan şeyler

- DB şeması (kolon, indeks, constraint) **hiç değişmedi**.
- Sync mantığı, kronoloji marker mantığı, "Ne İzlesem?" puanlaması
  hiç değişmedi.
- Şu an dolu olan TR alanlarınızda — notlar, kişisel konu, anime
  başlıkları, vs. — hiçbir oynatma yok.
- Mevcut tercih cookie/session değeri (settings.display_language)
  korunur; sürüm yükseldikten sonra hâlâ aynı dilde açılır.

## Bilinen davranışlar

- **Veritabanından gelen serbest metinler İngilizceye dönmez.** Anime
  başlıkları, notlar, kişisel sinopsis, sıralama etiket adları
  ("Okulda geçsin" vb.) — bunlar veri olarak Türkçe yazıldıysa
  öyle kalır. Bu doğru davranıştır: UI çevirisi içerik çevirisi
  değildir.

- **0.6.2 öncesinde girilmiş "edit_anime" form etiketleri.** Şu anki
  edit_anime.php, sözlük tarafından henüz tam karşılanmayan birkaç
  `edit_anime.*` anahtarı çağırabilir (önceki bir oturumda kısmen
  i18n'lenmiş hâlinin kalıntısı). Anahtar adının kendisi UI'da
  görünürse t() helper'ının görünür uyarısıdır — bir sonraki bakım
  geçişinde sözlüğe eklenecek. Etkilenen sayfa sayısı: 1. Etkilenen
  fonksiyonellik: 0 (form yine çalışır).

## Teknik notlar

Bu sürümün hiçbir migration adımı yoktur (şema değişikliği yok).
upgrade.sql dosyası sadece bir `SELECT 1` no-op'u içerir; migration
manager dosyayı yine de çalıştırıp settings.version'i 0.6.4'e
bumper. Eski sürümden geçişte ek bir manuel adım gerekmiyor.

### i18n disiplini (KARARLAR Bölüm 7)

Bu sürümle birlikte KARARLAR Bölüm 8'in açık kalan "9 sayfa daha
çevrilecek" maddesi kapatıldı. Yeni eklenecek sayfalar için
disiplin maddesi: lang_init($pdo) sayfa başına, t('namespace.key')
ile sar, lang/tr.php + lang/en.php'ye **aynı anda** ekle. Tek
taraflı eksiklik kullanıcıya t() helper'ın anahtar-kendisi
fallback'i ile görünür şekilde patlar — bu davranış bilerek
sürdürülür (geliştirici uyarısı).

### Admin sayfası eklemek (yeni)

Bir admin aracı eklenirse (örnek: `admin_backup.php`):
1. Dosya başında `lang_init($pdo)` yerine `lang_init_admin($pdo)` çağır.
2. `t('admin_backup.heading')` gibi `admin_*` namespace'i kullan.
3. Anahtarları `lang/admin_tr.php` **ve** `lang/admin_en.php`'ye ekle —
   user sözlüğüne (`lang/tr.php` / `lang/en.php`) yazma. Admin
   anahtarları orada olmaz, oraya yazılırsa kullanıcı kurulumlarında
   ölü sermaye olur.
4. `nav.about`, `lang.tr_label` gibi paylaşılan anahtarlar zaten user
   sözlüğünden gelir — admin sözlüğüne kopyalanmaz (cakışma yok,
   `array_merge` ile birleşir).

### Sözlük büyümesi sonrası bakım önerisi

499 anahtar (433 user + 66 admin) tek tek kontrol edilemez. Bir sonraki bakım turunda
basit bir test betiği — TR ve EN dosyalarındaki anahtar setlerinin
birebir paralelliğini doğrulayan — eklenmesi düşünülüyor. Şimdilik
sayım manuel (her sürüm öncesi `grep -c "^    '" lang/tr.php` vs.
en.php karşılaştırması; aynısı `lang/admin_tr.php` ve
`lang/admin_en.php` için de tekrar).
