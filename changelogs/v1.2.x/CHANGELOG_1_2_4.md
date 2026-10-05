# Anime Tracker 1.2.4

**Yayın tarihi:** 2026-10-01

Ana iş: **gizlilik ve kullanım koşulları sayfası.** Site artık hangi
bilgileri neden tuttuğunu, ne kadar sakladığını ve kime başvurulacağını
tek bir sayfada anlatıyor. Yanında: davetiye taleplerindeki ve
önerilerdeki **IP adresleri 30 gün sonra siliniyor.** Şema değişikliği
yok (boş migration); merkez katalogda yapılacak bir şey yok.

## 1. Neden

Çevrimiçi kurulum kişisel bilgi tutuyor: üyelerin e-posta adresi,
davetiye talebi bırakanların e-postası, gerekçesi ve IP adresi, öneri
gönderenlerin IP adresi. Bunların ne olduğu, ne için tutulduğu ve nasıl
sildirileceği sitede hiçbir yerde yazmıyordu.

## 2. Gizlilik sayfası (`privacy.php`)

- **Metin çalışma moduna göre değişir.**
  - **Çevrimiçi kurulum:** tutulan bilgiler, ne için kullanıldıkları,
    kimin gördüğü, çerezler, dış kaynaklar (Google Fonts, cdnjs), saklama
    süresi ve haklar (dışa aktarma, düzeltme, silme).
  - **Tek kullanıcılı kurulum:** hesap, e-posta ya da IP tutulmadığını
    söyler ve uygulamanın dışarıya bağlandığı yerleri listeler: katalog
    senkronu, güncelleme kontrolü, kurulum sayacı, isteğe bağlı
    özellikler.
- **Kısa kullanım koşulları:** bilgilerin doğruluğu garanti edilmez,
  posterler hak sahiplerine aittir, kötüye kullanımda hesap askıya
  alınabilir, site olduğu gibi sunulur.
- **Kendi sunucusuna kurulan kopyalar:** her kurulumdan onu işleten kişi
  sorumludur. Yazılımın geliştiricisi kendi işletmediği kurulumlardan
  sorumlu değildir.
- **Türkçe ve İngilizce;** sitemap'e girer, telefona oturur.
- **Bağlantılar:** Hakkında sayfası, kayıt formu, davetiye talep formu ve
  anime sayfasındaki Düzeltme Öner kutusu.

## 3. İletişim adresi yönetim panelinden

Gizlilik sayfasında gösterilen adres koda yazılı değil. Her kurulumun
verilerinden onu işleten kişi sorumlu olduğu için adresi o girer.

- **Yönetici Yetenekleri → İletişim** (yalnız yönetici, yalnız çevrimiçi
  kurulum).
- Davetiye bildirim adresinden ayrıdır; o adres davetiye taleplerini
  alır, bu adres sitenin iletişim adresidir. Boş bırakılırsa sayfa adres
  vermeden "site yöneticisine başvurun" der.
- Geçersiz bir adres kaydedilmez ve kartta bunu söyleyen bir uyarı çıkar.
- **Aynı adres Yardım sayfasının başında da gösterilir.** Orada eskiden
  her kurulumda aynı sabit adres yazıyordu; artık bu ayardan gelir, ayar
  boşsa (ve tek kullanıcılı kurulumda) iletişim satırı hiç çıkmaz.

## 4. IP adresleri 30 gün sonra siliniyor

Davetiye taleplerinde ve önerilerde IP adresi yalnız spam sınırı için
kullanılıyor: aynı adresten bir saatte en çok 5 gönderim. Bir saatten
eski adresin hiçbir işlevi yok, ama adresler süresiz duruyordu.

- **30 günden eski adresler siliniyor;** talebin ve önerinin metni
  kalıyor.
- Temizlik yeni bir talep ya da öneri gelirken ve yönetici bu listeleri
  açtığında kendiliğinden çalışır. Güncellemeden sonraki ilk açılışta
  eski adreslerin hepsi silinir.
- Silme, kayıtların "son güncelleme" zamanını değiştirmez.

## 5. Nasıl denendi

- Taze kurulmuş bir test veritabanında 45, 31, 29 ve 5 günlük IP'li
  kayıtlar açıldı. Yönetici sayfası açılınca 45 ve 31 günlükler silindi,
  29 ve 5 günlükler kaldı. Güncelleme zamanları değişmedi.
- Davetiye talebi gönderme yolunda da aynı sonuç alındı.
- İletişim adresi denendi: geçerli adres kaydedilip sayfada göründü,
  geçersiz adres kaydedilmedi ve uyarı çıktı, boş kayıt adresi kaldırdı.
  Bildirim adresi doluyken iletişim adresi boşsa gizlilik sayfası
  bildirim adresini göstermiyor. Yardım sayfası ayar boşken satır
  göstermiyor, doluyken ayardaki adresi gösteriyor (Türkçe ve İngilizce);
  tek kullanıcılı kurulumda satır yok.
- Gizlilik sayfası Türkçe, İngilizce ve tek kullanıcılı modda açıldı.
  375 piksel genişlikte taşma yok.

## Dosyalar

**Yeni:**

```
files/privacy.php                        gizlilik + kullanım koşulları (moda göre metin)
files/functions/privacy_helpers.php      IP saklama süresi + iletişim adresi
files/migration/1.2.4/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/functions.php                      privacy_helpers.php yüklenir
files/functions/auth_helpers.php         davetiye talebi kaydedilmeden önce eski IP'ler silinir
files/suggest.php                        öneri kaydedilmeden önce eski IP'ler silinir
files/admin/admin_capabilities.php       İletişim kartı (gizlilik sayfasındaki adres)
files/admin/admin_invites.php            liste öncesi IP temizliği
files/help.php                           iletişim satırı ayardan (sabit adres kalktı)
files/admin/admin_suggestions.php        liste öncesi IP temizliği
files/about.php                          gizlilik bağlantısı
files/register.php                       gizlilik bağlantısı
files/request_invite.php                 gizlilik bağlantısı
files/anime_details.php                  Düzeltme Öner kutusunda gizlilik bağlantısı
files/functions/seo_helpers.php          sitemap'e privacy.php
files/lang/tr.php, files/lang/en.php     sayfa metinleri
files/lang/admin_tr.php, admin_en.php    yönetici kartı metinleri
files/version.txt
```

## Dağıtım notu

- **Merkez katalogda iş yok.**
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- Güncellemeden sonra **Yönetici Yetenekleri → İletişim** doldurulmalı.
  Doldurulmazsa gizlilik sayfası adres göstermez.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.4'e çekilir ve `updates/1.2.4/anime-tracker-1.2.4.zip` paketi
  yayımlanır.
