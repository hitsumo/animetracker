# Anime Tracker 1.1.3

**Release date:** 2026-07-07

## New

- **18+ flagging for genres and sentences.** Catalog genres and recommendation
  sentences (tags) can now be flagged 18+. While adult content is off, those
  terms are hidden from the interface.
  - **Flagging:** A "18+" checkbox per row on the Genre Management and Sentence
    Management pages. Saving marks the term adult at the catalog level.
  - **Hiding:** While adult content is off, an adult genre drops out of the
    genre filter dropdown on the list, an adult sentence drops out of the
    recommendation sentence picker, and an adult genre badge drops off the
    detail page.
  - **Anime rows are unaffected:** Flagging a term 18+ does not hide the anime
    that carries it. Anime visibility is still governed by the adult-anime flag
    from 1.1.2. An anime that carries an adult genre or sentence but has no flag
    of its own stays in the list; only that term is hidden.
  - **Single toggle:** The existing "Show adult content" preference now governs
    anime, genres and sentences together. No separate preference was added.
  - **Catalog sync:** The 18+ flag for genres/sentences travels via catalog
    push/import (as a separate name-keyed map). Only flagged terms are sent; a
    sync from an older side that carries no map does NOT clear a local flag
    (once adult, it stays adult until cleared locally).

## Notes

- Schema changed: an is_adult column was added to the genres and tags tables
  (patch release, real migration). Existing rows start at 0 (not adult).
- The 18+ flag can be toggled by a moderator/admin (multi-user) or the operator
  (single-user) from the Genre/Sentence Management pages.
- The central catalog server owns the genre/sentence 18+ flag through its own
  management pages; an upward push does not carry this metadata.

## Changed files

- schema.sql
- migration/1.1.3/upgrade.sql (new)
- functions/anime_helpers.php
- functions/taxonomy_helpers.php
- manage_genres.php, manage_tags.php
- index.php, recommendations.php, anime_details.php
- catalog.php, catalog_import.php, catalog_push.php, admin_sync_example.php
- lang/tr.php, lang/en.php
- version.txt
