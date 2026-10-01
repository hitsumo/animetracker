# Anime Tracker 1.2.2

**Release date:** 2026-09-30

One job: **in-page tabs on the series chronology.** The chain tabs and
Air Date now live on the same page; clicking a tab no longer reloads it.
Search engines read every line of a series at one address. No schema
change (empty migration); nothing to do on the central catalog.

## 1. Why

The Series Chronology page shows a series through several tabs: the main
line, named lines such as "Films" / "OVA" or "Other Chain N", and Air
Date. Up to 1.2.1 every tab was **a separate address** (`?chain=…`,
`?mode=airdate`), and those addresses were closed to search engines so
they would not count as duplicate pages.

Search engines index one address per series: the page of the series'
lowest-numbered record. That address showed **only that record's line**.
So for someone searching "watch order", the page Google could show held
only part of the series. Examples:

- Himitsu no Akko-chan: only the 1969 series; the 1988 series, the films
  and the 1998 series were on another tab.
- Taiho Shichau zo: only the specials; the TV series were on another tab.
- Seitokai Yakuindomo: the films were on another tab.

## 2. What changed

- **The chain tabs and Air Date are on the same page.** Visitors still
  see one list at a time; clicking a tab swaps the list in place without
  a reload. The "N anime" counter in the header follows the open tab.
- **Shareable tab.** The open tab is added to the end of the address
  (`#airdate`, `#chain-65`); whoever opens the link sees the same tab.
- **Preference as before.** The chain ↔ Air Date choice is remembered for
  the session (saved in the background); other-line tabs are still not
  remembered. The default view in List Settings works unchanged.
- **The first tab is chosen by the same rule:** the opened anime's own
  line (or Air Date if that is the saved preference). The longest line
  was not moved to the front — for Heidi, Death Note or One Piece that
  would put the summaries or the films ahead of the TV series.
- The **Diagram** tab is a heavy drawing and still opens at its own
  address.
- **Old addresses work.** `?chain=…` and `?mode=airdate` links open the
  right tab; their rules for search engines (noindex, canonical) did not
  change.
- **With JavaScript off**, every list is shown one under another with
  its heading, and the tabs keep working as links.
- **Posters load lazily:** only the visible list's posters load with the
  page; the others when their tab is opened.

## 3. Search engine notification (IndexNow)

On an online installation, a change to a record (a relation, a chain
name, a new record) now also announces the series' chronology page —
even when the changed record is not the first record of the series. The
page shows every line, so every member's change changes its content. The
announced address is the same one the sitemap lists.

## 4. Help

The tabs section of the series help now says the tabs work on the same
page, that the open tab can be shared through the address, and that the
Diagram opens on a page of its own.

## Files

**New:**

```
files/js/series_tabs.js                  in-page tab switching, address hash, preference save
files/migration/1.2.2/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/series_timeline.php                every list tab on one page (one card template per panel: st_render_card)
files/functions/seo_helpers.php          seo_anime_locs(): series page on any member change; seo_series_head_listed()
files/lang/tr.php                        help.st.tabs.text
files/lang/en.php                        same
files/version.txt
```

Language file parity: 1169 = 1169.

## Deployment notes

- **Nothing to do on the central catalog.**
- The migration is empty; it moves the version stamp on the first page
  load.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.2 and publish
  `updates/1.2.2/anime-tracker-1.2.2.zip`.
