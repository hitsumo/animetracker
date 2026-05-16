# Anime Tracker 0.5.2

**Release date:** May 12, 2026
**Type:** Bug fixes and improvements

This release is delivered via automatic update; no action is required
on your part.

## What's new

- **Duplicate anime warning:** If you try to add an anime that already
  exists (same MAL or AniDB link), you now get a clear warning and can
  jump to the existing record with a single click. Previously a
  technical error screen appeared.

- **Image safety:** When you upload a new cover image while editing an
  anime, your old image is preserved if the save fails. Image loss no
  longer occurs.

- **Help page refreshed:** The time zone setting is now explained more
  clearly. It's documented that entering an AnimeSchedule link and
  pressing "Auto Fill" automatically fills in the broadcast day, time,
  and time zone — no manual entry needed.

- **Update system improved:** When the server can't be reached, an
  explicit error message is now shown instead of falsely reporting
  "up to date". The update process is more reliable.

## For those running their own server

If you run the catalog/synchronization system on your own server,
this release includes an improvement to the admin panel:

- **Sync protection:** The "Open sync page" button in the admin panel
  is now disabled with a warning when there are pending anime not yet
  promoted to the catalog. Previously, pushing in this state would
  silently skip the pending anime. Now you're guided to promote the
  pending ones to the catalog first, then push. (admin.php — this is
  an admin-side file; it is not included in the automatic update and
  must be updated manually.)

## Notes

The database structure did not change in this release; your existing
data is preserved as is.
