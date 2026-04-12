# Anime Tracker

Kişisel anime izleme takip uygulaması. Hangi animeleri
izlediğinizi, hangilerini izleyeceğinizi, kaçıncı bölümde
kaldığınızı, bir sonraki bölümün ne zaman yayınlanacağını ve
serilerin / filmlerin / OVA'ların hangi sırada izleneceğini
takip eder.

PHP ve MariaDB/MySQL ile yazılmış, yerel XAMPP/WAMP/MAMP
kurulumunda veya aynı stack'i destekleyen paylaşımlı hostinglerde
çalışabilecek şekilde tasarlanmıştır.

**Web sitesi:** https://www.sicakcikolata.com
**Kaynak kod:** https://github.com/hitsumo/animetracker
**Geliştirici:** Okan Sümer
**Lisans:** GNU General Public License v2

---

## Nedir

- Kişisel bir anime izleme listesi (Türkçe arayüz)
- İzleme durumu takibi (İzlendi / İzleniyor / İzlenme Planlandı)
- "Yayınlanan bölüm" ve "izlenen bölüm" bilgileri ayrı ayrı
  tutulur, One Piece gibi devam eden uzun seriler için mantıklı
  çalışır
- Her anime için yayın günü / saati / saat dilimi — "sonraki
  bölüm" geri sayımı kullanıcının olduğu yerden bağımsız doğru
  çalışır
- Seri gruplama: Detective Conan gibi bir franchise'ın TV
  sezonları, filmleri, OVA'ları ve özel bölümleri bir arada
  görünür
- Kronoloji notları: "23. bölümden sonra Film 1'i izle" tarzı
  izleme sırası ipuçları
- Tür yönetimi, istatistik sayfası, harf filtresi, sayfa başına
  gösterim, içe/dışa aktarma, otomatik güncelleme ve daha fazlası

## Ne Değildir

- Bir yayın servisi değildir. Video oynatmaz, korsan siteye
  yönlendirmez.
- Sosyal ağ değildir. Takipçi, yorum veya herkese açık profil yoktur.
- Henüz çok kullanıcılı değildir. Offline sürümü her kurulum
  başına tek kullanıcı için tasarlanmıştır. Çok kullanıcılı
  online sürüm uzun vadeli yol haritasında.

---

## Kurulum

Üç yol mevcuttur. Durumunuza uygun olanı seçin.

### Yol 1: Windows Kurulum Dosyası (.exe) — Kolay Yol

**XAMPP'i kendi kurmak istemeyen kullanıcılar için.**

1. `AnimeTracker-v0.5.exe` dosyasını (~150 MB) aşağıdaki
   resmi dağıtım bağlantısından indirin:
   https://drive.proton.me/urls/XQ92P0KM3R#tzPRSMRrUrCB
2. Çalıştırın (yönetici izni onayını kabul edin)
3. Kurulum, mevcut bir XAMPP kurulumu olup olmadığını kontrol eder:
   - XAMPP kuruluysa olduğu gibi kullanılır
   - Kurulu değilse XAMPP sessiz modda kurulur
4. Apache ve MySQL Windows servisi olarak kaydedilir ve başlatılır
5. Uygulama dosyaları `C:\xampp\htdocs\anime_tracker\` altına
   kopyalanır
6. Veritabanı oluşturulur ve şeması yüklenir
7. `setup.php` ve `install.php` otomatik olarak silinir
8. Tarayıcınızda `http://localhost/anime_tracker` adresini açın

### Yol 2: Manuel Kurulum — Kendi Web Sunucunuz İçin

**Zaten LAMP/XAMPP/WAMP/MAMP kurulumu olan veya paylaşımlı
hosting kullanan kullanıcılar için.**

1. `files/` klasörünün içeriğini web kök dizininize kopyalayın
   (XAMPP için bu `C:\xampp\htdocs\anime_tracker\` olur)
2. Tarayıcınızda `http://alan-adiniz/anime_tracker/` adresini açın
3. Otomatik olarak `setup.php`'ye yönlendirileceksiniz
4. Veritabanı formunu doldurun:
   - **Sunucu** — genellikle `localhost`
   - **Veritabanı adı** — istediğiniz bir şey, varsayılan
     `anime_tracker`
   - **Kullanıcı / Şifre** — veritabanını oluşturabilecek bir
     MySQL kullanıcısı. Yeni XAMPP kurulumunda `root` ve boş
     şifre.
5. Gönder. Veritabanı oluşturulur (gerekirse), `config.php`
   dosyası yazılır ve `install.php`'ye yönlendirilirsiniz
6. Şema yüklenir ve varsayılan türler eklenir
7. **Önemli:** kurulum başarılı olduktan sonra `setup.php` ve
   `install.php` dosyalarını silin. Kurulum sayfası bunu size
   hatırlatır.
8. "Ana sayfaya git" butonuna tıklayıp anime eklemeye başlayın

### Yol 3: Docker — Linux / macOS / VPS İçin

**Docker'ı zaten bilen veya her işletim sisteminde temiz,
tekrarlanabilir bir kurulum isteyen kullanıcılar için.**

Gerekenler: Docker 20.10+ ve Docker Compose v2 (ikisi de
Docker Desktop ile birlikte gelir).

1. Projeyi klonlayın veya indirin, klasörüne girin
2. **Önemli:** `docker-compose.yml` dosyasını açın ve iki
   yer tutucu şifreyi değiştirin:
   - `MARIADB_ROOT_PASSWORD` (veritabanı sunucusunun root
     şifresi)
   - `MARIADB_PASSWORD` ve `DB_PASS` (ikisi aynı olmalı;
     uygulama kullanıcısının şifresi)
3. Her şeyi başlatın:
   ```
   docker compose up -d
   ```
4. İlk derleme bir-iki dakika sürer. İlk çalıştırmada:
   - MariaDB container'ı başlatılır ve `schema.sql`
     otomatik yüklenir
   - Uygulama container'ı ortam değişkenlerinden
     `config.php`'yi oluşturur (`docker-entrypoint.sh`
     aracılığıyla)
5. Tarayıcınızda `http://localhost:8080` adresini açın

#### Docker İpuçları ve Dikkat Edilmesi Gerekenler

- **Kalıcı veri:** İki named volume (`db_data` ve
  `uploads`) veritabanı kayıtlarınızı ve kapak görsellerinizi
  container yeniden başlasa bile güvende tutar. Sade
  `docker compose down` komutu bunlara dokunmaz.
  `docker compose down -v` **hepsini siler** — temiz bir
  başlangıç yapmak istemiyorsanız bu komutu çalıştırmayın.
- **Şema sadece ilk açılışta yüklenir:** `schema.sql` yalnızca
  boş veritabanına uygulanır. Stack'i bir kere başlattıysanız
  ve sonra `schema.sql`'i değiştirdiyseniz, değişikliklerin
  geçerli olması için ya volume'u silip yeniden başlatın
  (`docker compose down -v` sonra tekrar up), ya da SQL'i
  `docker exec` ile elle çalıştırın.
- **Windows satır sonları:** `docker-entrypoint.sh` Unix
  (LF) satır sonları kullanmalıdır; Windows (CRLF) olursa
  bash dosyayı çalıştırmaz. `Dockerfile`, build sırasında
  `dos2unix` kurup çalıştırarak bu sorunu otomatik halleder.
  Yine de script'i bir Windows editörle düzenlerseniz LF
  olarak kaydetmeye dikkat edin.
- **Docker içinden katalog senkronizasyonu:** Uygulamanın
  "Katalogdan İçe Aktar" özelliği `animetracker.sicakcikolata.com`
  adresine HTTPS isteği yapar. Bu varsayılan olarak çalışır,
  ancak container'ın dışarıya internet erişimi olmalıdır
  (Docker varsayılanı bu).
- **Port çakışması:** Varsayılan port 8080'dir. Host
  makinenizde 8080'i başka bir şey kullanıyorsa
  `docker-compose.yml` dosyasındaki `ports` satırını
  `"8888:80"` gibi bir şeye çevirin.
- **Paylaşımlı hosting notu:** Docker paylaşımlı hosting
  için uygun bir seçenek değildir. Onun yerine Yol 2'yi
  (manuel) kullanın.

---

## Kurulum Sonrası İlk Adımlar

- **İlk animenizi ekleyin:** ana listede "Anime Ekle" tuşuna
  basın. Başlığı, durumu, bölüm sayısını, türleri girin;
  seri devam ediyorsa yayın bilgilerini de doldurun.
- **Katalogdan içe aktarın:** Liste Ayarları → "Katalogdan
  İçe Aktar" seçeneği ile sicakcikolata.com'daki merkezi
  katalogdan seçilmiş animeleri listenize çekin. Kendi
  izleme durumlarınız, bölüm ilerlemeleriniz ve notlarınız
  asla dokunulmaz.
- **Otomatik güncelleme:** Liste Ayarları → "Güncelleme
  Kontrolü" tuşuna basın. Yeni bir sürüm varsa uygulama onu
  WordPress tarzında yerinde indirip uygular. Veritabanınız
  ve yüklediğiniz kapak görselleri korunur.

---

## Teknik Notlar

- **Gerekenler:** PHP 7.4+ (8.x önerilir), MariaDB 10.3+
  veya MySQL 5.7+, UTF-8 (utf8mb4) veritabanı collation'ı
- **Kullanılanlar:** hazırlanmış sorgular ile PDO, tüm
  formlarda CSRF token'ları, beyaz liste + MIME kontrolü
  ile dosya yükleme doğrulaması
- **Depolama:** tüm kullanıcı verisi yerel veritabanında
  durur; kapak görselleri `uploads/` klasöründedir, bu
  klasör içinde PHP çalıştırmayı engelleyen `.htaccess`
  dosyası vardır
- **Saat dilimi:** sunucu tarafı tüm hesaplamaları UTC
  üzerinden yapar; her anime kendi yayın saat dilimini
  taşır (varsayılan Asia/Tokyo) — böylece geri sayımlar
  kullanıcının nerede olduğundan bağımsız doğru çalışır

---

## Yardım Almak

- Proje ana sayfası: https://www.sicakcikolata.com
- Kaynak kod ve hata bildirimi: https://github.com/hitsumo/animetracker
- Hata bildirimi ve özellik istekleri: yukarıdaki GitHub
  adresindeki "Issues" bölümünü veya proje ana sayfasındaki
  iletişim formunu kullanabilirsiniz
- Lisans metni: tam GPL v2 için `license.txt` dosyasına bakın

---

## Kaynak Koddan Derleme

Hazır `.exe` dosyasını indirmek yerine kendiniz build etmek
isterseniz hem NSIS kurulum dosyası hem de Docker imajı
kaynak ağacından yeniden üretilebilir.

### Windows Kurulum Dosyası (`.exe`)

1. NSIS 3.x'i https://nsis.sourceforge.io/Download adresinden
   indirin ve kurun
2. Windows x64 için XAMPP kurulum dosyasını (8.2.12 sürümü
   test edildi) https://www.apachefriends.org/download.html
   adresinden indirin
3. XAMPP kurulum dosyasını proje kökünde, `installer.nsi`
   dosyasının yanına yerleştirin. Dosya adı, `installer.nsi`
   dosyasının en üstündeki `XAMPP_INSTALLER` tanımıyla eşleşmelidir
   (sürümünüz farklıysa o satırı güncelleyin).
4. `installer.nsi` dosyasına sağ tıklayıp "Compile NSIS Script"
   seçeneğini seçin
5. Çıktı proje kökünde `AnimeTracker-v{sürüm}.exe` olarak
   oluşur. `{sürüm}` kısmı `files/version.txt` dosyasından
   otomatik okunur.

Ortaya çıkan `.exe` dosyası kendi kendine yeterlidir: XAMPP
içine gömülmüştür, kullanıcının kurulum sırasında internet
bağlantısına ihtiyacı yoktur. Çıktı boyutu yaklaşık 150 MB
olur.

### Docker İmajı

Docker Compose ile tek adımda build edip çalıştırın:

```
docker compose up -d --build
```

Dosyalarda değişiklik yaptıktan sonra yeniden build etmek için:

```
docker compose up -d --build --force-recreate
```

---

## Katkıda Bulunanlar

Anime Tracker, **Okan Sümer** tarafından yazılmış ve
sürdürülmektedir. Build, ikonlar ve metinler de aynı kişiye aittir.

Bu, düzgün yayın saati yönetimi ve kronoloji desteği olan bir
Türkçe anime takip uygulamasına duyulan kişisel bir ihtiyaçtan
doğmuş bir hobi projesidir. Başkaları da faydalı bulur umuduyla
GPL v2 lisansı altında özgür yazılım olarak yayınlanmaktadır.
