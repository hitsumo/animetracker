# Anime Tracker 1.1.50

**Yayın tarihi:** 2026-09-24

Tek iş: **yeni ilişki türü "Ek İçerik"** ve ana kaydın detay sayfasında
kendi **"Ek İçerikler"** bölümü. Bir filmin yanında verilen kısa bölüm,
Blu-ray bonusu, "manner movie" gibi küçük ekler artık bağlı oldukları
yapımın eki olarak kaydedilir ve orada S1, S2… diye numaralanmış
görünür. Şema değişikliği: `anime_relations.relation_type` listesine bir
değer eklendi. Merkez katalogda yapılacak bir şey yok.

## 1. Neden

MAL ve AniList bu tür küçük ekleri ayrı kayıt olarak tutar; katalog da
onlardan beslendiği için öyle tutuyor. Ama ekin hangi yapıma ait
olduğunu söyleyecek doğru bir ilişki türü yoktu: seçenekler "Yan Hikâye"
ya da "Diğer"di. İkisi de yanlış cümle — yan hikâye kendi başına bir
iştir, ek içerik ise bir işin ekidir. Detay sayfasında da bu küçük
kayıtlar devam sezonlarıyla aynı listede, aynı ağırlıkta görünüyordu.

Örnek: *Boku no Hero Academia the Movie: You're Next* filmi ve ona bağlı
"A Piece of Cake" gibi special'lar — her biri ayrı kayıt.

## 2. Yeni tür: Ek İçerik / Ana Kayıt

Düzenleme formunun **Seri ve İlişkiler** sekmesindeki İlişkiler
panelinde iki yeni seçenek:

- **Ek içeriği (special, bonus bölüm, kısa ek)** — seçtiğiniz anime,
  düzenlediğiniz animenin ekidir.
- **Ana kaydı (bu anime onun ek içeriği)** — aynı bağ, ekin
  sayfasından kurulur.

Tür **yönlüdür** (Yan Hikâye / Ana Hikâye gibi): bağ tek kayıttır ve
iki sayfada iki etiketle görünür. Hangi uçtan kurarsanız kurun satır
doğru yönde yazılır.

**Sıra belirtmez.** Seri kronolojisinin zincir sekmesi, "Sıradaki"
kutusu ve spoiler koruması yalnızca Devamı / Öncesi bağını izler; ek
içerik izleme sırasına girmez.

## 3. Detay sayfasında "Ek İçerikler" bölümü

Ana kaydın detay sayfasında ekler "İlişkili Animeler" listesinde
**değil**, kendi bölümünde görünür:

- **yayın tarihine göre** S1, S2, S3… diye numaralanır (tarihi
  bilinmeyenler sona düşer, eşitlikte ada göre),
- her satırda ad, medya türü, tarih (yalnız yıl ya da ay biliniyorsa
  o kadarı), bölüm sayısı ve sizin izleme durumunuz vardır.

Numara saklanmaz, her açılışta sırasından hesaplanır: daha eski tarihli
bir ek eklenirse ötekiler kayar. AniDB'nin special bölüm listesi de
böyle okunur.

Ekin kendi sayfasında ise "İlişkili Animeler" altında **Ana Kayıt**
başlığıyla geri bağlantı kalır.

## 4. Seri şeması

Seri Kronolojisi'nin **Şema** sekmesinde ek içerik bağları altın
rengi, kesikli çizgiyle çizilir; lejantta "Ek İçerik / Ana Kayıt"
satırı çıkar. Ok, öteki yönlü türlerde olduğu gibi türetilmiş işe
bakar: ana kayıt → ek içerik (lejant notu ve Şema yardımı buna göre
güncellendi).

## 5. Yardım

"İlişki Türleri" listesine Ek İçerik / Ana Kayıt maddesi eklendi (yan
hikâyeden farkı ve Ek İçerikler bölümü). Yön anlatımında "yönü olan üç
tür" dörde çıktı; "Zincir Nasıl Kurulur" bölümünde zincire girmeyen
kayıtlar için hangi türün seçileceği yeniden yazıldı (bonus bölüm →
Ek İçerik, kendi başına duran yan OVA → Yan Hikâye).

## 6. Var olan veri

Bu sürüm hiçbir ilişkiyi kendiliğinden **çevirmez**. Hangi "Yan Hikâye"
ya da "Diğer" bağının aslında ek içerik olduğunu program bilemez;
panelden eski bağı × ile silip "Ek içeriği" olarak yeniden
kurabilirsiniz.

## Dosyalar

**Yeni:**

```
files/migration/1.1.50/upgrade.sql        relation_type enum'una 'special'
```

**Değişen:**

```
files/functions/relation_helpers.php      tür sözlüğü, ters etiket, form seçenekleri, anime_relations_split_specials()
files/anime_details.php                   "Ek İçerikler" bölümü
files/css/series.css                      .specials-section, .special-number, .special-meta
files/functions/series_graph_helpers.php  şema oku rengi
files/series_timeline.php                 şema çizgi deseni
files/schema.sql                          enum + açıklama (taze kurulum)
files/lang/tr.php                         +8 anahtar; ilişki ve şema yardımı, şema lejant notu
files/lang/en.php                         aynı
files/version.txt
```

Dil dosyası eşitliği: 1157 = 1157.

## Dağıtım notu

- **Merkez katalogda iş yok.** İlişkiler kuruluma özeldir, merkeze
  gitmez; kolon eklenmedi.
- Migration tek `MODIFY` ifadesidir, yeniden çalıştırılabilir.
- JSON yedeği türü adıyla taşır. 1.1.50 yedeği daha eski bir kuruluma
  yüklenirse ek içerik bağları "atlandı" sayılır; hata vermez.
- Dosyalar birlikte gitmeli: eski `relation_helpers.php` sunucuda
  kalırsa ek içerik bağları "Diğer İlişki" diye görünür (çökmez).
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.50'ye çekilir ve `updates/1.1.50/anime-tracker-1.1.50.zip` paketi
  yayımlanır.
