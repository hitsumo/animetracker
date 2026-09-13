# Anime Tracker 1.1.39

**Yayın tarihi:** 2026-09-08

Şema değişikliği yok. Tek iş: aynı listeyi gösteren binlerce adres artık arama
motorlarına ayrı birer sayfa gibi görünmüyor.

## 1. Liste kopyaları artık indekslenmiyor

1.1.37 "bu **kayıt** indekslenmeye değer mi" sorusunu cevaplamıştı. Geriye aynı
sorunun ikinci yarısı kalmıştı: aynı kayıtları gösteren kaç tane **adres** var?

Liste sayfasındaki her düğme adrese bir parametre ekliyor — harf filtresi,
sıralama sütunu, yön, sayfa numarası, sayfa boyu, sekme. Bir tarayıcı bunların
hepsine tıklıyor ve her kombinasyonu ayrı bir sayfa sanıyor. Canlı katalogla
ölçüldüğünde **1.568 farklı (harf, sayfa) çifti × 10 sıralama kombinasyonu ≈
15.700 adres** çıkıyor; sayfa boyunun altı değeri bunu ~94.000'e taşıyor.

Ölçüm (8 Eylül 2026, Search Console'un 1.000 adreslik örneği):

| Pay | Ne |
|---|---|
| %65 | boş taslak detay sayfası — 1.1.37 bunları zaten kapattı, düşmeleri yeniden taramaya bağlı |
| **%29** | **liste kopyası** — bu sürümün konusu |
| %5 | gerçek içerikli sayfa |
| %1 | kronoloji / seri / yardım |

Aynı gün site haritası **564** adres tarif ediyordu; dizinde **5.463** adres
vardı.

Bu kopyalar yeni bir giriş kapısı **açmıyor**: hiçbiri bir arama sorgusunun
cevabı değil — "H harfi, izleme durumuna göre azalan, sayfa 43" diye kimse
aramıyor. Ama var olan kapının önünde sıra bekliyorlar. Tarama bütçesi sabit
olduğu için ölçüm, bütçenin 20'de 1'inin indekslenmesini istediğimiz sayfalara
gittiğini gösterdi.

Bundan sonra **çıplak liste sayfası** indeksleniyor; bir filtre, sıralama, arama
ya da sayfa numarası verildiği anda adres `noindex, follow` taşıyor. Sayfa
dizine girmiyor ama **bağlantıları izleniyor** — katalogun tamamına giden tek iç
yol sayfalama olduğu için bu şart.

**Site içinde hiçbir şey değişmedi.** Filtreler, sıralama, sayfalama ve sekmeler
aynen çalışıyor; değişen tek şey bu adreslerin arama motoruna ne söylediği.
Ziyaretçinin göreceği bir fark yok.

### Neden `canonical` yetmedi

Bu sayfalar 1.1.30'dan beri doğru `canonical` taşıyordu — hepsi çıplak liste
sayfasını gösteriyor. Ama `canonical` bir **tavsiye**; ölçüm, Google'ın onu yok
sayıp kopyaları yine de indekslediğini gösterdi. Yandex aynı bilgiyi
`Clean-param` olarak alıyor ve ona uyuyor.

### Neden `robots.txt`'e yasak eklenmedi

`Disallow: /*?sort=` gibi bir kural adresi **taramaya** kapatır. Kapalı bir
adresteki `noindex` okunamaz, yani hâlihazırda dizinde duran binlerce kopya
orada kalırdı. Önce okunmalı, sonra düşmeli.

### Seri kronolojisinde de aynı düzeltme

`series_timeline.php` bir serinin **tüm** üyeleri için aynı çizelgeyi çiziyor —
yani beş üyeli bir seride beş adres aynı sayfa; görünüm ve zincir sekmeleri bunu
bir kat daha çoğaltıyor. Buradaki `canonical` de doğruydu ama yine yalnızca
tavsiyeydi. Artık adres canonical'in kendisi değilse `noindex, follow` taşıyor.
Site haritasının listelediği adres eskisi gibi indeksleniyor; **site haritası
çıktısı değişmedi.**

## Yükleme sonrası ne beklenmeli

Kopyaların dizinden düşmesi **anında olmaz** ve ölçüsü haftalardır: Google önce
o adresi yeniden taramalı, `noindex`'i görmeli, sonra düşürmeli.

Search Console'da beklenen eğri: **"Dizine eklenmedi" sayısı önce artar** (düşen
her sayfa oraya, "noindex etiketiyle hariç tutuldu" nedeniyle geçer), "Dizine
eklenen" sayısı düşer. **Bu başarıdır, hata değil.**

Doğru ölçülecek şey sayfa sayısı değil, Performans raporundaki **gösterim ve
tıklama**. Düşen adreslerin gösterimi zaten sıfıra yakındı, yani toplam
gösterimin düşmemesi beklenir.

## Kapsam dışı (bilinçli)

- **Sayfa numarası hâlâ sınırsız.** `?page=780` boş ekran değil, listenin son
  sayfasını döndürür. Bu davranış elle adres düzenleyen ziyaretçi için doğru;
  arama motoru tarafındaki zararını zaten `noindex` kapattı.
- **Kronoloji sayfasına dokunulmadı** — tek parametresi kayıt kimliği ve o zaten
  canonical.
- **Son eklenenler, istatistikler ve öneriler** 1.1.30'dan beri zaten indeksleme
  dışı.

## Dosyalar

**Yeni:**

```
files/migration/1.1.39/upgrade.sql   (şemasız; sürüm damgası + gerekçe kaydı)
```

**Değişen:**

```
files/functions/seo_helpers.php   kural (tek yer)
files/index.php                   biçimlendirilmiş adrese noindex, follow
files/series_timeline.php         canonical olmayan adrese noindex, follow
files/robots.php                  Clean-param listesi tek yerden okunuyor
files/version.txt
```

Yeni dil anahtarı yok — arayüzde görünen hiçbir metin değişmedi. Yeni CSS/JS de
yok.

## Dağıtım notu

- `files/functions/seo_helpers.php`, `files/index.php` ve `files/robots.php`
  **birlikte** yüklenmelidir. Yardımcı dosyanın eski kopyası sunucuda kalırsa
  ana sayfa ve `robots.txt` çöker (iki yeni fonksiyon bulunamaz). Ters sıra
  güvenlidir: önce yardımcı dosya, sonra ötekiler — yeni yardımcı, eski
  sayfalarla da çalışır.
- `files/series_timeline.php` yeni fonksiyon çağırmaz, tek başına yüklenebilir.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** Katalog telinde yeni
  alan yok, elle `ALTER` gerekmiyor, `catalog_server/` altındaki hiçbir dosya
  değişmedi.
- Migration şema değiştirmez, yalnızca sürüm damgasını taşır ve kendiliğinden
  koşar.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.39'a çekilmeli ve `updates/1.1.39/anime-tracker-1.1.39.zip` paketi
  yayımlanmalı.
- Self-host kurulumlar etkilenmez — o modda zaten her sayfa indekslemeye kapalı.
