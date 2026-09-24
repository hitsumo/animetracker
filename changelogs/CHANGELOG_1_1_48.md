# Anime Tracker 1.1.48

**Yayın tarihi:** 2026-09-22

Tek iş: **ilişki şeması.** Seri Kronolojisi sayfasına üçüncü bir sekme
geldi — **Şema** — ve bir seriyi liste değil çizim olarak gösteriyor:
her kayıt bir kutu, her ilişki bir çizgi. Şema hiçbir ek girdi istemez;
zaten kurulmuş ilişkilerden ve zincir adlarından kendiliğinden çizilir.
Şema değişikliği yok (boş migration); merkez katalogda yapılacak bir şey
yok.

## 1. Neden

Seri Kronolojisi'nin iki sekmesi seriyi tek boyutta okur: "hangi sırayla
izlerim" (Zincir Sırası) ve "ne zaman çıktı" (Yayın Tarihi). Oysa
katalogda iki bilgi daha var ve ikisi de listede görünmüyordu:

- **Bağın türü** (1.1.38'den beri): "Crystal, 90'lar dizisinin
  alternatif versiyonudur" cümlesi yalnız detay sayfasında bir satırdı;
  kronolojide iki hat arasındaki bu bağ çizilmiyordu.
- **Hatlar** (1.1.36'dan beri): zincir adları sekme oluyordu, ama iki
  hattın birbirine göre nerede durduğu — hangisi önce, hangisi hangisine
  bağlı — görülmüyordu.

Kürasyon zaten yapılıyordu; eksik olan yalnızca çizim.

## 2. Şema sekmesi

`series_timeline.php?id=…&mode=graph`. Seri adı olan her animede
görünür (Yayın Tarihi gibi); "Diğer Zincir" görünümü her zamanki gibi
zincir modudur.

**Yerleşim** — iki doğal eksen, hesaplanan bir şey yok:

- **Satır = hat.** Her zincir adı bir satır; ad verilmemiş zincirler
  "Zincir 1, 2…" diye numaralanır (eskiden yeniye); hiçbir zincire
  girmeyen kayıtlar "Bağımsız" satırında toplanır.
- **Sütun = yayın yılı.** Kutular soldan sağa ilk gösterim tarihine göre
  dizilir. Aynı yılda çıkan iki kayıt **farklı satırlardaysa aynı sütunu
  paylaşır** (bir TV sezonu ile aynı yılın filmi alt alta durur), aynı
  satırdaysa yeni sütun açılır. Tarihi bilinmeyen kayıtlar en sağda "?"
  sütununda. Yıl etiketi yalnız değiştiğinde yazılır.
- **Kutu:** poster, kısaltılmış ad, medya türü · yıl. Sol kenardaki renk
  şeridi sizin izleme durumunuz (listedeki noktayla aynı renkler); mavi
  çerçeve, sayfaya geldiğiniz anime. Kutuya tıklayınca detay açılır;
  üzerine gelince tam ad görünür.
- **Ad kısaltma:** seri adı öneki kutuda düşer — "Tensei Shitara Slime
  Datta Ken: Tensura Nikki" kutuda "Tensura Nikki", "… (2021)" kutuda
  "2021". Önek başta olmasa da bulunur ("Gekijouban <seri>: Guren no
  Kizuna-hen" → "Guren no Kizuna-hen"; "Film" zaten alt satırda). Geriye
  bir şey kalmıyorsa (ilk sezon) tam ad kalır. Yalnız gösterim; tooltip
  ve bağlantı tam adı taşır.

**Çizgiler** — her `anime_relations` satırı bir çizgi:

- **Devamı / Öncesi:** düz mor ok, zincir boyunca soldan sağa akar. Aynı
  satırda arada başka kutu varsa çizgi kutunun altından geçmez, üstten
  kemer yapar (yoksa "A → B" gibi okunurdu).
- **Öteki türler:** satırlar arası kesikli eğri; tür başına ayrı desen ve
  renk (alternatif versiyon / kurgu yeşil, yan hikâye / özet turuncu,
  aynı evren / ortak karakter mavi, diğer gri). Aynı satırdaysa üstten
  kemer.
- **Ok yönü, tek kural:** yönü olan türlerde ok **türetilmiş işe** bakar
  — önceki → devamı, ana hikâye → yan hikâye, tam hikâye → özet. Yönsüz
  türlerde ok yok.
- Çizginin üzerine gelince iki kaydın adı ve bağın türü görünür.
- **Lejant** şemanın altındadır ve yalnız o şemada geçen türleri ve
  durumları listeler — sözlüğün tamamını değil.

**Kapsam:** seri adı grubu + o gruba **doğrudan** ilişkiyle bağlı dış
kayıtlar. Dış kayıt (başka seri ya da serisiz) soluk, kesik çerçeveli bir
**"hayalet" kutu** olarak en alt "Seri dışı" satırında durur: Lunlun ↔
Hua Xianzi gibi seri sınırını aşan bir bağ görünür kalır, ama o kaydın
serisinin geri kalanı buraya taşınmaz. Hayalete tıklamak kendi serisinin
şemasına götürür (serisi yoksa detayına).

**Boş durumlar:** ilişkisi olmayan seride kutular yayın sırasıyla dizilir
ve altta "henüz kurulmuş ilişki yok" notu çıkar. 18+ kaydın kutusu durur,
adı tercih açık değilse gizlenir (1.1.2 kuralı).

## 3. Neden saf SVG, neden kütüphane yok

Şemayı PHP üretir, sayfa hazır gelir; JavaScript kütüphanesi yok, ek
indirme yok. Mermaid gibi bir kütüphane işi kısaltırdı ama ~3 MB'lık bir
yük getirir ve kutuların yerine kendisi karar verirdi — "TV dizileri
üstte, filmler altta" diyemezdik. Burada kutuların yeri iki eksenden
zaten belli olduğu için genel bir graf yerleşimi (force-directed vb.)
gereksizdir. Projenin tek dış kaynağı Font Awesome olarak kalır.

**Türe göre kod yok.** Çizim kuralı türden bağımsızdır: `sequel` yatay
ok, geri kalan her tür kesikli eğri. Tür adı yalnız CSS sınıfına ve
lejant etiketine gider. Sözlüğe yeni bir tür eklendiğinde (1.1.47'nin
Aynı Evren / Ortak Karakter'i böyle geldi; sıradaki `special` böyle
gelecek) şema onu kendiliğinden çizer; CSS'e bir renk satırı eklemek
isteğe bağlıdır, yoksa gri kesikli kullanılır.

## 4. Liste Ayarları

"Seri Kronolojisi Görünümü" seçeneğine **Şema** eklendi: sayfa
varsayılan olarak şemayla açılabilir. Seçenek listesi ve kaydeden ucun
beyaz listesi artık tek kaynaktan (mod listesi) gelir — 1.1.46'da "Son
İzlenenler" seçeneğinin sessizce kaydedilmemesine yol açan sabit kopya
liste burada da vardı, kaldırıldı.

## 5. Yardım

- Seri yardımındaki "Seri Kronolojisi Sayfası" bölümü: sekme listesine
  Şema; "iki sekme" cümleleri "üç"e; yeni **"Şema"** alt başlığı —
  satır/sütun kuralı, çizgi türleri, ok yönü, durum şeridi, hayalet kutu,
  kaydırma.
- Liste Ayarları açıklaması şemayı sayar.

## Dosyalar

**Yeni:**

```
files/functions/series_graph_helpers.php  veri toplama, satır/sütun yerleşimi, SVG, lejant
files/migration/1.1.48/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/series_timeline.php                 Şema sekmesi, mode=graph dalı, şema CSS'i, geniş kapsayıcı
files/functions/series_helpers.php        series_timeline_modes(): 'graph'; liste tek kaynak notu
files/functions.php                       yeni yardımcı dosyası yüklenir
files/list_settings.php                   <select> ve beyaz liste series_timeline_modes()'tan
files/set_series_timeline_mode.php        yorum (mod listesi)
files/help/help_series.php                "Şema" alt başlığı
files/lang/tr.php                         +11 anahtar (series_timeline.tab.graph, series_graph.*, help.st.graph.*); 4 metin değişti
files/lang/en.php                         aynı
files/version.txt
```

Dil dosyası paritesi: 1149 = 1149.

## Dağıtım notu

- **Merkez katalogda iş yok.** Şema yalnız okur; yeni tablo/kolon yok.
- Migration boştur; ilk sayfa açılışında sürüm damgasını taşır.
- `functions/` klasörü **bütün** gitmeli: `functions.php` yeni
  `series_graph_helpers.php`'yi yükler; dosya eksikse her sayfa ölümcül
  hata verir.
- Bilinen sınırlar (bilinçli): satır etiketleri şemayla birlikte kayar
  (SVG içinde); çok geniş bir seri yatay kaydırma ister; sayfa mobil
  viewport meta'sı taşımıyor (1.1.23'ten beri böyle, bu sürümde
  dokunulmadı).
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.48'e çekilmeli ve `updates/1.1.48/anime-tracker-1.1.48.zip` paketi
  yayımlanmalı.
