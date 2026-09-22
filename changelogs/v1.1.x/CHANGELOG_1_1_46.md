# Anime Tracker 1.1.46

**Yayın tarihi:** 2026-09-21

Tek iş: **izleme günlüğü.** Uygulama artık izlenen bölüm sayınızın her
değişimini tarihiyle kaydediyor ve Son Güncellenenler sayfasındaki
**Son İzlenenler** sekmesi buna dayanan dört görünüm kazandı: son 1 hafta,
son 1 ay, hepsi, tarih aralığı. Şema değişikliği var (yeni tablo,
kurulum-yerel); merkez katalogda yapılacak bir şey yok.

## 1. Neden

"Geçen hafta ne izledim?" sorusunun dürüst bir cevabı yoktu. Kişisel
kaydın tek zaman damgası vardı — "satıra son dokunuş". Bir not
düzenlemesi, bir durum değişimi, bir bölüm artışı hepsi aynı damgaya
yazıyordu; MyAnimeList ya da AniList'ten liste aktarınca bütün kayıtlar
aktarım gününü alıyordu. Kayıt yalnız "şimdi neredeyim"i biliyor, "oraya
nasıl geldim"i bilmiyordu. 1.1.45'in Son İzlenenler sekmesi bu damgaya
göre sıralıyordu ve bir haftalık / aylık görünüm sunamıyordu.

## 2. İzleme günlüğü

Yeni bir tablo (`user_watch_log`) izlenen bölüm sayısının **her
değişimine** bir satır tutar: kaçtan kaça, ne zaman.

- **Tek yazan yer.** Bölüm sayısını yazan her yol zaten kişisel kaydı
  yazan tek yardımcıdan geçer; günlük o yardımcıya eklendi. Listedeki ve
  detay sayfasındaki +/− düğmeleri, düzenleme formu, anime ekleme,
  MAL / AniList / JSON içe aktarımı — hepsi kendiliğinden günlüğe düşer,
  hiçbiri ayrıca kod istemedi.
- **İçe aktarım tek satır.** Bir aktarım animeye "0 → 24, aktarım anı"
  diye bir satır bırakır. Olan da budur: o gün 24 bölümün sayıya
  girdiği bilinir, hangi gün izlendiği bilinmez; uydurulmaz.
- **"−" de yazılır.** Bir bölümü geri alırsanız satır "6 → 5" olur.
  Dönem görünümleri anime başına toplar: +1 basıp geri aldığınız bölüm
  sıfıra iner, sayılmaz.
- **Geriye dönük doldurma yok.** Yükseltmede var olan kayıtlara sahte
  tarih yazılmaz. 1.1.44'teki kural aynen: alınmamış bir damga icat
  edilmez. Eski izlemeleriniz aşağıdaki "Hepsi" görünümünde, etiketli,
  durmaya devam eder.
- **Kurulum-yerel.** Günlük merkez kataloğa gitmez. Yedeğinize girer
  (bkz. §4).

## 3. Son İzlenenler: dönem şeridi

Sekmenin altına bir şerit geldi: **Son 1 hafta · Son 1 ay · Hepsi** ve
iki tarihli bir **aralık** formu.

| Görünüm | Kaynak | Ne gösterir |
|---|---|---|
| Son 1 hafta | günlük | son 7 günde izlenen animeler |
| Son 1 ay | günlük | son 30 günde izlenen animeler |
| Aralık | günlük | iki tarih arasında (iki uç dahil) izlenen animeler |
| Hepsi | günlük + eski kayıt | ilerlemesi olan her anime, en yeni hareket üstte, 10 satır |

- Hafta, ay ve aralık görünümlerinde her kartta o dönemde izlenen **net
  bölüm** rozeti (+3 bölüm) ve listenin üstünde **"N animede M bölüm
  izlendi"** özeti vardır. Yalnız net artışı olan animeler listelenir.
- Hafta ve ay **kayan** pencerelerdir (şu andan geriye 7 / 30 gün) —
  konuşmada "son bir hafta" bu demektir; takvim haftası pazartesi sabahı
  tek anime gösterirdi.
- **Hepsi**, 1.1.45'teki listenin devamıdır: günlüğü olan anime son
  günlük satırıyla, günlüğü olmayan eski anime eskisi gibi son dokunuş
  zamanıyla yer alır ve **"günlük öncesi"** etiketi taşır; iki tür
  birbirine karışmaz.
- Boş dönem kendi cümlesini söyler ("bu dönemde günlüğe giren izleme
  yok"), sekmenin "henüz izleme aktiviteniz yok" cümlesini değil.
- Aralık formunda iki tarih de gerekir; ters girilirse yer değiştirir,
  bozuk ya da eksik tarih "Hepsi"ye düşer — hata sayfası yok.
- Seçilen dönem **adreste** yaşar, kaydedilmez. Liste Ayarları'ndaki
  varsayılan sekme tercihi olduğu gibi durur.
- Çevrimiçi kurulumda giriş yapmamış ziyaretçi dört görünümde de "bu
  sekme kişiseldir, giriş yapın" notunu görür.

## 4. Yedek

Liste dışa aktarımı her animenin altına `watch_log` dizisini koyar
(kaçtan kaça, ne zaman). Geri yükleme:

- Dosyada dizi varsa (1.1.46 ve sonrası yedek) satırlar **olduğu gibi**
  geri gelir; aynı satır iki kez yazılmaz, o yüzden aynı dosyayı iki kez
  yüklemek ya da yarısı zaten duran bir geçmişin üstüne yüklemek
  çoğaltmaz. Geri yükleme sıçraması ayrıca günlüğe girmez.
- Dosyada dizi yoksa (eski yedek) geri yükleme sıçraması tek satır
  olarak günlüğe girer — MAL aktarımıyla aynı kural.

## 5. Yardım

- Liste yardımındaki "Son Güncellenenler" bölümüne "İzleme günlüğü ve
  dönem filtreleri" başlığı: günlük ne kaydeder, dört görünüm, net sayım,
  geriye dönük doldurma yok, günlük öncesi etiketi, yedek.
- İçe/dışa aktarma yardımındaki "yedekte ne var" listesine günlük
  satırı.

## 6. Yan düzeltme: varsayılan sekme seçiminde "Son İzlenenler"

Liste Ayarları'ndaki "Son Güncellenenler Sekmesi" seçeneğine 1.1.45'te
"Son İzlenenler" eklenmişti ama kaydeden uç yalnız ilk iki değeri
tanıyordu; üçüncüsü seçilince sessizce "Bölüm Güncellenenler" kaydediliyordu.
Uç artık sekme listesinin kendisinden doğrular; seçim kaydedilir.

## Dosyalar

**Yeni:**

```
files/functions/watch_log_helpers.php    günlük yazma / dışa-içe aktarma / dönem sınırları / özet
files/migration/1.1.46/upgrade.sql       CREATE TABLE user_watch_log
```

**Değişen:**

```
files/functions/user_anime_helpers.php   ua_set_state: bölüm değişince günlük satırı; $logWatch bayrağı
files/functions.php                      yeni yardımcı dosyası yüklenir
files/schema.sql                         user_watch_log tablosu (taze kurulum)
files/recent.php                         dönem şeridi, iki yeni sorgu, özet, iki rozet
files/list_settings.php                  dışa aktarımda watch_log; geri yüklemede geri yazma + otomatik satırı kapatma (iki dal)
files/set_recent_tab_pref.php            'watched' değeri kabul edilir (recent_tabs() beyaz listesi)
files/help/help_list.php                 günlük başlığı + paragrafı
files/lang/tr.php                        +16 anahtar (recent.period.*, help.list.recent.log.*); 1 değişen (sekme ipucu); yedek listesi maddesi
files/lang/en.php                        aynı
files/version.txt
```

Dil dosyası paritesi: 1134 = 1134.

## Dağıtım notu

- **Merkez katalogda iş yok.** Tablo kurulum-yereldir; merkez sunucu
  onu bilmez.
- Migration ilk sayfa açılışında tabloyu oluşturur (`CREATE TABLE IF
  NOT EXISTS`, yeniden çalıştırılabilir). Yükseltme sonrası günlük
  boştur; ilk bölüm işaretlemeyle dolmaya başlar. Hafta / ay
  görünümleri o ana kadar boş cümlesini gösterir — bilinçli.
- `functions/` klasörü **bütün** gitmeli: `functions.php` yeni
  `watch_log_helpers.php`'yi yükler, `user_anime_helpers.php` onun
  fonksiyonunu çağırır; biri eksikse her bölüm işaretlemesi ölümcül hata
  verir.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.46'ya çekilmeli ve `updates/1.1.46/anime-tracker-1.1.46.zip` paketi
  yayımlanmalı.
