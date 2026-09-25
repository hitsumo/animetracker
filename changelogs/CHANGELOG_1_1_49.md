# Anime Tracker 1.1.49

**Yayın tarihi:** 2026-09-24

Tek iş: **içe aktarma kara listesinin yedeği.** Kara liste sayfasına
"Dışa aktar" ve "İçe aktar" düğmeleri geldi; liste artık ayrı bir dosya
olarak yedeklenip geri yüklenebiliyor. Şema değişikliği yok (boş
migration); merkez katalogda yapılacak bir şey yok. Yalnız çok
kullanıcılı (online) modu ilgilendirir.

## 1. Neden

İçe aktarma kara listesi (1.1.35) katalogdan silinen animelerin
kimliklerini tutar; MAL ve AniList içe aktarımı bu animeleri yeniden
katalog önerisi olarak açmaz. Ama liste **yalnızca uygulamanın
veritabanında** duruyordu:

- merkez kataloğa gönderilmez (tasarım gereği),
- Liste Ayarları'ndaki JSON yedeğine girmez,
- kara liste sayfasında da dışa / içe aktarma yoktu.

Sonuç: JSON yedeğinden temiz bir kuruluma dönüldüğünde liste boş
başlıyor ve ilk içe aktarım bilerek silinmiş animeleri yeniden
öneriyordu — 1.1.35'in kapattığı sorunun ta kendisi. Listeyi yalnızca
veritabanı yedeği kurtarıyordu.

## 2. Kara liste sayfasında "Yedek" kutusu

Yönetim panosu → **İçe Aktarma Kara Listesi** (moderatör ve üstü),
sayfanın altında:

- **Dışa aktar** — listenin tamamını `import_blacklist_YYYY-AA-GG.json`
  olarak indirir. Her kayıt: MAL ve AniDB kimliği, ad, kayıt türü
  (Silindi / Elle), not ve **orijinal tarih** (liste aynı zamanda silme
  defteri; "bunu ne zaman sildim" bilgisi korunur). Ekleyen kullanıcı
  taşınmaz — kullanıcı numarası kuruluma özeldir; geri yüklenen
  kayıtlarda ekleyen, yükleyen moderatör olur.
- **İçe aktar** — yalnızca **ekler**. Listede zaten olan kayıtlar
  atlanır, hiçbir şey silinmez ya da değişmez; aynı dosyayı iki kez
  yüklemek zararsızdır. Sonuç satırı "N kayıt eklendi, M kayıt zaten
  listedeydi" der; okunamayan satır varsa sayısını ayrıca söyler.
  Kayıtlar tek seferde yazılır: bir hata çıkarsa hiçbiri yazılmaz,
  yarım kalmış bir liste oluşmaz.

**Yanlış dosyaya karşı koruma:** yedek dosyası kendi türünü söyleyen
bir işaret taşır. Liste JSON yedeği (Liste Ayarları) ya da başka bir
dosya bu sayfaya yüklenirse reddedilir — bir üyenin anime listesini
kazara kara listeye çevirmek mümkün değildir.

**Tekrarları nasıl ayırt eder:** kimliği olan kayıtlar MAL / AniDB
kimliğiyle eşleşir. Dosyadaki bir kayıt, listedeki bir kayıtla
kimliklerinden yalnızca **birini** paylaşıyorsa "zaten listede" sayılır;
listedeki kayıt o kimliği engellemeye devam eder. Kimliği olmayan silme
kayıtları (hiçbir şeyi engellemezler, yalnız defterdir) ad + tarihle
eşleşir, böylece yeniden yükleme defteri ikiye katlamaz.

## 3. Neden liste JSON yedeğinin içinde değil

İki yol düşünüldü; ayrı dosya seçildi:

- Liste JSON yedeği **düz bir anime dizisidir**. Kara listeyi içine
  koymak dosyanın biçimini değiştirmek demekti ve 1.1.48 ve önceki
  sürümler yeni yedekleri "geçersiz biçim" diye reddederdi.
- Liste yedeği bir **üyenin kişisel dosyasıdır**; kara liste ise katalog
  politikasıdır ve yalnızca moderatörleri ilgilendirir. Üyelerin
  yedeğine girmemesi, üyelerin yüklemesiyle de değişmemesi gerekir.

## 4. Yardım

İçe aktarma yardımındaki kara liste bölümü güncellendi: eski "JSON
yedeğe girmez, yalnızca veritabanı yedeğinde yaşar" cümlesinin yerine
yedeğin nasıl alınıp yükleneceği ve **temiz kuruluma dönerken bu
dosyanın ilk içe aktarımdan önce yüklenmesi** gerektiği yazıldı.

## Dosyalar

**Yeni:**

```
files/migration/1.1.49/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/functions/blacklist_helpers.php     blacklist_export_rows(), blacklist_import_rows(), BLACKLIST_BACKUP_FORMAT
files/admin/admin_blacklist.php           export / import işlemleri, "Yedek" kutusu
files/lang/admin_tr.php                   +10 anahtar (admin_blacklist.backup.*, btn.export/import, hata/sonuç metinleri)
files/lang/admin_en.php                   aynı
files/lang/tr.php                         help.blacklist.where metni
files/lang/en.php                         aynı
files/version.txt
```

Dil dosyası eşitliği: kullanıcı 1149 = 1149, yönetici 321 = 321.

## Dağıtım notu

- **Merkez katalogda iş yok.** Tablo 1.1.35'ten beri var; kolon
  eklenmedi.
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- Tek kullanıcılı kurulumda kara liste zaten etkin değildir; yedek
  kutusu da orada görünmez.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.49'a çekilir ve `updates/1.1.49/anime-tracker-1.1.49.zip` paketi
  yayımlanır.
