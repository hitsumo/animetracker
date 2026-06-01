# Anime Tracker 0.7.5 - Changelog

**Release date:** June 2026

## English titles now also show for related anime

Previously the "Show English titles" preference applied only to top-level
anime titles. Now the same preference also applies to titles shown where an
anime is linked to other anime.

With the English interface + "Show English titles" enabled (and an English
title entered for that anime), English titles now also appear in:

- **Anime detail page:** the "Next anime" link, the same-series anime box,
  the chronology alert, the watch-order (marker) list, and the marker-add
  dropdown.
- **Chronology page:** the film/OVA titles interleaved into the watch order.
- **Series timeline page:** each entry's title in the chain.

If no English title is entered, or the preference is off, everything falls
back to the original (Romaji) title as before; no behavior changes there.

In addition, the chronology page's own heading is now wired to this
preference too (previously the main title on that page always showed Romaji
regardless of the preference).

## Other

### Schema

This release contains no schema change. `migration/0.7.5` is an empty
migration that only advances the version number; it runs automatically
during auto-update and requires no manual action.

### Files

Changed: `series_helpers.php`, `anime_details.php`, `chronology.php`,
`series_timeline.php`.
New: `migration/0.7.5/upgrade.sql`.
