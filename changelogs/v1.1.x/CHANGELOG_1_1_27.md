# Anime Tracker 1.1.27

**Yayın tarihi:** 2026-08-08

## Yeni: İzlenen bölüm sayısı artık anime detay sayfasından da değiştirilebiliyor

- **Detay sayfasındaki "İzlenen Bölüm" satırına (−) ve (+) düğmeleri geldi.**
  Şimdiye kadar bu sayıyı yalnızca liste sayfasından ya da düzenleme formunu
  açarak değiştirebiliyordunuz; bir animenin detayına bakarken bölüm ilerletmek
  için listeye geri dönmek gerekiyordu.
- **Listedeki düğmelerin aynısı.** Aynı sınırlar geçerli: sayı 0'ın altına
  inmez, toplam (ya da toplam bilinmiyorsa yayınlanan) bölüm sayısının üstüne
  çıkmaz. Sınıra gelindiğinde ilgili düğme sönükleşir ve tıklanamaz olur.
- **İzleme durumu kendiliğinden ilerler.** "İzlenme Planlandı" bir animede (+)
  basmak durumu "İzleniyor" yapar; son bölüme geldiğinizde "İzlendi" olur;
  geri aldığınızda tekrar "İzleniyor"a döner. Detay sayfasındaki renkli durum
  rozeti bunu anında yansıtır — hem yazısı hem rengi değişir.
- **Toplam bölüm sayısı bilinmeyen animelerde düğmeler çıkmaz.** Ne toplam ne
  de yayınlanan bölüm bilgisi varsa gösterilecek bir tavan yoktur; satır eskisi
  gibi düz sayı olarak görünür. Önce "Senkronize Et" ya da bölüm bilgisini
  girin.
- **Sayfa bir buçuk saniye sonra kendini tazeler.** Detay sayfasında izlenen
  bölüm sayısına bağlı üç yer daha var: "Sonraki Bölüm" satırı (kaç bölüm
  geride kaldınız), kronoloji uyarısı (şu bölümden sonra şunu izleyin) ve
  izlemeyi bitirme damgası. Sayı değişince bunların da yenilenmesi gerekir,
  yoksa sayfa kendisiyle çelişir. Arka arkaya basarsanız sayaç sıfırlanır —
  sekiz kez (+) basmak tek bir tazeleme yapar.
- **Yarım kalmış yazınız silinmez.** Sayfada doldurmaya başladığınız bir kutu
  varsa (düzeltme önerisi, kronoloji işareti, bölüm kutusu) tazeleme hiç
  yapılmaz; sayı yine de anında güncellenir.

## Yeni: Yayın tarihi ve bitiş tarihi de otomatik geliyor

- **"Otomatik Doldur" artık Yayın Tarihi alanını da dolduruyor** — animenin
  durumu ne olursa olsun (devam eden, henüz başlamamış, tamamlanmış). Bu alan
  şimdiye kadar hiç otomatik dolmuyordu.
- **Tamamlanmış animede ayrıca Yayın Bitiş Tarihi doluyor.** Bölüm sayısının
  yanı sıra bitiş tarihi de kendiliğinden geliyor.
- **Tek bölümlük yapımlarda (film, özel, OVA) bitiş tarihi gelmez** — çünkü
  öyle bir yapımda bitiş tarihi yayın tarihinin kendisidir ve form bu alanı
  zaten göstermez. Orada dolan şey Yayın Tarihi'dir. (Örnek: *Lupin III vs
  Meitantei Conan* — AniList'te de başlangıç ve bitiş aynı gün: 27.03.2009.)
- **Bölüm sayısı 1 olarak dolduğunda bitiş tarihi bölümü kendiliğinden
  kapanıyor.** Eskiden form bu kuralı yalnızca siz sayıyı elle yazdığınızda
  uyguluyordu; otomatik doldurma sonrası bölüm açık kalabiliyordu.
- **Tarihler AniList'ten geliyor, hesaplanmıyor.** AnimeSchedule'ın anime
  kaydında bitiş tarihi diye bir alan **yok** — ilk bölüm tarihi ve yayın saati
  var, finali işaretleyen bir şey yok. Tarihi "ilk bölüm + (bölüm sayısı−1)×7
  gün" diye hesaplamak mümkündü ama **doğru olmazdı:** arasında hafta atlayan
  bir seride sonuç sessizce kayar. Örnek: *Ahiru no Sora* 2 Ekim 2019'da
  başlayıp 50 bölüm sürdü; hesap 9 Eylül 2020 derdi, gerçek bitiş **30 Eylül
  2020**. Üç hafta fark. Bu yüzden tarihler, gerçekten bilen kaynaktan —
  AniList'in kendi tarih alanlarından — okunuyor.
- **Yayın tarihi de aynı yerden, aynı sebeple.** AnimeSchedule'da ilk bölümün
  zamanı var, ama o bir *an*; takvim gününe çevirmek gece yarısından sonra
  yayınlanan animelerde bir gün kaymaya açık (Japonya'da "Cuma 25:25" denen
  yayın aslında cumartesi 01:25'tir). AniList'in yayın tarihi zaten bu
  hesaplaşması yapılmış takvim günü olduğu için ikisi tek sorguda oradan
  alınıyor.
- **Ek bir ayar gerekmiyor.** AniList tarafı API anahtarı istemez; anahtarınız
  olmasa bile bu alan çalışır.
- **Hangi bağlantıyı girdiğiniz önemli değil.** Eşleşme için gereken numara,
  AnimeSchedule'ın zaten çektiğimiz cevabındaki site bağlantılarından okunur.
  Yani yalnızca AniDB bağlantısı girmiş olsanız da, ya da MAL kutusunu
  doldurmadan "Otomatik Doldur"a bassanız da tarihler gelir. Cevapta böyle bir
  bağlantı yoksa formdaki MAL bağlantısına bakılır; o da yoksa tarihler
  atlanır ve geri kalan doldurma aynen çalışır.
- **Yalnızca anlamlı olduğu yerde.** Devam eden bir animede bitiş tarihi zaten
  yoktur; tek bölümlük yapımda ise form bu alanı göstermez. Bu iki durumda
  bitiş tarihi yazılmaz — yayın tarihi ise her durumda yazılır.
- **Anime Ekle sayfasında yayın durumu da artık dolabiliyor.** Durum kutusu
  "Seçim Yapılmadı" ile başlar; bu bir seçim değil, formun boş hâlidir. Eskiden
  "dolu" sayıldığı için otomatik doldurma durumu hiç ayarlayamıyor, dolayısıyla
  duruma bağlı bölümler (yayın bilgileri, bitiş tarihi) hiç açılmıyordu. Artık
  durum da doluyor ve ilgili bölüm kendiliğinden açılıyor.

## Düzeltme: Tamamlanmış animede "Otomatik Doldur" doldurmadığı alanları doldurdum diyordu

- **Sorun:** yayını tamamlanmış bir animede AnimeSchedule "Otomatik Doldur"
  düğmesine basınca "Alan dolduruldu: 3: broadcast_day, broadcast_timezone,
  total_episodes" yazıyor, ama formda gözle görülür bir değişiklik olmuyordu.
  Üç alandan ikisi gerçekte doldurulmamış sayılırdı:
  - **Yayın günü ve saat dilimi, tamamlanmış animede formda görünmez.** Yayın
    bilgileri bölümü yalnızca "Yayın Devam Ediyor" durumunda açılır — çünkü
    haftalık yayın günü yalnızca devam eden bir yayın için anlamlıdır ve
    tamamlanmış animede detay sayfasında da hiç gösterilmez. Otomatik doldurma
    bu gizli bölüme yazıp bunu başarı olarak sayıyordu.
  - **Saat dilimi zaten yazacağı değerdeydi.** Alan "Asia/Tokyo" ile başlar ve
    servisten gelen değer de "Asia/Tokyo"dur; yani üstüne aynı değer yazılıyor,
    hiçbir şey değişmiyor, ama sayaca yine de giriyordu. Bu madde her seferinde
    gereksiz yere listeye ekleniyordu.
- **Artık tamamlanmış anime için yayın günü/saati/dilimi hiç getirilmiyor.**
  Bu animelerde doldurulabilecek şey bölüm sayısıdır; mesaj da bunu söylüyor.
  Doldurulacak bir şey kalmadıysa dürüstçe "doldurulacak boş alan bulunamadı"
  yazıyor.
- **Devam eden ve henüz başlamamış animelerde hiçbir şey değişmedi** — yayın
  günü ve saati eskisi gibi getiriliyor.
- **Mesaj artık yalnızca gerçekten değişen alanları sayıyor.** Bir alan
  yazıldıktan sonra değeri hiç değişmediyse "dolduruldu" denmiyor. Değer
  formdaki seçeneklerden hiçbirine uymuyorsa (tarayıcı böyle bir atamayı sessizce
  yok sayar) alan eski haline döndürülüyor ve bu durum mesajda belirtiliyor.
  Bir alan doluyor ama o an ekranda görünmüyorsa, adının yanında "(gizli
  bölümde)" notu çıkıyor — böylece kimse formda olmayan bir alanı aramıyor.

## Düzeltme: İngilizce arayüzde izleme durumu Türkçe yazılıyordu

- İngilizce arayüz kullanırken listede (+) ya da (−) düğmesine bastığınızda,
  durum hücresine güncellenen değer **Türkçe** yazılıyordu ("Izlendi" gibi) —
  sayfanın geri kalanı İngilizceyken. 0.6'dan beri süren bu davranış düzeltildi;
  artık arayüz diliniz neyse o yazılıyor. Türkçe kullanıyorsanız sizin için
  değişen bir şey yok.

## Nasıl çalışıyor (teknik)

- Detay sayfası **yeni bir kayıt yolu açmaz**: listedeki düğmelerle aynı ucu
  (`update_watched.php`) kullanır. Sınır kuralları, izleme durumu geçişleri ve
  yetki kapısı tek bir yerde durmaya devam eder; detay sayfası aynı ucun ikinci
  müşterisidir.
- Tavan hesabı iki sayfada birebir aynı kuralla yapılır (toplam varsa toplam,
  yoksa yayınlanan, ikisi de boşsa tavan yok). Sunucu bu sınırı kendisi de
  zorlar — tarayıcıdaki hesap yalnızca görünüm içindir.
- Durum rozetinin **rengi** de sunucudan gelir (cevaba `watch_status_css`
  alanı eklendi). Böylece durum adı ile renk eşlemesi tek bir yerde kalır,
  tarayıcı tarafında ikinci bir kopyası tutulmaz.
- Giriş yapmamış ziyaretçiye düğmeler hiç basılmaz; izlenen bölüm kişisel
  veridir ve uç zaten giriş ister.
- Düğme stilleri (`.ep-*`) `index.php` içindeki gömülü stil bloğundan
  `css/components.css`'e taşındı. Widget artık iki sayfada birden durduğu için
  iki ayrı kopya stilin zamanla birbirinden ayrılması kaçınılmazdı. Kurallar
  aynen taşındı, liste sayfasının görünümü değişmedi.

## Şema / migration

- **Şema değişikliği yok.** Yeni tablo, kolon veya tercih eklenmedi.
  `migration/1.1.27/upgrade.sql` yalnızca sürüm damgası taşır.
- **Merkez katalog sunucusunda elle işlem GEREKMEZ** — katalog teline
  dokunulmadı. İzlenen bölüm ve izleme durumu zaten kişisel veridir.

## Değişen / yeni dosyalar

- files/anime_details.php (izlenen bölüm satırına +/- widget'ı ve betiği)
- files/update_watched.php (cevaba `watch_status_css`; dil başlatma düzeltmesi)
- files/index.php (gömülü `.ep-*` stil bloğu boşaltıldı)
- files/css/components.css (`.ep-*` stilleri buraya taşındı + yatay varyant)
- files/functions/animeschedule_helpers.php (tamamlanmış animede yayın
  bilgisi taşınmıyor)
- files/functions/anilist_import_helpers.php (AniList'ten bitiş tarihi okuyan
  yeni yardımcı)
- files/fetch_animeschedule.php (bitiş tarihi adımı)
- files/js/anime_form.js (rapor yalnız gerçek değişimi sayar; MAL bağlantısı
  uca gönderilir; "Seçim Yapılmadı" boş sayılır)
- files/add_anime.php, files/edit_anime.php (iki yeni dil anahtarı betiğe geçirildi)
- files/lang/tr.php, files/lang/en.php (dört yeni metin)
- files/migration/1.1.27/upgrade.sql (yeni, yalnızca sürüm damgası)
- files/version.txt

## Dağıtım notu

- Yeni dosya yok, ama `files/anime_details.php` ile `files/css/components.css`
  **birlikte** yüklenmelidir. `components.css` eksik kalırsa yalnızca detaydaki
  yeni düğmeler değil, **listedeki eski düğmeler de** stilsiz görünür — kurallar
  artık orada duruyor. Tarayıcı önbelleğini 1.1.24'ten beri kullanılan sürüm
  damgası (`?v=1.1.27`) kendisi tazeler; elle bir şey yapmanız gerekmez.
