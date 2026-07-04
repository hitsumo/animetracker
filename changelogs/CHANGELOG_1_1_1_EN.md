# Anime Tracker 1.1.1

**Release date:** 2026-07-03

## New

- **MyAnimeList list import (Phase 1).** A new section embedded in the
  List Settings page: upload your MyAnimeList export file (XML or gzip'd
  .gz) and import your list. The flow has two steps: a preview (dry-run)
  is shown first, and nothing is written until you confirm.
  - Matching is done by MAL id. For anime that match the catalog, your
    personal watch status, watched-episode count, start/finish dates and
    note are written to your own record.
  - The preview has per-status checkboxes (all checked by default); you
    can leave out statuses you do not want (for example a large
    "Plan to Watch" pile).
  - The default is "skip entries already in my list", with an optional
    "overwrite" choice. When overwriting, only the fields MAL provided are
    written, so an existing note or date is never blanked out.
  - Anime not in the catalog are sent as catalog suggestions online, or
    added locally in self-host.

## Notes

- Status mapping: Watching -> Watching, Completed -> Watched,
  On-Hold -> On Hold, Dropped -> Dropped, Plan to Watch -> Plan to Watch
  (numeric 1/2/3/4/6 are also accepted).
- The watch score is not imported.
- No schema change; this is a patch release. Your existing watch data is
  not affected.

## Changed files

- functions/mal_import_helpers.php (new)
- functions.php
- list_settings.php
- lang/tr.php, lang/en.php
- version.txt
- migration/1.1.1/upgrade.sql (new, no-op)
