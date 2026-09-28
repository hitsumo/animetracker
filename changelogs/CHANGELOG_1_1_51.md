# Anime Tracker 1.1.51

**Yayın tarihi:** 2026-09-25

Tek iş: **"Ne İzlesem?" duygu araması çevrimiçi kurulumda tüm üyelerin
işaretlerine bakıyor.** Başladığın animeler de artık varsayılan olarak
sonuçlarda gizleniyor. Şema değişikliği yok (boş migration); merkez
katalogda yapılacak bir şey yok. Tek kullanıcılı kurulumda hiçbir şey
değişmedi.

## 1. Neden

Ne İzlesem'deki duygu paneli (0.6.5) eşleşmeyi yalnızca **senin kendi**
işaretlerinde arıyordu. "Güldürsün" seçtiğinde gelen animeler, zaten
izleyip "Güldürdü" dediklerindi: öneri aracı yeni bir şey önermiyordu.
Çevrimiçi kurulumda diğer üyelerin işaretleri vardı (detay sayfasındaki
duygu dağılımı 1.1.45'ten beri bunları gösteriyor) ama öneri bunları
kullanmıyordu.

## 2. Tüm üyelerin işaretleri

Çevrimiçi (çok kullanıcılı) kurulumda duygu kepçesi artık herkesin
işaretlerinden çeker:

- **Puan değişmedi:** eşleşen her duygu 1 puandır, cümlelerle aynı birim.
  Beş üyenin "Güldürdü" dediği bir anime, iki kritere uyan bir animenin
  önüne geçmez.
- **Eşitlikte işaret sayısı:** aynı puandaki animelerde daha çok işaret
  alan üstte durur (izlenmemiş olanlar izlenenlerden önce gelme kuralı
  yerinde).
- **Rozette sayı:** eşleşen duygu rozeti kaç üyenin işaretlediğini
  gösterir ("Güldürdü · 3"). Sayılar anonimdir; kimin ne işaretlediği
  görünmez. Bu, detay sayfasındaki dağılım satırının zaten gösterdiği
  bilgidir.
- **Panel yeni üyeye de açılır:** eskiden hiç işareti olmayan kullanıcı
  duygu paneli yerine "henüz işaret koymamışsın" notu görüyordu. Artık
  herhangi bir üyenin işareti varsa panel açılır; misafirler de
  kullanabilir. Hiç kimse işaret koymamışsa not buna göre yazılır.
- Panelin altına tek satır açıklama geldi: "Tüm üyelerin işaretleri
  sayılır; kimin ne işaretlediği görünmez."

## 3. "Yalnız başlamadıklarım" kutusu

Çevrimiçi kurulumda, giriş yapmış üyeler için formda yeni bir kutu var.
**Varsayılan olarak işaretlidir:**

- İzlediğin, izlemekte olduğun, beklettiğin ya da bıraktığın animeler ve
  bölüm saydığın her anime sonuçlardan çıkar. "İzlemeyi planlıyorum"
  durumundaki ve durumu seçilmemiş (bölüm sayılmamış) animeler kalır.
- Kutu hem cümle hem duygu eşleşmelerine uygulanır: soru "sırada ne
  izlesem".
- Sonuçların üstünde kaç animenin gizlendiği yazar ("7 anime listende
  olduğu için gizlendi") ve **Onları da göster** bağlantısı aynı aramayı
  kutu kapalı olarak yeniden açar. Eşleşenlerin hepsi gizlendiyse boş
  sonuç yerine bu açıklama çıkar.
- Tercih kaydedilmez, adreste yaşar; kutu kaldırılıp aranırsa o arama
  gizlemeden yapılır.

Misafirde ve tek kullanıcılı kurulumda kutu görünmez.

## 4. Tek kullanıcılı kurulum

Değişmedi. Orada tek kişinin işaretleri var; duygu araması "bu duyguyu
işaretlediğim animeler" demeye devam eder, rozetlerde sayı çıkmaz, kutu
yoktur.

## 5. Yardım

Ne İzlesem yardımına **Duygularla Öneri** başlığı eklendi (duygu paneli
0.6.5'ten beri yardımda anlatılmıyordu): kepçe olarak duygu, çevrimiçide
tüm üyelerin işaretleri, rozetteki sayı, "Yalnız başlamadıklarım" kutusu,
tek kullanıcılı kurulumdaki anlamı.

## Dosyalar

**Yeni:**

```
files/migration/1.1.51/upgrade.sql     boş (sürüm damgası)
```

**Değişen:**

```
files/recommendations.php              duygu sorgusu (herkes / kendi), başlamadıklarım süzgeci, rozet sayıları, gizlenen satırı
files/help/help_discovery.php          "Duygularla Öneri" başlığı
files/lang/tr.php                      +9 anahtar
files/lang/en.php                      aynı
files/version.txt
```

Dil dosyası eşitliği: 1166 = 1166.

## Dağıtım notu

- **Merkez katalogda iş yok.** Tablo ve indeks 0.6.1'den beri var.
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.1.51'e çekilir ve `updates/1.1.51/anime-tracker-1.1.51.zip` paketi
  yayımlanır.
