# Anime Tracker

A self-hosted anime watchlist application. Track what you watch,
what you plan to watch, which episode you're on, when the next
episode airs, and how series, movies and OVAs fit together in
chronological order.

Built with PHP and MariaDB/MySQL, designed to run on a local
XAMPP/WAMP/MAMP installation, or on any shared hosting that
supports the same stack.

**Website:** https://www.sicakcikolata.com
**Repository:** https://github.com/hitsumo/animetracker
**Author:** Okan Sumer
**Licence:** GNU General Public License v2

---

## What it is

- A personal anime watchlist, in Turkish
- Tracks watch status (Watched / Watching / Planned)
- Tracks aired and watched episode counts separately, so
  ongoing series like One Piece make sense
- Broadcast day / time / timezone per series, so the
  "next episode" countdown works from any user location
- Series grouping: a franchise like Detective Conan groups
  its TV seasons, movies, OVAs and specials together
- Chronology markers: "after episode 23, watch Movie 1"
  style watch-order hints
- Genre management, statistics, letter filter, per-page view,
  import/export, auto-update, and more

## What it isn't

- Not a streaming service. It does not play video or link
  to pirate sites.
- Not a social network. There are no followers, reviews,
  or public profiles.
- Not multi-user (yet). The offline version is designed
  for a single user per installation. A multi-user online
  version is on the long-term roadmap.

---

## Installation

Three paths are available - pick the one that fits your situation.

### Path 1: Windows installer (.exe) - the easy way

**For users who don't want to set up XAMPP themselves.**

1. Download `AnimeTracker-v0.5.exe` (~150 MB) from the
   official distribution link:
   https://drive.proton.me/urls/XQ92P0KM3R#tzPRSMRrUrCB
2. Run it (accept the admin prompt)
3. The installer checks for an existing XAMPP installation:
   - If XAMPP is present, it's used as-is
   - If not, XAMPP is installed silently
4. Apache and MySQL are registered as Windows services and started
5. The application files are copied to
   `C:\xampp\htdocs\anime_tracker\`
6. The database is created and the schema is loaded
7. `setup.php` and `install.php` are removed automatically
8. Open `http://localhost/anime_tracker` in your browser

### Path 2: Manual installation - for your own web server

**For users who already have LAMP/XAMPP/WAMP/MAMP or shared hosting.**

1. Copy the contents of the `files/` folder into your web root
   (for XAMPP this is `C:\xampp\htdocs\anime_tracker\`)
2. Open `http://your-domain/anime_tracker/` in your browser
3. You will be redirected to `setup.php` automatically
4. Fill in the database form:
   - **Host** - usually `localhost`
   - **Database name** - anything you like, `anime_tracker`
     is the default
   - **User / Password** - a MySQL user that can create the
     database. On a fresh XAMPP install this is `root` with
     no password.
5. Submit. The database is created (if needed), a `config.php`
   file is written, and you're redirected to `install.php`
6. The schema is loaded and default genres are inserted
7. **Important:** delete `setup.php` and `install.php` after
   successful installation. The install page reminds you.
8. Click "Go to main page" and start adding anime

### Path 3: Docker - for Linux / macOS / VPS deployments

**For users who already know Docker, or who want a clean,
reproducible install on any OS.**

Requirements: Docker 20.10+ and Docker Compose v2 (both
bundled with Docker Desktop).

1. Clone or download the project and enter its folder
2. **Important:** open `docker-compose.yml` and change the
   two placeholder passwords:
   - `MARIADB_ROOT_PASSWORD` (root password of the DB server)
   - `MARIADB_PASSWORD` and `DB_PASS` (must match; this is
     the app user's password)
3. Start everything:
   ```
   docker compose up -d
   ```
4. The first build takes a minute or two. On the first run:
   - MariaDB container initialises and loads `schema.sql`
     automatically
   - The app container generates `config.php` from
     environment variables (via `docker-entrypoint.sh`)
5. Open `http://localhost:8080` in your browser

#### Docker caveats and tips

- **Persistent data:** two named volumes (`db_data` and
  `uploads`) keep your database rows and cover images safe
  across container restarts. Plain `docker compose down`
  leaves them intact. `docker compose down -v` **deletes
  them** - don't run this unless you want a clean slate.
- **Schema on first boot only:** `schema.sql` is only applied
  to an empty database. If you already started the stack
  once and then changed `schema.sql`, the changes won't take
  effect unless you recreate the volume
  (`docker compose down -v`, then up again) or run the SQL
  manually via `docker exec`.
- **Line endings on Windows:** `docker-entrypoint.sh` must
  use Unix (LF) line endings, not Windows (CRLF), or bash
  will refuse to run it. The `Dockerfile` installs and runs
  `dos2unix` during build so this is handled automatically,
  but if you edit the script with a Windows editor be sure
  to save it as LF.
- **Catalog sync from inside Docker:** the application's
  "Import from Catalog" feature makes an HTTPS request to
  `animetracker.sicakcikolata.com`. This works out of the
  box in Docker, but requires the container to have
  outbound internet access (the default).
- **Port collisions:** the default exposed port is 8080.
  If something else uses 8080 on your host, edit
  `docker-compose.yml` and change the `ports` line to
  something like `"8888:80"`.
- **Shared hosting note:** Docker is not the right choice
  for shared hosting. Use Path 2 (manual) instead.

---

## First steps after installation

- **Add your first anime:** click "Add Anime" on the main list
  and fill in title, status, episode count, genres, and broadcast
  details if the series is ongoing.
- **Import from the catalog:** go to List Settings → "Import
  from Catalog" to pull a curated list of anime from the central
  catalog at sicakcikolata.com. Your personal data (watch status,
  episode progress, notes) is never touched.
- **Automatic updates:** go to List Settings → "Check for Update".
  If a new version is available, the application will download
  and apply it in-place, WordPress-style. Your database and
  uploaded cover images are preserved.

---

## Technical notes

- **Requires:** PHP 7.4+ (8.x recommended), MariaDB 10.3+ or
  MySQL 5.7+, UTF-8 (utf8mb4) database collation
- **Uses:** PDO with prepared statements, CSRF tokens on all
  forms, whitelist file-upload validation with MIME sniffing
- **Storage:** all user data lives in the local database; cover
  images are stored in the `uploads/` folder which is protected
  by an `.htaccess` file that disables PHP execution
- **Timezones:** the server always computes in UTC; each anime
  carries its own broadcast timezone (default Asia/Tokyo) so
  countdowns work correctly no matter where the user is

---

## Getting help

- Project homepage: https://www.sicakcikolata.com
- Source code and issues: https://github.com/hitsumo/animetracker
- Bug reports and feature requests: please use GitHub Issues
  at the repository link above, or the contact form on
  the project homepage
- Licence text: see `license.txt` for the full GPL v2

---

## Building from source

If you prefer to build the installer yourself instead of
downloading the pre-built `.exe`, both the NSIS installer
and the Docker image can be rebuilt from the source tree.

### Windows installer (`.exe`)

1. Install NSIS 3.x from https://nsis.sourceforge.io/Download
2. Download the XAMPP installer for Windows x64 (tested with
   8.2.12) from https://www.apachefriends.org/download.html
3. Place the XAMPP installer in the project root, next to
   `installer.nsi`. The file name should match the
   `XAMPP_INSTALLER` define at the top of `installer.nsi`
   (update that line if your version differs).
4. Right-click `installer.nsi` and choose
   "Compile NSIS Script"
5. The output is `AnimeTracker-v{version}.exe` in the
   project root, where `{version}` is automatically read
   from `files/version.txt`

The resulting `.exe` is self-contained: XAMPP is embedded
inside it, so the user does not need an internet connection
during installation. Expect the output to be ~150 MB.

### Docker image

Build and run in one step with Docker Compose:

```
docker compose up -d --build
```

To rebuild after changing files:

```
docker compose up -d --build --force-recreate
```

---

## Credits

Anime Tracker is written and maintained by **Okan Sumer**.
Build, icons and copy by the same.

This is a hobby project built around a personal need for
a Turkish-language anime tracker with proper broadcast-time
handling and chronology support. It is released as free
software under GPL v2 in the hope that others will find it
useful.
