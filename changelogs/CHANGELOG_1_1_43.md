# Anime Tracker 1.1.43

**Yayın tarihi:** 2026-09-19

Şema değişikliği yok. Tek iş: **kurulum sayacı** — projenin, dünyada kaç
Anime Tracker kurulumu olduğunu kabaca görebilmesi. Kurulum başına **tek
bir** istek; kimlik yok, kişisel veri yok, tek satırla kapatılabilir.

## 1. Kurulum sayacı

### Neden

Proje açık kaynak ve self-host. Bu zamana kadar bir kurulumun merkezle
konuşmak zorunda olduğu **hiçbir otomatik nokta yoktu**: kişisel kurulum
merkez kataloğu çekmeyebilir, güncelleme kontrolü yalnızca düğmesine
basılınca çalışır. Yani GitHub'dan indirilip kurulan bir kopyanın var
olduğu hiçbir zaman bilinmiyordu. "Ne kadar büyüdük?" sorusunun cevabı
yoktu.

### Ne yapar

Kurulum, **yalnızca bir kez** — ana sayfanın ilk açılışından sonra —
projenin sayacına tek bir küçük istek gönderir. İçinde üç şey vardır:

| Alan | Ne | Nereden |
|---|---|---|
| `id` | Rastgele 32 karakterlik kurulum kimliği | Gönderim anında `random_bytes` ile üretilir, `settings.install_id`'ye yazılır. Adresten, veritabanından, kişiden türetilmez. |
| `v` | Kurulu sürüm | `version.txt` |
| `m` | `single` (kişisel) / `multi` (çok kullanıcılı) | `MULTI_USER_MODE` |

**Gönderilmeyenler:** site adresi, üye sayısı, anime, izleme verisi,
notlar, sunucu adı. Sayaç **IP adresini saklamaz**. Kimlik yalnızca bir
tekrarın (yeniden deneme, geri yüklenen yedek) yeni kurulum sayılmaması
içindir; `settings` tablosundaki `install_id` ve `install_ping_done`
satırlarını silen kurulum sayaçta yeni bir kurulum olur.

**Neden günlük değil, bir kez:** ilk taslak günlük "yaşıyorum" pingiydi
(toplamın yanında "son 30 günde aktif" sayısını da verirdi). Karar: en
küçük iz — kurulumda bir kez, "gerekirse genişletiriz". Kapı öyle kuruldu
ki günlüğe dönmek tek satırlık iş.

### Nasıl çalışır

- **Sayfa beklemez.** `index.php` yalnızca "ping başardı mı, bugün denendi
  mi?" diye bakar (`settings.install_ping_done` / `last_install_ping`).
  Gerekiyorsa sayfanın sonuna tek satırlık bir `fetch('install_ping.php')`
  basar; dışarı giden isteği o uç yapar, 3 saniye zaman aşımıyla. Sayaç
  sunucusu yavaş ya da kapalı olsa bile kullanıcının baktığı sayfa
  etkilenmez.
- **Başarana kadar günde en fazla bir deneme, sonra bir daha asla.** Uç,
  göndermeden **önce** günü işaretler; HTTP 200 gelince `install_ping_done`
  yazılır ve kapı kalıcı kapanır. Kurulum anında sayaç sunucusu kapalıysa
  o kurulum kaybolmaz — ertesi gün sayılır; arada hiçbir sayfa yüklemesi
  dışarı istek atmaz.
- **POST + CSRF.** Öteki durum değiştiren uçlarla aynı kalıp. İkinci istek
  (iki sekme, sayfanın JS'ini çalıştıran bir tarayıcı botu) 204 döner, hiçbir
  şey yapmaz.
- **Kapatma:** `config.php`'de `define('INSTALL_PING', false);`. Sabit yoksa
  açık sayılır (eski `config.php` dosyaları için `MULTI_USER_MODE` ile aynı
  gelenek). Yeni kurulumlarda `setup.php` ve Docker giriş betiği satırı
  `true` olarak yazar ki anahtar gözle görülsün.

### Sayılar nerede görünür

- **Yönetici paneli → "Kurulum Sayacı" kartı:** toplam, kişisel / çok
  kullanıcılı dağılımı, kurulumların hangi sürümle başladığı.
  Kart sayıları tarayıcıdan çeker (sunucu tarafında çekmek admin sayfasını
  uzak sunucuya bağımlı kılardı). Bu kurulumda sayaç kapalıysa kart bunu
  ayrıca söyler.
- **Herkese açık:** `ping.php?stats=1` yalnızca toplamları döner, kimlik
  değil. Karar: sayıda korunacak bir şey yok; ileride Hakkında sayfasında
  "N kurulum" rozeti olabilir.

### Şeffaflık

Açık kaynak bir projede "sessiz" bir sayaç olmaz. Ne gittiği dört yerde
yazılı: `README.md` (TR+EN, yeni "Kurulum sayacı" bölümü), yardım
(Senkronizasyon ve Güncelleme → yeni **Kurulum Sayacı** bölümü:
ne gönderilir, ne gönderilmez, nasıl kapatılır, neden var),
`config_example.php` ve yardımcı dosyanın başındaki açıklama. Sayı kabaca
doğrudur, kanıt değildir: sayacı kapatan görünmez, kimliğini sıfırlayan iki
kez sayılır — bu da yardımda yazıyor.

## 2. Detay sayfasında "Düzenle" yalnızca düzenleyebilene

Detay sayfası "Düzenle" düğmesini herkese basıyordu; anonim ziyaretçi tıklayınca
`edit_anime.php` giriş sayfasına yönlendiriyordu (uç zaten moderatör kapılı).
Liste sayfası aynı düğmeyi öteden beri yalnız moderatöre gösteriyordu, detay
sayfası unutulmuştu. Zararı arama motorunda görüldü: Search Console 1.034
"yönlendirmeli sayfa" biriktirmişti, hepsi `edit_anime.php?id=…`. Düğme artık
detayda da yalnız moderatör ve üstüne görünür. Kişisel kurulumda sahip her
zaman yetkili olduğundan davranış değişmez.

## 3. Seri kronolojisi sayfasının başlığında "İzleme Sırası"

Search Console, "tensei shitara slime izleme sırası" aramasında seri kronolojisi
sayfasını değil filmin detay sayfasını gösteriyordu: sayfanın başlığı "Seri
Kronolojisi" idi, arayan "izleme sırası" yazıyordu. `<title>` ve meta açıklama
artık iki ifadeyi de taşıyor — *"X İzleme Sırası - Seri Kronolojisi"* /
*"X Watch Order - Series Chronology"*; açıklamaya "hangi film hangi bölümden
sonra izlenir" eklendi. Arayüzdeki sayfa adı ve yardım metinleri değişmedi.

## Dosyalar

**Yeni:**

```
files/functions/install_ping_helpers.php   mantık; INSTALL_PING_URL sabiti
files/install_ping.php                      yerel AJAX ucu (POST + CSRF)
files/migration/1.1.43/upgrade.sql          şemasız; sürüm damgası + gerekçe
catalog_server/ping.php                     MERKEZ: kayıt + ?stats=1
```

**Değişen:**

```
files/index.php                    tetikleyici (yalnız ping gerekiyorsa basılır)
files/anime_details.php            "Düzenle" düğmesi yalnız moderatöre (§2)
files/series_timeline.php          <title> ve seo_head başlığı yeni kalıptan (§3)
files/functions.php                yeni yardımcı yükleniyor
files/admin/admin.php              "Kurulum Sayacı" kartı
files/lang/admin_tr.php            +9 anahtar
files/lang/admin_en.php            +9 anahtar
files/help/help_sync.php           yeni "Kurulum Sayacı" bölümü
files/help.php                     içindekiler (+1 satır)
files/lang/tr.php                  +12 anahtar, 1 değişen (seo.series.description_fmt)
files/lang/en.php                  aynı
files/config_example.php           INSTALL_PING açıklaması
files/setup.php, setup_en.php      üretilen config.php'ye INSTALL_PING satırı
files/schema.sql                   settings anahtar listesine install_id / last_install_ping / install_ping_done
files/robots.php                   install_ping.php disallow listesinde
files/version.txt
docker-entrypoint.sh               üretilen config.php'ye INSTALL_PING satırı
catalog_server/catalog_server_README.md   ping.php bölümü + tablo SQL'i
README.md                          "Kurulum sayacı" bölümü (TR+EN)
```

Dil dosyası paritesi: 1109 = 1109, yönetici 312 = 312. Yeni CSS/JS yok.

## Dağıtım notu

- **Merkez katalog sunucusunda iki adım:** (1) `installs` tablosunu oluştur
  (SQL `catalog_server_README.md` ve `migration/1.1.43/upgrade.sql`
  içinde), (2) `catalog_server/ping.php` dosyasını yayımla. Uç,
  `private/admin_push_config.php`'nin veritabanı bilgilerini kullanır
  (yazma yetkili kullanıcı); `private/rate_limit/` klasörünü kendisi açar.
- **Sıra önemli değil.** Uygulama önce giderse ping 404 alır, günlüğe yazar,
  ertesi gün yine dener (başarana kadar günde bir); sayfalar etkilenmez.
  Merkez önce giderse tablo boş bekler.
- Uygulama tarafında migration şema değiştirmez; `install_id` ilk ping'de
  PHP tarafından üretilir. Taze kurulum replay'i etkilenmez.
- `functions/install_ping_helpers.php` ile `functions.php` **birlikte**
  gitmeli: `functions.php` yeni dosyayı `require_once` eder, dosya yoksa
  her sayfa çöker (0.6.7'den beri geçerli "functions/ klasörünün tamamı"
  kuralı).
- Yönetici kartı sayıları `INSTALL_PING_URL?stats=1` adresinden tarayıcıda
  çeker; merkez uç yayımlanana kadar kart "Sayaç sunucusuna ulaşılamadı"
  gösterir, başka bir şey bozulmaz.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.43'e çekilmeli ve `updates/1.1.43/anime-tracker-1.1.43.zip` paketi
  yayımlanmalı.
- Kendi kurulumlarınız (yerel XAMPP dahil) da sayılır. Sayılmasın
  istediğiniz kopyada `INSTALL_PING`'i `false` yapın.
