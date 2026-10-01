# Anime Tracker 0.5.5

**Release date:** May 19, 2026
**Type:** New feature + UI fix

This release is delivered via automatic update; no action is required
on your part.

## New feature

- **Quick episode update from the list:** Each anime's "Watched
  Episodes" column on the main list now has small `−` / `+` buttons.
  When you watch an episode you can bump the watched count up or down
  straight from the list, without opening the "Edit" screen. The cell
  updates instantly, with no page reload.

  - The watched count cannot go below 0 (`−` is disabled at that
    point).
  - The watched count cannot exceed the total episode count (or, if
    no total has been entered yet, the aired episode count); `+` is
    disabled at that point.
  - If neither the total nor the aired episode count is known, the
    buttons are hidden; you need to enter episode information or run
    a sync first.

## Fix

- **List table no longer overflows:** In previous versions the list
  table could spill outside the page box, especially at higher
  browser zoom levels (for example 125%). This release constrains the
  table to the page width; long text now wraps inside its cell and
  the table no longer overflows.

## Notes

This release contains UI and usability changes only; no database
change or extra step is required. Your existing tracking data is
preserved as-is.
