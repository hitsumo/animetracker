# Anime Tracker 0.6.7 - Changelog

**Release date:** May 2026 (after 0.6.6)

This is an internal code reorganization release. There are NO visible
feature or interface changes; the app behaves and looks exactly like
0.6.6. The goal is to make the codebase easier to maintain for future
development.

## Code modularized

Two large files were split into smaller, purpose-grouped parts:

- `functions.php` (2131 lines) is now a thin loader. All helper
  functions are grouped by purpose into 8 modules under a `functions/`
  folder (translations, watch status, emotion, anime data, security,
  series/chronology, genres/tags, AnimeSchedule).
- `style.css` (1635 lines) is now a thin loader. All styles are grouped
  into 6 modules under a `css/` folder (base, components, list/table,
  series/chronology, emotion, language switcher).

Both loaders keep the original file name, so every page keeps working
with no changes. Function behavior and visual styling are byte-for-byte
identical.

## Other

### Migration

No schema changes in this release. `migration/0.6.7/upgrade.sql` is an
empty step that only bumps the version number.

### i18n

Dictionary unchanged. No new strings/keys in this release.
