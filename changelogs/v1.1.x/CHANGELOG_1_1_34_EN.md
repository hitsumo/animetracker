# Anime Tracker 1.1.34

**Release date:** 2026-08-26

A small release with no schema change. It carries three separate pieces of
work, two of which were added after the main one:

1. **Picking a watch status is no longer required when you add an anime** —
   the main subject, right below.
2. **The spoiler guard now matches the page** — the box introduced in 1.1.33
   is gone.
3. **The installer backup filename** — concerns the Windows `.exe` installer
   only.

## The problem

On the add-anime form the "Watch Status" dropdown opened on **"Select..."**,
and the field was marked required. The browser does not accept that row as an
answer, so an anime **could not be saved** without choosing a watch status —
the form simply refused to submit and showed "please fill in this field".

Yet leaving the watch status empty is a legitimate state for this field.
Anime imported from the catalog enter your list exactly that way, with no
watch status at all, and the list and detail screens render those records as
"Not Selected". The data side already supported it; only **manual adding**
forbade it.

There was a way out: the "Not Selected" option at the **bottom** of the list.
But it meant almost the same thing as "Select..." at the top, so in practice
nobody found it — two rows both saying "I haven't chosen yet", one accepted,
the other rejected.

## What changed

The empty "Select..." row is gone. **"Not Selected" is now the first option
and is selected by default.**

So if you never touch the watch status while adding an anime, the anime is
saved with no watch status. The list shows "Not Selected", and you can set a
real status later from the detail or edit screen whenever you want. Nothing
changes for anyone who does want to pick a status — the options are the same.

| Before | After |
|---|---|
| Select... *(default, blocks saving)* | **Not Selected** *(default, saves)* |
| Watched | Watched |
| Watching | Watching |
| Plan to Watch | Plan to Watch |
| On Hold | On Hold |
| Dropped | Dropped |
| Not Selected | — |

## The edit screen

The same dropdown on the edit-anime screen was reordered to match. Its
behaviour was already correct — opening an anime with no watch status
preselected "Not Selected", which is why nobody ever got stuck there. Only
the order changed, plus the removal of the dead "Select..." row; the two
screens now show the same list in the same order.

## What did not change

- **Airing Status is still required.** That field is the anime's own data,
  not a personal preference: every anime has an airing status, and leaving it
  blank is not a choice but a missing record. The MyAnimeList and AniDB links
  stay required too.
- **Existing records were left alone.** Every watch status you have picked so
  far stands as it is; no bulk correction was run.
- **Personal Synopsis, notes, watch dates** and everything else are unchanged.

## The spoiler guard now matches the page

The second piece, again a separate subject. The spoiler guard introduced in
1.1.33 sat inside a **box**: dashed orange border, cream background, inner padding.
The anime detail page, however, is a plain row layout — label on the left,
value on the right, a thin grey rule underneath. The box looked like a patch on
that layout: the season you had watched read as a plain line, while the one you
had not looked like a coloured panel.

The guard is now plain too:

- **Border, cream background and padding are gone.**
- **The notice** uses the same grey and the same size as the page's other small
  notes (the translation note, for instance).
- **"Let me read it anyway" / "Hide synopsis"** is no longer a filled orange
  button but plain blue text — **with no underline**. It darkens on hover.
  Links inside a sentence need the underline, since nothing else separates
  them from the prose; here the label stands alone on its own line, and the
  colour already says it is clickable.

**The behaviour did not change**: same rule, same places, same preference. Only
the appearance was brought in line with the rest of the page. The teaser on the
Recommendations → Surprise card gets the same simplification.

## The installer backup filename

The third piece, unrelated to the other two. When the Windows `.exe`
installer runs on a machine that already has the database, it drops a
database backup on the desktop before touching any files. The date in that
backup's filename came from a constant written by hand when the `.exe` was
**compiled**.

Two problems. The cosmetic one: nobody remembered to update the constant, so
the name showed the day the update was forgotten rather than the day of the
install. The real one: because the name was fixed, **a second install from the
same `.exe` silently overwrote the first install's backup.** That backup exists
precisely so you can go back if an install goes wrong; losing the point you
would return to defeats it.

The stamp is now generated **at install time**, not at compile time, and
includes the clock:

```
at_install_backup_2026-08-26_182630.sql
```

Two installs on the same day now write to separate files, and there is no
longer a value anyone has to remember to update.

This only concerns the `.exe` installer; it has nothing to do with the
application files on a server or with the update package.

## Changed files

**New:**

```
files/migration/1.1.34/upgrade.sql
```

**Changed:**

```
files/add_anime.php    watch status dropdown + empty value treated as empty
files/edit_anime.php   same dropdown order
files/css/base.css     spoiler guard: box removed, toggles are plain blue
                       text (no underline)
files/version.txt
installer.nsi          installer backup filename (.exe build only)
```

## Deployment note

- No database schema change; `files/migration/1.1.34/upgrade.sql` is a version
  stamp only.
- **What goes to the server** (`installer.nsi` does not — it lives outside the
  `files/` tree): `files/add_anime.php`, `files/edit_anime.php`,
  `files/css/base.css`, `files/version.txt` and the new
  `files/migration/1.1.34/` folder.
- The files have no **code** dependency on each other: if one is left behind,
  only its own page keeps the old behaviour and nothing breaks. There is no new
  function, helper or language key, so the "old file calls a new function"
  failure cannot happen in this release.
- **One exception:** `files/css/base.css` and `files/version.txt` must go
  **together**. The version stamp on stylesheet URLs is derived from
  `version.txt`; if the version is left behind, the browser sees the same stamp
  and keeps using the cached old stylesheet, so the guard may still look boxed.
  The order does not matter, only that both go. **If you are skipping 1.1.33 and coming straight to this
  release**, nothing extra is needed: `base.css` also carries the spoiler rules
  introduced in 1.1.33 — one copy brings both versions.
- Nothing to do on the central catalog server — watch status is a personal
  field and never travels on the catalog wire.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must be moved to 1.1.34 — otherwise "Check for Updates" still
  believes 1.1.33 is the latest — and the
  `updates/1.1.34/anime-tracker-1.1.34.zip` package must be published, or the
  "Update" button gets a 404 at the download address.
