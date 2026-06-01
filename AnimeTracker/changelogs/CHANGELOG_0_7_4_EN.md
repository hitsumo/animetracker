# Anime Tracker 0.7.4 - Changelog

**Release date:** May 2026

## List backup (export/import) now works correctly

The JSON export/import on the List Settings page has been fixed.

- **Exported backups now include genres and tags (sentences).** Previously the
  exported JSON carried only the basic anime fields; genre and tag links were
  never saved. Now each anime's genres and tags are written to the backup too.
- **Import now works.** Previously, trying to restore a backup errored out and
  stopped halfway. The backup now restores cleanly, and genres and tags are
  re-created as well.
- **Summary after import.** When the import finishes, an info message shows
  "X anime imported, Y skipped".

Import behaves as a **restore**: it adds your backup back into your list. Before
restoring the same backup a second time, it is recommended to empty the list
with "Clear List"; otherwise the same anime may be added again.

## "Clear List" fixed

The "Clear List" action was also fixed - previously it could fail to clear the
list and leave it untouched. It now properly clears the list and its genre/tag
links. The master list of genre and tag names is preserved.

## Statistics - Total Episodes count

On the Statistics page, next to "Total Watched Episodes" there is now a
**Total Episodes** count: the sum of the known episode counts of all anime in
your list. Anime whose episode count is not yet known (undetermined / ongoing)
are not included in this total.

## Other

### Schema

This release contains no schema change. `migration/0.7.4` is an empty migration
that only advances the version number; it runs automatically during auto-update
and requires no manual action.

### Files

Changed: `list_settings.php`, `statistics.php`, `tr.php`, `en.php`.
New: `migration/0.7.4/upgrade.sql`.
