# Anime Tracker 1.1.23

**Release date:** 2026-07-26

## New: "Air Date" tab on the Series Chronology

- **The series chronology page now has two tabs.** "Chain Order" is the
  view you already know: it follows the Next in Series links hop by hop.
  The new "Air Date" tab lists **every** anime sharing the series name,
  ordered by first air/release date — TV series show their start–end range
  (day.month.year), films and specials show their single premiere date.
- **Why?** The chain view depends on hand-made links: one missing link
  splits the list in two, and a catalog-imported anime is always born
  unlinked. A linear chain also cannot express overlapping broadcast
  periods — films premiering while a TV series is still airing are the
  typical case. The Air Date tab never looks at the chain, so neither
  problem can touch it: a missing link cannot split it, and date ranges
  show the overlaps as they really were.
- **Entries without a date** sink to the end of the list with a "no date"
  label — they never masquerade as the start of the series.
- **The tab choice is remembered for the session:** switch to Air Date on
  one series and another series' chronology opens on that tab too; your
  saved default is never overwritten.

## New: a persistent default in List Settings

- List Settings → General Settings gains a "Series Chronology View"
  preference: which tab the page opens in by default — Chain Order (the
  default, matching the old behavior) or Air Date. It is an exact twin of
  the Chronology View preference (1.1.15): the on-page tabs switch
  temporarily without overwriting it. This preference affects only you.

## New: the button now opens from the series name too

- The "Series Chronology" button on the detail page used to appear only on
  anime already in a chain (having a Next in Series link). It now also
  appears on an anime that **has never been chained but shares its series
  name with other entries** — a catalog-imported anime that nobody has
  linked yet joins its series' timeline immediately through the Air Date
  tab.

## Fixed: adult titles were unmasked on the series chronology

- A user with the adult-content preference off could see the plain title of
  an adult-flagged chain member on the series chronology — every other
  related-anime card in the app has masked these with a neutral placeholder
  since 1.1.2, but this page had been left out. Both tabs now apply the
  same mask: the card stays, the title does not leak; opted-in users keep
  seeing the real title.

## How it works (technical)

- The Air Date tab is fed by a single query over `series_name`, ordered by
  `release_date` (NULLs last). The chain walk, the card template and the
  progress/status badges are shared by both tabs.
- Tab selection follows the 1.1.15 chronology-mode pattern: ephemeral
  session choice > saved per-user default > Chain Order. The persistent
  preference is stored in the `user_pref` table as `series_timeline_mode`;
  the tabs are plain in-page links — only List Settings writes the
  persistent value.
- An anime without a series name shows no Air Date tab; the page draws the
  chain exactly as before.

## Schema / migration

- **No schema change.** The new preference is a row in the existing
  `user_pref` key-value table; every column used already existed.
  `migration/1.1.23/upgrade.sql` carries only the version stamp.
- **No manual action is needed on the central catalog server** — the change
  is purely presentational.

## Changed / new files

- files/series_timeline.php (tabs, air-date view, adult mask)
- files/anime_details.php (Series Chronology button opens from the series name too)
- files/list_settings.php (Series Chronology View preference)
- files/set_series_timeline_mode.php (new, endpoint writing the persistent preference)
- files/functions/series_helpers.php (mode resolution + air-date query)
- files/lang/tr.php, files/lang/en.php (new strings)
- files/migration/1.1.23/upgrade.sql (new, version stamp only)
- files/version.txt
