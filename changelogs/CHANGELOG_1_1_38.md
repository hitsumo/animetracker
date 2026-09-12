# Anime Tracker 1.1.38

**Yayın tarihi:** 2026-09-04

1.1.36 zincire bir **ad** verdi. Bu sürüm bağın **türünü** veriyor: artık "bu
iki kayıt birbirine ait ama biri diğerinin devamı değil" cümlesi yazılabilir —
ve uygulama onu anlar.

## Sorun

1.1.36'nın teşhisi, çözdüğünden fazlasını söylüyordu. Ortadaki eksik yalnızca
"hangi hat" bilgisi değildi; **"ne tür bağ"** bilgisiydi.

- *Space Adventure Cobra (1982)* filmi, AniDB'ye göre TV dizisinin
  **alternatif versiyonu**. Katalog yalnızca "bağlı" ya da "bağsız"
  diyebiliyordu, yani doğru cevap **söylenemiyordu** — ve söylenemeyen bu cümle
  ekranda *eksik veri* gibi görünüyordu.
- *Sailor Moon Crystal* (yine alternatif versiyon) tam tersini yapmıştı: 90'lar
  zincirinin **içine** bağlanmıştı, yani zaman çizelgesi var olmayan bir izleme
  sırası iddia ediyordu.

1.1.36 ikisini de görünür kıldı (ad vererek), ama **neden** ayrı durduklarını
kayda geçirmedi. Bu sürüm onu yapıyor.

## Çözüm: tipli ve sırasız ilişkiler

Düzenleme sayfasının altına yeni bir bölüm geldi: **İlişkiler**. Bir anime
seçip türü işaretliyorsunuz; form tek bir soru soruyor:

> Seçtiğiniz anime, bu animenin ___'idir.

| Seçenek | Anlamı |
|---|---|
| Alternatif versiyonu | Aynı hikâyenin başka bir anlatımı |
| Alternatif kurgusu | Aynı karakterler, başka bir dünya/kurgu |
| Yan hikâyesi / Ana hikâyesi | Yan anlatı ile ana anlatı (iki yön) |
| Özeti / Tam hikâyesi | Kısaltılmış anlatım ile tamamı (iki yön) |
| İlişkilisi (diğer) | Yukarıdakilerin hiçbiri |

İlişkiler detay sayfasında **İlişkili Animeler** başlığı altında, türe göre
gruplanmış olarak görünür.

## İki uç, iki farklı cümle

Yan hikâye ve özet **çift yönlüdür**: A, B'nin yan hikâyesiyse B, A'nın yan
hikâyesi *değildir* — **ana** hikâyesidir. Bu yüzden aynı kayıt iki animenin
sayfasında iki farklı etiketle görünür:

```
ZZ Filmi sayfasında:   Yan Hikâye   → ZZ TV Dizisi   (yanlış olurdu)
ZZ TV Dizisi sayfasında: Ana Hikâye  → ...
```

Doğrusu: filmi "yan hikâye" diye eklediğinizde dizinin sayfasında film "Yan
Hikâye", filmin sayfasında dizi "Ana Hikâye" olarak görünür. Alternatif
versiyon, alternatif kurgu ve "diğer" iki yönden de aynı okunur, onlarda tek
etiket vardır.

## `sequel` bilerek yok

Listede **devam/öncesi** diye bir tür göremezsiniz. İzleme sırası hâlâ tek bir
yerde duruyor: **Sıradaki Anime** alanı. Sequel burada da saklanabilseydi aynı
çift hakkında iki kaynak konuşabilir, çeliştiklerinde de birinin kazanması
gerekirdi. Değeri hiç eklememek, çelişkiyi "önerilmez" değil **girilemez**
yapıyor.

Buradaki hiçbir tür sıra belirtmez. Seri kronolojisi ve konu spoiler kapısı bu
sürümde **hiç değişmedi** — ikisi de eskisi gibi yalnızca "Sıradaki Anime"
bağlarını izliyor.

## Kabul edilmeyen iki durum

**Aynı çifte ikinci ilişki eklenemez.** İki satır ya tekrardır ya çelişkidir;
tek ve daha iyi bir tür seçerek anlatılamayacak üçüncü bir durum yok. Yeni bir
tür girmek isterseniz önce mevcut olanı silin.

**"Sıradaki Anime" ile bağlı iki kayıt arasına ilişki kurulamaz.** O çift aynı
anda hem sıralı hem sırasız olduğunu iddia ederdi — yani tam olarak Sailor Moon
Crystal hatası. Uygulama bunu reddeder ve çözümü söyler: önce bağı kaldırın.

Bu kontrol 1.1.36'nın kuralını tekrar etmez, **çağırır**: bağ yalnızca iki ucun
**zincir adı aynıysa** izlenir. Adlar farklı olduğu için zaten izlenmeyen
"uykudaki" bir bağ hiçbir şeyi engellemez.

## Yedeğinize girer

JSON yedeği ilişkileri de taşır. Karşı uç, yerel kayıt numarasıyla değil
**kimlikle** (MAL / AniDB / katalog kimliği / başlık) yazılır — tıpkı kronoloji
notlarında olduğu gibi — ve geri yüklemede yeniden bulunur. Yani yedek al /
geri yükle turunda ilişkiler kaybolmaz.

Geri yükleme iki durumda bir ilişkiyi atlar ve bunu size sayı olarak bildirir:
karşı uç bu kurulumda yoksa, ya da tür tanınmıyorsa. Tanınmayan bir tür
"diğer"e **düşürülmez** — anlamadığı bir bağı kaydetmek, sonradan doğrusunu
girmenizi de engellerdi.

## Var olan verinize etkisi yok

Migration yalnızca boş bir tablo oluşturur; hiçbir bağı dönüştürmez, hiçbir
kaydı değiştirmez. Yükledikten sonra listeniz, zincirleriniz ve spoiler kapınız
bire bir aynıdır — ilişkileri siz elle girersiniz.

## Ekleme ve düzenleme formu artık sekmeli

Aynı sürümde ikinci, ayrı bir iş. Anime ekleme ve düzenleme formu tek uzun
sayfaydı ve yeni İlişkiler paneli onu daha da uzatıyordu — üstelik kaydet
tuşlarının **altında** kalıyordu. Alanlar beş sekmeye bölündü:

| Sekme | İçindekiler |
|---|---|
| Künye | Ad, alternatif isimler, ortam türü, ülke, durum, bölüm sayıları, tarihler, +18, filler takibi, resim |
| Konu ve Türler | Konu metinleri (TR/EN, katalog + kişisel), türler, cümleler |
| Seri ve İlişkiler | Seri adı, zincir adı, sıradaki anime **ve İlişkiler paneli** |
| Yayın ve Kaynaklar | Bölüm aralığı, yayın günü/saati/saat dilimi, AniDB/MAL/AnimeSchedule bağlantıları |
| Kişisel | İzleme durumu, izlenen bölüm, izleme tarihleri, kişisel notlar |

Ekleme sayfasında da aynı sekmeler var; tek fark, orada "Seri" sekmesinin
İlişkiler panelini taşımaması — kayıt henüz oluşmadığı için ilişkinin bir ucu
eksik olurdu.

**Form hâlâ tek parça kaydedilir.** Sekme değiştirmek hiçbir şeyi göndermez ya
da kaydetmez; **Güncelle** her sekmede sayfanın en altında durur ve *bütün*
alanları birlikte kaydeder — hangi sekmenin açık olduğu fark etmez.

Üç incelik:

- **Zorunlu bir alan başka sekmede boş kaldıysa** tarayıcı formu sessizce
  göndermez ("odaklanılamayan alan"). Artık o alanın sekmesi kendiliğinden
  açılır, uyarı balonu görünür hâle gelir.
- **JavaScript kapalıysa** sekme çubuğu hiç görünmez ve bütün alanlar eskisi
  gibi alt alta listelenir. Sayfa 1.1.37'deki gibi çalışır.
- **"Otomatik Doldur" raporu** artık başka sekmedeki bir alana "(gizli
  bölümde)" demiyor — alan oradadır, sekmeye basınca görünür. Not yalnızca
  gerçekten gizli alanlar için çıkar (örneğin duruma bağlı yayın bölümü).

Ayrıca düzenleme sayfası **yarı yarıya küçüldü**: İlişkiler kutusunun anime
listesi sunucuda ikinci kez basılmıyor, tarayıcıda "Sıradaki Anime"
kutusundan kopyalanıyor. 8.000 kayıtlık bir katalogda ölçüldü: 3,9 MB → 1,9 MB.

## Değişen dosyalar

**Yeni:**

```
files/functions/relation_helpers.php   türler, yön/ters etiket ve kurallar
files/add_anime_relation.php           ilişki ekleme ucu
files/delete_anime_relation.php        ilişki silme ucu
files/migration/1.1.38/upgrade.sql
```

**Değişen:**

```
files/functions.php                  yeni yardımcı dosyanın yüklenmesi
files/edit_anime.php                 "İlişkiler" paneli + sekmeler
files/add_anime.php                  sekmeler
files/anime_details.php              "İlişkili Animeler" bölümü
files/list_settings.php              yedekte dışa aktarım + geri yükleme
files/js/anime_form.js               sekme geçişi, doğrulama, seçenek kopyalama
files/css/series.css                 panel ve bölüm stilleri
files/css/components.css             sekme çubuğu stilleri
files/robots.php                     iki yeni uç arama motorlarına kapalı
files/lang/tr.php, files/lang/en.php 37 yeni metin
files/schema.sql
files/version.txt
```

## Dağıtım notu

- `files/functions.php` ile `files/functions/relation_helpers.php` **birlikte**
  yüklenmelidir. Yükleyici dosyayı çağırıp bulamazsa **her sayfa** çöker.
- `files/edit_anime.php` ve `files/anime_details.php`, `relation_helpers.php`
  olmadan yüklenirse o iki sayfa "undefined function" verir; aynı pakette
  gitmeliler.
- `files/lang/*.php` de aynı pakette gitmelidir, yoksa metin yerine anahtar adı
  görünür.
- `files/css/series.css`, `files/css/components.css` ve `files/js/anime_form.js`
  `files/version.txt` ile birlikte gitmelidir: bağlantılardaki sürüm damgası
  `version.txt`'ten üretilir, eskide kalırsa tarayıcı yeni sayfayı eski stil ve
  eski betikle çizer (sekmeler açılmaz).
- `files/add_anime.php` ve `files/edit_anime.php`, `files/js/anime_form.js`
  olmadan yüklenirse sekme çubuğu hiç görünmez ve form eski hâlindeki gibi tek
  uzun liste olur — bozulmaz ama sürümün yarısı görünmez.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** İlişkiler, tıpkı
  "Sıradaki Anime" ve "Zincir Adı" gibi uygulamaya özeldir: katalog telinde
  yeni alan yok, elle `ALTER` gerekmiyor, `catalog_server/` altında değişen
  dosya yok.
- Migration tek bir tablo ekler ve kendiliğinden koşar.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.38'e çekilmeli ve `updates/1.1.38/anime-tracker-1.1.38.zip` paketi
  yayımlanmalı.

## Sırada ne var

Yol haritasının üçüncü ve son adımı: devam/öncesi ilişkisinin de tabloya
taşınması ve "Sıradaki Anime" alanının emekliye ayrılması. O adım şimdilik
bekliyor, çünkü bugünkü veride **dallanma yok** — bir kaydın iki ayrı devamının
olduğu bir durum henüz ortaya çıkmadı.
