# Anime Tracker 1.1.40

**Yayın tarihi:** 2026-09-12

Şemalı sürüm; merkez katalogda elle işlem yok. Tek iş: izleme sırası artık bir
**ilişki türü**. "Sıradaki Anime" kutusu ve arkasındaki `next_in_series` kolonu
emekli oldu; sıra, 1.1.38'de gelen ilişkiler tablosunda **Devamı / Öncesi**
olarak tutuluyor. 1.1.36'da kararlaştırılan üç adımlık yolun üçüncü ve son
adımı: zincir adı (1.1.36) → tipli ilişkiler (1.1.38) → sıra tabloya (1.1.40).

## 1. "Devamı" / "Öncesi" — sıra da bir ilişki

1.1.38'den beri iki kaydın bağı iki ayrı yerde duruyordu: alternatif versiyon,
yan hikâye gibi sırasız türler **İlişkiler** panelinde, izleme sırası ise ana
formdaki **Sıradaki Anime** kutusunda. `sequel` o sürümde bilerek ilişki
türlerine konmamıştı — iki kaynak aynı çift hakkında çelişmesin diye. Ama
ikilik kendi bedelini taşıyordu:

- **Sıra yedeğe girmiyordu.** `next_in_series` yerel bir kayıt numarasıydı ve
  her kurulum kayıtlarını farklı numaralandırır; JSON yedeğini geri yüklediğinizde
  zincir bağları sessizce kayboluyordu. İlişkiler ise 1.1.38'den beri kimlik
  dörtlüsüyle (MAL / AniDB / katalog kimliği / başlık) taşınıyor.
- **Aynı soru iki ekrandan soruluyordu.** "Bu ikisinin bağı ne?" sorusunun
  cevabı, hangi kutuya baktığınıza göre değişiyordu.
- **Kolon tekildi.** Bir kaydın tek devamı olabiliyordu; öncülü ise zaten
  birden çok olabiliyordu.

Artık tek yer var. İlişkiler panelinde iki yeni seçenek:

| Seçenek | Anlamı |
|---|---|
| **Devamı** (bundan sonra izlenir) | Eski "Sıradaki Anime" kutusuyla birebir aynı şey |
| **Öncesi** (bundan önce izlenir) | Aynı bağ, karşı uçtan girilmiş |

Bir uçtan "Devamı" dediğiniz kayıt, öteki uçta kendiliğinden "Öncesi" olarak
görünür — tıpkı yan hikâye / ana hikâye çiftinde olduğu gibi.

**"Sıra tek yerde" kuralı korundu, yeri değişti.** 1.1.38'in çelişki gerekçesi
bugün de geçerli ve başka bir mekanizmayla sağlanıyor: **bir çift en çok bir
ilişki taşır.** "A, B'nin devamıdır" ile "A, B'nin alternatif versiyonudur"
aynı anda yazılamaz. 1.1.38'in "Sıradaki Anime ile bağlı çifte ilişki
kurulamaz" hatası bu yüzden kalktı — koruduğu durum artık oluşamıyor.

### Mevcut bağlarınız kendiliğinden taşınır

Migration, katalogtaki her "Sıradaki Anime" bağını tek seferde bir **Devamı**
ilişkisine çevirir. Elle yapılacak bir şey yok. İki istisna:

- Bir kaydın kendisini işaret eden bağ (uygulama zaten engelliyordu) atlanır.
- Aynı çift için zaten bir ilişki tanımlıysa bağ atlanır, ilişki kalır. Bu
  yalnızca **uyuyan** bir bağ için mümkündü: zincir adları farklı olduğu için
  zaten izlenmeyen bir bağın üstüne 1.1.38 ilişki girilmesine izin veriyordu.
  Elle yazdığınız tür kazanır.

**Uyuyan bağlar da çevrilir.** Zincir adları farklı olduğu için izlenmeyen bir
bağ silinmez, "Devamı" ilişkisine dönüşür. Seri kronolojisi ve spoiler
koruması onu eskisi gibi **yine izlemez** (zincir adı kuralı aynen geçerli),
ama detay sayfasındaki **İlişkili Animeler** bölümü onu "Devamı" / "Öncesi"
başlığı altında **gösterir** — o bölüm kayıtlı her bağın ham listesidir.
Migration'ın sizin girdiğiniz bir veriyi kendi başına silmesi doğru olmazdı;
istemiyorsanız düzenleme ekranındaki panelden tek tıkla silersiniz.

### Ne değişmedi

- **Zincir adı kuralı (1.1.36) aynen duruyor.** Bir bağ yalnızca iki ucun
  zincir adı aynıysa izlenir. Seri kronolojisinin zincir sekmesi, "Diğer
  Zincir" sekmeleri ve konu spoiler koruması adsız veride 1.1.39 ile **birebir
  aynı** sonucu verir — bu iddia değil ölçüm (aşağıda).
- **Detay sayfasındaki "Sıradaki" kutusu** yerinde. Artık ilişkiler tablosundan
  okunuyor ve zincir adı kuralına uyuyor: başka bir hattaki "devam" bu kutuda
  görünmez, yalnız İlişkili Animeler bölümünde görünür.
- **Merkez kataloga hiçbir şey gitmiyor.** `next_in_series` gitmiyordu,
  ilişkiler de gitmiyor.

### Dallanma

Kolon gidince bir kaydın birden çok devamı olabilir. Yeni bir kural yazılmadı:
öncüller için 1.1.25'ten beri geçerli olan "aynı adlı ilk komşu" kuralı ileri
yöne de uygulandı. Hangi dalın çizileceğine program değil **kurator** karar
verir — zincir adıyla. İzlenmeyen dal, adı varsa yine listeye girer (adlı
zincirde üyelik addan gelir, 1.1.36).

### Yan kazanç: zincir bağları artık yedekte

JSON yedeği "Devamı" ilişkilerini de taşıyor; geri yüklemede karşı uç kimlik
dörtlüsüyle bulunuyor. Aynı yedeği ikinci kez yüklemek yeni satır üretmiyor.
1.1.38 sürümüne ait bir kurulum, 1.1.40'tan gelen bir yedekteki "Devamı"
satırlarını tanımaz ve **atlar** — "diğer" diye yanlış kaydetmez (1.1.38 bunu
baştan böyle yazmıştı).

## 2. Düzenleme ekranı

- "Seri ve İlişkiler" sekmesinden **Sıradaki Anime kutusu kalktı**; sekmede
  seri adı, zincir adı ve İlişkiler paneli duruyor.
- İlişkiler panelindeki hedef listesi artık **sunucuda basılıyor**. 1.1.38'de
  bu liste boş geliyor ve tarayıcı onu Sıradaki Anime kutusundan kopyalıyordu
  (aynı listeyi iki kez basmak sayfayı ikiye katlardı). Kutu gidince sayfadaki
  tek liste bu oldu; kopyalayan JavaScript de kaldırıldı.
- Panel ipucu ve form ipucu yeni duruma göre yazıldı; yardımdaki "Sonraki Seri"
  maddesi "İzleme Sırası (Devamı / Öncesi)" oldu ve nasıl kurulduğunu söylüyor.

## Doğrulama

- Migration, yerel veritabanının bir **kopyasında** (8.113 anime, 14 bağ)
  gerçek MigrationManager mantığıyla koşuldu: 1.1.26 → 1.1.40, 14 migration.
  12 bağ çevrildi; kendini işaret eden 1 bağ ve ilişkisi zaten olan 1 uyuyan
  bağ atlandı — tam beklendiği gibi. Sürüm damgası 1.1.39'a geri alınıp
  **ikinci kez** koşuldu: yeni satır yok, tablo dökümü aynı; üçüncü koşu 0
  migration.
- **Birebir karşılaştırma:** 1.1.36'nın (son değişen) `series_helpers.php`'si
  1.1.39 durumundaki kopya üzerinde, yenisi 1.1.40 durumundaki aynı kopya
  üzerinde koşturuldu — 24 seri için zincir keşfi, çizelge satırları, zincir
  başı ve spoiler öncül listeleri JSON'a döküldü ve `diff` **boş**.
- Yardımcı düzeyinde 31 kontrol: tür sırası, etiketler iki yönden, seçenek
  ayrıştırma, uç yönü (Devamı → from=seçilen, Öncesi → from=düzenlenen), eski
  `chain` hata kodunun genel cümleye düşmesi, "Sıradaki" kartı, uyuyan bağın
  çevrilip izlenmemesi, ilişkisi olan çiftin atlanması, kendini işaret eden
  bağın düşmesi, **dallanma** (iki adsız devam → küçük kimlik; adlı dal atlanır),
  **geçişli döngü** (A→B→…→A) altında yürüyüşlerin sonlanması, spoiler kapısı.
- Uçtan uca (yerel PHP sunucusu + curl): detay sayfasında "Sıradaki" kartı +
  Öncesi/Devamı grupları + "Seri Kronolojisi" düğmesi; spoiler kapısı ("... ve
  zincirdeki 1 kayıt daha henüz izlenmedi"); Devamı ekleme (`exists` reddi),
  Öncesi ekleme (yön doğru), silme; TR ve EN görünüm; anime ekleme ve düzenleme
  (kolon olmadan INSERT/UPDATE); seri kronolojisinde 11 halkalık zincir ve adlı
  zincire bağsız üyenin sona eklenmesi; JSON dışa aktarımda 12 `sequel` satırı
  ve `next_in_series` anahtarının olmaması; silinen bir Devamı bağının yedekten
  **geri gelmesi**, aynı yedeğin ikinci yüklemede satır üretmemesi.
- Tarayıcıda: detay sayfası düşük çözünürlükte görüldü (Sıradaki kartı, Seri
  Kronolojisi düğmesi, Öncesi/Devamı grupları alt alta, düzen bozulmamış).
  Tarayıcı paneli yine kararsızdı; yüksek çözünürlüklü görüntü alınamadı.

## Dosyalar

**Yeni:**

```
files/migration/1.1.40/upgrade.sql   enum'a sequel; bağları çevir; kolonu düşür
```

**Değişen:**

```
files/functions/relation_helpers.php  sequel türü, Öncesi etiketi, seçenekler;
                                      zincir çelişkisi kontrolü kaldırıldı
files/functions/series_helpers.php    yürüyüşler tabloyu okur
                                      (seriesChainNeighbours / seriesChainStep);
                                      validateNextInSeries kaldırıldı
files/edit_anime.php                  Sıradaki Anime kutusu kalktı; hedef listesi
                                      sunucuda; UPDATE'ten kolon çıktı
files/add_anime.php                   INSERT'ten kolon çıktı
files/anime_details.php               "Sıradaki" kartı + kronoloji düğmesi
                                      ilişkilerden
files/add_anime_relation.php          zincir çelişkisi reddi kalktı
files/catalog_import.php              INSERT'ten kolon çıktı
files/list_settings.php               yalnız yorumlar
files/series_timeline.php             yalnız yorumlar
files/set_series_timeline_mode.php    yalnız yorumlar
files/anime_link_search.php           yalnız yorumlar
files/help/help_series.php            yalnız yorumlar
files/js/anime_form.js                seçenek klonlama bloğu kaldırıldı
files/lang/tr.php, files/lang/en.php  4 yeni anahtar, 3 anahtar kaldırıldı,
                                      6 metin güncellendi (parite 1038 = 1038)
files/schema.sql                      kolon/FK/index kaldırıldı; enum
files/version.txt
```

Yeni CSS yok. JS değiştiği için 1.1.24'ün sürüm damgası devreye girer
(`version.txt` ile birlikte yüklenmeli).

## Dağıtım notu

- **Kolon düştüğü için dosyaların tamamı birlikte yüklenmelidir.** Eski bir
  dosya sunucuda kalırsa "Unknown column next_in_series" ile düşer:
  `edit_anime.php` (kayıt), `add_anime.php` (ekleme), `catalog_import.php`
  (**katalog senkronu**), `anime_details.php` (detay),
  `functions/series_helpers.php` (seri sayfası ve spoiler kapısı). Eski
  `functions/relation_helpers.php` kalırsa çökmez ama Devamı satırlarını
  "Diğer İlişki" diye gösterir.
- Migration ilk sayfa açılışında kendiliğinden koşar ve **yeniden
  çalıştırılabilir**: kolonu okumadan önce geri ekler (varsa yok sayılır),
  sonda düşürür; yarım kalan bir koşunun ardından ikinci koşu yeni satır
  üretmez.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** Katalog telinde yeni
  alan yok, elle `ALTER` gerekmiyor, `catalog_server/` altındaki hiçbir dosya
  değişmedi. Merkez sunucunun kendi tablosunda `next_in_series` kolonu varsa
  orada durabilir; hiçbir istemci onu okumuyor.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.40'a çekilmeli ve `updates/1.1.40/anime-tracker-1.1.40.zip` paketi
  yayımlanmalı.
- Self-host kurulumlar aynı migration'ı koşar; ek adım yok.
