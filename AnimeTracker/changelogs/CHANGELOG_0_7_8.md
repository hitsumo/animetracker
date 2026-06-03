# Anime Tracker 0.7.8 - Degisiklikler

**Yayin tarihi:** Haziran 2026

## Kurulum ekranlari artik Ingilizce de var

Kurulum sihirbazi ve "kurulum tamamlandi" ekrani simdiye kadar yalnizca
Turkceydi. Bu iki ekran veritabani kurulmadan once calistigi icin
uygulamanin geri kalaninda kullanilan dil mekanizmasi burada
calismiyordu; bu yuzden Ingilizce karsiliklari, AI Kullanim Beyani
sayfasinda oldugu gibi, ayri dosyalar olarak eklendi.

Kurulum ve kurulum tamamlandi ekranlarinin sag ust kosesinde bir dil
baglantisi var: Turkce ekranda "English", Ingilizce ekranda "Turkce".
Ingilizce kurulumu secen bir kullanici, baglanti bilgilerini girmekten
kurulumun tamamlanmasina kadar tum akis boyunca Ingilizce kalir.

Mevcut kurulumlar icin gozle gorulur bir degisiklik yoktur; bu ekranlar
yalnizca ilk kurulum sirasinda gorunur. Fark, uygulamayi Ingilizce
kurmak isteyen yeni kullanicilar icin ortaya cikar.

## Diger

### Sema

Bu surum sema degisikligi icermez. Eklenen ekranlar veritabanina
dokunmayan duz PHP/HTML sayfalaridir. `migration/0.7.8` yalnizca surum
numarasini ilerleten bos bir migration'dir; otomatik guncelleme
sirasinda kendiliginden gecer, elle bir sey yapmaniza gerek yoktur.

### Dosyalar

Yeni: `setup_en.php`, `install_en.php`, `migration/0.7.8/upgrade.sql`.
Degisen: `setup.php`, `install.php` (dil baglantisi eklendi; kurulum
sonrasi silinmesi gereken dosya listesi yeni iki dosyayi da kapsayacak
sekilde guncellendi), `installer.nsi` (.exe kurulumu artik bu iki yeni
dosyayi da otomatik siler).
