# Anime Tracker 1.1.25

**Release date:** 2026-08-02

## New: "Other Chain" tabs on the Series Chronology

- **The other chains of a series are now visible on the page.** One series
  name can hold more than one chain: in Ghost in the Shell the feature
  films are one chain and the SAC TV series are an entirely different one.
  Until now the page drew only the chain containing **the anime you opened
  it from**; the sole way to reach another chain was to navigate to an
  anime already inside it. If you did not know the series, nothing told
  you how many chains existed.
- **Next to Chain Order and Air Date, the tab bar now gains one tab for
  every chain other than your own:** "Other Chain 1", "Other Chain 2"… Hover
  a tab and it tells you how many anime that chain holds. Click it and the
  chain is drawn on the same page, in the same layout.
- **The count is not configured, it is derived.** On every visit the page
  scans the records sharing the series name and finds the chains. Build a
  new chain (or break a link) and the tab list updates itself on the next
  visit.
- **The order is stable:** "Other Chain 1" is the earliest-dated chain and
  the rest follow. Whichever anime you view the series from, the chains
  keep the same order — only the chain you are currently inside drops out
  of the list, because that one is the "Chain Order" tab.
- **A single unlinked record is not a chain** (the threshold is 2 anime).
  Otherwise every standalone film sharing the series name would spawn its
  own tab. Those records already appear on the Air Date tab, in date order.
- **Your saved tab preference is left alone.** Switching to another chain
  is an ephemeral view: it touches neither the "Series Chronology View"
  preference in List Settings nor your session's Chain/Air Date choice.
  Open another anime's chronology and the page comes back on that anime's
  own chain.

## How it works (technical)

- Chain discovery is derived from existing data: the members of the series
  name group are scanned in first-air-date order, each one is walked
  backwards along `next_in_series` to find the chain start, the chain is
  walked forward once and its members are marked. Since the remaining
  members of a walked chain are skipped, every chain enters the list once.
- The chain walk (backwards and forwards) moved out of
  `series_timeline.php` into `series_helpers.php`. The chain the page draws
  and the chain discovery finds now come from the same code, so the two
  copies can no longer drift apart. The walk itself is unchanged — cycle
  guard included.
- The selection travels as `?chain=<start_id>` and is **never written to
  the session**. The value may only be the start of a chain found within
  the same series name group; anything else is silently ignored and the
  page falls back to your own chain.
- The other-chain view is always chain order: "Air Date" spans the whole
  series and never narrows to a single chain.
- The tab label does **not** carry the title of the chain's first anime;
  the tooltip states only the anime count. That keeps the adult-content
  mask from leaking into the tab bar.
- An anime with no series name shows no tab bar at all; the page draws the
  chain exactly as before.

## Schema / migration

- **No schema change.** No new table, column or preference; the chains are
  derived from the existing `next_in_series` + `series_name` data.
  `migration/1.1.25/upgrade.sql` carries only the version stamp.
- **No manual action is needed on the central catalog server** — the
  catalog wire was not touched.

## Changed / new files

- files/series_timeline.php (other-chain tabs, `chain` parameter)
- files/functions/series_helpers.php (chain walk moved here + chain
  discovery)
- files/lang/tr.php, files/lang/en.php ("Other Chain %d" label)
- files/migration/1.1.25/upgrade.sql (new, version stamp only)
- files/version.txt

## Deployment note

- There are no new files, but `files/series_timeline.php` and
  `files/functions/series_helpers.php` must be uploaded **together**: the
  chain walk moved from the page into the helper, so updating only one of
  them leaves the page calling an undefined function.
