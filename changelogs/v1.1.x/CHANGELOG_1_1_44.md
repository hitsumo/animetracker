# Anime Tracker 1.1.44

**Yayın tarihi:** 2026-09-19

Tek iş: **"Son Güncellenenler" sayfası iki sekmeye ayrıldı** — *Bölüm
Güncellenenler* ve *İçerik Güncellenenler*. Hangisiyle açılacağı Liste
Ayarları'ndan seçilir; ön tanımlı **bölüm**. Bir şema değişikliği var
(`animes.episodes_updated_at`), yalnızca kurulum-yerel; merkez katalogda
yapılacak bir şey yok.

## 1. İki sekme

### Neden

Sayfa "katalogda en son eklenen ya da düzenlenen beş anime"yi
`animes.updated_at`'e göre listeliyordu. Ama `updated_at` MySQL'in
otomatik damgası: satıra dokunan **her** yazma onu tazeler. Günlük yayın
senkronu bir bölüm daha saydığında anime listenin tepesine çıkıyordu —
içeriği değiştiği için değil, bir bölümü daha yayınlandığı için. İki
farklı soru tek listede birbirini eziyordu:

- **"Katalogda ne değişti?"** — yeni kayıt, düzeltilen konu, eklenen tarih.
- **"Hangi animenin yeni bölümü geldi?"** — günün asıl sorusu.

Sezon ortasında ikinci soru birinciyi tamamen gömüyordu: sayfa her gün
senkronun saydığı beş diziyi gösteriyor, bir hafta önce düzenlenen kayıt
görünmüyordu.

### Ne yapar

| Sekme | Ne listeler | Zaman damgası |
|---|---|---|
| **Bölüm Güncellenenler** | Yayınlanan bölüm sayısı en son değişen beş anime | `episodes_updated_at` (yeni) |
| **İçerik Güncellenenler** | Kataloğa en son eklenen ya da bilgisi düzenlenen beş anime | `updated_at` |

Bölüm sekmesindeki her kartta değişen sayı öne çıkar: **"Son bölüm: 12"**.
Sekmeler düz bağlantıdır (`recent.php?tab=episodes` / `?tab=content`);
adresteki seçim o görünüm için geçerlidir, kalıcı bir şey yazmaz.

**Bölüm sayısını kim yazar:** günlük yayın senkronu (AnimeSchedule),
düzenleme formu ve katalog içe aktarımı. Üçü de yeni damgayı **yalnızca
sayı gerçekten değişince** atar; senkron aynı sayıyı yeniden getirirse
liste oynamaz.

**İçerik sekmesi artık gerçekten içerik:** yalnız-bölüm yazan yollar
(yayın senkronu, sayfa açılışında hesaplanan sonraki bölüm tarihi, katalog
içe aktarımının bölüm adımı, kataloğa alma / çıkarma işaretleri)
`updated_at`'e dokunmaz. Düzenleme formu bir içerik düzenlemesidir, eskisi
gibi damgalar; formda yalnız bölüm sayısını değiştirirseniz anime iki
sekmede de görünür — doğru olan da bu.

### Varsayılan sekme ayarı

Liste Ayarları → Genel Ayarlar → **"Son Güncellenenler Sekmesi"**: bölüm /
içerik. Kişisel tercih, yalnız sizi etkiler; ön tanımlı **bölüm**. Sayfadaki
sekmeler bu varsayılanı ezmeden geçici olarak değiştirir. Seri
kronolojisi görünümü ayarıyla (1.1.23) aynı kalıp.

### Güncelleme sonrası ilk gün

Yeni kolon var olan kayıtlarda boştur; bölüm sekmesi ilk yayın senkronundan
sonra dolar (senkron günde bir, ana sayfa açılışında). O ana kadar sekme
"Henüz kayıtlı bölüm güncellemesi yok" der. Tohumlama **bilerek** yapılmadı:
`updated_at`'i kopyalamak "son dokunuş bölüm artışıydı" varsayımı olurdu —
sorunun kendisi tam da bunun bilinmemesiydi.

### Yan etki: sitemap `lastmod`

`updated_at` artık yalnız içerik zamanı olduğundan sitemap'in `lastmod`'u
iki damganın büyüğünü alır; bölüm sayısı değişen bir detay sayfası
arama motoruna yine "değişti" görünür. IndexNow duyurusu da bölüm
değişikliğini kapsamaya devam eder.

## 2. Yardım

Liste sayfası yardımındaki "Son Güncellenenler" bölümü iki sekmeyi, kimin
buraya yazdığını ve ayarı anlatacak şekilde yeniden yazıldı; "Son
İzlenenler ile karıştırmayın" kutusu olduğu gibi duruyor.

## Dosyalar

**Yeni:**

```
files/migration/1.1.44/upgrade.sql   animes.episodes_updated_at (ADD COLUMN, yeniden çalıştırılabilir)
files/set_recent_tab_pref.php        varsayılan sekme ucu (POST + CSRF)
```

**Değişen:**

```
files/recent.php                          iki sekme, sekmeye göre sorgu, "Son bölüm" rozeti, boş durum metni
files/list_settings.php                   "Son Güncellenenler Sekmesi" ayarı
files/functions/user_anime_helpers.php    recent_tabs() / recent_default_tab()
files/functions/animeschedule_helpers.php airedEpisodesUpdateSql(): beş bölüm yazımı tek SQL kalıbından
files/functions/anime_helpers.php         sonraki bölüm tarihi yazımları updated_at'e dokunmaz
files/functions/seo_helpers.php           sitemap lastmod = iki damganın büyüğü
files/edit_anime.php                      bölüm sayısı değişince episodes_updated_at
files/catalog_import.php                  bölüm sayısı ayrı adımda (kendi damgası), içerik UPDATE'i saf içerik
files/admin/admin_pending.php             kataloğa alma / çıkarma updated_at'e dokunmaz
files/robots.php                          set_recent_tab_pref.php disallow listesinde
files/schema.sql                          episodes_updated_at kolonu (taze kurulum)
files/lang/tr.php                         +9 anahtar, 1 değişen (yardım metni)
files/lang/en.php                         aynı
files/version.txt
```

Dil dosyası paritesi: 1118 = 1118. Yeni CSS/JS yok (sekme stili sayfanın
içinde).

## Dağıtım notu

- **Merkez katalogda iş yok.** Yeni kolon kurulum-yereldir; push ve
  katalog uçları kolon listesini açık yazar, yeni kolon tele girmez.
- Migration ilk sayfa açılışında koşar; tek `ALTER TABLE ... ADD COLUMN`,
  kolon zaten varsa yok sayılır. 8.113 satırlık kopyada 1.1.26 → 1.1.44
  zinciri ve boş veritabanında `schema.sql` + tam zincir denendi; ikisi de
  temiz.
- `functions/animeschedule_helpers.php`, `anime_helpers.php`,
  `user_anime_helpers.php` ve `seo_helpers.php` **birlikte** gitmeli
  (functions/ klasörünün tamamı kuralı): `recent.php` ve
  `list_settings.php` yeni yardımcıları çağırır.
- Bölüm sekmesi ilk senkrona kadar boştur; bu beklenen davranış.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.44'e çekilmeli ve `updates/1.1.44/anime-tracker-1.1.44.zip` paketi
  yayımlanmalı.
