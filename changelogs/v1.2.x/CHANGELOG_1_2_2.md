# Anime Tracker 1.2.2

**Yayın tarihi:** 2026-09-30

Tek iş: **seri kronolojisinde sayfa içi sekmeler.** Zincir sekmeleri ve
Yayın Tarihi artık aynı sayfanın içinde; sekmeye tıklayınca sayfa yeniden
yüklenmiyor. Arama motorları serinin bütün hatlarını tek adreste okuyor.
Şema değişikliği yok (boş migration); merkez katalogda yapılacak bir şey
yok.

## 1. Neden

Seri Kronolojisi sayfası bir seriyi birden çok sekmeyle gösterir: ana
hat, "Filmler" / "OVA" gibi adlandırılmış ya da "Diğer Zincir N" hatları
ve Yayın Tarihi. 1.2.1'e kadar her sekme **ayrı bir adresti**
(`?chain=…`, `?mode=airdate`) ve bu adresler kopya sayfa olmasınlar diye
arama motorlarına kapalıydı.

Arama motorları her seri için tek bir adresi dizinler: serinin en küçük
numaralı kaydının sayfası. O adres yalnızca **o kaydın hattını**
gösteriyordu. Sonuç: "izleme sırası" diye arayan birine Google'ın
gösterebildiği sayfa serinin yalnızca bir parçasını içeriyordu. Örnekler:

- Himitsu no Akko-chan: yalnızca 1969 dizisi; 1988 dizisi, filmler ve
  1998 dizisi başka sekmedeydi.
- Taiho Shichau zo: yalnızca özel bölümler; TV dizileri başka sekmedeydi.
- Seitokai Yakuindomo: filmler başka sekmedeydi.

## 2. Ne değişti

- **Zincir sekmeleri ve Yayın Tarihi aynı sayfada.** Ziyaretçi yine bir
  seferde tek liste görür; sekmeye tıklayınca liste yerinde değişir,
  sayfa yeniden yüklenmez. Başlıktaki "N anime" sayacı açık sekmeye göre
  değişir.
- **Paylaşılabilir sekme.** Açık sekme adresin sonuna eklenir
  (`#airdate`, `#chain-65`); bağlantıyı açan kişi aynı sekmeyi görür.
- **Tercih eskisi gibi.** Zincir ↔ Yayın Tarihi seçimi oturum boyunca
  hatırlanır (arka planda kaydedilir); diğer hat sekmeleri eskisi gibi
  hatırlanmaz. Liste Ayarları'ndaki varsayılan görünüm aynen çalışır.
- **İlk açılan sekme aynı kuralla seçilir:** açılan animenin kendi hattı
  (ya da kayıtlı tercih Yayın Tarihi ise o). Serinin en uzun hattı öne
  alınmadı — Heidi, Death Note ya da One Piece'te bu, TV dizisi yerine
  özetleri veya filmleri öne çıkarırdı.
- **Şema** sekmesi ağır bir çizim olduğu için eskisi gibi ayrı adreste
  açılır.
- **Eski adresler çalışır.** `?chain=…` ve `?mode=airdate` bağlantıları
  doğru sekmeyi açar; arama motorları için kuralları (noindex, canonical)
  değişmedi.
- **JavaScript kapalıysa** bütün listeler başlıklarıyla alt alta görünür,
  sekmeler bağlantı olarak çalışmaya devam eder.
- **Posterler tembel yüklenir:** sayfa açılırken yalnızca görünen
  listenin posterleri iner; diğerleri sekme açılınca.

## 3. Arama motoruna bildirim (IndexNow)

Çevrimiçi kurulumda bir kayıt değişince (ilişki, zincir adı, yeni kayıt)
serinin kronoloji sayfası da artık bildiriliyor — değişen kayıt serinin
ilk kaydı olmasa bile. Sayfa bütün hatları gösterdiği için her üyenin
değişikliği o sayfanın içeriğini değiştirir. Bildirilen adres site
haritasındakiyle aynıdır.

## 4. Yardım

Seri yardımındaki sekmeler bölümüne, sekmelerin aynı sayfada çalıştığı,
açık sekmenin adresle paylaşılabildiği ve Şema'nın ayrı sayfada açıldığı
eklendi.

## Dosyalar

**Yeni:**

```
files/js/series_tabs.js                  sayfa içi sekme geçişi, adres çapası, tercih kaydı
files/migration/1.2.2/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/series_timeline.php                bütün liste sekmeleri tek sayfada (panel başına tek kart şablonu: st_render_card)
files/functions/seo_helpers.php          seo_anime_locs(): üye değişince serinin sayfası; seo_series_head_listed()
files/lang/tr.php                        help.st.tabs.text
files/lang/en.php                        aynı
files/version.txt
```

Dil dosyası eşitliği: 1169 = 1169.

## Dağıtım notu

- **Merkez katalogda iş yok.**
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.2'ye çekilir ve `updates/1.2.2/anime-tracker-1.2.2.zip` paketi
  yayımlanır.
