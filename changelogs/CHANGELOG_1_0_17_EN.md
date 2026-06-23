# Anime Tracker 1.0.17

**Release date:** 23.06.2026

## Fixes

- On an online install, adding a new chronology note now sends it to the
  central catalog automatically, at the moment it is added. Previously a newly
  added note was saved only on the local install and the List Settings page
  showed a "not synced with the catalog" warning. The note is now sent the
  instant it is added, and the warning clears on its own once the send
  succeeds.

- If the send fails, the note is still saved locally (it is not lost) and a
  warning banner is shown on the home page; a later catalog send retries it.

## Notes

- The database schema did not change.
- This change affects online (multi-user) installs only. On a self-host
  install the behavior of adding a chronology note is unchanged.
- Deleting a chronology note is still not propagated to the central catalog;
  a delete affects the local install only.
- No new translations were added; the existing warning text is used.
