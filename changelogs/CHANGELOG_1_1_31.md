# Anime Tracker 1.1.31

**Yayın tarihi:** 2026-08-24

Bu sürüm tek bir soruyu çözer: **tarihin tamamını bilmiyorsan ne yazacaksın?**

Eski yapımların çoğunda kaynaklar günü vermez, bazen ayı da vermez — elde
yalnızca yıl kalır. Şimdiye kadar iki seçenek vardı ve ikisi de kötüydü:

- **Alanı boş bırakmak.** Bilinen yıl da kayboluyordu.
- **Günü uydurmak.** Ekranda `01.01.1979` görünüyordu; okuyan kişi bunu
  "3 Kasım değil, 1 Ocak" diye anlıyordu. Yani yanlış bir kesinlik.

Artık tarihin **bilinen kadarı** girilebiliyor, bilinmeyen kısım `??` olarak
görünüyor.

## Nasıl görünüyor

| Bilinen | Ekranda |
|---|---|
| Gün, ay, yıl | `08.04.2005` |
| Ay ve yıl | `??.04.2005` |
| Yalnız yıl | `??.??.2005` |
| Hiçbiri | `??.??.????` |

Kural hem **Yayın Tarihi** hem **Yayın Bitiş Tarihi** için geçerli, ikisi
birbirinden bağımsız: başlangıcı gün gün bilip bitişin yalnızca yılını
bilmek serbest.

## Nasıl giriliyor

Anime ekleme ve düzenleme ekranında her tarih alanının başında bir açılır
kutu var:

- **Tam tarih** — bugünkü takvim seçici, hiçbir şey değişmedi (varsayılan).
- **Ay ve yıl** — bir ay kutusu ve bir yıl kutusu çıkar.
- **Yalnız yıl** — yalnız yıl kutusu çıkar.
- **Bilinmiyor** — hiçbir kutu çıkmaz.

Kimse elle `??` yazmaz; seçim neyse ekranda o görünür. Tam tarihten "yalnız
yıl"a geçtiğinde yazdığın yıl kendiliğinden yıl kutusuna taşınır, yeniden
yazman gerekmez.

## "Bilinmiyor" ile "boş" aynı şey değil

Bu ayrım bilerek korundu:

- **Alanı boş bırakırsan** anime detayında eskisi gibi **"Belirtilmemiş"**
  yazar — "henüz doldurmadım" demektir.
- **"Bilinmiyor" seçersen** `??.??.????` yazar — "baktım, kaynak yok"
  demektir.

Böylece bir listede hangi kaydın doldurulmayı beklediği, hangisinin
gerçekten kaynaksız olduğu ayırt edilebilir.

## Kısmi tarihe geri sayım yapılmaz

Yayını başlamamış animelerde detay sayfası "ilk bölüme kalan süre"yi
sayıyor. Tarihin günü bilinmiyorsa bu satır **hiç basılmaz**. Ekranda
`??.??.2027` yazarken "42 gün kaldı" demek, bilinmeyen bir güne kesin bir
sayı uydurmak olurdu.

## Yıl filtresi ve sıralama bozulmadı

Ana listedeki **yıla göre filtre** ve seri kronolojisindeki tarih sıralaması
aynen çalışıyor. Yalnızca yılı bilinen bir anime kendi yılının filtresinde
görünür ve o yılın başında sıralanır.

## "Otomatik Doldur" artık kısmi tarihi de getiriyor

AnimeSchedule bağlantısıyla çalışan **Otomatik Doldur** düğmesi, tarihleri
AniList'ten alıyor. AniList eski yapımların çoğunda yalnızca yılı tutar; bu
kayıtlar şimdiye kadar **tamamen düşüyordu** ve alan boş kalıyordu. Artık
bilinen parça forma giriyor ve hassasiyet kutusu kendiliğinden doğru seçeneğe
geçiyor.

Bir nokta korundu: elle girdiğin bir tarihin üzerine hiçbir zaman
yazılmıyor. Tam tarih girmişsen AniList "yalnız yıl" dese bile senin girdiğin
gün olduğu gibi kalıyor.

## Seri kronolojisinde

"Yayın Tarihi" sekmesindeki tarihler de aynı biçimi kullanıyor
(`??.04.2005 – ??.06.2005` gibi). Başlangıç ve bitiş aynı değere işaret
ediyorsa yalnızca biri yazılır; farklıysa ikisi de.

## Katalogla ilgili iki not

**Hassasiyet katalogla birlikte taşınıyor.** Küratörün girdiği "yalnızca yıl
biliniyor" bilgisi merkez katalog üzerinden bütün kurulumlara gidiyor;
1.1.31'den eski bir katalog dosyası ise "tam tarih" olarak okunuyor, yani
eski davranış aynen korunuyor.

**Bir eksik kapandı: bitiş tarihi artık gerçekten senkronlanıyor.** Merkez
katalog bitiş tarihini 1.1.14'ten beri *gönderiyordu* ama istemci tarafı onu
hiç kaydetmiyordu — alan sessizce düşüyordu. Bu boşluk kapandı.

> **Davranış değişikliği:** katalogdan gelen bir kayıtta elle girdiğin bitiş
> tarihi, bundan sonraki senkronda katalogun değeriyle değişebilir. Aynı
> kaydın başlığı, durumu, konusu ve yayın tarihi için bu kural zaten
> geçerliydi; bitiş tarihinin korunması bir kural değil, gözden kaçmaydı.

## Ay kutusu ve özel açılır liste

Uzun açılır listeler (8'den fazla seçeneği olanlar) masaüstünde özel bir
widget'a dönüştürülüyor — bu 1.1.11'den beri böyle ve amacı listenin ekranı
kaplamasını önlemek. Kısmi tarihin ay kutusu 13 seçenekli olduğu için o da
dönüştürülüyor.

Bu iki özellik ilk denemede çakıştı: kutuyu gizleme kuralı ekranda görünen
widget'a değil, arkadaki asıl listeye uygulanıyordu. Sonuç, hassasiyet ne
seçilirse seçilsin ay kutusunun ekranda kalmasıydı — "Tam tarih" ve "Yalnız
yıl" seçiliyken bile. Tarih ve yıl kutuları birer metin kutusu olduğu, yani
dönüştürülmediği için doğru çalışıyordu; hata yalnız ay kutusunda görülüyordu.

Artık gizleme widget'ın kendisine uygulanıyor. Düzeltme geneldir: sayfanın
gizlediği her uzun açılır liste, dönüştürüldükten sonra da gizli kalır.

## Değişen dosyalar

**Yeni:**

```
files/functions/date_precision_helpers.php
files/migration/1.1.31/upgrade.sql
```

**Değişen:**

```
files/functions.php                     (yeni yardımcı dosyanın yüklenmesi)
files/functions/anime_helpers.php       (kısmi tarihte geri sayım yok)
files/functions/series_helpers.php      (yeni sütunlar sorgulara eklendi)
files/functions/anilist_import_helpers.php  (AniList kısmi tarihi korunuyor)
files/fetch_animeschedule.php           (Otomatik Doldur kısmi tarih taşır)
files/add_anime.php                     (form + kayıt)
files/edit_anime.php                    (form + kayıt)
files/js/anime_form.js                  (hassasiyet kutusu, alan değiştirme)
files/js/select_enhance.js              (gizlenen uzun listeler gizli kalır)
files/css/components.css                (tarih alanının yerleşimi)
files/anime_details.php                 (gösterim)
files/series_timeline.php               (Yayın Tarihi sekmesi)
files/catalog_import.php                (hassasiyet + bitiş tarihi senkronu)
files/admin/catalog_push.php            (yeni sütunlar gönderiliyor)
files/admin/admin_catalog_requests.php  (öneri onayında hassasiyet taşınır)
files/list_settings.php                 (yedek geri yükleme + öneri akışı)
files/schema.sql
files/lang/tr.php, files/lang/en.php    (9'ar yeni metin + yardım sayfası)
files/version.txt
catalog_server/catalog.php              (yeni sütunlar yayımlanıyor)
catalog_server/admin_push.php           (yeni sütunlar kaydediliyor)
```

## Dağıtım notu

- `files/functions.php` ile `files/functions/date_precision_helpers.php`
  **birlikte** yüklenmelidir. Yükleyici satırı olmadan dosya hiç okunmaz ve
  tarih basan her sayfa açılmaz.
- İki form (`add_anime.php`, `edit_anime.php`) ile `files/js/anime_form.js`
  de birlikte gitmelidir: eski betik yeni formdaki hassasiyet kutusunu
  tanımaz ve alan değiştirme çalışmaz. Sunucu tarafı yine doğru kaydeder,
  yalnızca üç kutu aynı anda görünür.
- `files/js/select_enhance.js`, `files/js/anime_form.js` ve
  `files/css/components.css` üçü birlikte gitmelidir; yalnız biri eskide
  kalırsa ay kutusu hassasiyet ne seçilirse seçilsin ekranda kalır.
- Dil dosyaları eski kalırsa seçenek etiketleri yerine anahtar adı görünür —
  zararsız ama çirkin.
- **Merkez katalog sunucusunda elle iki `ALTER` gerekir.** Migration orada
  çalışmaz; sütunlar eklenmeden yapılan gönderim hata verir. Sıra: önce
  merkez sunucuda `ALTER`, sonra uygulama dağıtımı, sonra gönderim. Komutlar
  `files/migration/1.1.31/upgrade.sql` dosyasının başında yazılı.
- **Dağıtım sunucusunda** iki işlevsel adım her zamanki gibi: yayımlanan
  `version.txt` 1.1.31'e çekilmeli — yoksa "Güncelleme Denetle" hâlâ 1.1.30'u
  son sürüm sanar — ve `updates/1.1.31/anime-tracker-1.1.31.zip` paketi
  yayımlanmalı, yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
