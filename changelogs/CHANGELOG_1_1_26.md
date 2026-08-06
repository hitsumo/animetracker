# Anime Tracker 1.1.26

**Yayın tarihi:** 2026-08-05

## Yeni: Konu metnine anime bağlantısı eklemek artık elle yazılmıyor

- **Konu kutularının altına "Anime bağlantısı ekle" düğmesi geldi.** 1.1.19'dan
  beri bir konu metninin içinden başka bir animeye bağlantı verilebiliyordu,
  ama bunun tek yolu kısa kodu **elle yazmaktı** — üstelik hedef animenin MAL
  numarasını ezbere bilmeniz gerekiyordu. Artık düğmeye basıp anime adını
  yazmanız yeterli.
- **Yazdıkça katalogda arıyor.** Çıkan listede her animenin adı, yılı, türü
  (TV/Film/OVA…) ve MAL numarası görünür — aynı adı taşıyan yeniden çevrimler
  ile film sürümlerini birbirinden ayırabilmeniz için.
- **Seçtiğiniz anime imlecin durduğu yere yazılır.** Cümlenin ortasındaysanız
  bağlantı tam oraya girer; metnin geri kalanına dokunulmaz. Eklenen kod
  `[[anime:52991|Sousou no Frieren]]` biçimindedir ve etiket olarak
  **sizin okuduğunuz başlık** kullanılır (Başlık Dili tercihiniz neyse o).
- **Nerede var:** Anime Ekle sayfasında Türkçe ve İngilizce katalog konusu;
  Anime Düzenle sayfasında bunlara ek olarak Türkçe ve İngilizce kişisel konu
  alanları. Salt okunur konu kutusunda düğme çıkmaz.
- **Listede yalnızca gerçekten bağlanabilecek animeler var.** Kısa kod hedefini
  MAL numarasıyla adresler; numarası olmayan bir kayıt bu listeye girmez, çünkü
  seçilseydi hiçbir yere gitmeyen bir kod üretirdi.
- **+18 tercihiniz burada da geçerli.** Yetişkin içeriği gizliyken o animeler
  arama sonucunda çıkmaz — tıpkı liste sayfalarında olduğu gibi.
- **Elle yazmak hâlâ mümkün.** Yardımcı yalnızca bir kolaylıktır; kodu eskisi
  gibi kendiniz de yazabilirsiniz.

## Yeni: Hakkında sayfasında sürüm numarası

- Hakkında sayfası artık kurulu sürümü yazıyor ("Sürüm 1.1.26"). Şimdiye kadar
  hangi sürümde olduğunuzu arayüzün hiçbir yerinden öğrenemiyordunuz.

## Nasıl çalışıyor (teknik)

- Arama, yeni bir uç üzerinden yapılır: `anime_link_search.php`. Sorgu en az
  iki karakterden başlar, her tuş vuruşunda değil yazmayı bıraktıktan ~0,25
  saniye sonra gider ve en çok 12 sonuç döner. Geç gelen bir cevap, daha yeni
  bir sorgunun sonucunun üzerine yazmaz.
- Uç, formu barındıran **iki sayfanın zayıf olanına** göre kapılıdır: Anime
  Düzenle moderatör ister, Anime Ekle ise her üyeye açıktır, o yüzden uç
  "giriş yapmış olmak" ister. Bu bir açılış değildir — dönen her kayıt üyenin
  zaten katalog listesinden görebildiği bir kayıttır.
- Anime listesi sayfaya **gömülmez**. Düzenleme sayfası "Sıradaki Anime"
  kutusu için zaten bir liste taşıyor; ikinci bir tam kopyayı (bu kez MAL
  numaraları ve alternatif adlarla) her form açılışında göndermek sayfayı
  katalogla birlikte büyütürdü. Arama ucu ise katalog ne kadar büyürse büyüsün
  aynı boyutta kalır.
- Arama hem ana başlıkta hem alternatif adlarda yapılır; ana başlıktan
  eşleşenler listenin başına çıkar. Aradığınız metinde `%` veya `_` geçse bile
  bunlar joker karakter sayılmaz, harfi harfine aranır.
- Etiket olarak yazılan başlıktan `[`, `]` ve `|` karakterleri temizlenir:
  bunlar kısa kodun kendi ayraçlarıdır ve bırakılsalardı kod bozulurdu.
- Yardımcı **salt ilerlemeci geliştirmedir**: betik yüklenmezse ya da arama
  hata verirse konu kutuları önceki sürümdeki gibi çalışmaya devam eder.
  Kaydetme yolunda hiçbir değişiklik yoktur.
- Hakkında sayfasındaki sürüm `files/version.txt` dosyasından okunur, veri
  tabanındaki sürüm satırından değil: sorulan şey sunucudaki **kodun**
  sürümüdür. Dosya okunamazsa satır hiç basılmaz.

## Şema / migration

- **Şema değişikliği yok.** Yeni tablo, kolon veya tercih eklenmedi.
  `migration/1.1.26/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — katalog teline
  dokunulmadı.

## Değişen / yeni dosyalar

- files/anime_link_search.php (yeni — arama ucu)
- files/js/synopsis_link.js (yeni — bağlantı seçici)
- files/about.php (sürüm satırı)
- files/add_anime.php (konu kutuları işaretlendi, dil anahtarları, betik)
- files/edit_anime.php (konu kutuları işaretlendi, dil anahtarları, betik)
- files/css/components.css (seçici stilleri)
- files/lang/tr.php, files/lang/en.php
- files/migration/1.1.26/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt

## Dağıtım notu

- İki **yeni** dosya var: `files/anime_link_search.php` ve
  `files/js/synopsis_link.js`. Bunlar `add_anime.php`, `edit_anime.php` ve
  `css/components.css` ile **birlikte** yüklenmelidir; biri eksik kalırsa düğme
  ya hiç çıkmaz ya da araması çalışmaz. Eksiklik konu kutusunu bozmaz — kod
  elle yazılmaya devam edilebilir.
