


![apple-touch-icon](https://github.com/user-attachments/assets/6ec42336-7231-4175-95be-01085b0d4e28)



Anime Tracker bir anime listesi oluşma düzenleme ayrıca eklediğiniz animeye kişisel not ekleme (  anime listesine not ekleme özelliği bu projeyi oluşturma nedenim :) ) ve listede devam eden animelerin yeyın takibini yapan bir programdır.
Bu program web sitesinin localhostta çalışması için gerekli xammp programını kurup apache ve mysql  servislerini başlatacaktır.
Ardından veritabanı ve websitesi için gerekli dosyaları oluşturacaktır.

Kurulum bittikten sonra tarayıcınızdan http://localhost/anime_tracker/  adresine gitmeniz yeterli olacak.

ÖNEMLİ 

**Windows SmartScreen Uyarisi:**
Kurulum sırasında "Windows protected your PC" uyarısını görebilirsiniz.
Uygulama imzalı olmadığı için normaldır.
"More info" -> "Run anyway" diyerek devam edin.

<a href="https://drive.proton.me/urls/XQ92P0KM3R#tzPRSMRrUrCB">Windows exe</a>

www.sicakcikolata.com

## Docker ile kurulum

Linux, macOS veya Docker Desktop kurulu Windows için:

git clone https://github.com/hitsumo/animetracker
cd animetracker/AnimeTracker
cp .env.example .env       # Linux/macOS
copy .env.example .env     # Windows
# .env dosyasini ac, DB_PASS ve DB_ROOT_PASS sifrelerini DEGISTIR
docker compose up -d

Tarayicidan http://localhost:8080 (veya .env'de APP_PORT degistirdinse o port).
