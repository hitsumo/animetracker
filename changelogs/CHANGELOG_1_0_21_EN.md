# Anime Tracker 1.0.21

**Release date:** 29.06.2026

## Fixes

- For an ongoing anime whose total episode count is unknown, catching up to the
  latest aired episode no longer flips the watch status to "Watched" by mistake;
  it now stays "Watching". When a new episode airs it correctly remains
  "Watching". The status only becomes "Watched" when the series actually ends:
  the known total is reached, or a finished broadcast is fully watched.

## Notes

- This release does not change the database schema.
- A record that was stuck as "Watched" before this fix returns to the correct
  state as soon as you decrease its watched-episode count by one, or fix it from
  the Edit screen.
