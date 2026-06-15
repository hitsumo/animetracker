# Anime Tracker 1.0.13

**Release date:** (to be filled on deploy day)

## Improvements

- Added a "finished" counter to the AnimeSchedule automatic episode
  tracking result message. After running List Settings -> Update, the
  summary now also reports how many anime were automatically moved to the
  "Finished airing" status in that run.

  Example: "0 anime updated, 11 unchanged, 1 finished, 0 not found in
  schedule."

  Previously this count was only written to the server log; it is now
  visible to the user. When no anime finished, the count is shown as zero
  (not hidden), so it is clear from the screen whether auto-finish was
  triggered in that run.

## Notes

- No database schema change.
- Interface language updated for both TR and EN.
