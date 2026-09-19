# Anime Tracker 1.1.42

**Yayın tarihi:** 2026-09-16

Şema değişikliği yok. Tek iş: yardım, son üç sürümün eklediği özellikleri
anlatmıyordu — artık anlatıyor. Kod davranışında hiçbir değişiklik yok;
değişen yalnızca yardım sayfaları ve dil dosyaları.

## 1. Yardım kapsamı, ikinci tur

1.1.33 yardımı dört yeni grupla genişletmişti. Sonraki sürümler üç büyük
özellik ekledi ve yardım onları izlemedi:

| Sürüm | Özellik | Yardımdaki durumu |
|---|---|---|
| 1.1.38 | Tipli ilişkiler ("Alternatif Versiyon", "Yan Hikâye"...) | **Hiç yoktu** |
| 1.1.38 | Ekleme / düzenleme formu beş sekmeye bölündü | "Sekme" kelimesi geçmiyordu |
| 1.1.40 | Sıra bir ilişkidir ("Devamı" / "Öncesi"; "Sıradaki Anime" kutusu kalktı) | Tek cümle |
| 1.1.41 | Paylaşımlı MAL / AniDB kaydı (parça numarası) | **Hiç yoktu** |
| 1.1.15 | Kronoloji notlarının nasıl eklendiği / düzenlendiği / silindiği | "Ne olduğu" vardı, "nasıl" yoktu |
| 1.1.19 / 1.1.26 | Konu içinde `[[anime:...]]` bağlantı kodu ve bağlantı seçici | **Hiç yoktu** |
| 1.1.35 | İçe aktarma kara listesi | **Hiç yoktu** |

Bu sürüm bu yedi boşluğu kapatıyor.

### Seriler ve Bölüm Bilgisi sayfası

- **Giriş yeniden yazıldı:** birbirine bağlı animeler üç katmanda tarif
  edilir — seri adı ("hangi aileden?"), ilişkiler ("iki kayıt birbirinin
  nesi?") ve kronoloji notları ("kaçıncı bölümden sonra hangi film?").
- **Seri Bilgisi:** seri adının nereden girildiği, neyi beslediği (not
  formundaki hedef listesi, Seri Kronolojisi'nin "Yayın Tarihi" sekmesi)
  ve sıra söylemediği. Bölümün adı düzeltildi: detay sayfasındaki bölüm
  "Bağlı Animeler" değil **"Bağlantılı Animeler"**.
- **İzleme Sırası:** bağın yalnızca iki uç aynı zincir adını taşıyorsa
  izlendiği eklendi.
- **Kronoloji Notları:** notun neye bağlı olduğu (dizinin kaydına; hedef
  ayrı kayıt), üç görünme yeri (liste, aktif uyarı, "Kronoloji" sayfası).
  **Yeni:** *Kronoloji Notu Ekleme, Düzenleme, Silme* — kim ekleyebilir,
  form ne zaman çıkar, dört alan ne demek, yerinde düzenlemenin hangi
  noktayı değiştirdiği, silme. **Yeni:** *"Kronoloji" ile "Seri
  Kronolojisi" aynı şey mi?* — biri tek dizinin içine, öteki serinin
  tamamına bakar.
- **Yeni bölüm: İlişkili Animeler.** Altı tür ve tanımları (Devamı /
  Öncesi sıra belirten tek tür; Alternatif Versiyon; Alternatif Kurgu; Yan
  Hikâye / Ana Hikâye; Özet / Tam Hikâye; Diğer). Yön: formun tek sorusu
  ("Seçtiğiniz anime, düzenlediğiniz animenin ___'idir"), yönü olan üç
  türün listede iki kez geçmesi, öteki üçünün yönsüzlüğü. Kurallar: bir
  çift en çok bir ilişki taşır, kendine ilişki yok, zincir adı kuralı,
  ilişkilerin bu kurulumda kalması ve yedeğe girmesi. *Bağ mı, ilişki mi?
  — Zincir nasıl kurulur:* üç adım ve ölçüt ("bağ, bunu bitirince şunu
  izle demektir"), Sailor Moon Crystal örneğiyle. Kutu: "Sıradaki Anime"
  kutusu nereye gitti.
- **Seri Kronolojisi:** zincir sekmesi artık "Devamı / Öncesi bağlarıyla"
  diye anlatılıyor ve yeni bölüme bağlanıyor.

### Alanlar ve Kişisel Veri sayfası

- **Yeni bölüm: Ekleme / Düzenleme Formu — Sekmeler.** Beş sekmenin içeriği;
  hepsinin tek form olduğu ve tek kaydetmeyle kaydedildiği; zorunlu bir
  alan başka sekmede boş kaldıysa formun o sekmeye götürdüğü; son sekmenin
  hatırlandığı; İlişkiler panelinin neden yalnızca düzenleme formunda
  olduğu (iki *var olan* kaydı bağlar); kimin hangi formu gördüğü.
- **Yeni bölüm: Paylaşımlı MAL / AniDB Kaydı.** Neden var (kaynak ile
  katalog "bir anime"de anlaşamayabilir), nasıl kullanılır (kutu, parça
  numarasının kendiliğinden gelmesi, işaretsiz kopyanın reddi, düzenlemede
  kutunun veriden türemesi ve kaldırılamaması), nerede görünür ("MyAnimeList
  · 2/3" rozeti, "Aynı Kaynak Kaydı" bölümü) ve nelere etki eder (MAL /
  AniList içe aktarma kuralı, Otomatik Doldur, `[[anime:2994/2]]` konu
  bağlantısı, silme ve kara liste, yedek ve katalog).
- **Yeni bölüm: Konuda Başka Bir Animeye Bağlantı.** `[[anime:2994|Death
  Note]]` kodunun ne olduğu, neden düz bağlantı değil kod olduğu (metin her
  kuruluma gider, MAL numarası her yerde aynı; hedef katalogda yoksa düz
  metne düşer), "Anime bağlantısı ekle" düğmesi (yerel katalogda arar, kodu
  imlece yazar, paylaşımlı kayıtta parçayı taşır) ve elle yazarken kurallar.
- Katalog alanları listesinden "sonraki seri" kalktı (1.1.40'ta emekli
  olmuştu); MAL / AniDB satırı paylaşımlı kutuya bağlanıyor. Yeni not: zincir
  adı, ilişkiler ve kendi eklediğiniz kronoloji notları ne katalog alanıdır ne
  kişisel — bu kurulumun küratör verisidir, sync dokunmaz, yedeğe girer.

### İçe / Dışa Aktarma sayfası

- **Yeni bölüm: İçe Aktarma Kara Listesi.** Yalnızca çok kullanıcılı sitede;
  çözdüğü sorun (silinen anime içe aktarımla geri geliyordu), nasıl çalıştığı
  (silmede kendiliğinden, yalnız kimlikle eşleşme, yalnız eşleşmeyen kayıtları
  keser, katalogda duran animeyi engellemez, paylaşımlı kayıtta son parça
  kuralı), yönetici sayfası ve yedeğe girmediği.

### İçindekiler

Beş yeni satır: *Ekleme / Düzenleme Formu — Sekmeler*, *Konuda Başka Bir
Animeye Bağlantı*, *Paylaşımlı MAL / AniDB Kaydı*, *İlişkili Animeler*,
*İçe Aktarma Kara Listesi*.

## 2. Düzeltilen yanlış bilgi

Yardımdaki uyarı kutusu **"Kendiniz marker eklediyseniz sync sonrası
kaybolur"** diyordu. Bu doğru değildi: katalogdan içe aktarma yalnızca
katalogdan gelen notları yeniler, **kendi eklediğiniz notlar silinmez** ve
Liste Ayarları bunları "katalogla senkronize olmayan not" uyarısıyla
hatırlatır. Yardım, kodun eski hâlini anlatıyordu. Metin doğrusuyla
değiştirildi.

## Dosyalar

**Yeni:**

```
files/migration/1.1.42/upgrade.sql   (şemasız; sürüm damgası + gerekçe kaydı)
```

**Değişen:**

```
files/help.php                    içindekiler (+5 satır)
files/help/help_series.php        +2 alt başlık, yeni "İlişkili Animeler" bölümü
files/help/help_fields.php        yeni "Sekmeler", "Konu Bağlantısı", "Paylaşımlı Kayıt" bölümleri
files/help/help_transfer.php      yeni "Kara Liste" bölümü
files/lang/tr.php                 47 yeni + 10 değişen anahtar
files/lang/en.php                 aynı
files/version.txt
```

Dil dosyası paritesi: 1098 = 1098. Yeni CSS/JS yok.

## Dağıtım notu

- Yardım sayfaları dil dosyalarındaki anahtarları basar. Sayfalar yeni gidip
  dil dosyaları eski kalırsa sayfa çökmez ama metin yerine anahtar adı
  görünür. **`lang/tr.php` ve `lang/en.php` önce ya da birlikte gitmeli;**
  ters yön zararsızdır (fazla anahtar kimseyi rahatsız etmez).
- Migration şema değiştirmez, yalnızca sürüm damgasını taşır ve kendiliğinden
  koşar.
- **Merkez katalog sunucusunda yapılacak bir şey yok.**
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.42'ye çekilmeli ve `updates/1.1.42/anime-tracker-1.1.42.zip` paketi
  yayımlanmalı.
- Self-host ile çevrimiçi site aynı metni görür; rol cümleleri iki modu da
  anlatır.
