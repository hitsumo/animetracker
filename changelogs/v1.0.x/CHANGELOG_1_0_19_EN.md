# Anime Tracker 1.0.19

**Release date:** 27.06.2026

## New

- The aired-episode sync can now be run from the command line as well (cron on
  Linux, Task Scheduler on Windows). This lets the automatic update run on a
  schedule on its own, at whatever frequency you choose, instead of depending on
  someone opening the List Settings page. New file: sync_aired.php (setting it up
  is entirely optional).

## Fixes

- On servers with no working IPv6 egress that resolve animeschedule.net to an
  IPv6 address, the airing sync could fail to connect and time out. AnimeSchedule
  requests are now pinned to IPv4.

## Notes

- The command-line sync runs every time it is invoked; how often it runs is set
  by the scheduled task you create. The List Settings page's once-a-day trigger
  skips its own sync if the day's sync already happened.
- Setting up the scheduled task differs per operating system and is not part of
  the package; users who want it add their own scheduled job.
- The database schema did not change.
- The website's behavior is unchanged.
