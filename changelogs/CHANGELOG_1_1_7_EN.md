# Anime Tracker 1.1.7

**Release date:** 2026-07-12

## Fixed

- **Imported adult (+18) titles are now flagged correctly.** When you import
  your AniList list, titles marked adult on AniList are now also flagged +18 in
  the catalog. Those titles then respect the "hide adult content" preference and
  the +18 filter.
  - The previous version (1.1.6) did not read this information, so adult titles
    could enter the catalog unflagged (visible). 1.1.7 closes that gap.
  - The +18 flag is carried both on a personal (self-host) direct add and on the
    online catalog-suggestion -> moderator-approval path.

- **Large AniList lists now import reliably.** Because of AniList's per-minute
  request limit, importing a very large (hundreds of entries) list could stop
  midway with a "rate limit reached" error. Page requests are now paced under
  the limit, and if the limit is hit the import waits briefly and retries the
  same page instead of stopping, so large lists finish. (As a result, importing
  a very large list may take a little longer.)

## Fixing titles imported earlier (optional)

- Titles you imported with AniList BEFORE 1.1.7 may have been left unflagged. A
  command-line tool corrects them in one pass:

  ```
  php anilist_isadult_backfill.php > isadult_backfill.sql
  ```

  The tool changes nothing in the database; it only asks AniList and generates an
  `UPDATE` SQL statement for the adult titles. You review the generated file and
  run it in your database manager (e.g. phpMyAdmin).

## Notes

- This release has a real schema change: an +18 flag column is added to the
  online catalog-suggestion queue (migration/1.1.7). The migration runs
  automatically; no manual action is needed on the central catalog server.
- The +18 flag on the anime table already existed; what changed is that the
  import path now fills it in.

## Changed files

- functions/anilist_import_helpers.php
- list_settings.php
- admin/admin_catalog_requests.php
- anilist_isadult_backfill.php (new, one-time tool)
- schema.sql
- migration/1.1.7/upgrade.sql (new)
- version.txt
