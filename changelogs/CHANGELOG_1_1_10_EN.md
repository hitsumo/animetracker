# Anime Tracker 1.1.10

**Release date:** 2026-07-14

## New feature

- **Three new broadcast-status values.** An anime's broadcast status is no longer
  limited to "Currently Airing" and "Finished Airing"; three more states can now
  be chosen:
  - **Not Yet Aired** - upcoming anime that has not started airing.
  - **Not Selected** - unspecified / unknown status. New anime forms now default
    to this value, so adding an anime no longer forces a finished/ongoing guess.
  - **Cancelled** - anime whose broadcast was cancelled.
- The new states appear in the add/edit status dropdown, the broadcast-status
  filter on the main page, and the statistics/display surfaces.
- **Automatic "Not Yet Aired" -> "Currently Airing" transition.** "Not Yet Aired"
  is not a dead end: when an anime actually starts broadcasting it flips to
  "Currently Airing" on its own - just as "Currently Airing -> Finished" is
  already automatic. This completes the broadcast-status lifecycle.

## How it works (technical)

- Three values were APPENDED to the `animes.status` enum (and its twin
  `catalog_requests.status`) - appended in place so existing rows keep their
  ordinal and no data is rewritten. Applied automatically on every install via
  `migration/1.1.10/upgrade.sql` (MODIFY is idempotent / re-run-safe).
- Broadcast-status labelling is now single-sourced through the new
  `broadcast_status_helpers.php` (`broadcast_status_label()` /
  `broadcast_status_options()`), replacing the if/elseif that was duplicated at
  every render surface - the same pattern as the watch_status helper family.
- Language-aware labels: the new states are translated in the English UI too
  (`index.broadcast.not_started` / `.unselected` / `.cancelled`).

## Import mapping

- **AniList** status now maps almost one-to-one (previously five states folded
  into two): FINISHED -> Finished, RELEASING/HIATUS -> Ongoing,
  NOT_YET_RELEASED -> Not Yet Aired, CANCELLED -> Cancelled.
- **AnimeSchedule** auto-fill: "Upcoming" -> "Not Yet Aired".
- Unknown / status-less imports (the legacy MAL export carries no airing status)
  now default to "Not Selected" instead of "Finished".

## Automatic transition (Not Yet Aired -> Currently Airing)

- The aired sync (`syncAllOngoingAiredEpisodes`, run from the page and from cron
  via `sync_aired.php`) now sweeps "Not Yet Aired" rows in addition to
  "Currently Airing" ones. The moment AnimeSchedule's timetable shows such a row
  with an ALREADY-aired episode (future-dated episodes are skipped by
  `isTimetableRowAired`), broadcast has begun, so the row is promoted to
  "Currently Airing" (or straight to "Finished" if its whole raw run has already
  aired). No extra API calls - the same weekly timetable request that updates
  ongoing shows also catches the ones that just started. A new `started` counter
  is added to the sync summary (web message + cron STDOUT).
- Limit: promotion only works for animes that have an `anime_schedule_link` and a
  `mal_id`; a "Not Yet Aired" anime lacking those must be moved by hand.
  Promotion does not compute `next_episode_date` (the aired sync never does), so
  the countdown still relies on `broadcast_day`/`broadcast_time` (cosmetic).

## Maintenance tool

- **`tek_kullanimlik/anilist_airing_backfill.php` updated for 1.1.10.** In 1.1.9
  it folded everything into a single "not-finished -> ongoing" bucket; it now
  asks AniList for every animes.mal_id and emits a SEPARATE UPDATE per target
  state (same mapping as anilist_airing_status_to_enum): NOT_YET_RELEASED ->
  Not Yet Aired, CANCELLED -> Cancelled, RELEASING/HIATUS -> Ongoing, FINISHED
  -> Finished. A row is written only when the target differs from its current
  status. Rows where AniList returns a duplicate/inconsistent idMal go to a
  manual-review list (never touched automatically). The "-> Finished" block is
  emitted commented out (a raw flip does not reconcile aired/total the way the
  edit form does). Same pacing/retry/resume design is preserved.

## Central catalog note (IMPORTANT)

- The central catalog server (`catalog_server/`) does NOT run the
  MigrationManager. Its `animes.status` enum must be ALTERed by hand to the same
  five values, otherwise a pushed row carrying a new status is rejected:
  ```sql
  ALTER TABLE `animes`
    MODIFY `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor',
      'Yayın Başlamadı','Seçim Yapılmadı','Yayın İptal Edildi') NOT NULL;
  ```

## Changed files

- schema.sql (animes.status + catalog_requests.status enums)
- migration/1.1.10/upgrade.sql (new - ALTERs both tables)
- functions/broadcast_status_helpers.php (new helper)
- functions.php (loads the new helper)
- functions/anilist_import_helpers.php (five-state mapping)
- functions/animeschedule_helpers.php (Upcoming mapping + Not-Yet sweep/promote)
- sync_aired.php (started counter in cron summary)
- add_anime.php, edit_anime.php (status dropdown + server-side normalization)
- index.php (filter dropdown)
- recent.php, statistics.php, anime_details.php, pending.php,
  admin/admin_pending.php, admin/admin_catalog_requests.php (label helper)
- list_settings.php (whitelist + defaults + web sync started counter)
- catalog_import.php, catalog_server/admin_push.php (default status)
- lang/tr.php, lang/en.php (three new label keys + started result label)
- tek_kullanimlik/anilist_airing_backfill.php (four-state backfill)
- version.txt
