# Anime Tracker 1.1.0

**Release date:** 01.07.2026

## New

- Personal watch dates were added for each anime: a start date and a finish
  date. Entered manually, optional, and may be left blank. They are set from
  the add and edit forms and shown on the detail page.
- If the finish date is before the start date a warning is shown, but the
  value is still saved.
- Watch dates are personal; they are not shared via catalog sync. They are
  included in your list backup (export/import).

## Notes

- This release adds two date columns to the user_anime table; your existing
  watch data is not affected.
