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
   `https://animetracker.sicakcikolata.com/catalog.php` and `/admin_push.php`.
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
