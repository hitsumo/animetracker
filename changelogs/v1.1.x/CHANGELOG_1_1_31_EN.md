# Anime Tracker 1.1.31

**Release date:** 2026-08-24

This release answers one question: **what do you write when you do not know
the whole date?**

For older productions the sources often give no day, and sometimes no month
either — only a year survives. Until now there were two options and both were
bad:

- **Leave the field empty.** The year you did know was lost too.
- **Invent a day.** The screen then showed `01.01.1979`, and a reader took
  that to mean "1 January, not 3 November". A false precision.

You can now enter **as much of the date as is known**; the unknown part shows
up as `??`.

## What it looks like

| Known | On screen |
|---|---|
| Day, month, year | `08.04.2005` |
| Month and year | `??.04.2005` |
| Year only | `??.??.2005` |
| Nothing | `??.??.????` |

The rule applies to both **Release Date** and **End Date**, independently of
each other: knowing the start to the day and only the year of the end is
perfectly fine.

## How you enter it

On the add and edit anime screens each date field now starts with a dropdown:

- **Full date** — today's date picker, nothing changed (the default).
- **Month and year** — a month box and a year box appear.
- **Year only** — only the year box appears.
- **Unknown** — no box at all.

Nobody types `??` by hand; the screen shows whatever you picked. When you
switch from a full date to "Year only", the year you already typed moves into
the year box on its own — no need to type it again.

## "Unknown" and "empty" are not the same

The distinction is deliberate:

- **Leave the field empty** and the anime detail page still says **"Not
  specified"** — meaning "I have not filled this in yet".
- **Pick "Unknown"** and it says `??.??.????` — meaning "I looked, there is
  no source".

That way you can tell which record is waiting to be filled in and which one
genuinely has no source.

## No countdown on a partial date

For anime that have not started airing, the detail page counts down to the
first episode. If the day of the date is not known, that line is **not
printed at all**. Saying "42 days left" while the screen reads `??.??.2027`
would be inventing an exact number for an unknown day.

## The year filter and sorting still work

The **year filter** on the main list and the date ordering in the series
timeline work exactly as before. An anime whose year alone is known shows up
under that year's filter and sorts at the start of that year.

## "Auto-fill" now brings partial dates too

The **Auto-fill** button behind an AnimeSchedule link takes its dates from
AniList. AniList holds only the year for many older productions; until now
those records were **dropped entirely** and the field stayed empty. The known
part now lands in the form and the precision dropdown moves to the matching
option by itself.

One thing is preserved: a date you typed is never overwritten. If you entered
a full date, your day stays put even when AniList says "year only".

## In the series timeline

Dates on the "Air Date" tab use the same format (for example
`??.04.2005 – ??.06.2005`). When the start and the end point at the same
value only one of them is printed; when they differ, both are.

## Two notes about the catalog

**Precision travels with the catalog.** The curator's "only the year is
known" reaches every install through the central catalog. A catalog file
older than 1.1.31 is read as "full date", so the old behaviour is preserved
exactly.

**A gap was closed: the end date is now really synced.** The central catalog
had been *sending* the end date since 1.1.14, but the client side never
stored it — the field was silently dropped. That gap is closed.

> **Behaviour change:** on a record that comes from the catalog, an end date
> you entered by hand may be replaced by the catalog's value on the next
> sync. That rule already applied to the same record's title, status,
> synopsis and release date; the end date surviving was not a rule but an
> oversight.

## The month box and the custom dropdown

Long dropdowns (more than 8 options) are turned into a custom widget on the
desktop — that has been the case since 1.1.11, so a long list never covers
the screen. The partial-date month box has 13 options, so it gets converted
too.

The two features collided on the first attempt: the rule that hides the box
was applied to the underlying native list rather than to the widget actually
on screen. The result was that the month box stayed visible whatever
precision you picked — even on "Full date" and "Year only". The date and year
boxes are text inputs, so they are never converted and behaved correctly; the
fault showed only on the month box.

The hiding is now applied to the widget itself. The fix is general: any long
dropdown the page hides stays hidden after conversion.

## Changed files

**New:**

```
files/functions/date_precision_helpers.php
files/migration/1.1.31/upgrade.sql
```

**Changed:**

```
files/functions.php                     (loads the new helper)
files/functions/anime_helpers.php       (no countdown on a partial date)
files/functions/series_helpers.php      (new columns added to the queries)
files/functions/anilist_import_helpers.php  (AniList partial dates kept)
files/fetch_animeschedule.php           (auto-fill carries partial dates)
files/add_anime.php                     (form + save)
files/edit_anime.php                    (form + save)
files/js/anime_form.js                  (precision dropdown, field swapping)
files/js/select_enhance.js              (hidden long dropdowns stay hidden)
files/css/components.css                (layout of the date field)
files/anime_details.php                 (display)
files/series_timeline.php               (Air Date tab)
files/catalog_import.php                (precision + end-date sync)
files/admin/catalog_push.php            (new columns are sent)
files/admin/admin_catalog_requests.php  (precision carried through approval)
files/list_settings.php                 (backup restore + suggestion flow)
files/schema.sql
files/lang/tr.php, files/lang/en.php    (9 new strings each + help page)
files/version.txt
catalog_server/catalog.php              (new columns are published)
catalog_server/admin_push.php           (new columns are stored)
```

## Deployment notes

- `files/functions.php` and `files/functions/date_precision_helpers.php` must
  be uploaded **together**. Without the loader line the file is never read
  and every page that prints a date fails to open.
- The two forms (`add_anime.php`, `edit_anime.php`) and
  `files/js/anime_form.js` must also travel together: an old script does not
  know the new precision dropdown and the field swapping will not work. The
  server side still saves correctly, you would just see all three boxes at
  once.
- `files/js/select_enhance.js`, `files/js/anime_form.js` and
  `files/css/components.css` must travel together; if only one of them stays
  old, the month box remains on screen whatever precision is picked.
- If the language files stay old, the key name shows up instead of the option
  labels — harmless but ugly.
- **Two `ALTER` statements have to be run by hand on the central catalog
  server.** Migrations do not run there; a push made before the columns exist
  will fail. Order: `ALTER` on the central server first, then the application
  deployment, then the push. The commands are written at the top of
  `files/migration/1.1.31/upgrade.sql`.
- **On the distribution server** the usual two functional steps apply: the
  published `version.txt` must be moved to 1.1.31 — otherwise "Check for
  updates" still thinks 1.1.30 is the latest — and the
  `updates/1.1.31/anime-tracker-1.1.31.zip` package must be published, or the
  "Update" button gets a 404 at the download address.
