# Anime Tracker 1.1.34

**Yayın tarihi:** 2026-08-26

Küçük bir sürüm; şema değişikliği yok. Üç ayrı iş var, ikisi asıl işten
sonra eklendi:

1. **Anime eklerken izleme durumu seçmek artık zorunlu değil** — asıl konu,
   hemen aşağıda.
2. **Spoiler kapısı sayfaya uyduruldu** — 1.1.33'te gelen kutu kalktı.
3. **Kurulum yedeğinin adı** — yalnızca Windows `.exe` kurulumunu ilgilendirir.

## Sorun

Anime ekleme formunda "İzleme Durumu" listesi açılışta **"Seçiniz"**
satırında duruyordu ve alan zorunlu işaretliydi. Tarayıcı bu satırı geçerli
bir cevap saymadığı için, izleme durumu seçilmeden **anime kaydedilemiyordu**;
form gönderilmiyor, ekranda "bu alanı doldurun" uyarısı çıkıyordu.

Oysa izleme durumunun boş kalması bu alanda geçerli bir durum. Katalogdan içe
aktarılan animeler tam olarak böyle, izleme durumu boş olarak listeye
giriyor; liste ve detay ekranları o kayıtları "Seçim Yapılmamış" diye
gösteriyor. Yani veri tarafı bu durumu zaten destekliyordu, yalnızca **elle
ekleme** onu yasaklıyordu.

Çıkış yolu aslında vardı: listenin **en altındaki** "Seçim Yapılmamış"
seçeneği. Ama tepedeki "Seçiniz" ile neredeyse aynı anlama geldiği için
pratikte görünmüyordu — iki satır da "henüz seçmedim" diyordu, biri kabul
ediliyor, öteki reddediliyordu.

## Ne değişti

Boş "Seçiniz" satırı kaldırıldı. **"Seçim Yapılmamış" artık listenin başında
ve açılışta seçili.**

Yani anime eklerken izleme durumuna hiç dokunmazsanız anime izleme durumu boş
olarak kaydediliyor. Listede "Seçim Yapılmamış" görünüyor, sonradan detay ya
da düzenleme ekranından istediğiniz zaman verilebiliyor. Bir durum seçmek
isteyenler için değişen bir şey yok, seçenekler aynı.

| Önce | Sonra |
|---|---|
| Seçiniz *(seçili, kaydettirmiyor)* | **Seçim Yapılmamış** *(seçili, kaydediyor)* |
| İzlendi | İzlendi |
| İzleniyor | İzleniyor |
| İzlenme Planlandı | İzlenme Planlandı |
| İzleme Ertelendi | İzleme Ertelendi |
| İzleme Bırakıldı | İzleme Bırakıldı |
| Seçim Yapılmamış | — |

## Düzenleme ekranı

Anime düzenleme ekranındaki aynı liste de aynı sıraya getirildi. Orada
davranış zaten doğruydu — izleme durumu boş olan bir animeyi açtığınızda
"Seçim Yapılmamış" seçili geliyordu, o yüzden düzenlemede kimse takılmıyordu.
Değişen yalnızca sıra ve işe yaramayan "Seçiniz" satırının kalkması; iki ekran
artık aynı listeyi aynı sırada gösteriyor.

## Değişmeyenler

- **Yayın Durumu hâlâ zorunlu.** O alan animenin kendi bilgisi, kişisel bir
  tercih değil: her animenin bir yayın durumu vardır, boş bırakmak bir seçim
  değil eksik kayıttır. MyAnimeList ve AniDB bağlantılarının zorunluluğu da
  yerinde duruyor.
- **Var olan kayıtlara dokunulmadı.** Bugüne kadar seçtiğiniz izleme
  durumları olduğu gibi duruyor; toplu bir düzeltme yapılmadı.
- **Kişisel Konu, notlar, izleme tarihleri** ve geri kalan her şey aynı.

## Spoiler kapısı sayfaya uyduruldu

İkinci iş, yine ayrı bir konu. 1.1.33'te gelen spoiler kapısı, kesikli turuncu
çerçeve + krem zemin + iç boşluktan oluşan bir **kutu** içindeydi. Anime detay
sayfası ise düz satır düzenindedir: solda etiket, sağda değer, altında ince gri
bir ayraç. Kutu o düzenin içinde yamalı duruyordu — izlediğiniz "1. sezon" sade
bir satırken, izlemediğiniz "2. sezon" renkli bir pano gibi görünüyordu.

Artık kapı da sade:

- **Çerçeve, krem zemin ve iç boşluk kalktı.**
- **Uyarı metni**, sayfanın öteki küçük notlarıyla (örneğin çeviri notu) aynı
  gri ve aynı boyutta.
- **"Yine de okumak istiyorum" / "Konuyu gizle"** artık dolgulu turuncu bir
  düğme değil, düz mavi bir metin — **altı çizgisiz**. Üzerine gelince rengi
  koyulaşıyor. Cümle içinde geçen bağlantılarda alt çizgi gerekli, çünkü düz
  metinden başka türlü ayrılmazlar; burada etiket kendi satırında tek başına
  duruyor, renk zaten tıklanabilir olduğunu söylüyor.

Kapının **işleyişi değişmedi**: aynı kural, aynı yerler, aynı tercih. Yalnızca
görünümü sayfanın geri kalanına uyduruldu. Öneriler → Sürpriz kartındaki
tanıtım metni de aynı sadeleşmeden yararlanıyor.

## Kurulum yedeğinin adı

Üçüncü iş, öncekilerle hiç ilgisi yok. Windows kurulum `.exe`'si, üzerine
kurulum yapılan bir makinede dosyaları değiştirmeden önce masaüstüne bir
veritabanı yedeği bırakır. O yedeğin adındaki tarih, `.exe` **derlenirken**
elle yazılan sabit bir değerden geliyordu.

İki sorun vardı. Birincisi kozmetik: değer elle güncellenmediği için ad,
kurulumun yapıldığı günü değil, güncellemenin unutulduğu günü gösteriyordu.
İkincisi asıl olan: ad sabit olduğu için **aynı `.exe` ile ikinci kez kurulum
yapıldığında birinci kurulumun yedeği sessizce üzerine yazılıyordu.** Yedek tam
da "kurulum ters giderse buradan dön" diye alınıyor; dönülecek noktanın
kaybolması onu amacından ediyordu.

Artık damga derleme anında değil, **kurulum anında** üretiliyor ve saati de
içeriyor:

```
at_install_backup_2026-08-26_182630.sql
```

Aynı gün yapılan iki kurulum bile ayrı dosyalara yazıyor. Elle güncellenmesi
gereken bir değer kalmadı.

Bu değişiklik yalnızca `.exe` kurulumunu ilgilendirir; sunucudaki uygulama
dosyalarıyla ve güncelleme paketiyle ilgisi yoktur.

## Değişen dosyalar

**Yeni:**

```
files/migration/1.1.34/upgrade.sql
```

**Değişen:**

```
files/add_anime.php    izleme durumu listesi + boş değerin boş kabul edilmesi
files/edit_anime.php   aynı liste düzeni
files/css/base.css     spoiler kapısı: kutu kaldırıldı, düğmeler sade
                       mavi metin (altı çizgisiz)
files/version.txt
installer.nsi          kurulum yedeğinin adı (yalnızca .exe derlemesi)
```

## Dağıtım notu

- Veritabanı şeması değişmedi; `files/migration/1.1.34/upgrade.sql` yalnızca
  sürüm damgasıdır.
- **Sunucuya giden dosyalar** (`installer.nsi` gitmez, `files/` ağacının
  dışındadır): `files/add_anime.php`, `files/edit_anime.php`,
  `files/css/base.css`, `files/version.txt` ve yeni
  `files/migration/1.1.34/` klasörü.
- Dosyaların birbirine **kod** bağımlılığı yok: biri eskide kalırsa yalnızca
  kendi sayfası eski davranışla çalışır, hiçbir sayfa bozulmaz. Yeni fonksiyon,
  yeni yardımcı ya da yeni dil anahtarı olmadığı için "eski dosya yeni
  fonksiyonu çağırır" riski bu sürümde yok.
- **Tek istisna:** `files/css/base.css` ile `files/version.txt` **birlikte**
  gitmelidir. Stil adreslerindeki sürüm damgası `version.txt`'ten üretilir;
  sürüm eskide kalırsa tarayıcı damgayı aynı görür ve önbellekteki eski stili
  kullanmaya devam eder, yani kapı hâlâ kutulu görünebilir. Sıra önemli değil,
  birlikte gitmeleri yeterli. **1.1.33 atlanıp doğrudan bu sürüme geçiliyorsa** ayrıca bir şey
  yapmaya gerek yok: `base.css`, 1.1.33'te gelen spoiler kurallarını da taşır —
  tek kopya iki sürümü birden getirir.
- Merkez katalog sunucusunda yapılacak bir şey yok; izleme durumu kişisel bir
  alandır, katalog telinde yer almaz.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.34'e çekilmeli — yoksa "Güncelleme Denetle" hâlâ 1.1.33'ü son sürüm
  sanar — ve `updates/1.1.34/anime-tracker-1.1.34.zip` paketi yayımlanmalı,
  yoksa "Güncelle" düğmesi indirme adresinde 404 alır.
