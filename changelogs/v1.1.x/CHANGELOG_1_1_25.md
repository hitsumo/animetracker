# Anime Tracker 1.1.25

**Yayın tarihi:** 2026-08-02

## Yeni: Seri Kronolojisi'nde "Diğer Zincir" sekmeleri

- **Bir serideki diğer zincirler artık sayfada görünüyor.** Bir seri adı
  altında birden çok zincir olabilir: Koukaku Kidoutai'de sinema filmleri
  bir zincir, SAC dizileri bambaşka bir zincirdir. Sayfa bugüne kadar
  yalnızca **açtığınız animenin** içinde bulunduğu zinciri çiziyordu;
  diğerine ulaşmanın tek yolu o zincirdeki bir animeye gitmekti. Seriyi
  tanımıyorsanız kaç zincir olduğunu görmenin hiçbir yolu yoktu.
- **Sekme çubuğuna, Zincir Sırası ve Yayın Tarihi'nin yanına, kendi
  zinciriniz dışındaki her zincir için bir sekme ekleniyor:** "Diğer
  Zincir 1", "Diğer Zincir 2"… Sekmenin üzerinde beklerseniz o zincirin
  kaç anime taşıdığı görünür. Tıklayınca zincir aynı sayfada, aynı
  düzende çizilir.
- **Sayı elle ayarlanmaz, kendiliğinden çıkar.** Sayfa her açılışta seri
  adını taşıyan kayıtları tarayıp zincirleri bulur. Yeni bir zincir
  kurduğunuzda (ya da bir halkayı kopardığınızda) sekme listesi bir
  sonraki açılışta kendini günceller.
- **Sıralama sabittir:** "Diğer Zincir 1" en eski tarihli zincirdir,
  sonrakiler onu izler. Aynı seriye hangi animeden bakarsanız bakın
  zincirlerin sırası aynı kalır — yalnızca o an içinde bulunduğunuz
  zincir listeden çıkar, çünkü o zaten "Zincir Sırası" sekmesidir.
- **Hiçbir yere bağlanmamış tek kayıt zincir sayılmaz** (eşik 2 anime).
  Yoksa seri adını paylaşan her bağımsız film ayrı bir sekme üretirdi. O
  kayıtlar zaten "Yayın Tarihi" sekmesinde, tarih sırasında duruyor.
- **Kalıcı sekme tercihiniz bozulmaz.** Diğer bir zincire geçmek geçici
  bir görünümdür: Liste Ayarları'ndaki "Seri Kronolojisi Görünümü"
  tercihine de, oturumdaki Zincir/Yayın Tarihi seçiminize de dokunmaz.
  Başka bir animenin kronolojisini açtığınızda sayfa yine o animenin
  kendi zinciriyle gelir.

## Nasıl çalışıyor (teknik)

- Zincir keşfi mevcut veriden türetilir: seri adı grubundaki kayıtlar ilk
  gösterim tarihine göre taranır, her kayıt için `next_in_series`
  bağlantısı geriye doğru yürünüp zincirin başı bulunur, zincir bir kez
  ileri yürünür ve üyeleri işaretlenir. Aynı zincirin diğer üyeleri
  atlandığı için her zincir listeye bir kez girer.
- Zincir yürüyüşü (geri ve ileri) `series_timeline.php` içinden
  `series_helpers.php`'ye taşındı. Sayfanın çizdiği zincir ile keşfin
  bulduğu zincir artık aynı koddan gelir; iki kopyanın birbirinden
  ayrışma ihtimali kalmadı. Yürüyüşün davranışı değişmedi — döngü koruması
  dahil.
- Seçim `?chain=<başlangıç_id>` ile taşınır ve **oturuma yazılmaz**.
  Değer yalnızca aynı seri adı grubunda bulunmuş bir zincirin başlangıcı
  olabilir; başka her değer sessizce yok sayılır ve sayfa kendi zincirine
  döner.
- Diğer zincir görünümü daima zincir sırasıdır: "Yayın Tarihi" bütün
  seriyi kapsar, tek bir zincire daralmaz.
- Sekme etiketinde zincirin ilk animesinin adı **yazılmaz**; ipucu metni
  yalnızca anime sayısını söyler. Böylece +18 maskesi sekme çubuğundan
  sızmaz.
- Seri adı olmayan animede sekme çubuğu hiç çıkmaz, sayfa eskisi gibi
  yalnızca zinciri çizer.

## Şema / migration

- **Şema değişikliği yok.** Yeni tablo, kolon veya tercih eklenmedi;
  zincirler var olan `next_in_series` + `series_name` verisinden
  türetilir. `migration/1.1.25/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — katalog teline
  dokunulmadı.

## Değişen / yeni dosyalar

- files/series_timeline.php (diğer zincir sekmeleri, `chain` parametresi)
- files/functions/series_helpers.php (zincir yürüyüşü buraya taşındı +
  zincir keşfi)
- files/lang/tr.php, files/lang/en.php ("Diğer Zincir %d" etiketi)
- files/migration/1.1.25/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt

## Dağıtım notu

- Yeni dosya yoktur, ancak `files/series_timeline.php` ile
  `files/functions/series_helpers.php` **birlikte** yüklenmelidir: zincir
  yürüyüşü sayfadan yardımcıya taşındı, yalnızca biri güncellenirse sayfa
  tanımsız fonksiyon hatası verir.
