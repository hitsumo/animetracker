# Anime Tracker 1.1.33

**Release date:** 2026-08-25

This release does two things. The first is the main one: **the synopsis of a
later season gives away the earlier one.** The second is a much larger help
section — see the end of this page.

The summary of a second (or third, or fourth) entry in a series almost always
describes how the previous one ended. A synopsis that opens with "after X
died, the remaining crew..." tells someone who has not watched it yet nothing
useful — but it spoils the whole previous season. Opening a later season's
page just to check the episode count was enough to read it.

From now on the synopsis on such a page is not printed directly; it waits
behind a **"Let me read it anyway"** button.

## What it looks like

If any entry that comes before this one in the chain is unwatched, the
"Synopsis" row shows a box instead of the text:

> Seri S2 and 1 more entry in the chain are still unwatched — this synopsis
> may spoil earlier seasons.
>
> **[ Let me read it anyway ]**

Pressing the button expands the synopsis in place and the label turns into
"Hide synopsis". If only a single entry is missing, the wording is singular:
"Seri S2 is still unwatched — this synopsis may spoil the earlier season."

When **all** earlier entries have been watched there is no button at all; the
page looks exactly as it did in 1.1.32.

## The rule

| Situation | Result |
|---|---|
| Every earlier entry in the chain is watched | Synopsis shown directly |
| One of the earlier entries is unwatched | Synopsis behind the button |
| An earlier entry is **half** watched ("Watching") | Synopsis behind the button |
| You are watching / finished / dropped this anime | Synopsis shown directly |
| The anime is not part of any chain | Synopsis shown directly |

Three points were deliberate:

**All earlier entries are checked, not just the immediately preceding one.**
On the page for S3, S2 may be watched while S1 was skipped — and the synopsis
can still give S1 away. A "look at the nearest link only" rule would not have
protected that reader.

**A half-watched season does not count as watched.** A summary that describes
what happens after the point you stopped is a spoiler too.

**No guard on an anime you have started.** There is nothing left to spoil in
the synopsis of an anime you are already watching (or finished, put on hold,
or dropped) — even if you skipped the previous season, you learned it while
watching this one.

## Where it applies

- **Anime detail page** — the "Synopsis" row.
- **Recommendations → Surprise** — the 200-character teaser on the card.
  Without this the protection would leak: the detail page would hide the
  synopsis while the surprise card printed its opening sentences, and the
  spoiler is usually in the first sentence.

**The Personal Synopsis is outside the guard.** You wrote that text and only
you can see it; there is no point protecting you from your own note.

## For signed-out visitors

An anonymous visitor has no personal watch data, so nothing counts as
watched and later entries open behind the button for them as well. Someone
browsing the catalog for the first time is exactly the person who needs the
protection, and it costs one click.

## It can be turned off

**List Settings → Spoiler Guard** has a checkbox, **on** by default. With it
off no button ever appears and the synopsis is always shown directly. The
preference is per-user and affects only your own account.

## Small notes

- The box is the browser's own disclosure element (`<details>`) and uses **no
  JavaScript**: it opens with scripts disabled, it is keyboard accessible, and
  opening it costs no extra request — the text already arrived with the page.
- The description the detail page gives to search engines is unchanged. That
  description is a single text published to the whole world; it does not vary
  per visitor.
- The main list page never printed a synopsis, so nothing changed there.

## The help pages grew

A second job in the same release: the help was reviewed from scratch and the
features that **had been built but never documented** were written up. Four
new sections:

- **List, Search and Filters** — the difference between the Full List and My
  List and the default-tab preference, what the search box actually scans,
  the six filters (genre, watch status, broadcast status, letter, year,
  country), where the emotion filter comes from, "Items Per Page" and the
  sort arrows, plus the "Recently Updated" page.
- **Personal Preferences** — all seven preferences from List Settings in one
  list, with the interface language and adult content (18+) explained in
  full.
- **Moving and Importing Lists** — taking and restoring a JSON backup,
  importing a MyAnimeList file, importing by AniList username (both modes
  and the preview step), and how irreversible "Clear the List" really is.
- **Membership and Contributing** — signing in, registering, requesting an
  invite, the account page, what each of the four roles can do, how an anime
  you add lands in the approval queue, and the "Suggest a correction" box.

Three sections were added to "Series and Episode Info": the **Series
Chronology page** (its tabs and the "Other Chain" headings), the **Spoiler
Guard** (the full rule above) and **Broadcast Info and Countdown**.

A few corrections as well: the `+/-` buttons are now noted as being on the
detail page too, start/finish dates joined the personal-fields list and
Country joined the catalog-fields list, sentence tags are assigned by
curators rather than "the admin", the update section is marked as visible
only on a personal install, and one internal link that led nowhere was fixed.

## Changed files

**New:**

```
files/set_spoiler_pref.php
files/help/help_list.php
files/help/help_prefs.php
files/help/help_transfer.php
files/help/help_account.php
files/migration/1.1.33/upgrade.sql
```

**Changed:**

```
files/functions/series_helpers.php   (walking the chain backwards + the guard)
files/anime_details.php              (Synopsis row)
files/recommendations.php            (Surprise card teaser)
files/list_settings.php              (Spoiler Guard checkbox)
files/css/base.css                   (the look of the box)
files/help.php                       (contents: four new groups)
files/help/help_series.php           (three new sections)
files/lang/tr.php, files/lang/en.php (128 new strings each + fixes)
files/version.txt
```

## Deployment note

- `files/functions/series_helpers.php` and the three pages
  (`anime_details.php`, `recommendations.php`, `list_settings.php`) must be
  uploaded **together**. If the helper file stays behind, those pages call a
  function that does not exist and fail to open.
- If `files/css/base.css` stays behind the guard still **works** but looks
  plain (an unstyled disclosure row); nothing is lost functionally.
- If the language files stay behind, the key name shows instead of the notice.
- If `files/set_spoiler_pref.php` is missing the guard still works; only the
  checkbox in List Settings returns 404.
- `files/help.php` and the four new help pages must be uploaded **together**;
  the table of contents links to them. A help side left behind does not
  affect the rest of the application.
- Nothing to do on the central catalog server: the schema did not change and
  the catalog wire was not touched.
- **On the distribution server**, the usual two functional steps: the
  published `version.txt` must be moved to 1.1.33 — otherwise "Check for
  updates" still thinks 1.1.32 is the latest — and the package
  `updates/1.1.33/anime-tracker-1.1.33.zip` must be published, otherwise the
  "Update" button gets a 404 at the download address.
