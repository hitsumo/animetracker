# Anime Tracker 1.1.47

**Yayın tarihi:** 2026-09-22

Tek iş: **iki ilişki türü daha.** İki kaydın bağını anlatan sözlüğe
**Aynı Evren** ve **Ortak Karakter** eklendi. Şema değişikliği var (bir
enum genişlemesi, kurulum-yerel); merkez katalogda yapılacak bir şey yok.

## 1. Neden

İlişki sözlüğü 1.1.38'de beş, 1.1.40'ta altı türdü; AniDB'nin ilişki
sözlüğü on bir. Eksiklerden ikisi, iki işin **hikâye paylaşmadan**
birbirine değebileceği iki yoldu ve ikisi de "Diğer İlişki"ye düşüyordu.
Yardım metni bile "Diğer"i anlatırken örnek olarak tam bunları veriyordu:
"ortak evren, konuk karakter". Yani doğru cevap biliniyordu ama
seçilemiyordu; "en yakın" türü seçmek de sözlüğü bozardı.

Tetikleyen vaka: **Hana no Ko Lunlun** (1979) ile **Hua Xianzi: Mofa
Xiang Dui Lun** (2026). Aynı çiçek perileri dünyası, iki nesil sonra yeni
bir kahraman. Yapımcı "devamı" diyor; içerik yeni kahraman, aynı dünya —
ve dizi 1980'de bitmiş, yeni iş onun devamı olarak izlenmez. O güne kadar
en yakın tür olan "Alternatif Kurgu" seçilmişti, ki o tam tersini söyler
(aynı karakterler, başka dünya).

## 2. İki tür

| Tür | Anlamı | Örnek |
|---|---|---|
| **Aynı Evren** | aynı dünya, **bambaşka** karakterler | Lunlun ↔ Hua Xianzi |
| **Ortak Karakter** | bir-iki karakter ortak, hikâyeler ayrı | konuk karakter, crossover sahnesi |

- İkisi de **yönsüz**: "A, B ile aynı evrendedir" iki uçtan aynı
  cümledir. Alternatif Versiyon / Alternatif Kurgu / Diğer gibi tek
  kayıt olarak saklanır, ters etiketi yoktur; formda hangi uçtan
  kurduğunuzun önemi yoktur.
- **Sıra belirtmezler.** "Sırada" kutusu, Seri Kronolojisi'nin zincir
  sekmesi ve spoiler koruması yalnız "Devamı" bağını izler; bu iki tür
  onların gözünde yoktur. Bir yapımcının "devamı" dediği ama izleme
  sırasına girmeyen nesil atlamaları için doğru yer burasıdır.
- **Aynı Evren ile Alternatif Kurgu'yu karıştırmayın** — biri ötekinin
  aynadaki hâlidir. Alternatif Kurgu: karakterler aynı, dünya başka ("ya
  öyle olsaydı", okul hayatı yeniden kurgusu). Aynı Evren: dünya aynı,
  karakterler başka. Yardım metni ikisini yan yana anlatır.
- Detay sayfasındaki "İlişkili Animeler" bölümünde iki tür kendi
  başlığıyla, Özet'ten sonra ve Diğer İlişki'den önce gruplanır.
  Ekleme formundaki tür listesinde de aynı yerde dururlar.
- **Var olan veri çevrilmez.** Hangi "Diğer" (ya da geçici "Alternatif
  Kurgu") satırının aslında Aynı Evren / Ortak Karakter olduğunu program
  bilemez; bunu bilen kürator panelden bağı silip doğru türle yeniden
  kurar. Yükseltme hiçbir satıra dokunmaz.
- **"Diğer İlişki"nin tanımı daraldı.** Yardımdaki örnekleri artık
  "aynı yaratıcının bağlantılı işi, ortak kaynaktan türeyen ama hiçbir
  kalıba oturmayan iki yapım". Ortak evren ve konuk karakter kendi
  türlerine taşındı.

## 3. Yedek

Liste yedeği ilişkileri tür adıyla taşır; iki yeni tür de öyle gider ve
gelir. 1.1.47 öncesi bir kuruluma 1.1.47 yedeği yüklenirse bilinmeyen
tür "atlandı" sayılır — çökmez.

## 4. Yardım

- Seri yardımındaki "İlişki Türleri" listesine iki madde (Aynı Evren,
  Ortak Karakter); "Diğer İlişki" maddesinin örnekleri güncellendi.
- "Yön: Form Tek Soru Sorar" bölümünde yönsüz türlerin sayısı üçten beşe.

## Dosyalar

**Yeni:**

```
files/migration/1.1.47/upgrade.sql       relation_type enum'una iki değer (MODIFY)
```

**Değişen:**

```
files/functions/relation_helpers.php     tür listesi + yönsüz tür listesi + form seçenekleri; başlık yorumu
files/schema.sql                         aynı enum (taze kurulum) + tablo yorumu
files/lang/tr.php                        +4 anahtar (relation.type.*, relation.opt.*); yardım listesi 2 madde + 2 metin
files/lang/en.php                        aynı
files/version.txt
```

Dil dosyası paritesi: 1138 = 1138.

## Dağıtım notu

- **Merkez katalogda iş yok.** İlişkiler kurulum-yereldir; merkez
  sunucu tabloyu bilmez, katalog telinde alan yok.
- Migration ilk sayfa açılışında enum'u genişletir (`MODIFY`, yeniden
  çalıştırılabilir; var olan satırlar yerinde kalır).
- `functions/relation_helpers.php` ile dil dosyaları **birlikte**
  gitmeli: eski yardımcı kalırsa yeni tür satırları "Diğer İlişki" diye
  görünür ve formda seçilemez (çökmez); eski dil dosyası kalırsa iki
  seçenek anahtar adıyla görünür.
- Canlı veride bilinen tek çevrilecek satır Lunlun ↔ Hua Xianzi:
  panelden "Alternatif Kurgu" bağını silip "Aynı Evren" ile yeniden kur.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.47'ye çekilmeli ve `updates/1.1.47/anime-tracker-1.1.47.zip` paketi
  yayımlanmalı.
