


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
 .env dosyasını aç, DB_PASS ve DB_ROOT_PASS şifrelerini DEĞİŞTİR
docker compose up -d

Tarayıcıdan http://localhost:8080 (veya .env'de APP_PORT değiştirdinse o port).





Anime Tracker is a self-hosted anime tracking application that lets you build a personal anime list, edit entries, follow ongoing broadcasts, and add personal notes per anime. (The "personal note per anime" feature was the original reason this project exists :))

It runs as a local web site. There are two installation options.

**Languages:** [Türkçe](README.md) · English (this file)

## Features

- Anime list with editing and watch status tracking
- Broadcast tracking: stay aware of new episodes
- **Emotion tags** (0.6.1+): instead of scoring, mark how an anime made you feel (Saddened, Made Me Laugh, Thought-provoking...)
- Chronology markers: notes like "after episode N of this anime, switch to that one"
- Personal notes per anime
- Fully local — your data belongs to you

## Installation

### Option 1: Windows (easy)

[Download the Windows installer](https://drive.proton.me/urls/XQ92P0KM3R#tzPRSMRrUrCB), then run it.

**Windows SmartScreen warning:** You may see "Windows protected your PC". This is normal — the binary is unsigned. Click "More info" → "Run anyway" to continue.

After installation, open <http://localhost/anime_tracker/>.

### Option 2: Docker (Linux / macOS / Windows)

```bash
git clone https://github.com/hitsumo/animetracker
cd animetracker/AnimeTracker
cp .env.example .env       # Linux/macOS
copy .env.example .env     # Windows
# Open .env and CHANGE DB_PASS and DB_ROOT_PASS to strong random values
docker compose up -d
```

Open <http://localhost:8080> in your browser.

If XAMPP or another system is using port 8080, set `APP_PORT=8090` in `.env` to pick a different port.

## Links

- [Website](http://www.sicakcikolata.com)
- [Release notes](https://github.com/hitsumo/animetracker/releases)
- [AI usage notice](AI_NOTICE_EN.md)

## License

GPL v2 — see [LICENSE.txt](LICENSE.txt)
