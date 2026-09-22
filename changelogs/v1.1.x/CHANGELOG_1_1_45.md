# Anime Tracker 1.1.45

**Yayın tarihi:** 2026-09-21

Ana iş: **detay sayfasında toplu duygu dağılımı.** Çevrimiçi (çok
kullanıcılı) kurulumda duygu düğmelerinin altında, o animeye tüm üyelerin
koyduğu işaretlerin toplamı görünür. İkinci iş: **"Son İzlenenler"
İstatistikler'den Son Güncellenenler sayfasına taşındı** — üçüncü sekme.
Yanında küçük bir sadeleştirme: yapım ülkesi listesinden ABD ve Fransa
çıkarıldı. Şema değişikliği yok; merkez katalogda yapılacak bir şey yok.

## 1. Toplu duygu dağılımı

### Neden

Duygu işaretleri 0.6.1'den beri kişisel: her üye bir animeye en çok üç
duygu koyar ve yalnız kendi işaretini görür. Çevrimiçi kurulumda aynı
animeye başka üyelerin ne koyduğu **hiçbir yerde** görünmüyordu. Oysa bu
sitenin puan vermeyi bilerek reddetmesinin karşılığı tam da buydu: "7.8"
yerine "kimi heyecanlandırdı, kimi sıktı".

### Ne yapar

Detay sayfasında duygu düğmelerinin hemen altına bir satır gelir:

> **3 kişi işaretledi:** Heyecanlandırdı **2** · Düşündürdü **2** · Sıktı **1**

- **Anonim.** Yalnız işaret başına toplam ve kaç farklı üyenin
  işaretlediği; kimin ne işaretlediği çıkmaz.
- **Herkese açık.** Konuklar da görür — puanın karşılığı olan bilgi,
  puan gibi açıktır.
- **Sıralama:** en çok işaretlenen önce; eşitlikte düğmelerin sırası.
  İki kişi aynı sayfayı aynı sırada görür.
- **Boşken satır yok.** Kimse işaretlememişse ne satır ne "henüz kimse
  işaretlemedi" metni çıkar; küçük bir kurulumda sayfaların çoğu boştur,
  her sayfada aynı cümle gürültü olurdu.
- **Anında tazelenir.** Bir düğmeye basınca sunucunun cevabı yeni
  dağılımı da taşır; satır sayfa yenilenmeden değişir. Çizim tek yerde
  (sunucuda) — tarayıcı yalnız hazır metni yerine koyar, kendi kopyasını
  çizmez; etiket çevirisi ve renkler bir yerden yönetilir.
- **Kendi bilgisayarınızdaki tek kullanıcılı kurulumda satır yoktur.**
  Dağılım kendi işaretleriniz olurdu; üstteki düğmeler zaten onu
  gösteriyor. Sorgu bile atılmaz.

Çipler 0.6.1'de "detay sayfası özeti" için ayrılan salt-okunur duygu
rozetleridir; ilk kullanımları bu.

### Maliyet

İki küçük sorgu (`GROUP BY emotion` ve `COUNT(DISTINCT user_id)`), ikisini
de tablonun mevcut `idx_anime` indeksi taşır. Yeni tablo, kolon, indeks
yok.

## 2. Yardım

Keşif yardımındaki "Duygular" bölümüne bir paragraf: satır ne gösterir,
anonimlik, konuklar, boş durum, tek kullanıcılı kurulumda neden yok.

## 3. "Son İzlenenler" artık Son Güncellenenler'in üçüncü sekmesi

İstatistikler sayfasındaki **Son İzlenenler** sekmesi (1.1.1'den beri
oradaydı) **Son Güncellenenler** sayfasına taşındı. Sayfanın artık üç
sekmesi var:

| Sekme | Ne listeler | Kimin |
|---|---|---|
| Bölüm Güncellenenler | Yayınlanan bölüm sayısı en son değişen 5 anime | katalog |
| İçerik Güncellenenler | En son eklenen / düzenlenen 5 anime | katalog |
| **Son İzlenenler** | İzleme ilerlemenizi en son değiştirdiğiniz 10 anime | **siz** |

"Son zamanlarda ne oldu" sorusunun üç cevabı tek sayfada; İstatistikler
ise adının söylediği şeye — sayılara — döndü (iki sekme: kişisel özet,
global dağılım).

- Aynı kart görünümü, aynı "az önce / 3 saat önce" zaman etiketi; liste
  eskisi gibi 10 kayıt, yalnız ilerlemesi olan animeler (0/12 "planlandı"
  satırı bir izleme hareketi değildir).
- Kişisel sekme, sayfada `user_anime`'a göre sıralanan **tek** sekmedir;
  diğer ikisinde bir bölüm işaretlemeniz hiçbir şeyi oynatmaz — o kural
  aynen duruyor.
- Çevrimiçi kurulumda giriş yapmamış ziyaretçi bu sekmede liste yerine
  "bu sekme kişiseldir, giriş yapın" notunu görür.
- Liste Ayarları'ndaki "Son Güncellenenler Sekmesi" tercihine üçüncü
  seçenek eklendi; ön tanımlı hâlâ bölüm.
- Yan düzeltme: sayfadaki kartlar artık **başlık dili tercihinize** uyar
  (İstatistikler'deki tablo 1.1.18'den beri uyuyordu, bu sayfa uymuyordu).

Yardım: Liste sayfası yardımındaki "Son Güncellenenler" bölümü üç
sekmeyi anlatır, "karıştırmayın" kutusu katalog sekmeleri / kişisel sekme
ayrımına göre yeniden yazıldı; İstatistikler yardımından taşınan sekmenin
paragrafı kaldırıldı.

## 4. Yapım ülkesi: ABD ve Fransa çıkarıldı

Ekleme / düzenleme formundaki "Yapım Ülkesi" listesi artık dört seçenek:
Çin, Güney Kore, Japonya, Tayvan. 7.800'ü aşkın kayıtlık katalogda ABD
ya da Fransa kodlu tek satır yoktu; iki seçenek listeyi uzatıyordu.

Var olan veriye dokunulmadı ve kaybolan bir şey yok: listede olmayan bir
kodla gelen kayıt (AniList'in menşe ülkesi, katalog içe aktarımı) kodu
sütununda tutar; sayfada ülke satırı boş görünür, ülke filtresi — veriden
kurulduğu için — o seçeneği sunmaz. Geri eklemek tek satır + iki dil
anahtarı.

## Dosyalar

**Yeni:**

```
files/migration/1.1.45/upgrade.sql   damga (şema değişikliği yok)
```

**Değişen:**

```
files/anime_details.php                  dağılım satırı + toggle betiğinde satırı değiştirme
files/update_emotion.php                 cevaba distribution_html; lang_init (cevap artık çevrilen metin taşıyor)
files/functions/emotion_helpers.php      emotion_distribution() / emotion_distribution_html()
files/css/emotion.css                    .emotion-dist (boşken gizli)
files/recent.php                         üçüncü sekme (watched), sekme başına metin haritası, display_title
files/statistics.php                     Son İzlenenler sekmesi / sorgusu / CSS'i kaldırıldı
files/list_settings.php                  varsayılan sekme seçimine üçüncü seçenek
files/functions/user_anime_helpers.php   recent_tabs() += 'watched'
files/help/help_discovery.php            duygu paragrafı eklendi; istatistik yardımından Son İzlenenler kaldırıldı
files/functions/country_helpers.php      US / FR çıkarıldı
files/lang/tr.php                        +7 anahtar, −7 (country.us/fr, statistics.tab.recent_watched, statistics.col.last_watched, statistics.recent_watched.empty, help.stats.recent.h3/.text); 4 değişen
files/lang/en.php                        aynı
files/version.txt
```

Dil dosyası paritesi: 1118 = 1118.

## Dağıtım notu

- **Merkez katalogda iş yok.** Duygu verisi kurulum-yereldir.
- Migration damga-only; ilk sayfa açılışında sürüm 1.1.45'e taşınır.
- `functions/emotion_helpers.php` ve `functions/user_anime_helpers.php`
  **birlikte** gitmeli (functions/ klasörünün tamamı kuralı):
  `anime_details.php` / `update_emotion.php` yeni yardımcıları çağırır,
  `recent.php` ise `recent_tabs()`'ın üç değerini bekler; eski yardımcıyla
  `?tab=watched` sessizce varsayılan sekmeye düşer.
- Var olan `recent_default_tab` tercihleri geçerli kalır (episodes /
  content); `watched` yalnız seçenlerde yazılır.
- CSS sürüm damgası `version.txt`'ten gelir; `emotion.css` değişikliği
  yeni adresle iner, önbellek sorunu olmaz.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.45'e çekilmeli ve `updates/1.1.45/anime-tracker-1.1.45.zip` paketi
  yayımlanmalı.
