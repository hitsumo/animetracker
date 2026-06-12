# Anime Tracker 1.0.7 - Changelog

**Release date:** June 2026 (internal milestone)

> Note: This release mostly concerns online / multi-user (Docker) operators and
> fresh installs. For self-host (single-user) installations the only visible change
> is that list export/import now also carries emotion marks (see below); otherwise
> everything works as before. Version numbers are internal development steps. This
> release contains no schema (database structure) changes.

## Summary

This release makes online (multi-user) deployment possible via Docker, lets catalog
entries added online propagate automatically to the central server, groups the admin
pages into a separate `admin/` folder, cleans up old copies of relocated files on
update, simplifies the shared code of the add/edit anime pages, and adds emotion
support to list export/import.

## Online (multi-user) deployment via Docker

Previously the Docker image only started in single-user (self-host) mode, because
the installer wizard's mode selection was skipped in the Docker flow. In this
release the mode is chosen via an environment variable:

- In your `.env` file set `MULTI_USER_MODE=true`, provide `ADMIN_USER` and
  `ADMIN_PASS` (at least 8 characters), then run `docker compose up -d`.
- On first start the database is prepared, the app is configured in multi-user
  mode, and the first administrator account is created from the values you provided.
- If `MULTI_USER_MODE` is not set, the default is self-host (no login) - the
  previous Docker behavior is preserved.

Notes: The mode is written to the config only on first install; to change it later,
edit config.php inside the container. Once the first admin exists you can remove
`ADMIN_PASS` from `.env` (it is not used again because the admin already exists).

## Catalog additions made online now reach the central server automatically

Previously, anime added on an online (multi-user) installation stayed only in that
installation's database: because they were never sent to the central catalog
server, offline / self-host users never saw them. The manual push tool only ran
from the machine itself (localhost), so it could not be used on a remote online
server.

In this release, when an administrator promotes a pending anime to the catalog
(approves it) on the "Pending Anime" page, the online server automatically sends the
promoted records to the central catalog server (server-to-server, signed). Offline /
self-host users then receive those anime on their next catalog import. No extra
button or manual push is needed.

Setup (online instance only): in `admin/admin_secret.php`, define `ADMIN_PUSH_SECRET`
(identical to the server's secret) and `CATALOG_PUSH_URL` (the central server's
address). If the push fails, the local promotion still stands; the status message
reports the outcome.

Notes: For now only approved records are pushed automatically; anime an administrator
adds directly (without going through approval) are not covered. Self-host
installations are unaffected - they push manually as before.

## Admin pages in a separate folder

The admin interface pages (dashboard, pending anime, suggestion/invite/user
management, capabilities) and the local operator tool examples are now grouped in
the `admin/` subfolder. On online / Docker installations these pages are reached at
`.../admin/admin.php`. The self-host `.exe` installer does not include admin pages,
so there is no change for those users.

## Updates now clean up old file copies

Files that a release moves or removes are now handled automatically during an
update: each file is relocated to its new place and the old copy is cleaned up.
Previously the updater only copied new files and left the old-location copies behind
- and because this release moves the admin pages into the `admin/` folder, that left
stale admin files in the install root. Those leftovers no longer remain. Your personal
configuration files (e.g. settings) are not deleted; they are preserved by relocating
them. This applies to in-app updates, manual copying, and Docker.

## Catalog Requests table on fresh installs

Databases created from scratch now also create the `catalog_requests` table (the
suggestion queue for anime not yet in the catalog during online import). Existing
installations already received this table via the automatic update; this is only a
correction that completes the clean-install path. On self-host the table stays empty.

## Internal improvement

The shared form code of the add-anime and edit-anime pages was consolidated into a
single file (`js/anime_form.js`) - behavior is unchanged. One small visible change:
the AnimeSchedule "Auto-fill" result now shows the number of filled fields on both
pages (previously only on the add page).

## List backups now include emotions

List export/import (List Settings) now also carries the emotion marks you set on
anime. Previously only watch status, watched episodes and notes were exported;
emotions were left out. When you move a list to another install (for example your
online account), the emotions are attached to the importing user; non-canonical
values are skipped, at most 3 marks per anime are kept, and re-importing the same
file creates no duplicates. Older backup files (without the emotions field) keep
working.

## What changed for self-host users

Almost nothing - your list, watch states, adding and editing all work as before. The
one change: list export/import now also carries emotion marks (see the section
above), so a backup is complete and you can move your list together with your
emotions.
