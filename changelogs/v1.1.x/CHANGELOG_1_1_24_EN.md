# Anime Tracker 1.1.24

**Release date:** 2026-07-30

## Fixed: broken-looking pages after an upgrade

- **Layouts no longer break after a version upgrade.** Style and script
  files were linked with URLs that never changed (`style.css`,
  `js/select_enhance.js`). Because the URL stayed the same, the browser kept
  serving the **old** file from its cache even after an upgrade: new HTML met
  old CSS. That is exactly what produced the broken layout seen right after
  the 1.1.20 deploy.
- **Every local style and script URL now carries a version stamp**
  (`css/base.css?v=1.1.24`). The moment the version changes, so does the
  URL, and the browser has to refetch. No Ctrl+F5, no manual cache clearing
  after an upgrade: the page itself is not cached, so the new HTML arrives on
  the first load, sees the stamped URLs and pulls fresh CSS.
- **This does not make pages slower.** Within one version the URLs are
  stable, so the files stay cacheable indefinitely just as before — they are
  refetched only when the version moves.
- **Stylesheet modules are now linked one by one.** `style.css` is only a
  loader; the real rules live in the eight modules under `css/` and are
  pulled in with `@import`. Stamping the loader alone would not have helped:
  the browser re-reads `style.css`, sees the same unstamped `@import` URLs
  and serves `css/components.css` from cache anyway. Pages now emit one
  stamped link per module, in the loader's exact `@import` order — so the
  visual result is unchanged.

## Fixed: a help page requested a stylesheet that does not exist

- The Help → Time Zone subpage still linked `help.css`, a file that has not
  existed since the 0.6.7 file split; the request 404'd on every load. The
  link is gone. Nothing changes visually — those rules already came from the
  `css/help.css` module.

## How it works (technical)

- New `files/functions/asset_helpers.php`: `asset_version()` reads the stamp
  from `files/version.txt` — **not** from the `settings.version` row, because
  the things being stamped are FILES and must be paired with the file
  version; it also works with no database at all, which the setup pages need.
  The value is validated before use, since it ends up in a URL; anything
  unexpected counts as "no version".
- `asset_styles()` renders a page's whole set of stylesheet links,
  `asset_url()` builds a single asset URL. Both take the calling page's path
  to the `files/` root: empty for pages in the root, `../` for those under
  `admin/` and `help/`.
- **The module list is read from style.css's `@import` lines at runtime.** No
  second list is kept in sync: adding a module still means adding one
  `@import` to `style.css` and nothing else. `style.css` also remains a
  working stylesheet on its own.
- The degraded paths are covered: if `version.txt` cannot be read, the
  asset's own modification time is used as the stamp (so a dev checkout still
  serves fresh files), and if `style.css` cannot be read the page falls back
  to the pre-1.1.24 behavior and links the loader — an unstyled page is never
  a possible outcome.
- The helper is registered in the `functions.php` loader; the three pages
  that do not load it (`setup`, `install`, `ai_notice` and their English
  twins) require the helper directly. It is deliberately self-contained — no
  database, no i18n, no config — so it works before the app is installed.

## Schema / migration

- **No schema change.** The change is entirely in the presentation layer: no
  new table, column or preference. `migration/1.1.24/upgrade.sql` only
  carries the version stamp.
- **No manual work needed on the central catalog server** — the catalog wire
  was not touched.

## Changed / new files

- files/functions/asset_helpers.php (**new** — stamp, asset URLs, module
  list)
- files/functions.php (registers the helper in the loader)
- files/style.css (comment only: notes that the `@import` list is now read
  as the module list too)
- files/help/help_timezone.php (removed the 404ing `../help.css` link)
- Every page with a style/script link in its head (41 files):
  about, account, add_anime, ai_notice, ai_notice_en, anime_details,
  chronology, edit_anime, filler_edit, help, index, install, install_en,
  list_settings, login, logout, manage_genres, manage_tags, pending,
  recent, recommendations, register, request_invite, series_timeline,
  setup, setup_en, statistics; under admin/: admin, admin_capabilities,
  admin_catalog_requests, admin_invites, admin_pending, admin_suggestions,
  admin_sync_example, admin_users; under help/: help_basics,
  help_discovery, help_fields, help_series, help_sync, help_timezone
- files/migration/1.1.24/upgrade.sql (new, version stamp only)
- files/version.txt

## Deployment note

- `files/functions/asset_helpers.php` is a **new** file and is loaded on
  every page through `functions.php`. If it does not reach the server, pages
  die on the first helper call — so verify the file upload is complete for
  this release.
- The contents of `css/` and `js/` are unchanged; the stamp rides in the URL,
  so those files need no edits.
