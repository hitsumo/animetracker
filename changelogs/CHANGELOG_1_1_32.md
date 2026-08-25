# Anime Tracker 1.1.32

**Yayın tarihi:** 2026-08-24

Bu sürüm tek bir soruyu çözer: **bir sayfa değiştiğinde arama motoru bunu ne
zaman öğrenir?**

1.1.30 siteye bir sitemap verdi. Sitemap "burada neler var" sorusunu
cevaplar ve tarayıcı onu kendi takvimiyle okur — günler, bazen haftalar.
Yeni eklenen bir animenin adresi o okumaya kadar bilinmez; silinen bir adres
de tarayıcı tekrar uğrayana kadar arama sonuçlarında durur.

**IndexNow** öteki soruyu cevaplar: *şimdi ne değişti?* Bing ile Yandex'in
ortak kullandığı bir bildirim protokolü; tek bir uca haber verirsin, katılan
motorlar paylaşır. Google katılmaz — bu yüzden IndexNow sitemap'in yerine
geçmez, yanına eklenir.

## Ekranda hiçbir şey değişmedi

Bu sürüm site yöneticisini ilgilendirir, listesini tutan kullanıcıyı değil.
Yeni bir düğme, yeni bir alan, değişen bir sayfa yok. Kendi bilgisayarında
tek kullanıcı olarak çalıştıranlar için ise hiç devreye girmez: giriş
istemeyen bir kurulum zaten "beni dizine ekleme" diyor, adres duyurması
kendisiyle çelişirdi.

## Ne zaman haber verilir

| Yaptığın iş | Duyurulan |
|---|---|
| Yeni anime eklemek | Yeni animenin adresleri |
| Var olan animeyi düzenlemek | O animenin adresleri |
| Anime silmek | Silinen adresler (motor uğrar, 404 görür, dizinden düşürür) |
| Kronoloji notu eklemek/değiştirmek/silmek | Etkilenen kronoloji sayfası |
| Üye önerisini onaylamak | Onaylanan animenin adresleri |
| Katalogdan içeri aktarmak | Yalnızca **gerçekten değişen** kayıtlar |

Hangi adreslerin duyurulduğu sitemap'in kuralına bağlıdır — sitemap ne
listeliyorsa o: yetişkin işaretli kayıtlar dışarıda, kronoloji sayfası
yalnız notu olan animede, seri zaman çizelgesi yalnız seriyi temsil eden
kayıtta.

Ana sayfa bilerek dışarıda. Neredeyse her yazmada değişiyor, tarayıcılar
zaten sık uğruyor ve yeni anime kendi detay adresinden keşfediliyor; her
seferinde duyurmak, en az ihtiyacı olan sayfaya bütçe harcamak olurdu.

## Kaydettiğinde bir şey beklemezsin

Akla ilk gelen çözüm — kaydet düğmesine basınca arama motoruna istek atmak —
üç ayrı yerden yanlıştı:

- Kaydetme işlemine üçüncü taraf bir ağ isteği girerdi; uç yavaşlarsa form
  yavaşlamış gibi hissedilirdi.
- Başarısız istek kaybolurdu. Tekrar denemesi yok, adres hiç duyurulmamış
  olurdu.
- 500 satırlık bir içeri aktarma 500 istek atardı; aynı animeyi bir dakikada
  beş kez düzenlemek beş istek atardı — protokolün gönderenlerden
  *yapmamasını* istediği şey tam olarak bu.

Bu yüzden kaydetme sırasında yapılan tek şey küçük bir kayıt: "şu adres
değişti". Gönderimi, saatte bir çalışan ayrı bir görev toplu hâlde yapıyor —
bölüm senkronunda 1.0.19'dan beri kullanılan düzenin aynısı. Aynı sayfa gün
içinde on kez değişse tek satır kalır, tek duyuru gider.

Bunun pratik anlamı: **"şimdi ping atmalı mıyım?" diye düşünmen gereken bir
an yok.** Sen her zamanki gibi kaydediyorsun, gerisi kendiliğinden oluyor.

## Kurulum (bir kez)

Özellik varsayılan olarak **kapalıdır** ve iki satır yapılandırma ister:

1. Anahtarı üret:

   ```
   php indexnow_ping.php --genkey
   ```

2. Yazdırdığı satırları `config.php`'ye ekle:

   ```php
   define('INDEXNOW_KEY', '...');
   define('SITE_URL', 'https://siteadresin');
   ```

   `SITE_URL` burada **zorunlu**. Web isteğinde sitenin adresi isteğin
   kendisinden çıkarılabiliyor, ama komut satırında böyle bir bilgi yok;
   olmadan her adres `http://localhost/...` diye üretilir ve reddedilir.
   Betik onsuz çalışmayı reddeder, yanlış adres göndermez.

3. `https://siteadresin/<anahtar>.txt` adresinin anahtarı düz metin olarak
   döndürdüğünü kontrol et. (Bu dosya `robots.txt` gibi üretiliyor; diske
   elle bir `.txt` koymana gerek yok.)

4. Görevi zamanla — saatte bir yeterli:

   ```
   0 * * * * php /yol/indexnow_ping.php >> /var/log/anime_indexnow.log 2>&1
   ```

Anahtar tanımlı değilse hiçbir şey kuyruğa yazılmaz, hiçbir istek gitmez ve
uygulamanın geri kalanı etkilenmez.

## Yönetici için dört komut

```
php indexnow_ping.php             bekleyenleri gönder
php indexnow_ping.php --status    yapılandırma + kuyruk raporu, gönderim yok
php indexnow_ping.php --dry-run   sıradaki partinin adreslerini yazdır, gönderim yok
php indexnow_ping.php --retry     takılan satırların sayacını sıfırla, sonra gönder
```

`--status` çıktısı şuna benzer:

```
indexnow status
  mode:          online (indexable)
  key:           a1b2... (32 chars)
  site url:      https://siteadresin
  key file:      https://siteadresin/a1b2....txt
  curl:          yes
  queued:        14
  stuck:         0 (attempts >= 5; use --retry)
  last ping:     2026-08-24 09:00:00 UTC
  last count:    31
```

## Bir şey ters giderse

Gönderim başarısız olursa kuyruk **silinmez**. Geçici bir arıza (aşırı
istek, sunucu hatası, bağlantı kopması) bir sonraki koşuda kendiliğinden
telafi edilir. Kalıcı bir sorun — yanlış anahtar, tutmayan adres — beş
denemeden sonra o satırları beklemeye alır ki arkalarındaki yeni adresleri
tıkamasınlar; satırlar silinmez, `--status` bunları `stuck` diye sayar ve
sebep düzeltilince `--retry` hepsini geri alır.

Her başarısızlığın ayrıntısı sunucu hata günlüğüne yazılır.

## Değişen dosyalar

**Yeni:**

```
files/functions/indexnow_helpers.php    (kuyruk + gönderim mantığı)
files/indexnow_ping.php                 (cron/komut satırı görevi)
files/indexnow_key.php                  (anahtar dosyasını üreten sayfa)
files/migration/1.1.32/upgrade.sql
```

**Değişen:**

```
files/functions.php                     (yeni yardımcı dosyanın yüklenmesi)
files/functions/seo_helpers.php         (tek anime için adres kuralı)
files/add_anime.php                     (ekleme sonrası kuyruğa yazar)
files/edit_anime.php                    (düzenleme sonrası kuyruğa yazar)
files/index.php                         (silmeden ÖNCE kuyruğa yazar)
files/catalog_import.php                (yalnız gerçekten değişenleri yazar)
files/admin/admin_catalog_requests.php  (öneri onayı kuyruğa yazar)
files/add_chronology_marker.php         (kronoloji sayfası kazanılabilir)
files/update_chronology_marker.php      (kronoloji içeriği değişti)
files/delete_chronology_marker.php      (son not silinmiş olabilir)
files/.htaccess                         (anahtar dosyası yönlendirmesi)
files/config_example.php                (INDEXNOW_KEY açıklaması)
files/schema.sql
files/version.txt
```

## Dağıtım notu

- `files/functions.php` ile `files/functions/indexnow_helpers.php`
  **birlikte** yüklenmelidir. Yükleyici satırı olmadan dosya hiç okunmaz ve
  kuyruğa yazan her sayfa açılmaz.
- `files/functions/seo_helpers.php` de aynı pakette gitmelidir: adres kuralı
  orada duruyor, eski dosyada o fonksiyon yok.
- `files/.htaccess` yenilenmezse anahtar dosyası adresi 403 döner ve
  gönderimler sessizce reddedilir. Site çalışmaya devam eder; yalnızca
  IndexNow işe yaramaz.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** 1.1.31'in aksine
  katalog telinde yeni alan yok, elle `ALTER` gerekmiyor.
- Migration otomatik koşar ve tek bir tablo oluşturur.
- **Dağıtım sunucusunda** iki işlevsel adım her zamanki gibi: yayımlanan
  `version.txt` 1.1.32'ye çekilmeli — yoksa "Güncelleme Denetle" hâlâ
  1.1.31'i son sürüm sanar — ve `updates/1.1.32/anime-tracker-1.1.32.zip`
  paketi yayımlanmalı, yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
  Paketin üç yeni dosyayı taşıdığı açılıp denetlenmelidir.
