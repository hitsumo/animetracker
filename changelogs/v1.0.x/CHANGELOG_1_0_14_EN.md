# Anime Tracker 1.0.14

**Release date:** 16.06.2026

## Improvements

- Chronology notes are now included in list export/import. A backup taken
  with List Settings -> Export now also carries each anime's chronology
  notes, and Import restores them. Previously the backup contained no
  chronology notes, so they were lost when moving a list to another install
  or restoring it.

  A line was added to the import result message. When the backup contains
  notes, the message reads for example "84 anime imported, 0 skipped. 21
  chronology note linked, 0 skipped." Older backups without notes do not
  show this extra line.

  For a chronology note to be linked, both anime it points to must exist on
  the target install. If the related anime is missing, that note is skipped
  and counted; the rest of the import completes normally.

## Notes

- No database schema change.
- Interface language updated for both TR and EN.
