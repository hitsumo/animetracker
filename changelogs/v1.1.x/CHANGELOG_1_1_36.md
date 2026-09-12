# Anime Tracker 1.1.36

**Yayın tarihi:** 2026-09-02

Bu sürüm zincire bir **ad** veriyor — ve bununla birlikte "bu iki kayıt aynı
hikâyenin iki ayrı anlatımı" diyebilmenizi sağlıyor.

## Sorun

1.1.25'ten beri bir seri adı altında birden çok zincir olabiliyordu, ama
zincirin kendisi bir şey değildi: yalnızca "Sıradaki Anime" bağlantılarını
yürüyünce ortaya çıkan geçici bir kümeydi. Bunun üç sonucu vardı.

**Zincir adlandırılamıyordu.** Sekmeler "Diğer Zincir 1..N" diyordu; hangisinin
ne olduğunu ancak içine bakınca anlıyordunuz.

**"Bunlar ayrı hatlar" denemiyordu.** Elinizde yalnızca "birbirine bağlı mı"
vardı. İki gerçek örnek:

- *Space Adventure Cobra (1982)* filmi, AniDB'ye göre *Space Cobra* TV
  dizisinin **alternatif versiyonu** — devamı değil. İkisi de bağlanmamıştı,
  dolayısıyla ikisi de "1 anime" diyen bir zincir gösteriyordu ve hiçbir
  sekme çıkmıyordu.
- *Sailor Moon Crystal* (yine alternatif versiyon) ise tam tersi: 90'lar
  zincirinin **içine**, Sailor Stars'ın arkasına bağlanmıştı. Zaman çizelgesi
  "Sailor Stars'tan sonra Crystal" diyordu ve spoiler kapısı, Crystal'ı açan
  kişiye 90'lar serisinin sekiz kaydını "izlenmemiş öncül" olarak sayıyordu.

**Tek kayıtlık bir hat ifade edilemiyordu.** Hiçbir yere bağlanmamış tek kayıt
zincir sayılmıyordu — bu kural doğruydu (yoksa seri adını paylaşan her bağımsız
film ayrı bir sekme üretirdi), ama Cobra'nın filmi gibi **bilerek** ayrı duran
bir kaydı da görünmez kılıyordu.

## Çözüm: üyelik addan, sıra bağdan

Düzenleme ve ekleme ekranlarına yeni bir alan geldi: **Zincir Adı
(opsiyonel)**. Mevcut adlar otomatik önerilir.

| Alan | Neyi söyler |
|---|---|
| Seri Adı | Hangi seri |
| **Zincir Adı** | **Serinin içinde hangi hat** |
| Sıradaki Anime | O hattaki sıra |

Aynı adı taşıyan kayıtlar tek zincir sayılır ve seri kronolojisinde **kendi
adıyla** bir sekme alır. "Diğer Zincir 1" yerine "Crystal" yazar.

Sailor Moon'da yapmanız gereken: Sailor Stars'ın "Sıradaki Anime" bağını
kaldırmak ve iki hatta ad vermek. Sonuç:

```
[ 90'lar Anime ] [ Crystal ] [ Diğer Zincir 1 ] [ Yayın Tarihi ]
```

Cobra'da ise tek bağ kurmadan, yalnızca ad vererek:

```
[ Alternatif Versiyon (Film) ] [ 1982 TV Serisi ] [ Yayın Tarihi ]
```

## Adı olan tek kayıt da zincirdir

Adsız tek bir kayıt hâlâ zincir sayılmaz — o yalnızca bir kayıttır. Ama **adı
olan** tek kayıt bilinçli bir beyandır: "bu kayıt kendi hattıdır." Cobra'nın
1982 filmi tam olarak budur.

## Spoiler kapısı da sınırda durur

Kapı artık yalnızca **aynı hattaki** önceki halkalara bakar. Crystal'ı açan
kişiye 90'lar serisi bir daha "önce izlemelisin" diye sunulmaz.

Bir ayrım bilinçli: adlı bir hattın bağlanmamış üyesi **listede görünür**
(yayın tarihine göre sona eklenir), ama spoiler kapısı onu **öncül saymaz**.
Listede bir kaydı göstermek zararsızdır; "şunu önce izlemelisin" demek ise bir
iddiadır ve yalnızca sizin elle kurduğunuz bağa dayanmalıdır. Girilmemiş bir
bağı tarihten uydurmak, uyarıyı tahmine çevirirdi.

## Sekme sırası değişti

Zincir sekmeleri artık bir arada duruyor, **"Yayın Tarihi" sona geçti**.
Adlar gelince araya giren bir sekme okumayı bölüyordu. Adresler ve davranış
aynı; değişen yalnızca sıra.

## Zincir adı vermezseniz hiçbir şey değişmez

1.1.36 öncesi her kaydın zincir adı boştur ve boş ile boş "aynı" sayılır — yani
hiçbir yürüyüş kısalmaz, zincir keşfi ve spoiler kapısı 1.1.35 ile **birebir
aynı** sonucu verir. Bu iddia değil ölçümdür: 1.1.35 algoritması testte satır
satır korundu ve iki sürümün çıktısı aynı veri üzerinde karşılaştırıldı.

## Değişen dosyalar

**Yeni:**

```
files/migration/1.1.36/upgrade.sql
```

**Değişen:**

```
files/functions/series_helpers.php   zincirin tek kuralı (chain_same) + üyelik
files/series_timeline.php            sekme etiketleri ve sırası
files/add_anime.php                  Zincir Adı alanı
files/edit_anime.php                 Zincir Adı alanı
files/list_settings.php              JSON geri yüklemede zincir adı taşınır
files/lang/tr.php, files/lang/en.php form metinleri + yardım sayfası
files/schema.sql
files/version.txt
```

## Dağıtım notu

- `files/functions/series_helpers.php` ile `files/series_timeline.php`
  **birlikte** yüklenmelidir: sayfa `chain_name_norm()` çağırıyor, eski
  yardımcı dosyada o fonksiyon yok.
- `files/lang/*.php` de aynı pakette gitmelidir, yoksa yeni alanın etiketi
  yerine anahtar adı görünür.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** Zincir adı, tıpkı
  "Sıradaki Anime" gibi uygulamaya özeldir: katalog telinde yeni alan yok,
  elle `ALTER` gerekmiyor, `catalog_server/` altında değişen dosya yok. Katalog
  senkronu zincir adını **ezmez**.
- Migration tek bir kolon ve bir index ekler, kendiliğinden koşar.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.36'ya çekilmeli ve `updates/1.1.36/anime-tracker-1.1.36.zip` paketi
  yayımlanmalı.

## Sırada ne var

Zincir adı, bir hattın **ne olduğunu** yazmanızı sağlıyor ama ilişkinin
**türünü** hâlâ bilmiyor: "Alternatif Versiyon" yazdığınızda insan anlar,
uygulama anlamaz. Bir sonraki sürüm bunu veri hâline getirecek (sequel,
alternative version, side story, summary…), AniDB'nin ayrımına uyumlu şekilde.
