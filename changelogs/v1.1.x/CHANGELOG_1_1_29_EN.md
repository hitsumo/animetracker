# Anime Tracker 1.1.29

**Release date:** 2026-08-23

Two pieces of work: a countdown for anime that have not started airing yet,
and completing the project's GPL-2.0 licence notices. The second one is
comments only — it has no effect on screen.

## New: "time until episode 1" for anime that have not started

- **The problem:** for an airing anime the detail page counted down —
  "Next Episode: 3 d 4 h". For an anime that had **not started** it only
  printed "Release Date: 07.10.2026", leaving you to count the days on a
  calendar.
- **Everything needed was already stored:** the release date, the broadcast
  time and the broadcast timezone. 1.1.28 brought all three to the form and
  the detail page for upcoming anime; the only missing step was building a
  countdown out of them.
- **Now it does.** A new **Premiere** row appears on the detail page:
  *"Time until ep. 1: 45 d 7 h"*. The format is identical to the airing case —
  same calculation, same look.
- **The list page too.** The "next episode" column showed a dash (`-`) for
  upcoming anime; it now shows the same countdown.
- **The label is deliberately not "Next Episode".** With no episode aired yet,
  "next" would be misleading.
- **If no broadcast time is recorded** the countdown uses midnight on that
  date. The day count is still right; only the hours within the day are a
  guess.
- **If the release date has passed but the status is still "not started"**,
  the row reads *"Premiere date has passed"*. The airing message ("New episode
  aired") would be wrong here — nothing has aired; this is a hint that the
  record's status needs updating.
- **Nothing is written to the database.** The countdown is computed on every
  render; no derived value is stored.

## Licence notices

Everything in this section is comment lines; no running code is affected.

### Why

Anime Tracker is GPL-2.0 licensed, and the full licence text already shipped
both in the repository (`LICENSE.txt`) and inside the installed application
(`files/license.txt`) — so nothing was missing licence-wise. What was missing
were the **per-file notices:** 80 of 195 source files carried none at all, and
the ones that did were written in three different styles.

This was not a violation; GPL-2 does not require per-file headers. But having
files with and without a notice sitting side by side in the same folder makes
a first-time reader ask "is this file outside the licence?" In an open-source
project that question should never come up.

### What changed

- **A short notice was added to the 80 files that had none.** The wording is
  the one already used by the majority of the codebase:

  ```
  Anime Tracker - <file title>
  https://www.sicakcikolata.com
  Copyright (C) 2025-2026 Okan Sumer
  Licensed under GNU General Public License v2
  ```

  Each file got it in its own comment syntax. Breakdown: 60 migration files,
  five application pages (`recent`, `series_timeline`, `statistics`,
  `sync_aired`, `admin_catalog_requests`), `anime_form.js`, four
  example/template files, four packaging files (Dockerfile, Docker Compose,
  Docker entrypoint, Windows installer script) and three one-off CLI scripts.

- **The full GPL header in 21 files was normalised.** Those headers still had
  the FSF template's square-bracket placeholders in them — the copyright line
  literally read `Copyright (C) 2025 [Okan Sumer]`. The brackets are gone and
  the indentation is aligned. The same 21 headers had drifted into six
  whitespace variants; they are now one text. In `list_settings.php` the
  comment block was accidentally opened twice (a `/**` inside a `/**`); that
  is fixed too.

- **`db.php`'s truncated notice was completed.** It ran as far as the warranty
  disclaimer but the final paragraph ("You should have received a copy of the
  GNU General Public License…") was missing. It was the only half-written
  notice in the codebase.

- **Copyright year is now `2025-2026` instead of `2025`.** The project was
  published in 2025 and is still being developed in 2026. A copyright line was
  also added to both the Turkish and the English licence section of the README.

- **The author name is now spelled one way.** Two spellings were in
  circulation; since code comments in this project are written in ASCII
  Turkish, the majority form won. The README, being prose, keeps the proper
  spelling.

### What did not change

- `LICENSE.txt` and `files/license.txt` were left alone — both are the full
  GPLv2 text and identical to each other. The Docker image and the Windows
  installer already shipped the licence.
- No licence-acceptance page was added to the Windows installer. GPL does not
  ask for click-through consent, and `license.txt` sits in the install
  directory.
- No visible licence/warranty text was added to the About page. That can be
  done later; a notice-cleanup release is not the place to quietly change the
  interface.

## Verification

That the **licence sweep** touched nothing but comments was **measured**, not
assumed:

- All 331 PHP files were compared before and after with comments and
  whitespace stripped: **331/331 byte-identical.**
- The same 331 files were run through the parser: 331/331 passed.
- The scan was re-run: **195/195** source files now carry a copyright and
  licence line (it was 115/195 before).
- In files that use CRLF line endings the inserted lines were written with
  CRLF too; no file's line-ending style was disturbed.

The **countdown** was measured separately — both that it works and that it
breaks nothing else:

- The date arithmetic was exercised in 13 cases: the real record
  (07.10.2026 23:45 JST → 07.10.2026 14:45 UTC), a record with no time, one
  with no timezone, an invalid timezone, Istanbul time, a malformed date,
  missing fields, and four different broadcast statuses. 13/13 as expected.
- The detail page's broadcast block was rendered **from lines pulled out of
  the file itself**, across five broadcast statuses × chronology markers
  present/absent = 10 cases. The result was compared with the same test on
  1.1.28: **only the "not started" rows changed**; the other eight came out
  byte-identical.
- The same test was run on the list page cell: of seven cases **exactly one**
  changed (upcoming anime, `-` replaced by the countdown); six unchanged.
- The chronology button was counted in every case: **exactly once**, so the
  rule established in 1.1.28 still holds.
- Of the 331 PHP files, exactly **five** differ semantically from the
  licence-sweep state — the five that carry this feature. The other 326 are
  byte-identical with comments stripped.

## Schema / migration

- **No schema change.** No new table, column or preference — the countdown is
  computed on every render, never stored.
  `migration/1.1.29/upgrade.sql` only carries the version stamp.
- **No manual step is needed on the central catalog database** — the catalog
  wire was not touched.

## Deployment note

**On the application server** 195 files changed, but in 190 of them the only
difference is a copyright/licence comment. The five files that carry the
countdown must be uploaded **together**:

```
files/functions/anime_helpers.php    (the calculation and its wording)
files/anime_details.php              (the Premiere row)
files/index.php                      (the list column)
files/lang/tr.php, files/lang/en.php (the Premiere label)
```

Plus `files/version.txt` and the new `files/migration/1.1.29/` folder. The
remaining files are cosmetic and can be uploaded whenever convenient.

**Partial-upload warning:** if `anime_helpers.php` stays old, the detail page
calls a function that does not exist and the page fails to load. If the
language files stay old, the key name shows instead of the label. Treat those
five as one unit. No such dependency exists among the other 190 files.

The files in the old migration folders (`0.5.1` … `1.1.28`) changed as well,
but they do not need to reach the application server again: the migration
runner only executes folders numbered above the current version, so those
files are never read again.

**On the distribution server** (the one installations check for updates)
there are two functional steps: the published `version.txt` must be moved to
1.1.29 — otherwise "Check for updates" still reports 1.1.28 as the latest —
and the `updates/1.1.29/anime-tracker-1.1.29.zip` package must be published,
otherwise the "Update" button gets a 404 at the download address. The
`catalog.php`, `admin_push.php` and two example config files on that same
server changed too, but the only difference in them is the copyright line.
