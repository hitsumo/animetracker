# Anime Tracker 1.1.29

**Yayın tarihi:** 2026-08-23

İki iş: henüz başlamamış animelerde geri sayım, ve projenin GPL-2.0 lisans
bildirimlerinin eksiksiz hâle getirilmesi. İkincisi tamamen yorum
değişikliğidir — ekranda hiçbir etkisi yok.

## Yenilik: başlamamış animede "ilk bölüme kalan süre"

- **Sorun:** yayını devam eden bir animede detay sayfası "Sonraki Bölüm:
  3 gün 4 saat" diye geri sayıyordu. Henüz **başlamamış** bir animede ise
  yalnızca "Yayın Tarihi: 07.10.2026" yazıyordu — kalan günü takvimden elle
  saymanız gerekiyordu.
- **Oysa gereken her şey zaten kayıtlıydı:** yayın tarihi, yayın saati ve
  yayın saat dilimi. 1.1.28 bu üçünü başlamamış animede hem forma hem detay
  sayfasına getirmişti; eksik olan tek şey bunlardan bir geri sayım
  kurmaktı.
- **Artık kuruyor.** Detay sayfasında yeni bir **İlk Bölüm** satırı çıkıyor:
  *"1. bölüme kalan süre: 45 gün 7 saat"*. Biçim yayını devam eden animedeki
  ile birebir aynı — aynı hesap, aynı görünüm.
- **Liste sayfasında da var.** "Sonraki Bölüm" sütunu başlamamış animelerde
  tire (`-`) gösteriyordu; artık aynı geri sayımı yazıyor.
- **Etiket bilerek "Sonraki Bölüm" değil.** Henüz yayınlanmış bir bölüm
  yokken "sonraki" demek yanıltıcı olurdu.
- **Yayın saati girilmemişse** geri sayım o günün gece yarısına göre
  hesaplanır. Gün sayısı yine doğrudur, yalnızca gün içindeki saat tahminîdir.
- **Yayın tarihi geçmiş ama durum hâlâ "Yayın Başlamadı" ise** satır
  *"Yayın tarihi geçti"* der. Devam eden animedeki "Yeni bölüm yayınlandı"
  mesajı burada yanlış olurdu — henüz hiçbir şey yayınlanmadı; bu, kaydın
  durumunun güncellenmesi gerektiğini söyleyen bir uyarıdır.
- **Hiçbir veri yazılmaz.** Geri sayım her görüntülemede tarihten hesaplanır;
  veritabanına türetilmiş bir değer kaydedilmez.

## Lisans bildirimleri

Bu bölümdeki işin tamamı yorum satırlarıdır; çalışan hiçbir kod etkilenmez.

### Neden

Anime Tracker GPL-2.0 lisanslı ve lisans metninin tam hâli hem depoda
(`LICENSE.txt`) hem kurulan uygulamanın içinde (`files/license.txt`)
duruyordu — yani lisans bakımından eksik bir şey yoktu. Eksik olan, **dosya
başı bildirimlerdi:** 195 kaynak dosyanın 80'inde hiç bildirim yoktu ve
bildirimi olanlar üç ayrı biçimde yazılmıştı.

Bu bir ihlal değildi; GPL-2 dosya başı bildirimi zorunlu kılmaz. Ama aynı
klasörde bildirimli ve bildirimsiz dosyaların yan yana durması, kodu ilk kez
açan birine "acaba bu dosya lisans kapsamı dışında mı?" dedirtir. Açık kaynak
bir projede bu sorunun sorulmaması gerekir.

### Ne değişti

- **Bildirimi olmayan 80 dosyaya kısa bildirim eklendi.** Kullanılan biçim,
  kod tabanında zaten çoğunlukta olan biçim:

  ```
  Anime Tracker - <dosyanın başlığı>
  https://www.sicakcikolata.com
  Copyright (C) 2025-2026 Okan Sumer
  Licensed under GNU General Public License v2
  ```

  Her dosya kendi yorum sözdizimiyle yazıldı. Dağılım: 60 migration dosyası,
  beş uygulama sayfası (`recent`, `series_timeline`, `statistics`,
  `sync_aired`, `admin_catalog_requests`), `anime_form.js`, dört örnek/şablon
  dosyası, dört paketleme dosyası (Dockerfile, Docker Compose, Docker
  entrypoint, Windows kurulum betiği) ve üç tek kullanımlık CLI betiği.

- **21 dosyadaki tam GPL başlığı tek biçime getirildi.** Bu başlıklarda
  FSF şablonunun köşeli parantez yer tutucuları temizlenmemişti —
  `Copyright (C) 2025 [Okan Sumer]` diye duruyordu. Parantezler kalktı,
  girintiler hizalandı. Aynı 21 başlık altı ayrı boşluk varyantına dağılmıştı;
  hepsi tek metne indi. `list_settings.php`'de yorum bloğu yanlışlıkla iki kez
  açılıyordu (`/**` içinde bir `/**` daha), o da düzeldi.

- **`db.php`'nin yarım kalan bildirimi tamamlandı.** Garanti reddine kadar
  yazılmıştı ama son paragraf ("lisansın bir kopyasını almış olmalısınız…")
  eksikti. Kod tabanındaki tek yarım bildirim buydu.

- **Telif yılı `2025` yerine `2025-2026`.** Proje 2025'te yayımlandı, 2026'da
  geliştirilmeye devam ediyor. README'nin hem Türkçe hem İngilizce lisans
  bölümüne de bir telif satırı eklendi.

- **Yazar adı tek yazıma indi.** Kodda iki yazım dolaşıyordu; yorumlar bu
  projede ASCII Türkçe yazıldığı için çoğunluk biçimi seçildi. README bir
  belge olduğu için orada gerçek yazım kullanılıyor.

### Değişmeyenler

- `LICENSE.txt` ve `files/license.txt`'ye dokunulmadı — ikisi de tam GPLv2
  metni ve birbirinin aynı. Docker imajı da Windows kurulumu da lisansı
  zaten taşıyordu.
- Windows kurulum sihirbazına lisans onay sayfası eklenmedi. GPL tıklama
  onayı istemez; kurulan dizinde `license.txt` duruyor.
- Hakkında sayfasına görünür bir lisans/garanti metni eklenmedi. Bu ileride
  yapılabilir; bir "bildirim düzeltme" sürümünde sessizce arayüz değiştirmek
  istemedim.

## Doğrulama

**Lisans taramasının** yorum dışında hiçbir şeye dokunmadığı **ölçüldü**,
tahmin edilmedi:

- 331 PHP dosyasının tamamı, yorumları ve boşlukları atılmış hâliyle tarama
  öncesi ve sonrası karşılaştırıldı: **331/331 birebir aynı.**
- Aynı 331 dosya ayrıştırıcıdan geçirildi: 331/331 geçti.
- Tarama yeniden koşuldu: **195/195** kaynak dosyası artık telif ve lisans
  satırı taşıyor (önce 115/195 idi).
- CRLF satır sonu kullanan dosyalarda eklenen satırlar da CRLF ile yazıldı;
  hiçbir dosyanın satır sonu biçimi bozulmadı.

**Geri sayım** ayrıca ölçüldü — hem çalıştığı, hem başka hiçbir şeyi
bozmadığı:

- Tarih hesabı 13 durumda sınandı: gerçek kayıt (07.10.2026 23:45 JST →
  07.10.2026 14:45 UTC), saat girilmemiş kayıt, saat dilimi girilmemiş kayıt,
  geçersiz saat dilimi, İstanbul saati, bozuk tarih, eksik alanlar ve dört
  farklı yayın durumu. 13/13 beklenen sonucu verdi.
- Detay sayfasının yayın bloğu **dosyadan çıkarılan gerçek satırlarla**, beş
  yayın durumu × kronoloji işareti var/yok = 10 durumda render edildi. Sonuç
  1.1.28'in aynı testiyle karşılaştırıldı: **yalnızca "Yayın Başlamadı"
  satırları değişti**, diğer sekiz durum birebir aynı çıktı.
- Aynı test liste sayfasının hücresi için de koşuldu: yedi durumdan
  **yalnızca biri** değişti (başlamamış anime `-` yerine geri sayım),
  altısı aynı.
- Kronoloji düğmesi her durumda **tam bir kez** sayıldı — 1.1.28'de kurulan
  kural bozulmadı.
- Yayını devam eden animenin geri sayımı, yedi girdilik bir matrisle
  1.1.28 ve 1.1.29 dosyalarında ayrı ayrı koşuldu: çıktılar birebir aynı.
- 331 PHP dosyasından tam **beşi** lisans taraması sonrasındaki hâlinden
  anlamlı olarak farklı — bu özelliği taşıyan beş dosya. Kalan 326'sı,
  yorumları atıldığında birebir aynı.

## Şema / migration

- **Şema değişikliği yok.** Yeni tablo, kolon veya tercih eklenmedi — geri
  sayım her görüntülemede hesaplanır, saklanmaz.
  `migration/1.1.29/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog veritabanında elle işlem gerekmez** — katalog teline
  dokunulmadı.

## Dağıtım notu

**Uygulama sunucusunda** 195 dosya değişti ama bunların 190'ında değişen tek
şey telif/lisans yorumudur. Geri sayımı getiren beş dosya şunlar ve
**birlikte** yüklenmelidir:

```
files/functions/anime_helpers.php   (hesap + geri sayım metni)
files/anime_details.php             (İlk Bölüm satırı)
files/index.php                     (liste sütunu)
files/lang/tr.php, files/lang/en.php (İlk Bölüm etiketi)
```

Bunlara ek olarak `files/version.txt` ve yeni `files/migration/1.1.29/`
klasörü gerekli. Geri kalan dosyalar kozmetiktir, rahat bir zamanda
yüklenebilir.

**Yarım yükleme uyarısı:** `anime_helpers.php` eski kalırsa detay sayfası
olmayan bir fonksiyonu çağırır ve sayfa açılmaz. Dil dosyaları eski kalırsa
etiket yerine anahtar adı görünür. Yukarıdaki beşli tek parça olarak
düşünülmeli. Diğer 190 dosya arasında böyle bir bağımlılık yoktur.

Eski migration klasörlerinin (`0.5.1` … `1.1.28`) dosyaları da değişti, ama
bunların uygulama sunucusuna yeniden gitmesi gerekmez: sürüm yöneticisi
yalnızca mevcut sürümden büyük numaralı klasörleri çalıştırır, yani o
dosyalar bir daha hiç okunmaz.

**Dağıtım sunucusunda** (kurulumların güncelleme için baktığı yer) iki
işlevsel adım var: yayımlanan `version.txt` 1.1.29'a çekilmeli — yoksa
"Güncelleme Denetle" hâlâ 1.1.28'i son sürüm sanar — ve
`updates/1.1.29/anime-tracker-1.1.29.zip` paketi yayımlanmalı, yoksa
"Güncelle" düğmesi indirme adresinde 404 alır. Aynı sunucudaki
`catalog.php`, `admin_push.php` ve iki örnek yapılandırma dosyası da değişti
ama onlarda değişen tek şey telif satırı.
