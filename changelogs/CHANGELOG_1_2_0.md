# Anime Tracker 1.2.0

**Yayın tarihi:** 2026-09-27

Tek iş: **sitenin dili artık adreste taşınıyor.** Her sayfanın İngilizce
hâli ayrı bir adreste (`?lang=en`) duruyor ve çevrimiçi kurulumda arama
motorlarına iki dilin de adresi bildiriliyor. Şema değişikliği yok (boş
migration); merkez katalogda yapılacak bir şey yok. Tek kullanıcılı
kurulumda dil seçimi eskisi gibi çalışır.

## 1. Neden

Arayüz dili yalnızca oturumda (misafir) ya da hesap tercihinde (üye)
yaşıyordu; adres hangi dilde olursa olsun aynıydı. Arama motorları çerez
taşımaz, bu yüzden her sayfanın yalnızca varsayılan (Türkçe) hâlini
görüyordu: İngilizce sürüm onlar için yoktu. Search Console bunu gösterdi:
son üç ayda Türkiye dışından gelen gösterimler sonuçlarda 44 ile 72.
sıralar arasında kaldı ve neredeyse hiç tıklama almadı. İngilizce bir
aramaya Türkçe bir sayfa cevap veriyordu.

## 2. Dil adreste

- Parametresiz adres Türkçedir, değişmedi. Mevcut Türkçe sıralamalar
  etkilenmez.
- Aynı adrese `?lang=en` eklenince sayfa İngilizce açılır
  (ör. `series_timeline.php?id=56&lang=en`).
- **Misafir:** seçim oturuma da yazılır. İngilizce bir aramadan gelen
  ziyaretçi sitedeki bağlantılara tıkladıkça İngilizce devam eder.
- **Üye:** adres yalnızca o sayfayı o dilde gösterir; hesaptaki dil
  tercihi değişmez. Bir İngilizce bağlantıyı açmak, kayıtlı tercihinizi
  kimsenin sizin yerinize değiştirmesine yol açmaz.

## 3. TR | EN bağlantısı

Çevrimiçi kurulumda, giriş yapmamış ziyaretçiler kamuya açık sayfaların
(liste, detay, kronoloji, seri kronolojisi, Hakkında, yardım, Son
Güncellenenler, İstatistikler, Ne İzlesem) sağ üstünde küçük bir
**TR | EN** bağlantısı görür. Bağlantı bulunduğunuz sayfanın kendisine
gider, yalnız dili değiştirir. Üyeler dili eskisi gibi Liste
Ayarları'ndan seçer; tek kullanıcılı kurulumda bağlantı görünmez.

## 4. Arama motorları

Yalnızca çevrimiçi kurulumda (tek kullanıcılı kurulum zaten hiç
indekslenmez):

- **hreflang:** indekslenebilir her sayfa iki dil sürümünü ve
  `x-default`'u (İngilizce: iki dilimizden birini konuşmayan ziyaretçi
  için) listeler. Liste aynı sayfanın iki sürümünde de birebir aynıdır.
- **Kanonik adres** etkin dilde yazılır: Türkçe sürüm parametresiz,
  İngilizce sürüm `?lang=en`.
- **Sitemap** her adresi iki dilde verir ve her birinde dil alternatiflerini
  (`xhtml:link`) taşır. **IndexNow** da iki dilin adreslerini bildirir.
- **Konusu yalnız Türkçe olan kayıt:** İngilizce sayfa bu durumda Türkçe
  konuyu gösterdiği için karışık dilli olur; o kaydın İngilizce sürümü
  indekslenmez ve Türkçe sürüm ona işaret etmez. Konusu hiç olmayan ama
  posteri ya da kronoloji notu olan kayıt iki dilde de indekslenir.
- `?lang=tr` ve tanınmayan bir `?lang=` değeri parametresiz sayfanın
  kopyasıdır; bu adresler indekslenmez ("noindex, follow").
- **İngilizce başlıkta İngilizce ad:** İngilizce aramalar çoğunlukla
  İngilizce adla yapılıyor. Kaydın `[en]` etiketli alternatif adı varsa
  İngilizce sayfanın başlığına eklenir; ör. "B-gata H-kei (B Gata H Kei:
  Yamada's First Time) Watch Order". Detay, kronoloji ve seri kronolojisi
  sayfalarında geçerlidir; seri sayfasında ad, seriyi temsil eden kayıttan
  alınır.

## 5. Yardım

Tercihler yardımındaki "Arayüz Dili" metni güncellendi: üyeler dili Liste
Ayarları'ndan seçer, misafirler TR | EN bağlantısını kullanır, her
sayfanın İngilizce adresi `?lang=en` eklenmiş hâlidir ve paylaşılan bir
İngilizce bağlantı kayıtlı tercihi değiştirmez. (Eski metin, 1.1.4'ten
beri bulunmayan bir "sağ üstteki dil seçici"den söz ediyordu.)

## Dosyalar

**Yeni:**

```
files/migration/1.2.0/upgrade.sql      boş (sürüm damgası)
```

**Değişen:**

```
files/functions/i18n_helpers.php       ?lang= okuma, lang_supported / lang_default / lang_url_param / lang_path, guest_lang_links()
files/functions/seo_helpers.php        hreflang, dile göre kanonik, og:locale:alternate, dil kuralı (seo_row_indexable_in_lang), İngilizce ad, sitemap + IndexNow dilleri
files/sitemap.php                      dil başına <url> + xhtml:link
files/anime_details.php                dil kuralı, İngilizce ad, TR | EN
files/series_timeline.php              İngilizce ad, TR | EN
files/chronology.php                   İngilizce ad, TR | EN
files/index.php                        TR | EN
files/about.php                        TR | EN
files/recent.php                       TR | EN
files/statistics.php                   TR | EN
files/recommendations.php              TR | EN
files/help.php                         TR | EN
files/help/help_*.php (10 dosya)       TR | EN
files/css/lang.css                     .guest-lang-links
files/lang/tr.php                      help.prefs.ui_lang.text
files/lang/en.php                      aynı
files/version.txt
```

Dil dosyası eşitliği: 1166 = 1166 (yeni anahtar yok).

## Dağıtım notu

- **Merkez katalogda iş yok.**
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- Sitemap'teki adres sayısı yaklaşık iki katına çıkar; Search Console'da
  sitemap'i yeniden göndermek yeterli.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.0'a çekilir ve `updates/1.2.0/anime-tracker-1.2.0.zip` paketi
  yayımlanır.
