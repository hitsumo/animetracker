# Anime Tracker 1.1.8

**Release date:** 2026-07-12

## Improvement

- **Saving an anime is now much faster.** When you add or edit an anime, the
  app used to resend the ENTIRE catalog to the central server; as the catalog
  grew (thousands of animes) this made saving wait on a full resync. Now only
  the relevant anime's **series** (rows sharing the same series name) is sent,
  or just that one anime when it has no series name. So saving pushes a handful
  of records instead of the whole catalog.

## For administrators

- **"Push Entire Catalog" button on the edit page (admin only).** Since the
  normal "Update" now sends only the relevant series, an admin who wants to
  resend the whole catalog to the server at once uses this button. (The
  existing manual push tool only worked on localhost; this button works online.)

## Notes

- No schema change in this release.
- Bulk approval (promoting pending items) and adding a chronology note still
  send the full catalog - these are infrequent and need a full resync.

## Interface

- List/detail buttons ("Anime List", "Anime Details", etc.) changed from blue
  to teal.

## Changed files

- admin/catalog_push.php
- edit_anime.php
- add_anime.php
- lang/tr.php, lang/en.php
- css/list.css
- version.txt
- migration/1.1.8/upgrade.sql (new, no-op)
