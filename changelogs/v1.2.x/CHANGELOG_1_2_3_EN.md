# Anime Tracker 1.2.3

**Release date:** 2026-09-30

Main job: **phone layout.** Most public pages opened on a phone as a
shrunken desktop page; they now render at the screen's width. Plus one
small fix: the **broadcast time source note** on the anime detail page now
sits under the time. No schema change (empty migration); nothing to do on
the central catalog.

## 1. Why

In the last three months of Search Console data, two thirds of the
clicks come from phones (29 of 43).

Yet the `<meta name="viewport">` tag, which tells a phone to render the
page at its own width, existed only on the main list and the anime
detail page. Series Chronology, Chronology, About, Statistics, Recently
Updated, What to Watch and every help page opened on a phone as a
shrunken ~980 px desktop layout. Search engines rank by the mobile
version, so these pages could count as "not mobile-friendly".

## 2. What changed

- **The viewport tag is on every public page.** It now comes from the
  pages' shared SEO header, so future public pages get it automatically.
- **Series Chronology fits the phone.** Long anime titles no longer
  widen the card; they are cut with "…". The timeline dots stay on
  screen. Outer spacing shrinks on phones.
- **Help pages fit the phone.** Side padding shrinks on narrow screens;
  wide tables scroll inside their own box instead of the page.
- **The desktop layout is unchanged.** Page widths are identical to the
  pixel.

## 3. Detail page: the time source note in its place

For airing and not-yet-aired anime, the "Broadcast time data from
AnimeSchedule" note was printed under the Release Date. Once the Country of
Origin row was added, the note ended up alone under it, far from the
time it belongs to; Broadcast Day and Broadcast Time sit much lower on the
page.

- **The note now lives in the broadcast block:** right under Broadcast
  Time and the countdown (Next Episode / time until episode 1).
- **The note only shows when a time is set.** An anime whose time is
  "Not set" gets no source note.

## 4. How it was measured

All 21 public pages were opened at 375 px (phone) and checked for
horizontal overflow. Three overflowed before (Series Chronology 550 px,
two help pages ~400 px); none do after the fix. At desktop width
(1280 px) the page boxes were compared with their old sizes.

The time note was tried in four cases: with a time, both a not-yet-aired
and an airing anime show the note under the time; a not-yet-aired anime
without a time and a finished anime show no note.

## Files

**New:**

```
files/migration/1.2.3/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/functions/seo_helpers.php          seo_head(): viewport tag (first tag)
files/anime_details.php                  hand-written viewport line removed (seo_head emits it);
                                         time source note moved into the broadcast block, only when a time is set
files/index.php                          same
files/series_timeline.php                container at screen width; card can shrink; phone spacing
files/css/help.css                       container at screen width; phone spacing; tables scroll in place
files/version.txt
```

## Deployment notes

- **Nothing to do on the central catalog.**
- The migration is empty; it moves the version stamp on the first page
  load.
- If 1.2.2 is not live yet, it can go out together with this release.
  `seo_helpers.php` and `series_timeline.php` carry the changes of both.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.3 and publish
  `updates/1.2.3/anime-tracker-1.2.3.zip`.
