# Anime Tracker 0.6.3

**Release date:** 27 May 2026
**Type:** Fix (schema synchronisation + server alignment)

This release ships via auto-update; you do not need to do anything.
Your existing data is untouched - the migration only adds missing
columns and does not modify any existing column or row.

## Fix

- **Two columns added that older installations may have been missing.**
  `end_date` (the air date of the anime's last episode) and
  `user_synopsis` (a second synopsis box where you can write your
  own take) were added to the application some time ago but the
  corresponding migration was never written for existing installs.
  This release closes that gap.

- **The migration is idempotent.** If the columns already exist
  MariaDB reports a "duplicate column" warning, which the migration
  manager recognises and silently skips. So if your local install
  already has them, nothing happens; if you are on an older install,
  the two columns are added.

## Known behaviour

- **`user_synopsis` stays empty.** This column is user-specific and
  is never touched by any sync. If you fill it in on the anime
  detail page it shows up under a "My Take" heading; if you leave
  it empty it stays hidden.

- **`end_date` is useful for completed anime.** You can enter the
  last-episode date on the edit page for anime whose status is
  "Yayin Tamamlandi". For ongoing anime it remains NULL.

## Technical notes

The migration scope of this release is narrow (one file, two ALTERs).
However, a handful of schema-alignment tasks on the server API side
and the admin sync chain were also part of this release. These do
not directly affect self-host users (they are admin- and server-side
code); they are listed here so the release stays on record.

### Migration (shipped to users)

- The 0.6.2 -> 0.6.3 transition is a single migration step: two
  ALTER TABLE statements.
- The schema change touches the animes table only; no other tables
  are affected.
- If a rollback is ever needed (e.g. something goes wrong), the
  columns are nullable so DROP COLUMN restores the previous shape
  cleanly; data loss only matters for users who actually filled
  them in.

### Server API (sicakcikolata.com side, does not affect self-host)

- **`admin_push.php` rewritten.** The old version wrote to a text
  column `animes.genres`; that column was migrated to an
  `anime_genres` join table in 0.6 but the push endpoint was never
  updated to match. Result: admin sync returned HTTP 500. The new
  version mirrors the tag pattern - resolves names to ids in the
  master `genres` table and writes link rows to `anime_genres`
  (idempotent: race-safe `INSERT IGNORE` plus UNIQUE constraint).

- **`admin_push.php` now also writes `end_date`.** UPDATE/INSERT/
  params list updated. Last-episode dates entered locally by the
  admin now flow to the server too.

- **`catalog.php` fixed two bugs at once.** The non-existent
  `genres` column was removed from the SELECT (the previous version
  was silently broken, hidden only by the 1-hour cache TTL). A
  block was added that builds the CSV from a `anime_genres + genres`
  JOIN (wire format unchanged - the local client expects the same
  CSV). `end_date` was added to the SELECT.

### Local admin side

- **`admin_sync.php` SELECT now reads `end_date`.** So that the
  payload sent to the server is complete. Other columns were
  already correct.

### Discipline note for future releases

The findings of this release were recorded as a new entry in KARARLAR
Section 2: a schema change must always be accompanied by a
migration/{version}/upgrade.sql in the same commit. Otherwise new
installs get the correct schema, existing installs are skipped -
which is exactly the half-deploy pattern this release exists to fix.
