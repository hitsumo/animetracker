# Anime Tracker 1.1.37

**Yayın tarihi:** 2026-09-02

Şema değişikliği yok. Birbirinden bağımsız dört küçük iş: biri arama
motorlarıyla, ikisi yeni dillerin önünü açmakla, biri de dışarıya kendini
tanıtmakla ilgili.

## 1. Boş kayıtlar artık arama motorlarına verilmiyor

Katalog üç yoldan büyüyor ve bunlardan ikisi — offline listenin online'a
aktarılması ve MAL/AniList içe aktarımı — kaydı **ince taslak** olarak açıyor:
başlık ve kimlik var, konu yok, görsel yok. Katalogun büyük çoğunluğu bugün bu
durumda.

Bugüne kadar site haritasının tek filtresi "yetişkin içeriği değil" idi, yani bu
taslakların **hepsi** site haritasına giriyordu; IndexNow eklendiğinden beri aynı
adresler arama motorlarına aktif olarak da bildiriliyordu. Site yakın zamanda
Google, Yandex ve Bing'e tanıtıldığı için bu artık teorik bir sorun değildi.

Bundan sonra bir kayıt, ziyaretçiye **başlık dışında verecek bir şeyi varsa**
duyuruluyor: konu (herhangi bir dilde), görsel ya da kronoloji notu. Bölüm sayısı
veya tarih tek başına yetmiyor — onlar okuyucunun bir arama sonucundan beklediği
içerik değil, künye alanı.

Boş bir kaydın detay sayfası artık `noindex, follow` taşıyor: sayfa indekslenmez
ama **bağlantıları izlenir**, yani aynı serideki dolu kayıtlar oradan
keşfedilmeye devam eder.

**Hiçbir kayıt silinmez ya da gizlenmez.** Site içinde her şey eskisi gibi
görünür ve gezilir; değişen tek şey arama motorlarına ne bildirildiği. Bir
taslağa konu ya da görsel eklediğiniz anda kayıt kendiliğinden yeniden
indekslenebilir hâle gelir.

Kural site haritası, IndexNow ve detay sayfası tarafından **tek bir yerden**
okunuyor; üçünün ayrışması mümkün değil.

Self-host kurulumlar etkilenmez — o modda zaten her sayfa indekslemeye kapalı.

## 2. Eksik çeviri artık İngilizceye düşüyor

Arayüz metinleri sırayla aranır: seçili dil, sonra yedek dil, sonra anahtarın
kendisi. Yedek şimdiye kadar Türkçeydi.

İki dil varken bunun bir önemi yoktu. Üçüncü bir dil eklenince oluyor:
çevrilmemiş bir metinde Endonezce konuşan bir ziyaretçiye Türkçe düşmek,
İngilizce düşmekten belirgin biçimde kötü.

Bu aynı zamanda **dil katkısını mümkün kılan şey**: bir çevirmenin sözlüğün
tamamını bitirmesi gerekmiyor. Eksik bırakılan her anahtar İngilizceye düşer,
ekranda bozuk bir şey görünmez, dosya parça parça tamamlanabilir.

Yönetici arayüzünde de aynı değişiklik yapıldı.

## 3. AniList istekleri kendini tanıtıyor

Giden isteklere `AnimeTracker/<sürüm> (+depo adresi)` kimliği eklendi.

Bu bir uyum değişikliği değil — içe aktarım zaten yalnızca **kullanıcının kendi
listesini** çekiyor, katalog taramıyor ve künye verisi toplamıyor. Sebep, fark
edildiğinde ne olacağı: kimliksiz bir istek karşı taraf için tanınmayan bir
bottur ve sessizce engellenir; kimlikli bir istek önce bir e-posta getirir.

AnimeSchedule'a eklenmedi, o istekler zaten anahtarla imzalı.

## 4. README'ye "yeni dil ekleme" bölümü

Üç adım: `lang/en.php`'yi kopyala, değerleri çevir (anahtarlara dokunma),
izinli diller listesine kodu ekle.

Bir uyarıyla birlikte: çoğu metinde ufak bir hata zararsızdır, ama **sonucu
olan** metinler bir insan gözünden geçmeli — 18+ uyarısı, silme onayları,
spoiler kapısı ve yedekleme/geri yükleme uyarıları. Yanlış çevrilirse sonuç
yalnızca "tuhaf" olmaz; kullanıcı veri kaybedebilir ya da spoiler görebilir.

## Değişen dosyalar

**Yeni:**

```
files/migration/1.1.37/upgrade.sql   (şemasız; sürüm damgası + gerekçe kaydı)
```

**Değişen:**

```
files/functions/seo_helpers.php          indekslenebilirlik kuralı (tek yer)
files/anime_details.php                  ince sayfaya noindex, follow
files/functions/i18n_helpers.php         yedek dil TR -> EN
files/functions/anilist_import_helpers.php   User-Agent
README.md                                yeni dil ekleme (TR + EN)
files/version.txt
```

## Dağıtım notu

- `files/anime_details.php` ile `files/functions/seo_helpers.php` **birlikte**
  yüklenmelidir: sayfa yeni bir yardımcı fonksiyon çağırıyor, eski yardımcı
  dosyada o fonksiyon yok. Diğer iki işin dosyaları birbirinden bağımsızdır.
- **Merkez katalog sunucusunda yapılacak bir şey yok.** Katalog telinde yeni
  alan yok, elle `ALTER` gerekmiyor, `catalog_server/` altındaki hiçbir dosya
  değişmedi.
- Migration şema değiştirmez, yalnızca sürüm damgasını taşır ve kendiliğinden
  koşar.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.37'ye çekilmeli ve `updates/1.1.37/anime-tracker-1.1.37.zip` paketi
  yayımlanmalı.
- Yükleme sonrası arama motorlarının site haritasını yeniden okuması gerekir;
  hâlihazırda indekslenmiş boş sayfaların düşmesi zaman alır.
