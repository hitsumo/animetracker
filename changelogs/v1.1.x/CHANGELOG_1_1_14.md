# Anime Tracker 1.1.14

**Yayin tarihi:** 2026-07-16

## Yeni: Yila gore filtre

- **Ana liste sayfasina "Yila Gore Filtrele" filtresi eklendi.** Yayin durumu
  filtresi ile harf filtresi arasinda duran, acilir-kapanir bir bolum icinde
  yil onay kutulari (checkbox) yer alir.
- **Tek ya da birden cok yil secebilirsiniz.** Sadece tek bir yili
  (orn. 1972) isaretleyebilir, birkac yili birden (orn. 1972 + 1973) ya da
  bitisik olmayan yillari (orn. 1972 + 1986 + 2004) ayni anda secebilirsiniz.
  Secili yillari iceren tum animeler listelenir.
- **Yillar katalogtan otomatik gelir.** Kutulardaki yillar elle tutulan bir
  liste degildir; katalogdaki animelerin yayin tarihinden (`release_date`)
  turetilir ve azalan sirada dizilir. Yeni bir yila ait anime eklediginizde
  o yilin kutusu kendiliginden ortaya cikar; o yila ait anime kalmazsa kutu
  kendiliginden kaybolur. (Yalniz yayin tarihi girili animeler bir yil
  kutusuna dahil olur.)
- **Secili yil aninda vurgulanir.** Bir kutuyu isaretlediginizde cip mavi
  dolar, isareti kaldirdiginizda aninda beyaza doner - "Filtrele"ye basmadan
  once bile secili durumu net gorursunuz.
- **"Yil secimini temizle" butonu.** Aktif bir yil filtresi varken yil
  bolumunun altinda cikan bu buton, diger filtrelerinizi (arama, tur, izleme
  durumu, harf, duygu, sayfa boyutu, aktif sekme) koruyarak yalnizca yil
  secimini tek tiklamada temizler.
- **Filtre mevcut aramanizi, siralamanizi ve sayfalamanizi korur.** Secili
  yillar; siralama baglantilari, harf filtresi ve sayfa numaralari arasinda
  gezerken korunur.

## Iyilestirme: Yardim sayfalarindaki "geri don" baglantisi

- **Yardim sayfalarindaki "Ana Sayfaya Don" / "Yardim Icindekiler"
  baglantilari artik buton gorunumunde.** Duz mavi yazi yerine cerceveli buton
  olarak gorunur; basindaki ok isareti kaldirildi. Islevi degismedi.

## Temizlik

- **Iki olu dosya kaldirildi:** `files/user_anime_helpers.php` (artik
  `functions/user_anime_helpers.php` ile birebir ayni bir kopyaydi ve hicbir
  yerden cagrilmiyordu) ve `files/dizin_listesi.txt` (eski bir dizin dokumu
  artefakti). Sunucuya kurulumu guncellerken bu iki dosya varsa elle
  silinebilir; islevsel bir etkisi yoktur.

## Nasil calisir (teknik)

- Filtre `year_filter[]` dizi parametresiyle secilir. Yil ayri bir kolon
  degildir; predikat mevcut `animes.release_date` kolonu uzerinde
  `YEAR(a.release_date) IN (...)` olarak calisir. Yeni tablo, alan veya ayar
  gerekmez.
- Secilen yil degerleri sunucuda `(int)`'e cast edilir ve yalnizca katalogda
  gercekten bulunan yillarla eslesecek sekilde beyaz listeye alinir; SQL'e
  yalnizca dogrulanmis tamsayilar gomulur, boylece enjeksiyon yuzeyi yoktur.
- Cip vurgusu sunucu tarafi bir sinifa degil canli checkbox durumuna baglidir
  (`:has(input:checked)` + kucuk bir JS senkronu), bu yuzden isareti
  kaldirinca vurgu aninda kalkar.

## Sema / migration

- `migration/1.1.14/upgrade.sql` yalnizca surumu 1.1.14'e tasir; **sema
  degisikligi yoktur** (calistirilacak SQL ifadesi yok). Merkez katalog
  etkilenmez, sunucuda elle bir islem GEREKMEZ.

## Degisen / yeni dosyalar

- index.php (Yila gore filtre: parametre ayristirma, iki sorgu dalinda
  `YEAR(release_date) IN (...)` predikati, secili yillarin siralama/harf/
  sayfalama baglantilarinda korunmasi, filtre formu arayuzu, cip senkron JS'i)
- css/series.css (yil izgarasi ve cip stilleri, "Yil secimini temizle" butonu)
- css/help.css (yardim sayfasi "geri don" baglantisinin buton gorunumu)
- lang/tr.php, lang/en.php (index.filter.year / year_none / year_clear
  anahtarlari; help.back_to_home / back_to_index metinlerinden ok isaretinin
  kaldirilmasi)
- migration/1.1.14/upgrade.sql (yeni)
- version.txt
- Kaldirildi: files/user_anime_helpers.php, files/dizin_listesi.txt
