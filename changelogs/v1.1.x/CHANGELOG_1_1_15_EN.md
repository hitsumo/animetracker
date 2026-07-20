# Anime Tracker 1.1.15

**Release date:** 2026-07-17

## New: Story order for chronology

- **A chronology marker can now carry two insertion points.** Until now a
  marker only recorded where a related anime (a film, OVA, ...) sits in
  **release order** - the point where it actually aired. A marker can now also
  carry a **story order** point: where it is best watched in the narrative.
  Example: the first Card Captor Sakura film aired after episode 46 but is
  recommended after episode 35.
- **You only fill the second number when it differs.** If a marker's story
  point is left empty it is treated as "same as release", so existing markers
  need no change and appear at the same spot in both orders. You add a story
  point only to the markers that actually diverge.
- **A single button switches the view: release → story → both.** On the anime
  detail page (the chronology notes list) and on the Chronology page, one
  button cycles through showing the release order, the story order, or both
  orders one under the other. Clicking it changes the current view only for
  your session; it does not overwrite your saved default.
- **A saved default in List Settings.** A new "Chronology View" preference
  chooses which order opens by default: release (the default), story, or both.
  It is per-user and affects only you.
- **Both points are editable inline on existing markers.** Next to each row in
  the notes list, a small field adjusts that row's episode: the release-order
  list edits the release point, the story-order list edits the story point
  (empty clears it) - without deleting and re-creating the marker. The two boxes
  are independent; changing one does not move the other.

## Unchanged behaviour

- **The active "watch this next" alert still follows release order only.** The
  reminder that appears on the detail page as you progress is unchanged; the new
  story order is a listing/view feature, not a second alert.

## How it works (technical)

- A new nullable column `chronology_markers.story_after_episode` holds the story
  point. `NULL` means "no divergence"; the story view falls back to the release
  point with `COALESCE(story_after_episode, after_episode)`. The column is
  deliberately **not** part of the marker's UNIQUE KEY, so a catalog re-push
  updates it through `ON DUPLICATE KEY UPDATE`.
- The display mode (release / story / both) is resolved as: an ephemeral session
  override (the cycle button) first, then the saved per-user preference
  (`chrono_display_mode`), then `release`. The button posts to
  `set_chrono_mode.php`; List Settings posts the same endpoint with `persist=1`
  to write the saved default and clear the session override.
- The story point travels through the full catalog wire format, so it is shared
  with all users like the rest of a marker: local push, server store, catalog
  pull/import, the member catalog-request path and admin approval all carry it.

## Schema / migration

- `migration/1.1.15/upgrade.sql` adds the `story_after_episode` column to
  `chronology_markers` (a single `ALTER TABLE`; a duplicate-column re-run is
  ignored) and advances the version to 1.1.15.
- **Central catalog manual step (online only):** the central catalog database is
  a separate install and has no automatic migration runner. Before pushing story
  points to it, run once on the central catalog DB:
  `ALTER TABLE chronology_markers ADD COLUMN story_after_episode INT NULL AFTER after_episode;`
  Until this is done the server keeps every story point as NULL; self-host
  installs are unaffected (their local migration runs automatically).

## Changed / new files

- files/migration/1.1.15/upgrade.sql (new: story_after_episode column)
- files/schema.sql (column + comment for a fresh install)
- files/functions/series_helpers.php (getChronologyMarkers order param;
  chrono_current_mode / chrono_next_mode / chrono_display_modes / chrono_mode_label)
- files/add_chronology_marker.php (accept + validate story_after_episode)
- files/update_chronology_marker.php (new: set/clear story point on a marker)
- files/set_chrono_mode.php (new: cycle button + persist=1 default)
- files/anime_details.php (story field in the add form; both-number display;
  inline story edit; cycle button; mode-aware marker list)
- files/chronology.php (mode-aware timeline; "both" = two labelled lists; cycle button)
- files/list_settings.php (Chronology View default preference; story in the
  backup export/import marker payload)
- files/catalog_import.php, files/admin/catalog_push.php,
  files/admin/admin_catalog_requests.php, files/admin/admin_sync_example.php,
  catalog_server/catalog.php, catalog_server/admin_push.php
  (story_after_episode across the catalog wire format)
- files/help/help_series.php, files/lang/tr.php, files/lang/en.php
  (release/story labels, mode button, settings, help text)
- files/css/series.css (cycle button, section labels, inline edit)
- files/version.txt
