# Anime Tracker 1.1.14

**Release date:** 2026-07-16

## New: Filter by year

- **The main list page gains a "Filter by Year" filter.** A collapsible
  section, sitting between the broadcast-status filter and the letter filter,
  holds a grid of year checkboxes.
- **You can pick a single year or several.** Check just one year (e.g. 1972),
  a few years at once (e.g. 1972 + 1973), or non-contiguous years
  (e.g. 1972 + 1986 + 2004). Every anime whose year is among those selected is
  listed.
- **The years are generated automatically from the catalog.** The years in the
  boxes are not a hand-maintained list; they are derived from each anime's
  release date (`release_date`) and sorted descending. Add an anime from a new
  year and that year's checkbox appears on its own; remove the last anime of a
  year and the box disappears on its own. (Only anime with a release date are
  included in a year box.)
- **The selected year is highlighted instantly.** Checking a box fills the chip
  blue; unchecking turns it white immediately - so you see the current
  selection clearly even before pressing "Filter".
- **A "Clear year selection" button.** When a year filter is active, a button
  appears below the year section that clears only the year selection in one
  click, while preserving your other filters (search, genre, watch status,
  letter, emotion, page size, active tab).
- **The filter keeps your current search, sort and pagination.** The selected
  years are preserved as you move between sort links, the letter filter and
  page numbers.

## Improvement: help pages "back" link

- **The "Back to Home" / "Help Contents" links on the help pages now look like
  buttons.** They render as an outlined button instead of plain blue text, and
  the leading arrow was removed. Their behaviour is unchanged.

## Housekeeping

- **Two dead files removed:** `files/user_anime_helpers.php` (it had become a
  byte-for-byte duplicate of `functions/user_anime_helpers.php` and was
  referenced from nowhere) and `files/dizin_listesi.txt` (an old directory-dump
  artifact). When updating a server install you may delete these two files if
  present; there is no functional impact.

## How it works (technical)

- The filter is selected with the `year_filter[]` array parameter. Year is not
  a separate column; the predicate runs on the existing `animes.release_date`
  column as `YEAR(a.release_date) IN (...)`. No new table, column or setting is
  needed.
- Selected year values are cast to `(int)` on the server and whitelisted
  against the years that actually exist in the catalog; only validated integers
  are embedded in the SQL, so there is no injection surface.
- The chip highlight is bound to the live checkbox state rather than a
  server-side class (`:has(input:checked)` plus a small JS sync), so unchecking
  removes the highlight instantly.

## Schema / migration

- `migration/1.1.14/upgrade.sql` only advances the version to 1.1.14; there is
  **no schema change** (no SQL statement to run). The central catalog is not
  affected and requires no manual step.

## Changed / new files

- index.php (Filter by year: parameter parsing, `YEAR(release_date) IN (...)`
  predicate in both query branches, selected years preserved across
  sort/letter/pagination links, filter-form UI, chip-sync JS)
- css/series.css (year grid and chip styles, "Clear year selection" button)
- css/help.css (button look for the help-page "back" link)
- lang/tr.php, lang/en.php (index.filter.year / year_none / year_clear keys;
  removal of the leading arrow from help.back_to_home / back_to_index)
- migration/1.1.14/upgrade.sql (new)
- version.txt
- Removed: files/user_anime_helpers.php, files/dizin_listesi.txt
