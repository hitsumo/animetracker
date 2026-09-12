# Anime Tracker 1.1.38

**Release date:** 2026-09-04

1.1.36 gave a chain a **name**. This release gives a link its **type**: you can
now write "these two entries belong together, but one is not the continuation
of the other" — and the application understands it.

## The problem

1.1.36's diagnosis said more than 1.1.36 fixed. What was missing was not only
"which track", but **"what kind of link"**.

- The *Space Adventure Cobra (1982)* film is, per AniDB, the **alternative
  version** of the TV series. The catalog could only say "linked" or "not
  linked", so the correct answer was **unsayable** — and an unsayable sentence
  looked exactly like *missing data* on screen.
- *Sailor Moon Crystal* (also an alternative version) was the opposite case:
  linked **inside** the 90s chain, so the timeline claimed a watch order that
  does not exist.

1.1.36 made both visible (by naming the tracks) but never recorded **why** they
stand apart. This release does.

## The fix: typed, orderless relations

The edit page gained a section at the bottom: **Relations**. You pick an anime
and mark the type; the form asks a single question:

> The anime you pick is this anime's ___.

| Option | Meaning |
|---|---|
| Alternative version | Another telling of the same story |
| Alternative setting | Same characters, a different world |
| Side story / Parent story | A side narrative and its main one (two directions) |
| Summary / Full story | A condensed retelling and the whole thing (two directions) |
| Related (other) | None of the above |

Relations appear on the detail page under **Relations**, grouped by type.

## Two ends, two different sentences

Side story and summary are **directional**: if A is B's side story, then B is
*not* A's side story — it is A's **parent** story. So the same record shows up
with two different labels on the two pages:

```
On the film's page:      Side Story   → the TV series   (would be wrong)
On the TV series' page:  Parent Story → ...
```

The correct reading: add the film as a "side story" and the series' page shows
the film under *Side Story*, while the film's page shows the series under
*Parent Story*. Alternative version, alternative setting and "other" read the
same from both ends and carry one label.

## `sequel` is deliberately absent

There is no "sequel / prequel" in the list. Watch order still lives in exactly
one place: the **Next Anime** field. If a sequel could also be stored here, two
sources could disagree about the same pair, and something would have to break
the tie. Leaving the value out makes the contradiction **impossible to enter**
rather than merely discouraged.

No type here states an order. The series timeline and the synopsis spoiler gate
are **unchanged** in this release — both still follow "Next Anime" links only.

## Two things the app refuses

**A second relation on the same pair.** Two rows are either a duplicate or a
contradiction, and there is no third case that a single, better-chosen type
cannot express. To change the type, delete the existing relation first.

**A relation between two entries already chained with "Next Anime".** That pair
would claim to be ordered and unordered at once — precisely the Sailor Moon
Crystal bug. The app refuses it and names the fix: remove the link first.

That check does not repeat the 1.1.36 rule, it **calls** it: a link is only
followed when both ends carry the same chain name. A dormant link (different
names, therefore already ignored) blocks nothing.

## Relations travel in your backup

The JSON backup carries relations. The far end is written by **identity**
(MAL / AniDB / catalog id / title) rather than by a local row number — the same
way chronology notes travel — and is resolved again on restore. A
backup-and-restore round trip no longer loses them.

Restore skips a relation in two cases and reports the count: the far end is not
present on this install, or the type is not recognised. An unrecognised type is
**not** downgraded to "other" — recording a link the app does not understand
would also block you from entering the right one later.

## Nothing changes in your existing data

The migration only creates an empty table. It converts no link and edits no
record: after the upgrade your list, your chains and your spoiler gate behave
exactly as before, and you fill in relations by hand.

## The add/edit form is now tabbed

A second, separate piece of work in the same release. The add and edit form was
one long page, and the new Relations panel made it longer still — worse, it sat
**below** the save buttons. The fields are now split across five tabs:

| Tab | What it holds |
|---|---|
| Basics | Title, alternative titles, media type, country, status, episode counts, dates, 18+, filler tracking, image |
| Synopsis & Genres | Synopsis text (TR/EN, catalog + personal), genres, sentences |
| Series & Relations | Series name, chain name, next anime **and the Relations panel** |
| Broadcast & Sources | Episode interval, broadcast day/time/timezone, AniDB/MAL/AnimeSchedule links |
| Personal | Watch status, watched episodes, watch dates, personal notes |

The add page has the same tabs; the only difference is that its "Series" tab
carries no Relations panel — the record does not exist yet, so one end of a
relation would be missing.

**The form is still saved in one piece.** Switching tabs submits nothing;
**Update** stays at the bottom of the page on every tab and saves *all* fields
together, whichever tab is open.

Three details:

- **If a required field is left empty on another tab**, the browser refuses to
  submit and says nothing (the field "is not focusable"). That field's tab now
  opens by itself, so the validation bubble is visible.
- **With JavaScript off** the tab bar never appears and every field is listed
  one after another, exactly as in 1.1.37.
- **The "Auto-fill" report** no longer marks a field on another tab as "(in a
  hidden section)" — the field is there, one click away. The note is kept for
  fields that really are hidden (e.g. the broadcast section that follows the
  status field).

The edit page also **halved in size**: the Relations picker no longer ships a
second copy of the anime list from the server, it is cloned in the browser from
the "Next Anime" picker. Measured on a catalog of 8,000 entries: 3.9 MB → 1.9 MB.

## Changed files

**New:**

```
files/functions/relation_helpers.php   types, direction/inverse labels, rules
files/add_anime_relation.php           add endpoint
files/delete_anime_relation.php        delete endpoint
files/migration/1.1.38/upgrade.sql
```

**Changed:**

```
files/functions.php                  loads the new helper
files/edit_anime.php                 the "Relations" panel + tabs
files/add_anime.php                  tabs
files/anime_details.php              the "Relations" section
files/list_settings.php              backup export + restore
files/js/anime_form.js               tab switching, validation, option cloning
files/css/series.css                 panel and section styles
files/css/components.css             tab bar styles
files/robots.php                     the two new endpoints are disallowed
files/lang/tr.php, files/lang/en.php 37 new strings
files/schema.sql
files/version.txt
```

## Deployment note

- `files/functions.php` and `files/functions/relation_helpers.php` must be
  uploaded **together**. If the loader cannot find the file, **every page**
  dies.
- `files/edit_anime.php` and `files/anime_details.php` call the new helper, so
  they belong in the same package.
- `files/lang/*.php` too, otherwise key names show instead of text.
- `files/css/series.css`, `files/css/components.css` and `files/js/anime_form.js`
  must go with `files/version.txt`: the version stamp on those links comes from
  `version.txt`, and a stale stamp leaves the browser drawing the new page with
  the old CSS and the old script (no tabs).
- If `files/add_anime.php` / `files/edit_anime.php` land without
  `files/js/anime_form.js`, the tab bar never appears and the form falls back to
  one long list — not broken, but half the release is invisible.
- **Nothing to do on the central catalog server.** Relations, like "Next Anime"
  and "Chain name", are application-local: no new field on the catalog wire, no
  manual `ALTER`, no changed file under `catalog_server/`.
- The migration adds a single table and runs itself.
- **On the distribution server**, the usual two steps: publish `version.txt` as
  1.1.38 and the `updates/1.1.38/anime-tracker-1.1.38.zip` package.

## What comes next

The third and final step of the plan: moving sequel/prequel into the table too
and retiring the "Next Anime" field. It is on hold because today's data has no
**branching** — no entry has turned out to have two separate continuations.
