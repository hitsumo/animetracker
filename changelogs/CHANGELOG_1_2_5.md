# Anime Tracker 1.2.5

**Yayın tarihi:** 2026-10-05

Ana iş: **hesap silme.** Çevrimiçi kurulumda yönetici bir üyeyi Kullanıcı
Yönetimi sayfasından silebiliyor, üye de kendi hesabını Hesap sayfasından
silebiliyor. Yanında: silinen ya da askıya alınan bir üyenin açık oturumu
artık bir sonraki istekte kapanıyor; Hakkında sayfasına veri kaynakları ve
AniDB atfı eklendi. Şema değişikliği yok (boş migration); merkez katalogda
yapılacak bir şey yok. Tek kullanıcılı kurulumda hesap silme ve oturum
denetimi yok; Hakkında sayfasındaki not orada da görünür.

## 1. Neden

Gizlilik sayfası "hesabını sildirmek için yaz" diyordu, ama uygulamada hesap
silmenin bir yolu yoktu: yönetici bir üyeyi yalnızca askıya alabiliyordu,
silme işi veritabanında elle yapılması gereken bir işti.

## 2. Silinen ve kalan bilgiler

Silme tek seferde yapılır; bir adım başarısız olursa hiçbir şey silinmez.

- **Silinir:** hesap, liste (izleme durumları, bölümler, notlar, Kişisel
  Konu), izleme günlüğü, liste ayarları, AniList içe aktarma kayıtları ve
  üyenin e-posta adresiyle bırakılmış davetiye talepleri.
- **İsimsiz kalır:**
  - **Duygu işaretleri** anime sayfalarındaki ve Ne İzlesem'deki isimsiz
    sayımlarda kalır; artık hiçbir hesaba bağlı değildir. Sayılar ve
    "kaç kişi işaretledi" bilgisi değişmez.
  - **Düzeltme önerileri** kalır; gönderenin adı ve IP adresi silinir.
  - **Katalog istekleri** (liste içe aktarırken oluşanlar ve üyenin
    incelediği istekler) kalır; kimin önerdiği ya da incelediği boşalır.
  - **Kara liste kayıtları** kalır; kimin eklediği boşalır.
- **Davet kodları:** üyenin kayıt olurken kullandığı kod "kullanıldı"
  olarak kalır ve e-posta adresi silinir. Kod yeniden kullanılabilir hâle
  gelmez. Üyenin ürettiği kodlar geçerli kalır, yalnızca kimin ürettiği
  boşalır.

## 3. Yöneticinin silmesi (Kullanıcı Yönetimi)

- Her satırda katlanmış bir **Sil** kutusu var. Açılınca onay için
  kullanıcı adını yazmak gerekir; ad eşleşmezse hiçbir şey silinmez ve
  bunu söyleyen bir uyarı çıkar.
- Yönetici kendi satırında bu kutuyu görmez.
- Kurulumun sahibi hesabı (kimlik 1) silinemez: tek kullanıcılı mod bu
  hesabı kullanır, kurulum o moda geri alınırsa hesap gerekir.
- Sitede tek etkin yönetici kaldıysa o hesap silinemez.

## 4. Üyenin kendi hesabını silmesi (Hesap sayfası)

- Hesap sayfasının altında **Hesabımı Sil** bölümü var. Şifre ve onay için
  kullanıcı adı istenir; neyin silinip neyin isimsiz kaldığı aynı yerde
  yazar.
- Silinince oturum kapanır ve giriş sayfasında "Hesabın silindi." yazar.
- Kimlik 1 ve son etkin yönetici bu bölümde form yerine neden
  silinemediklerini görür.

## 5. Oturum artık her istekte denetleniyor

Hesabın durumu yalnızca giriş yaparken denetleniyordu. Askıya alınan bir
üye açık oturumuyla siteyi kullanmaya devam edebiliyordu. Artık çevrimiçi
kurulumda her istekte hesabın hâlâ var ve etkin olduğuna bakılır; değilse
oturum kapanır ve istek misafir olarak sürer.

## 6. Metinler

- Gizlilik sayfasındaki "Hakların" bölümü kendi kendine silme yolunu ve
  silinince neyin gidip neyin isimsiz kaldığını anlatıyor.
- Yardım → Hesap Sayfası paragrafına Hesabımı Sil bölümü eklendi.
- Türkçe ve İngilizce.

## 7. Veri kaynakları ve AniDB atfı

AniDB'nin kullanım koşulları, sitedeki bilgilerin başka bir hizmette
kullanılmasını **CC BY-NC-SA** lisansına bağlıyor: kaynak belirtilmeli,
ticari amaçla kullanılmamalı ve aynı lisansla paylaşılmalı. Katalogdaki
bilgilerin bir kısmı, anime ilişkileri de dahil, AniDB'den derleniyor; ama
sitede bunu söyleyen bir satır yoktu.

- **Hakkında sayfasına "Veri kaynakları" paragrafı:** katalog bilgilerinin
  birden çok açık kaynaktan ve üyelerin katkılarından derlendiği, bir
  kısmının AniDB'den geldiği, AniDB'den derlenen bilgilerin CC BY-NC-SA ile
  paylaşıldığı ve kodun ayrıca GPL-2.0 lisanslı olduğu yazıyor. AniDB'ye ve
  kullanım koşulları sayfasına bağlantı veriyor. Türkçe ve İngilizce.
- Katalog merkezden her kuruluma gittiği için not her kurulumun Hakkında
  sayfasında görünür.
- Depodaki README'nin "Katalog modeli" bölümüne aynı lisans notu eklendi.

## 8. Nasıl denendi

Test veritabanında her tabloda verisi olan altı test hesabı açıldı ve
gerçek HTTP istekleriyle (giriş, form gönderme, yönlendirmeler) 71 denetim
yapıldı; hepsi geçti. Denenenlerden bazıları:

- Yanlış kullanıcı adıyla silme hiçbir şeyi silmedi.
- Silinen üyenin listesi, günlüğü, ayarları ve AniList kayıtları gitti;
  duygu sayıları ve "kaç kişi işaretledi" değişmedi; önerisi isimsiz ve
  IP'siz kaldı; kayıt olurken kullandığı davet kodu kullanılmış olarak
  kaldı ve yeniden geçerli olmadı; büyük harfle yazılmış e-postasıyla
  bırakılan davetiye talebi de silindi.
- Yönetici kendini Kullanıcı Yönetimi sayfasından silemedi; kimlik 1 başka bir
  yöneticinin eliyle de silinemedi; tek etkin yönetici kalınca silme
  engellendi.
- Üye yanlış şifreyle ve yanlış adla silemedi, doğru bilgilerle sildi ve
  giriş sayfasına düştü.
- Açık oturumu olan bir üye silinince ve başka bir üye askıya alınınca
  ikisinin de oturumu bir sonraki istekte kapandı.
- Sayfalarda PHP uyarısı ve sunucu hatası yok.
- Hakkında sayfasındaki yeni paragraf Türkçe ve İngilizce açıldı,
  bağlantıları doğru; 375 piksel genişlikte paragraf taşma yapmıyor.

## Dosyalar

**Yeni:**

```
files/functions/account_helpers.php      hesap silme (denetim + tek seferlik silme)
files/migration/1.2.5/upgrade.sql        boş (sürüm damgası)
```

**Değişen:**

```
files/functions.php                      account_helpers.php yüklenir; her istekte oturum denetimi
files/functions/auth_helpers.php         silinen / askıya alınan hesabın oturumunu kapatma
files/admin/admin_users.php              Kullanıcı Yönetimi: satır başına Sil kutusu
files/account.php                        Hesabımı Sil bölümü
files/login.php                          "Hesabın silindi." bildirimi
files/about.php                          "Veri kaynakları" paragrafı (AniDB atfı + lisans)
files/lang/tr.php, files/lang/en.php     hesap, giriş, gizlilik, yardım ve Hakkında metinleri
files/lang/admin_tr.php, admin_en.php    Kullanıcı Yönetimi metinleri
files/version.txt
```

Depoda (paket dışı): `README.md` — katalog verisinin lisans notu.

## Dağıtım notu

- **Merkez katalogda iş yok.**
- Migration boştur; ilk sayfa yüklemesinde sürüm damgasını taşır.
- **Dağıtım sunucusunda** her zamanki iki adım: yayımlanan `version.txt`
  1.2.5'e çekilir ve `updates/1.2.5/anime-tracker-1.2.5.zip` paketi
  yayımlanır.
