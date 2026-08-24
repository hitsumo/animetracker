# Anime Tracker 1.1.30

**Yayın tarihi:** 2026-08-23

Bu sürüm siteyi arama motorlarına tanıtır — **ve tanıtmaması gereken
kurulumları da açıkça gizler.** İkisi aynı işin iki yüzü olduğu için birlikte
yapıldı.

Şimdiye kadar sayfaların `<head>` bölümünde yalnızca başlık vardı: açıklama
yok, canonical yok, paylaşım etiketi yok. `robots.txt` yoktu, `sitemap.xml`
yoktu. Yani ne arama motoruna "şu sayfalar var" denebiliyordu, ne de "buraya
hiç bakma" denebiliyordu.

## Önce gizlilik: kendi sunucuna kuranlar için

**Kişisel kurulumlar artık arama motorlarına kapalı.** Tek kullanıcılı
(self-host) modda uygulamanın girişi yoktur — bu tasarım gereğidir, sayfayı
açan herkes o tek kullanıcıdır. Uygulamayı kendi sunucusuna kurup kişisel
listesini tutan biri, bugüne kadar hem herkese açık hem de **arama
motorlarında çıkabilir** durumdaydı. Bu bir SEO eksiği değil, gizlilik
kusuruydu.

Artık indeksleme moda bağlı:

| Kurulum | Davranış |
|---|---|
| **Tek kullanıcılı (self-host)** | Her sayfa "indeksleme" der; `robots.txt` her şeyi kapatır; `sitemap.xml` yayımlanmaz |
| **Çok kullanıcılı (online)** | Sayfalar indekslenebilir; site haritası yayımlanır |

Yeni bir ayar eklenmedi — karar zaten var olan `MULTI_USER_MODE` anahtarına
bağlandı. Kişisel bir kurulum, sahibi hiçbir kutucuk keşfetmeden kapalı
olmalı. Fikrini değiştiren `config.php`'de o anahtarı `true` yapar.

## Site haritası (`sitemap.xml`)

Yeni bir `sitemap.xml` adresi var ve içeriği her istekte güncel üretilir.
İçine girenler:

- ana liste sayfası, hakkında ve altı yardım sayfası,
- **her anime kaydının detay sayfası**,
- kronoloji işareti olan animelerin **izleme sırası** sayfası,
- her seri için **bir tane** seri kronolojisi sayfası.

Sitemap'in asıl gerekçesi listenin sayfalanması: liste varsayılan olarak
sayfa başına 10 kayıt gösterir, yani katalog büyüdükçe derindeki animelere
bağlantıyla ulaşmak pratikte imkânsızlaşır. Site haritası her kaydı doğrudan
adresiyle bildirir.

**Katalog büyürse harita kendini böler.** 2000 kayıttan sonra tek dosya
yerine bir "harita indeksi" ve parçalar üretilir; bu, arama motorlarının
beklediği standart yapıdır ve otomatik olur.

**+18 işaretli kayıtlar haritada yer almaz.** Detay sayfası onları zaten
tercih arkasında gizliyor; anonim bir ziyaretçi (ve arama motoru) yalnızca
nötr uyarıyı görür. Yer tutucu döndüren bir adresi listelemenin anlamı yok.

## `robots.txt`

Artık var ve moda göre yazılır. Çok kullanıcılı kurulumda ana sayfalar açık
kalır; giriş, kayıt, hesap, düzenleme, yönetim ve "tıklayınca iş yapan"
adresler kapatılır. Site haritasının adresi de burada bildirilir.

Yandex için ayrı bir bölüm var: liste sayfasının sıralama ve filtre
parametreleri `Clean-param` ile bildiriliyor, böylece aynı listenin onlarca
farklı adresi ayrı sayfa sanılmıyor.

## Sayfa başlıklarına eklenen etiketler

Her sayfaya **açıklama**, **canonical** ve **paylaşım etiketleri** (Open
Graph / Twitter kartı) eklendi. Somut karşılığı:

- **Arama sonucunda** başlığın altında artık anlamlı bir açıklama çıkar.
  Anime detay sayfalarında bu açıklama **katalog konusundan** üretilir.
- **Bir bağlantıyı WhatsApp'a, Discord'a ya da bir foruma yapıştırdığında**
  başlık, açıklama ve **poster** görünen bir kart çıkar; çıplak adres değil.

**Kişisel konu metni bu açıklamalara hiç girmez.** Açıklama yalnızca katalog
konusundan üretilir; kişisel konu senin kendi notundur, meta açıklama ise
tüm dünyaya yayımlanır.

### Canonical: aynı içeriğin tek adresi

- **Liste sayfası:** sıralama, filtre, arama ve sayfa numarası
  parametrelerinin hepsi çıplak liste adresini işaret eder.
- **Seri kronolojisi:** bu sayfa aynı seriyi her üyesi için aynı şekilde
  çizer, yani bir sayfanın birden çok adresi olurdu. Artık seri başına tek
  bir adres asıl kabul ediliyor.
- **İzleme sırası:** görünüm modu (yayın sırası / hikâye sırası) adrese
  yazılsa da asıl adres tektir.

### İndekslenmeyen üç sayfa

**Son Güncellenenler**, **İstatistikler** ve **Ne İzlesem?** bilerek arama
sonuçlarının dışında tutuldu: üçü de her ziyarette değişir, istatistikler
zaten tek kişinin listesini anlatır ve "Ne İzlesem?" sürpriz modunda her
istekte başka bir anime döndürür. Bu sayfalardaki bağlantılar yine takip
edilir — tarama emeği detay sayfalarına gitsin diye.

## Küçük ama önemli bir düzeltme

Olmayan bir anime numarasıyla girilen detay sayfası "bulunamadı" yazıyor ama
sunucu **"her şey yolunda" cevabı** veriyordu. Arama motoru bunu "bu sayfa
var" diye okur ve tek satırlık hata metnini içerik sanar. Artık düzgün bir
"bulunamadı" cevabı dönüyor. Ekranda görünen bir değişiklik yok.

## Yapılandırma: `SITE_URL` (isteğe bağlı)

Arama motorları mutlak adres ister; uygulama ise her yerde göreli bağlantı
kullanır. Adres artık isteğin kendisinden kuruluyor, yani **hiçbir şey
yapmana gerek yok** — alt dizine kurulmuş bir uygulama da doğru adresi
üretir.

Tek istisna: uygulama bir ters vekil ya da CDN arkasındaysa, gelen istek
sitenin gerçek adresini söylemeyebilir. O durumda `config.php`'ye
`SITE_URL` yazılabilir. Örnek yapılandırma dosyasında açıklamasıyla birlikte
duruyor, varsayılan olarak kapalı. Eski `config.php` dosyaları hiç
dokunulmadan çalışmaya devam eder.

## Sürümden sonra yapılacak (koddan bağımsız)

Site haritasının bir işe yaraması için arama motorlarına bildirilmesi
gerekir: Google Search Console, Bing Webmaster (aynı kayıt DuckDuckGo,
Ecosia ve Yahoo'yu da kapsar) ve Yandex Webmaster. Üçüne de **aynı** site
haritası adresi verilir; motor başına ayrı dosya gerekmez. Site sahipliği
doğrulaması **DNS TXT kaydıyla** yapılırsa siteye hiçbir dosya eklenmez —
en temiz yol budur.

## Değişen dosyalar

**Yeni:**

```
files/functions/seo_helpers.php
files/sitemap.php
files/robots.php
files/migration/1.1.30/upgrade.sql (yalnızca sürüm damgası)
```

**Değişen:**

```
files/.htaccess                    (/robots.txt ve /sitemap.xml yönlendirmeleri)
files/functions.php                (yeni yardımcı dosyanın yüklenmesi)
files/config_example.php           (isteğe bağlı SITE_URL)
files/index.php                    (meta + canonical)
files/anime_details.php            (meta + canonical + "bulunamadı" cevabı)
files/chronology.php               (meta + canonical)
files/series_timeline.php          (meta + seri başına tek canonical)
files/about.php, files/help.php    (meta + canonical)
files/help/help_basics.php, help_fields.php, help_sync.php,
files/help/help_discovery.php, help_series.php, help_timezone.php
files/recent.php, files/statistics.php, files/recommendations.php
                                   (indekslenmeme etiketi)
files/lang/tr.php, files/lang/en.php  (11'er yeni metin)
files/version.txt
```

## Dağıtım notu

- `files/functions.php` ile `files/functions/seo_helpers.php` **birlikte**
  yüklenmelidir. Yalnızca sayfalar güncellenirse olmayan bir fonksiyon
  çağrılır ve sayfa açılmaz.
- `files/.htaccess` eski kalırsa `/robots.txt` adresi çalışmaz (sunucu `.txt`
  uzantısını topluca engelliyor); `robots.php` ve `sitemap.php` kendi
  adlarıyla yine açılır.
- Dil dosyaları eski kalırsa açıklama metni yerine anahtar adı görünür —
  zararsız ama çirkin.
- Veritabanı şeması değişmedi; merkez katalog sunucusunda elle yapılacak bir
  iş yok.
- **Dağıtım sunucusunda** iki işlevsel adım her zamanki gibi: yayımlanan
  `version.txt` 1.1.30'a çekilmeli — yoksa "Güncelleme Denetle" hâlâ 1.1.29'u
  son sürüm sanar — ve `updates/1.1.30/anime-tracker-1.1.30.zip` paketi
  yayımlanmalı, yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
- Yükledikten sonra bakılacak tek şey: canlı `config.php`'de çok kullanıcılı
  mod açık olmalı, yoksa site kendini arama motorlarına kapatır.
