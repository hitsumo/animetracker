# Anime Tracker 1.1.22

**Yayın tarihi:** 2026-07-25

## Yeni: AniList içe aktarma artık etiketli isim üretiyor

- **AniList'ten içe aktarılan animeler artık tek isimle gelmiyor.** Şimdiye
  kadar içe aktarma her animenin yalnızca Romaji adını tutuyor, AniList'in
  verdiği İngilizce ve orijinal (native) adları çöpe atıyordu. Artık o adlar
  dil etiketiyle alternatif isim listesine yazılıyor:

      [en]Frieren: Beyond Journey's End|[ja]葬送のフリーレン

- **Bunun görünür sonucu:** Başlık Dili tercihi (1.1.21) içe aktarılan
  animelerde de çalışıyor. Tercihi İngilizce ya da Japonca olan bir kullanıcı,
  içe aktarmayla kataloğa giren bir animeyi artık o dilde görür — eskiden bu
  animeler herkes için Romaji'de kalıyordu.
- **Orijinal adın dili ülkeden bulunur.** AniList native adın dilini
  söylemez, yalnızca yapım ülkesini verir; köprü şöyle kurulur: Japonya →
  Japonca, Çin/Tayvan → Çince, Kore → Korece. Köprülenemeyen bir ülkede isim
  **etiketsiz** yazılır — yanlış tahmin edilmiş bir etiket, o dili seçen
  kullanıcıya yanlış başlık gösterirdi; etiketsiz isim ise dürüsttür.
- **Nereye yazılır?** Online modda öneri kaydına: moderatör onayladığında
  etiketli isimler animeyle birlikte kataloğa geçer (onay sayfası bu alanı
  zaten taşıyordu). Self-host'ta yerel eklenen anime doğrudan etiketli doğar.

## Düzeltildi: Liste Ayarları yavaş bağlantıda stilsiz görünüyordu

- **Sayfaya özgü stiller (sekme çubuğu, mavi butonlar, beyaz bölüm kartları)
  sayfanın en sonunda duruyordu.** Uygulamadaki diğer bütün sayfalar bu
  stilleri `<head>` içinde taşırken, Liste Ayarları tek istisnaydı: stil
  bloğu `</body>`'nin hemen üstündeydi. Yavaş ya da yarıda kesilen bir
  yüklemede tarayıcı, sayfanın son baytları gelene kadar içeriği **stilsiz**
  çiziyordu — sekmeler çıplak düğme, butonlar varsayılan gri görünüyordu
  (genel stiller dosyadan geldiği için sayfanın üst kartı normal kalıyor,
  bozulma yalnızca bu sayfaya özgü parçalarda oluyordu).
- Stil bloğu diğer sayfalardaki yerine, `<head>` içine taşındı. İçerik ve
  kural sırası değişmedi; yalnızca tarayıcının stilleri **içerikten önce**
  alması garanti oldu.

## MAL neden değişmedi

- **MAL'ın XML dışa aktarımı tek bir isim taşır** (`series_title`); dosyada
  İngilizce ya da Japonca ad diye ikinci bir alan yoktur. Üretilebilecek
  etiket olmadığı için MAL içe aktarma yolu olduğu gibi kaldı.

## Nasıl çalışıyor (teknik)

- AniList sorgusu artık `title { romaji english native }` çekiyor (`native`
  yeni). Ana başlık seçimi değişmedi: Romaji, yoksa İngilizce.
- İngilizce ad, ana başlığın aynısı ya da yalnızca büyük/küçük harf farklı
  kopyasıysa yazılmaz (AniList "One Piece" gibi adları iki alanda da verir).
  Aynı eleme native ad için de geçerlidir.
- Etiketli metin, ekleme/düzenleme formunun kullandığı aynı süzgeçten
  (`build_alt_titles`) geçer: boru işareti boşluğa çevrilir, ismin başındaki
  elle yazılmış bir etiket ayıklanır, bilinmeyen dil kodu etiketsize düşer.
  Yani içe aktarmanın yazdığı satır, formda yazılmış gibi kurallıdır.
- **Katalogda zaten olan (eşleşen) animelere dokunulmaz** — içe aktarma
  eksik etiketleri geriye dönük tamamlamaz; katalog içeriği küratör alanıdır.
- Eski bir oturum taslağı (önizleme 1.1.21'de, uygulama 1.1.22'de) yeni alanı
  taşımaz; alan sessizce boş kalır — ülke alanının 1.1.17'deki savunmasıyla
  aynı kalıp.

## Şema / migration

- **Şema değişikliği yok.** `alternative_titles` kolonları zaten vardı;
  değişen tek şey içe aktarma yazıcısının onları doldurmaya başlaması.
  `migration/1.1.22/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ.** Etiketli metin mevcut
  kolonun içinde, mevcut katalog teliyle akar.

## Değişen / yeni dosyalar

- files/functions/anilist_import_helpers.php (sorguya native eklendi; anilist_native_lang + anilist_alt_titles yeni)
- files/list_settings.php (AniList commit'in iki yazma yolu alternative_titles taşıyor; sayfa stilleri head'e taşındı)
- files/migration/1.1.22/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt
