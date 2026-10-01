# Anime Tracker 1.2.1

**Yayın tarihi:** 2026-09-28

Tek iş: **ilişkiler silinmeden düzeltilebiliyor.** Düzenleme formunun
İlişkiler panelinde her satıra bir kalem düğmesi geldi; ilişkinin türü
ve yönü yerinde değiştirilir. Şema değişikliği yok (boş migration);
merkez katalogda yapılacak bir şey yok.

## 1. Neden

1.2.0'a kadar yanlış girilmiş bir ilişkiyi düzeltmenin tek yolu onu
silip yeniden eklemekti: satırı × ile silmek, uzun listeden karşı kaydı
yeniden bulmak ve doğru türü seçmek.

En sık hata **ters yön**. Form tek bir soru sorar: *"Seçtiğiniz anime,
düzenlediğiniz animenin ___'idir."* Bağ yanlış sayfadan kurulursa kayıt
tam tersini söyler. Canlı katalogda bulunan örnekler:

- Alps no Shoujo Heidi (1974 TV), 1993 OVA'larının **özeti** olarak
  kayıtlıydı. Doğrusu: OVA'lar, TV dizisinin özetidir.
- Himitsu no Akko-chan'da 1989 filmi, 1988 dizisinin **öncesi** olarak
  kayıtlıydı. Doğrusu: film, dizinin devamıdır.

"Devamı / Öncesi" bağı ters olunca seri kronolojisinin sırası da, spoiler
korumasının "önce bunu izle" dediği kayıtlar da ters çıkar.

## 2. Kalem düğmesi

Düzenleme formu → **Seri ve İlişkiler** sekmesi → **İlişkiler** paneli.
Her ilişkinin sağında, silme düğmesinin yanında bir kalem var:

- Kaleme basınca satırın altında küçük bir form açılır:
  *"<karşı kayıt>, bu animenin: [tür]"*. Soru, ekleme formunun sorusunun
  aynısıdır ve kaydın **şu anki hâli seçili** gelir.
- Doğru türü seçip **Kaydet**'e basın. Ters girilmiş bir bağı düzeltmek
  için karşılığını seçmeniz yeter: Devamı ↔ Öncesi, Yan Hikâye ↔ Ana
  Hikâye, Özet ↔ Tam Hikâye, Ek İçerik ↔ Ana Kayıt.
- Tür de değişebilir (ör. yanlışlıkla "Devamı" girilmiş bir remake'i
  "Alternatif Versiyon" yapmak).
- **Karşı kayıt değişmez.** İlişkiyi başka bir animeye bağlamak için
  hâlâ silip yeniden eklemek gerekir.

Hiçbir şey değiştirmeden Kaydet'e basılırsa veritabanına yazılmaz.
Düzenleme, ekleme ve silmeyle aynı yetkiyle yapılır (çevrimiçi sitede
moderatör ve üstü) ve iki ucun sayfası da IndexNow kuyruğuna girer.

## 3. Yardım

Seri yardımındaki "Kurallar" listesinde "türü değiştirmek için önce
silin" cümlesi kalktı; yerine kalem düğmesinin nasıl kullanılacağı ve
ters yönün nasıl düzeltileceği yazıldı. "Bu iki anime arasında zaten bir
ilişki var" uyarısı da artık silmeyi değil, kalem düğmesini gösteriyor.

## Dosyalar

**Yeni:**

```
files/update_anime_relation.php          ilişkinin türünü / yönünü değiştiren uç (POST)
files/migration/1.2.1/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/edit_anime.php                     İlişkiler panelinde satır başına kalem + düzenleme formu
files/functions/relation_helpers.php     anime_relation_choice_key(): kayıtlı satırdan formun seçimini üretir
files/css/series.css                     .relation-edit* stilleri
files/lang/tr.php                        +3 anahtar (relation.edit_tooltip, relation.edit.prompt, relation.edit.submit); relation.error.exists ve help.rel.rules.list metinleri
files/lang/en.php                        aynı
files/robots.php                         /update_anime_relation.php Disallow listesine eklendi
files/version.txt
```

Dil dosyası eşitliği: 1169 = 1169.

## Dağıtım notu

- **Merkez katalogda iş yok.** İlişkiler merkeze gönderilmez; tablo
  1.1.38'den beri var.
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- 1.1.50, 1.1.51 ve 1.2.0 henüz canlıya çıkmadıysa sırayla, bu sürümle
  birlikte çıkabilir.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.1'e çekilir ve `updates/1.2.1/anime-tracker-1.2.1.zip` paketi
  yayımlanır.
