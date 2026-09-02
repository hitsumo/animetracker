# Anime Tracker 1.1.35

**Yayın tarihi:** 2026-08-31

Bu sürüm tek bir soruyu çözer: **katalogdan sildiğin bir anime neden bir
sonraki içe aktarımda geri geliyor?**

Katalog sahibi kataloğa tohumu açık bir AniList listesinden atıyor, sonra
elle ayıklıyor — AniDB karşılığı olmayan kayıtları siliyor. Bu iş akışı iki
yerden birden bozuluyordu ve aslında ikisi aynı kusurun iki yüzü:

1. **Ne sildiğin hiçbir yerde yazmıyordu.** Ayıklama kararı yalnızca senin
   aklındaydı; silinen kayıt arkasında iz bırakmıyordu. İki hafta sonra
   "bunu bilerek mi çıkarmıştım, yoksa hiç gelmedi mi" sorusunun cevabı
   yoktu.

2. **Bir sonraki içe aktarım hepsini geri getiriyordu.** İçe aktarmada bir
   satır "eşleşmedi" sayılır *çünkü* katalogda öyle bir kayıt yoktur — ki
   silmek tam olarak o durumu üretir. Yani silmek, animeyi bir sonraki içe
   aktarmanın taze adayı hâline getiriyordu.

Aynı kusurun üçüncü bir yüzü daha vardı: katalog önerilerindeki
tekilleştirme yalnızca bekleyen satırlara bakıyordu. Yani **reddettiğin bir
öneri de** her içe aktarımda yeniden açılıyordu; reddetmek "bir daha sorma"
demiyordu.

## Kara liste

Artık katalogdan bir anime sildiğinizde kimliği bir listeye yazılıyor. Bu
listedeki animeler AniList ve MyAnimeList içe aktarımlarında katalog
önerisi olarak **açılmıyor**.

Liste iki işi birden görüyor: hem *ne sildiğinin defteri*, hem de *geri
gelmesini engelleyen kapı*. Yönetici panelinde yeni bir kart var — **İçe
Aktarma Kara Listesi**.

Sayfada yapabilecekleriniz:

- Silinen animeleri tarih sırasıyla görmek (kim sildi, MAL / AniDB kimliği,
  varsa not)
- Başlığa ya da kimliğe göre aramak
- Fikir değiştirdiğinizde bir kaydı **listeden çıkarmak** — o anime bir
  sonraki içe aktarımda yeniden gelebilir
- Hiç eklenmemiş bir animeyi baştan **elle engellemek** (reddettiğiniz bir
  öneri için de bunu yapabilirsiniz)

## İçe aktarma önizlemesinde ne görünür

Önizleme özetinin altına yeni bir satır geliyor:

> Bunlardan 2 tanesi yönetici kara listesinde; içe aktarımda atlanacak.

İçe aktarma bittiğinde de sonuç mesajının sonuna ekleniyor:

> 2 anime yönetici kara listesinde olduğu için atlandı.

Kara liste boşsa ya da hiçbir kayıt engellenmediyse bu satırların ikisi de
hiç görünmez.

## Kara liste neyi engellemez

**Katalogda gerçekten duran bir anime** kara listede olsa bile üyenin
listesine eklenebilir. Kara liste "katalogda olmasın" demektir, "kimse
izleyemesin" demek değil. Kapı yalnızca *eşleşmeyen* satırlarda durur.

Bu durum pratikte yalnızca silinip sonra elle geri eklenmiş bir animede
oluşur; yönetici sayfası öyle bir kaydı **"Katalogda var"** rozetiyle
işaretler, temizleyebilesiniz diye.

## Eşleşme başlığa göre değil kimliğe göre

Liste `mal_id` ve `anidb_id` üzerinden eşleşir — kataloğun zaten anahtar
kabul ettiği kararlı kimlikler. **Başlığa göre eşleşmez:** birbiriyle
ilgisiz iki yapım aynı başlığı fazlasıyla sık paylaşır ve eski bir silmenin
adı yüzünden meşru bir animeyi sessizce düşüren bir içe aktarma, çözdüğü
sorundan daha kötü olurdu.

Kimliği hiç olmayan bir animeyi sildiğinizde kayıt **yine de tutulur** ama
hiçbir şeyi engelleyemez — eşleştirilecek anahtar yoktur. Sayfa bunu
**"Engellemez"** rozetiyle açıkça söyler, öyle değilmiş gibi yapmaz. Elle
eklerken ise en az bir kimlik zorunludur.

## Tek kullanıcılı kurulumda hiçbir şey değişmez

Kara liste **yalnızca çok kullanıcılı (online) modda** etkindir. Tek
kullanıcılı bir kurulumun paylaşılan kataloğu ve küratörü yoktur; sahibinin
silmeleri kişiseldir ve içe aktarması kendi listesinde ne varsa getirmeye
devam etmelidir.

Böyle bir kurulumda: silme hiçbir kayıt tutmaz, içe aktarma hiçbir şeyi
engellemez, yeni sayfa "bu özellik burada etkin değil" der. Kataloğu
senkronlayan bir kullanıcı da küratörün neyi kara listeye aldığından
etkilenmez — liste merkeze **gönderilmez**.

## Değişen dosyalar

**Yeni:**

```
files/functions/blacklist_helpers.php   (listenin tek kural yeri)
files/admin/admin_blacklist.php         (yönetici sayfası)
files/migration/1.1.35/upgrade.sql
```

**Değişen:**

```
files/functions.php                     (yeni yardımcı dosyanın yüklenmesi)
files/index.php                         (silme kaydı yazar)
files/list_settings.php                 (AniList + MAL içe aktarma kapısı)
files/admin/admin.php                   (yeni kart)
files/lang/tr.php, files/lang/en.php    (içe aktarma bildirimleri)
files/lang/admin_tr.php, admin_en.php   (yönetici sayfası metinleri)
files/schema.sql
files/version.txt
```

## Dağıtım notu

- `files/functions.php` ile `files/functions/blacklist_helpers.php`
  **birlikte** yüklenmelidir. Yükleyici satırı olmadan dosya hiç okunmaz ve
  silme ile içe aktarma sayfaları açılmaz.
- `files/lang/*.php` dosyaları `files/admin/admin_blacklist.php` ve
  `files/list_settings.php` ile birlikte gitmelidir; yoksa metin yerine
  anahtar adı görünür.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** Katalog telinde yeni
  alan yok, elle `ALTER` gerekmiyor, `catalog_server/` altındaki hiçbir dosya
  değişmedi.
- Migration otomatik koşar ve tek bir tablo oluşturur. Dosyalar yüklenip
  migration henüz koşmadıysa uygulama **çökmez**: silme çalışmaya devam eder
  (kayıt tutulmaz, sunucu günlüğüne yazılır), içe aktarma hiçbir şeyi
  engellemez ve yönetici sayfası "tablo okunamadı" der. Bir sonraki sayfa
  açılışında migration kendiliğinden koşar ve her şey yerine oturur.
- **Dağıtım sunucusunda** iki işlevsel adım her zamanki gibi: yayımlanan
  `version.txt` 1.1.35'e çekilmeli — yoksa "Güncelleme Denetle" hâlâ
  1.1.34'ü son sürüm sanar — ve `updates/1.1.35/anime-tracker-1.1.35.zip`
  paketi yayımlanmalı, yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
