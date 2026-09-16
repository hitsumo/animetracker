# Anime Tracker 1.1.41

**Yayın tarihi:** 2026-09-13

Şemalı sürüm; **merkez katalog sunucusunda elle `ALTER` gerekir.** Tek iş:
bir MAL ya da AniDB kaydı artık katalogda **birden çok anime** olabiliyor.

## 1. Sorun: kaynaklar "bir anime"nin ne olduğunda anlaşamıyor

MAL, *Death Note: Rewrite*'ı iki bölümlük **tek** kayıt olarak tutar (2994).
AniDB aynı yapımı birer bölümlük **iki** kayıt olarak tutar. Katalog AniDB
gibi iki kayıt açmak, ikisini de MAL 2994'e bağlamak istiyordu — ama MAL
numarası tekildi, ikinci kayıt "bu MAL ID zaten var" diye reddediliyordu.
Ters durum da var (AniDB tek, MAL iki); o yüzden AniDB numarası için de aynı
çözüm.

Tekillik yalnız bir tablo kısıtı değildi. MAL numarası altı yerde "bir
numara = bir kayıt" varsayımıyla kimlik anahtarı olarak kullanılıyordu:
ekleme/düzenleme formu, MAL ve AniList listesi içe aktarma, katalog senkronu
(çekme ve itme), JSON yedek, içe aktarma kara listesi ve konudaki
`[[anime:...]]` bağlantıları. Hepsi bu sürümde parçayı tanıyor.

## 2. Çözüm: parça numarası

Her kaydın MAL ve AniDB numarasının yanında bir **parça numarası** var
(`mal_part`, `anidb_part`). Var olan her kayıt kendi numarasının 1. parçası;
tekillik artık *(numara, parça)* çifti üzerinde. Yani bugüne kadarki dünya,
yeni dünyanın özel hâli: her numarada tek parça, hiçbir şey değişmedi.

**Formda bir kutu:** MAL bağlantısının altında *"Bu MAL kaydı birden çok
animeye karşılık geliyor"* (AniDB için ayrı kutu).

- **İşaretliyse** kayıt o numara için **sıradaki boş parça numarasını**
  kendisi alır (2/2, 3/3 …). Kimse numara yazmaz.
- **İşaretli değilse** aynı numarayla ikinci kayıt **yine reddedilir** —
  kazara çift kayıt hâlâ engelleniyor. Ret sayfası artık şunu da söylüyor:
  *"bilerek bölmek istiyorsanız kutuyu işaretleyin."*

Kutu **saklanmaz**; "paylaşımlı mı" sorusunun cevabı veriden türer (aynı
numarayı taşıyan başka kayıt var mı). Böylece form ile tablo birbirinden
ayrı düşemez. Düzenleme ekranında kutu, kayıt paylaşımlıysa işaretli gelir
ve yanında *"(şu an parça 2/3)"* yazar.

**Kutunun kaldırılması:** numarayı başka kayıtlar da taşıyorsa kayıt
reddedilir — bu kayıt "tek parça"ya geri dönemez, 1 ya zaten kendisidir ya
da bir kardeştedir; önce diğer parçalar ayrılmalı. Kimse taşımıyorsa parça
1'e iner: kardeşi silinmiş bir 2. parça, bir sonraki kayıtta kendiliğinden
düzelir.

**Neden bayrak değil gerçek kolon:** kimlikle kayıt adresleyen her yer
(içe aktarma, senkron, yedek, kara liste, konu bağlantısı) *hangi* parçayı
kastettiğini söyleyebilmeli. "2994" yetmez, "2994/2" gerekir.

## 3. Nerede görünüyor

**Anime detayı.** Dış Siteler'de düğme *"MyAnimeList · 2/3"* olur (yalnız
paylaşımlı kayıtta; tek parçalı kayıtta hiçbir şey değişmez). Ayrıca yeni
bir bölüm: **Aynı Kaynak Kaydı** — aynı MAL / AniDB numarasını taşıyan öteki
kayıtlar, parça numarasıyla. Veriden türer; ilişki kurmak gerekmez. (Kürator
isterse parçalar arasına ayrıca *alternatif versiyon* gibi tipli bir ilişki
kurabilir; bu ayrı bir ifadedir, zorunlu değil.)

**Konu bağlantısı.** `[[anime:2994/2|etiket]]` yazımı geldi. Eski
`[[anime:2994]]` yazımı her zaman ne demekse o — 1. parça — demeye devam
ediyor; hiçbir mevcut konu metni anlam değiştirmedi. Bağlantı seçici,
numara paylaşımlıysa kodu `/2` ekiyle yazar ve listede *"MAL 2994 · 2/3"*
gösterir; paylaşımlı değilse kod eskisi gibi çıplak numaradır.

## 4. İçe aktarma kuralı

MAL ya da AniList listesinden bir numara için **tek satır** gelir (durum +
izlenen bölüm); katalogda o numara N parça olabilir. Bölüm sayısı parçalara
**bölünemez** (2/3 → A'ya 1 mi, B'ye 1 mi?), bu yüzden uydurma sayı
**yazılmaz**:

1. **Durum bütün parçalara** yazılır (İzlendi / İzleniyor / Planlandı /
   Bıraktı …).
2. **Bölüm sayısına dokunulmaz.**
3. İki istisna: satır **0/N** ise parçalara 0 yazılır (başlamamış olan
   hiçbir parçaya başlamamıştır); satır **İzlendi** ise her parçaya **kendi
   toplam bölümü** yazılır (bütünü bitiren her parçayı bitirmiştir) —
   toplamı bilinmeyen parçada yalnız durum.
4. Sonuç cümlesine ayrı bir satır eklenir: *"N kayıt paylaşımlı MAL kimliği
   taşıyor; bölüm sayıları elle kontrol edilmeli."*

| MAL satırı | Parça A (1 bölüm) | Parça B (1 bölüm) |
|---|---|---|
| Tamamlandı 2/2 | İzlendi, 1/1 | İzlendi, 1/1 |
| İzleniyor 1/2 | İzleniyor, bölüm dokunulmaz | İzleniyor, dokunulmaz |
| Planlandı 0/2 | Planlandı, 0 | Planlandı, 0 |
| Bıraktı 1/2 | Bıraktı, dokunulmaz | Bıraktı, dokunulmaz |

Tek parçalı kimlikte içe aktarma 1.1.1'den beri neyse odur; kural yalnız
paylaşımlı kimlikte devreye girer.

## 5. Öteki yerler

- **Otomatik Doldur** (AnimeSchedule): formdaki kutu işaretliyse **toplam /
  yayınlanan bölüm sayısı doldurulmaz** — kaynağın sayısı bütünün sayısıdır,
  bu parçanın değil. Rapora bir satır eklenir; öteki alanlar (durum, yayın
  günü/saati, tarihler) yine dolar.
- **JSON yedek** parçaları taşır; kronoloji notu ve ilişkilerin karşı ucunu
  tarif eden kimlik dörtlüsü de parçayı taşır. Eski (1.1.41 öncesi) yedek
  parçasız okunur → 1. parça. Bu, 1.1.41'den önce alınmış bir yedeğin *tam
  olarak* eskisi gibi yüklenmesi demek.
- **Katalog senkronu.** Merkez katalog iki parça alanını yayımlar; çekme
  *(numara, parça)* ile eşler, itme aynı çiftle günceller. 1.1.41 öncesi bir
  sunucudan gelen tel parçasız okunur → 1. parça; böyle bir sunucuda
  zaten her numaradan bir kayıt olabilir, yerel 2. ve 3. parçalar
  "katalogda yok" sayılıp *local*'e düşer — doğru sonuç.
- **Kara liste.** Bir numara ancak **son parçası** silinince deftere girer.
  2994'ün 2. parçası silinirken 1. parçası hâlâ katalogdaysa "bu numara
  kataloğa ait değil" cümlesi yanlış olurdu. Silme kaydı yine tutulur
  (başlık); numarasız kalır.
- **Öneri kuyruğu** parça bilmez (bir numara = bir öneri); onaylanan öneri
  1. parça olarak doğar. Öneriyle taşınan kronoloji notları karşı ucun
  parçasını taşır.

## 6. Sıfırdan kurulum düzeltmesi

Bu sürümün testinde bulundu, bu sürümle kapandı: `schema.sql`'den yapılan
**taze kurulum** migration zincirini baştan koşar ve zincir **1.1.20'de
kırılıyordu** — 1.1.21'den beri sıfırdan kuran her self-host sessizce
1.1.19'da kalıyordu. Sebep: 1.0.6 migration'ı `catalog_requests` tablosunu
`CREATE TABLE IF NOT EXISTS` ile ve `title_english` kolonuyla yaratır;
schema.sql tabloyu daha önce (o kolon olmadan) yarattığı için CREATE atlanır,
kolon hiç doğmaz, 1.1.20 onu okurken düşer. Düzeltme yalnız schema.sql'de:
kolon, `animes`'teki kişisel kolonlar gibi "replay için" tanımlı; 1.1.20
okur, 1.1.21 sonunda düşürür. Migration dosyalarına dokunulmadı; var olan
kurulumlar etkilenmez. Boş bir veritabanında tam zincir koşturuldu:
**92 migration, 0.5 → 1.1.41**, kolon sonda iki tablodan da düşmüş.

## Doğrulama

- Migration, yerel veritabanının bir **kopyasında** (8.113 anime) gerçek
  MigrationManager mantığıyla koşuldu: 1.1.26 → 1.1.41, 15 migration. Bütün
  satırlar parça 1; iki bileşik tekil anahtar yerinde. Sürüm damgası 1.1.40'a
  geri alınıp **ikinci kez** koşuldu (index düşüp yeniden kuruldu, sonuç
  aynı); üçüncü koşu 0 migration. `schema.sql` boş bir veritabanına yüklenip
  üzerine 1.1.41 koşuldu: aynı sonuç.
- Yardımcı düzeyinde 37 kontrol: parça sayımı, sıradaki parça, ekleme ve
  düzenleme kararları (işaretli / işaretsiz / numara değişti / yalnız kalan
  parça), bileşik tekil anahtarın çift kaydı reddetmesi, kardeş listesi,
  rozet ve kod üretimi, içe aktarma kuralının beş satırı, konu bağlantısı
  (`2994` → 1. parça, `2994/2` → 2. parça, bilinmeyen parça → düz metin),
  silmede kalan taşıyıcı sayımı.
- Uçtan uca (yerel PHP sunucusu + curl): kutu işaretliyken ekleme (2994/2,
  sonra 2994/3); işaretsiz ekleme → ret sayfası + yeni ipucu; düzenlemede
  kutunun işaretli ve *"(şu an parça 2/3)"* gelmesi; işareti kaldırma → ret;
  işaretli kaydetme → parça korunur; detay sayfasında *"MyAnimeList · 2/3"*
  ve *Aynı Kaynak Kaydı* bölümü (parça 1 ve 3); bağlantı seçici sonuçlarında
  `2994/1`, `2994/2`, `2994/3` ve rozetler; MAL XML içe aktarma dört
  satırla (Tamamlandı 2/2, İzleniyor 1/2, Planlandı 0/2, Bıraktı 1/2) →
  yukarıdaki tablo birebir + sonuç cümlesi; JSON yedekte parçalar; 3. parça
  silinip yedekten **geri geldi** (parça 3 olarak; ona bağlı ilişki ve
  kronoloji notu da doğru uca bağlandı — parçasız olsaydı not 1. parçaya,
  yani kendisine düşüp atlanırdı); 1.1.41 öncesi biçimindeki yedek (parça
  alanları silinmiş) 1. parçayla eşleşti, çift kayıt üretmedi.
- **Katalog senkronu, sahte merkezle:** `catalog_server/` dosyaları yerel
  ikinci bir PHP sunucusunda, 1.1.26 kopyasından türetilmiş ve elle
  `ALTER`lanmış bir "merkez" veritabanıyla çalıştırıldı. Tam itme: 8.113
  güncellendi, 2 eklendi (2994/2, 2994/3 merkezde doğru parçayla); ikinci
  itme 0 eklendi, 8.115 güncellendi. Taze bir 1.1.41 kurulumuna çekme: 2
  yeni, 8.113 güncellendi, parçalar taşındı; ikinci çekme 0 yeni. **1.1.41
  öncesi sunucu** taklidi (parça alanları yok, her numaradan tek kayıt):
  1. parça güncellendi, 2. ve 3. parça *local*'e düştü.
- Tarayıcıda: düzenleme formunda iki kutu görünür, MAL kutusu işaretli ve
  etiketi *"(şu an parça 2/3)"*; detay sayfasında yeni bölüm turkuaz
  şeritle. Panel yine kararsızdı; ekran görüntüsü yerine DOM okundu.

## Dosyalar

**Yeni:**

```
files/functions/identity_helpers.php   parça kuralları, kardeşler, içe aktarma kuralı
files/migration/1.1.41/upgrade.sql     iki kolon + iki bileşik tekil anahtar
```

**Değişen:**

```
files/functions.php                     yeni yardımcının yüklenmesi
files/functions/synopsis_helpers.php    [[anime:N/P]] yazımı
files/add_anime.php                     kutular, parça atama, ret ipucu
files/edit_anime.php                    kutular (durum veriden), parça kararı, ret
files/anime_details.php                 "· 2/3" rozeti + Aynı Kaynak Kaydı bölümü
files/anime_link_search.php             ref + rozet
files/js/synopsis_link.js               kodu ref'ten yazar, rozeti gösterir
files/js/anime_form.js                  Otomatik Doldur'a kutu durumu + rapor satırı
files/fetch_animeschedule.php           paylaşımlıda bölüm sayıları düşer
files/list_settings.php                 yedek (parçalar), MAL/AniList içe aktarma kuralı
files/catalog_import.php                (numara, parça) eşleme; iki alan
files/admin/catalog_push.php            iki alan + id_map
files/admin/admin_catalog_requests.php  onayda kimlik çözümü parça duyarlı
files/index.php                         silmede kara liste yalnız son parçada
files/css/series.css                    yeni bölümün şeridi
files/schema.sql                        kolonlar + bileşik tekil anahtarlar;
                                        catalog_requests.title_english (replay)
files/lang/tr.php, files/lang/en.php    13'er yeni anahtar (parite 1051 = 1051)
files/version.txt
catalog_server/catalog.php              iki alan yayımlanıyor
catalog_server/admin_push.php           (numara, parça) eşleme; iki alan
```

CSS ve JS değiştiği için 1.1.24'ün sürüm damgası devreye girer
(`version.txt` ile birlikte yüklenmeli).

## Dağıtım notu

- **Merkez katalog sunucusunda elle `ALTER` gerekir — sıra kritik.**
  Migration orada çalışmaz.
  1. Merkezde: `mal_part` ve `anidb_part` kolonları eklenir, iki tekil
     anahtar bileşik yapılır. Komutlar
     `files/migration/1.1.41/upgrade.sql` dosyasının sonunda; önce
     `SHOW INDEX FROM animes` ile index adlarına bakılır.
  2. `catalog_server/catalog.php` ve `admin_push.php` yeni sürümü dağıtılır.
  3. Uygulama dağıtılır (migration ilk sayfa açılışında koşar).
  4. Tam katalog itme.

  Ters sırada: önce uygulama giderse parçalı bir kaydın itilmesi merkezde
  "duplicate" ile düşer; merkez `ALTER` atlanırsa yeni `catalog.php`
  bilinmeyen kolonu seçmeye çalışır ve katalog 503 verir, itme düşer.
- `files/functions.php` ile `files/functions/identity_helpers.php`
  **birlikte** yüklenmelidir; yükleyici satırı olup dosya yoksa her sayfa
  "undefined function" ile düşer.
- Kolon **eklendiği** için eski dosyalar yeni şemada çalışmaya devam eder
  (parçayı okumazlar, varsayılan 1 onları taşır). Yeni dosyalar eski şemada
  çalışmaz; migration dosyalarla aynı pakette gider ve ilk istekte koşar.
- `files/js/anime_form.js`, `files/js/synopsis_link.js`,
  `files/css/series.css` ile `version.txt` birlikte gitmeli (damga).
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.41'e çekilmeli ve `updates/1.1.41/anime-tracker-1.1.41.zip` paketi
  yayımlanmalı.
- Self-host kurulumlar aynı migration'ı koşar; ek adım yok.
