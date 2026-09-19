# Anime Tracker - Catalog Server

This directory holds the **central catalog server** endpoints. It is **not** part
of the Anime Tracker web application itself (that lives in the sibling `files/`
directory). Only the operator of the central catalog host
(`animetracker.sicakcikolata.com`) needs to deploy this folder.

A normal install - whether a single-user self-hosted copy or a multi-user online
instance - does **not** run anything in here. Those installs only *consume* the
catalog over HTTP (via `catalog.php` below); they never serve it.

## What's in here

### `catalog.php` - public catalog API (read-only)
A `GET`, JSON endpoint that exposes the curated catalog. Client installs call it
to sync anime metadata (titles, genres, tags, chronology) **without** touching any
user's personal watch progress. Personal columns (watch status, watched episodes,
notes, personal synopsis, next-episode date) are deliberately excluded so no
private data leaks. Only rows with `source = 'catalog'` are exported. Responses are
cached aggressively (`catalog_cache.json`, 1-hour TTL) because the catalog changes
rarely.

### `admin_push.php` - catalog push receiver (write, one-way)
A `POST`, JSON endpoint that receives catalog updates from the curator's local
installation and applies them to the server database. The direction is strictly
one-way: **admin local -> server**. Regular users never call this endpoint.

Security model:
- Shared secret kept in `../private/admin_push_config.php` (outside the web root,
  never committed to git).
- Every request carries an `X-Admin-Signature` header: a hex HMAC-SHA256 over
  `timestamp + "|" + raw body`, verified with `hash_equals` (constant-time).
- The timestamp must be within +/- 300 seconds of server time (replay protection).
- File-based rate limit: roughly one request per 5 seconds per IP.
- HTTPS is assumed (enforced via the host's web-server config / `.htaccess`).

### `ping.php` - install counter (1.1.43)
A `GET`, JSON endpoint with two jobs:

- `ping.php?id=<32 hex>&v=<version>&m=single|multi` - records "this install
  exists". Every client install that has not switched the counter off sends this
  **once**, on first use (`files/functions/install_ping_helpers.php`; retried at
  most once a day until it succeeds, then never again). One row per install id
  (`INSERT IGNORE`); stores the three values plus first-seen. The IP address is
  **not** stored. Per-IP throttle: one accepted ping per 10 s (zero-byte files
  under `private/rate_limit/`, keyed by IP hash).
- `ping.php?stats=1` - public totals, numbers only (total, by mode, by starting
  version). CORS-open, so the "Install Counter" card in any install's admin
  dashboard reads it straight from the browser.

It **writes**, so it loads `../private/admin_push_config.php` (the same DB
credentials `admin_push.php` uses), not the read-only `anime_api_config.php`.
Only the `DB_*` constants are used.

Table - create by hand on the catalog host, once:

```sql
CREATE TABLE IF NOT EXISTS installs (
  install_id  CHAR(32)     NOT NULL,
  first_seen  DATETIME     NOT NULL,
  version     VARCHAR(20)  NOT NULL DEFAULT '',
  mode        ENUM('single','multi') NOT NULL DEFAULT 'single',
  PRIMARY KEY (install_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

Order does not matter: until the table and file exist, clients get a 404/500,
log it, and try again the next day (once a day, until the first success).
Nothing on their pages waits for it.

**If the host uses a default-deny `.htaccess`** (every `.php` blocked unless
whitelisted - the production catalog host does), add the endpoint next to the
`catalog.php` rule, or every ping gets a 403 before PHP runs:

```apache
<Files "ping.php">
    Require all granted
</Files>
```

### `private/` - secrets and runtime state (must stay outside the web root)
Holds the real configuration and runtime files:
- `admin_push_config.php` - shared HMAC secret + database credentials.
- `anime_api_config.php` - database credentials for the catalog API.
- `rate_limit/` - per-IP rate-limit bookkeeping (created at runtime).

Only the `*_example.php` templates are committed to git. The real config files are
git-ignored and must be created by hand on the server (copy the example, rename it,
fill in the secret and DB credentials).

## How to deploy

1. Serve **this folder** as its own document root on the catalog host - e.g. point
   the catalog subdomain/vhost at `catalog_server/` so that requests reach
   `https://animetracker.sicakcikolata.com/catalog.php`, `/admin_push.php` and
   `/ping.php`.
2. Keep `private/` **one level above** that document root. The scripts load their
   config via `__DIR__ . '/../private/...'`, which resolves to the parent of the
   served folder. This keeps secrets unreachable over HTTP.
3. Copy each `*_example.php` in `private/` to its real name and fill in the shared
   secret and database settings.
4. Make sure HTTPS is enforced.

## Not required by

- **Self-hosted (single-user) builds** - the packaged app contains only `files/`.
- **Online (multi-user / members) instances** - they run the `files/` application
  and pull the catalog from this server over HTTP; they do not serve it.

In both cases this directory is a sibling of `files/` and is excluded from the
deployable application set, the same way `docker-compose.yml` and `installer.nsi`
are build-time siblings rather than part of the served app.

---

Part of Anime Tracker. Copyright (C) 2025 Okan Sumer. Licensed under the GNU
General Public License v2.
