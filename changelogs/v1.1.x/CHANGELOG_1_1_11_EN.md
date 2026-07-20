# Anime Tracker 1.1.11

**Release date:** 2026-07-15

## New: AniList import source limit

- **In online (multi-user) mode, a normal member may import from at most 3
  DIFFERENT AniList accounts.** This stops a single member from pulling an
  unbounded number of other people's public lists and flooding the moderation
  queue / catalog.
  - **The same account can be re-synced without limit.** An account you have
    already imported can be pulled again as often as you like without spending
    a new slot (a legitimate case: keep using AniList and come back later to
    re-import).
  - **A slot is consumed only on a real, successful import.** A mistyped name,
    previewing and cancelling, or an error never burns a slot; you can preview
    as much as you like.
  - Usernames are case-insensitive: `Mahmut`, `mahmut` and `MAHMUT` are one
    slot.

## Exemptions

- **Self-host (single-user) installs are never limited** - the sole owner may
  import from as many different names as they want.
- **Moderators and above are exempt** - bulk seeding is their legitimate job.

## Admin controls

- **The limit is configurable.** On the Admin Capabilities page, the "AniList
  import source limit" field sets the number of different accounts (default 3).
  **Setting 0 removes the limit (unlimited)** - an emergency off-valve.
- **An admin can reset a user's limit.** On the User Management page, every
  member who has used sources has a "Reset" button on their row; it clears that
  user's recorded sources so they regain the full allowance.

## How it works (technical)

- A new app-side table `anilist_import_sources` records the DISTINCT AniList
  names each member has used (one row per user, `(user_id, username)` UNIQUE).
  Used-source count = that user's row count.
- The check has two lines: at preview time (WITHOUT hitting AniList, no wasted
  call) and at import time (second defense + recording).
- The limit value lives in the `settings.anilist_import_source_limit` key; it
  needs no dedicated schema column.
- **No central-catalog impact** - this table is app-side only and never reaches
  the central catalog server; no manual step is required there.

## Improvement: Long dropdowns capped at 8 rows

- **Long dropdowns (more than 8 options) now show at most 8 rows, the rest
  scrolling.** For example the main list's "Filter by Genre" and the long
  selects on the form/detail pages used to cover almost the whole screen when
  opened; they are now compact, scrollable menus.
- **Applies site-wide:** the rule is not tied to one menu - every native
  `<select>` with more than 8 options is capped automatically; short menus
  (<=8) stay native.
- On desktop (mouse) the native `<select>` is enhanced into a compact menu;
  the selection is still written to the same form field (submit/filtering
  behavior unchanged). Touch devices keep the native picker. With JS off, the
  native list keeps working.
- `required` menus keep working (the native select is hidden but stays
  focusable, so browser validation is not broken). A very special menu (e.g.
  the genre-ADD picker that resets its own value) can be exempted with
  `data-no-enhance`.

## Security / deployment: main-app .htaccess

- **Added a dedicated `.htaccess` for the main-app document root (`files/`).**
  It 301-redirects http to https and sends HSTS, so the session cookie (written
  with the `Secure` flag in db.php over HTTPS) is always sent - removing the
  "logged in over https but logged out over http" symptom.
- Also: directory listing off, sensitive files denied (config.php, *.sql,
  *.md/*.txt, dotfiles), no web access to include/tooling dirs (functions/,
  migration/, tek_kullanimlik/), and no PHP execution under uploads/. PHP is
  NOT default-denied (the app's endpoints stay public) - this differs from the
  CENTRAL CATALOG server's default-deny `.htaccess`; the two must not be mixed.

## Schema / migration

- `migration/1.1.11/upgrade.sql` creates the new table (idempotent,
  `CREATE TABLE IF NOT EXISTS`). Fresh installs get it from `schema.sql`.

## Changed / new files

- functions/anilist_import_helpers.php (limit helpers)
- list_settings.php (preview + import limit checks, source recording)
- admin/admin_capabilities.php (global limit setting)
- admin/admin_users.php (per-user limit reset)
- lang/tr.php, lang/en.php, lang/admin_tr.php, lang/admin_en.php
- schema.sql (anilist_import_sources table)
- migration/1.1.11/upgrade.sql (new)
- js/select_enhance.js (new - generic dropdown enhancer)
- css/components.css (custom dropdown styles)
- index.php, add_anime.php, edit_anime.php, list_settings.php,
  anime_details.php, admin/admin_users.php, admin/admin_invites.php
  (select_enhance.js script tag; data-no-enhance on the add/edit genre-add
  picker)
- .htaccess (new - main-app docroot: HTTPS enforcement + HSTS + hardening)
- version.txt
