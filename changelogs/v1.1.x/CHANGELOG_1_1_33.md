# Anime Tracker 1.1.33

**Yayın tarihi:** 2026-08-25

Bu sürümde iki iş var. Birincisi asıl konu: **devam sezonunun konusu, önceki
sezonu ele veriyor.** İkincisi, yardım sayfalarının büyümesi — en altta.

Bir serinin ikinci ve sonraki halkalarının özeti neredeyse her zaman kendinden
öncekinin sonunu anlatır. "X öldükten sonra geriye kalan ekip..." diye başlayan
bir konu, o animeyi henüz izlememiş kişiye hiçbir şey anlatmaz ama bir önceki
sezonu baştan sona spoiler'lar. Kullanıcı devam sezonunun sayfasını yalnızca
"kaç bölüm" diye açsa bile konu ekranda duruyordu.

Artık böyle bir sayfada konu doğrudan basılmıyor; **"Yine de okumak
istiyorum"** düğmesinin arkasında bekliyor.

## Nasıl görünüyor

Zincirde bu animeden önce gelen halkalardan biri bile izlenmemişse "Konu"
satırında metin yerine şu kutu çıkıyor:

> Seri S2 ve zincirdeki 1 kayıt daha henüz izlenmedi — bu konu önceki
> sezonları ele verebilir.
>
> **[ Yine de okumak istiyorum ]**

Düğmeye basıldığında konu yerinde açılıyor, düğme "Konuyu gizle"ye dönüşüyor.
Yalnızca tek bir halka eksikse metin de tekil oluyor: "Seri S2 henüz izlenmedi
— bu konu önceki sezonu ele verebilir."

Önceki halkaların **hepsi** izlendiyse ortada düğme de yok; sayfa 1.1.32'deki
gibi görünüyor.

## Kural

| Durum | Sonuç |
|---|---|
| Zincirde önceki halkaların hepsi izlendi | Konu doğrudan görünür |
| Önceki halkalardan biri izlenmedi | Konu düğmenin arkasında |
| Önceki halka **yarım** izlendi ("İzleniyor") | Konu düğmenin arkasında |
| Bu animeyi izliyor / bitirdiysen / bıraktıysan | Konu doğrudan görünür |
| Zincirde hiç yer almayan bağımsız kayıt | Konu doğrudan görünür |

Üç noktası bilinçli seçildi:

**Yalnız bir önceki halkaya değil, zincirdeki tüm öncekilere bakılıyor.**
S3'ün sayfasında S2 izlenmiş ama S1 atlanmışsa konu yine de S1'i ele
verebilir; "yalnızca en yakın halkaya bak" kuralı o kişiyi korumazdı.

**Yarım izlenen sezon izlenmiş sayılmıyor.** Bıraktığın yerden sonrasını
anlatan bir özet de spoiler'dır.

**Başladığın animede kapı kurulmuyor.** İzlemekte olduğun (ya da bitirdiğin,
ertelediğin, bıraktığın) bir animenin konusunda senin için spoiler yoktur —
önceki sezonu atlamış olsan bile onu zaten bu animeyi izlerken öğrendin.

## Nerede geçerli

- **Anime detay sayfası** — "Konu" satırı.
- **Öneriler → Sürpriz** — kartın 200 karakterlik tanıtım metni. Bu olmasaydı
  koruma delik olurdu: detay sayfası konuyu gizlerken sürpriz kartı aynı
  metnin ilk cümlelerini basardı, ki spoiler cümlesi genelde özetin ilk
  cümlesidir.

**Kişisel Konu kapının dışında.** O metni sen yazıyorsun ve yalnızca sen
görüyorsun; kişiyi kendi notundan korumanın anlamı yok.

## Giriş yapmamış ziyaretçide

Anonim ziyaretçinin kişisel izleme verisi yok, yani hiçbir şey izlenmiş
sayılmıyor ve devam halkalarının konusu onda da düğmenin arkasında açılıyor.
Katalogu ilk kez gezen kişi tam da korunması gereken kişi; maliyeti tek tık.

## Kapatılabilir

**Liste Ayarları → Spoiler Koruması** altında bir kutucuk var; varsayılan
olarak **açık**. Kapatıldığında hiç düğme çıkmıyor, konu her zaman doğrudan
görünüyor. Tercih kişiye özel, yalnızca kendi hesabını etkiliyor.

## Küçük notlar

- Kutu tarayıcının kendi açılır bölümü (`<details>`), **JavaScript
  kullanmıyor**: betikler kapalıyken de açılıyor, klavyeyle geziliyor ve
  açmak yeni bir istek atmıyor — metin zaten sayfayla geliyor.
- Detay sayfasının arama motorlarına verdiği açıklama değişmedi. O açıklama
  tüm dünyaya yayımlanan tek bir metin, ziyaretçiye göre değişmez.
- Ana liste sayfası zaten hiç konu basmıyor, orada bir değişiklik yok.

## Yardım sayfaları büyüdü

Aynı sürümde ikinci bir iş: yardım baştan tarandı ve **yazılmış ama hiç
belgelenmemiş** özellikler yazıldı. Dört yeni bölüm eklendi:

- **Liste, Arama ve Filtreler** — Genel Liste ile Kişisel Liste arasındaki
  fark ve varsayılan sekme tercihi, arama kutusunun neyi taradığı, altı
  filtre (tür, izleme durumu, yayın durumu, harf, yıl, ülke), duygu
  filtresinin nereden geldiği, "Sayfada Göster" ve sıralama okları, artı
  "Son Güncellenenler" sayfası.
- **Kişisel Tercihler** — Liste Ayarları'ndaki yedi tercih tek listede;
  arayüz dili ve yetişkin içerik (+18) ayrıntılı anlatıldı.
- **Liste Taşıma ve İçe Aktarma** — JSON yedeği alma ve geri yükleme,
  MyAnimeList dosyası aktarma, AniList kullanıcı adıyla aktarma (iki türü
  ve önizleme adımı), "Listeyi Temizle"nin ne kadar geri alınamaz olduğu.
- **Üyelik ve Katkı** — giriş, kayıt, davetiye talebi, hesap sayfası, dört
  rolün ne yapabildiği, eklediğiniz animenin onay kuyruğuna düşmesi ve
  "Düzeltme Öner" kutusu.

"Seriler ve Bölüm Bilgisi" sayfasına üç bölüm girdi: **Seri Kronolojisi
sayfası** (sekmeler ve "Diğer Zincir" başlıkları), **Spoiler Koruması**
(yukarıdaki kuralın tamamı) ve **Yayın Bilgileri ve Geri Sayım**.

Ayrıca birkaç düzeltme: `+/-` butonlarının detay sayfasında da olduğu
yazıldı, kişisel alanlar listesine izlemeye başlama/bitirme tarihleri ve
katalog alanları listesine Ülke eklendi, cümle etiketlerini "yönetici"
değil küratörlerin atadığı düzeltildi, güncelleme bölümünün yalnızca
kişisel kurulumda göründüğü not edildi ve hiçbir yere gitmeyen bir iç
bağlantı onarıldı.

## Değişen dosyalar

**Yeni:**

```
files/set_spoiler_pref.php
files/help/help_list.php
files/help/help_prefs.php
files/help/help_transfer.php
files/help/help_account.php
files/migration/1.1.33/upgrade.sql
```

**Değişen:**

```
files/functions/series_helpers.php   (zincirin geriye yürünmesi + kapı)
files/anime_details.php              (Konu satırı)
files/recommendations.php            (Sürpriz kartının tanıtımı)
files/list_settings.php              (Spoiler Koruması kutucuğu)
files/css/base.css                   (kutunun görünümü)
files/help.php                       (içindekiler: dört yeni grup)
files/help/help_series.php           (üç yeni bölüm)
files/lang/tr.php, files/lang/en.php (128'er yeni metin + düzeltmeler)
files/version.txt
```

## Dağıtım notu

- `files/functions/series_helpers.php` ile üç sayfa (`anime_details.php`,
  `recommendations.php`, `list_settings.php`) **birlikte** yüklenmelidir.
  Yardımcı dosya eskide kalırsa sayfalar var olmayan bir fonksiyonu çağırır ve
  açılmaz.
- `files/css/base.css` eskide kalırsa kapı **çalışır** ama çirkin görünür
  (düz metin bir açılır satır olur); işlevsel kayıp yok.
- Dil dosyaları eskide kalırsa uyarı metni yerine anahtar adı görünür.
- `files/set_spoiler_pref.php` eksik kalırsa kapı yine çalışır, yalnızca Liste
  Ayarları'ndaki kutucuk 404 verir.
- `files/help.php` ile dört yeni yardım sayfası **birlikte** yüklenmelidir;
  içindekiler onlara bağlantı verir. Yardım tarafının eksik kalması
  uygulamanın geri kalanını etkilemez.
- Merkez katalog sunucusunda yapılacak bir şey yok; şema değişmedi, katalog
  teline dokunulmadı.
- **Dağıtım sunucusunda** iki işlevsel adım her zamanki gibi: yayımlanan
  `version.txt` 1.1.33'e çekilmeli — yoksa "Güncelleme Denetle" hâlâ 1.1.32'yi
  son sürüm sanar — ve `updates/1.1.33/anime-tracker-1.1.33.zip` paketi
  yayımlanmalı, yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
