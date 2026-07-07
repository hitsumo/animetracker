# Anime Tracker 1.1.2

**Release date:** 2026-07-05

## New

- **Adult (18+) content hiding.** Catalog anime can now be marked 18+, and such
  anime are HIDDEN BY DEFAULT; they only appear if the viewer turns them on in
  List Settings.
  - **Flagging:** An "18+ / Adult content" checkbox on the Add / Edit Anime form
    marks an anime as adult in the catalog.
  - **Toggle:** A "Show adult content" option in List Settings. Off by default.
    While off, 18+ anime are hidden from lists, search, recommendations,
    statistics and the detail page; turning it on reveals them.
  - **Per user:** The preference is personal. In a multi-user install one user's
    setting does not affect anyone else; in a single-user install it is the
    owner's own choice. Hiding by default protects a user who does not want such
    content (or a guest looking at a shared screen).
  - **Ordered relations:** In the chronology timeline and the series chain, an
    18+ node is shown as a neutral placeholder ("Hidden content") that keeps the
    structure intact without revealing its title.
  - **Detail page:** Opening the direct link of a hidden 18+ anime does not leak
    the page; a neutral notice explains how to enable it.
  - **Catalog sync:** The 18+ flag travels with catalog push/import, so it stays
    consistent across installs. A record arriving from an older side that does not
    carry the flag falls back to the safe value (not adult).

## Notes

- Schema changed: an is_adult column was added to the animes table (patch
  release, real migration). Existing anime are unaffected; the new column starts
  at 0 (not adult).
- The default behaviour is to hide; showing is a deliberate opt-in.
- The adult flag can be toggled by a moderator/admin (multi-user) or the operator
  (single-user).

## Changed files

- schema.sql
- migration/1.1.2/upgrade.sql (new)
- set_adult_pref.php (new)
- functions/anime_helpers.php
- functions/series_helpers.php
- add_anime.php, edit_anime.php
- index.php, recent.php, recommendations.php, statistics.php, anime_details.php
- chronology.php, series_timeline.php
- list_settings.php
- catalog.php, catalog_import.php, catalog_push.php, admin_push.php, admin_sync_example.php
- lang/tr.php, lang/en.php
- version.txt
