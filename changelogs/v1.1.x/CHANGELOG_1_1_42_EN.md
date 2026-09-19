# Anime Tracker 1.1.42

**Release date:** 2026-09-16

No schema change. One piece of work: the help did not describe what the
last three releases added — now it does. No behaviour changes; only the
help pages and the language files changed.

## 1. Help coverage, second round

1.1.33 had extended the help with four new groups. The releases after it
added three big features, and the help did not follow:

| Release | Feature | State in the help |
|---|---|---|
| 1.1.38 | Typed relations ("Alternative Version", "Side Story"...) | **Absent** |
| 1.1.38 | The add / edit form was split into five tabs | The word "tab" never appeared |
| 1.1.40 | Order is a relation ("Sequel" / "Prequel"; the "Next Anime" box went away) | One sentence |
| 1.1.41 | Shared MAL / AniDB record (part number) | **Absent** |
| 1.1.15 | How chronology notes are added / edited / deleted | "What" was there, "how" was not |
| 1.1.19 / 1.1.26 | The `[[anime:...]]` link code in a synopsis and the link picker | **Absent** |
| 1.1.35 | The import blacklist | **Absent** |

This release closes those seven gaps.

### Series and Episode Info page

- **The introduction was rewritten:** anime that belong together are
  described on three layers — series name ("which family?"), relations
  ("what is one record to the other?") and chronology notes ("after which
  episode comes which film?").
- **Series Info:** where the series name is entered, what it feeds (the
  target list of the note form, the "Air Date" tab of the Series
  Chronology) and that it says nothing about order. The section name was
  corrected: the detail-page section is "Related Anime".
- **Watch Order:** the link is only followed when both ends carry the same
  chain name.
- **Chronology Notes:** what a note is attached to (the series' record; the
  target is a separate record), the three places it shows (list, active
  alert, the "Chronology" page). **New:** *Adding, Editing and Deleting a
  Chronology Note* — who may, when the form appears, what the four fields
  mean, which point in-place editing changes, deletion. **New:** *Are
  "Chronology" and "Series Chronology" the same thing?* — one looks inside
  a single series, the other at the whole franchise.
- **New section: Relations.** The six types and their definitions
  (Sequel / Prequel as the only ordering type; Alternative Version;
  Alternative Setting; Side Story / Parent Story; Summary / Full Story;
  Other). Direction: the form's single question ("the anime you picked is
  the ___ of the anime you are editing"), why the three directional types
  appear twice, why the other three have no direction. Rules: at most one
  relation per pair, no self-relation, the chain-name rule, relations
  staying on this installation and going into the backup. *Link or
  relation? — How a chain is built:* three steps and the test ("a link
  means: when you finish this, watch that"), with the Sailor Moon Crystal
  example. Box: where the "Next Anime" box went.
- **Series Chronology:** the chain tab is now described as built from
  "Sequel / Prequel" links and points at the new section.

### Fields and Personal Data page

- **New section: The Add / Edit Form — Tabs.** What each of the five tabs
  holds; that it is one form saved by one click; that a required field
  left empty on another tab makes the form jump there; that the last tab
  is remembered; why the Relations panel exists only on the edit form (it
  links two *existing* records); who sees which form.
- **New section: Shared MAL / AniDB Record.** Why it exists (the source
  and the catalog may disagree on what "one anime" is), how to use it (the
  checkbox, the part number arriving by itself, an unticked duplicate
  being refused, the box deriving from the data when editing and not being
  untickable), where it shows (the "MyAnimeList · 2/3" badge, the "Same
  Source Record" section) and what it affects (the MAL / AniList import
  rule, Auto-fill, the `[[anime:2994/2]]` synopsis link, deletion and the
  blacklist, backup and catalog).
- **New section: Linking Another Anime in a Synopsis.** What the
  `[[anime:2994|Death Note]]` code is, why it is a code and not a plain
  link (the text travels to every installation, a MAL id is the same
  everywhere; a target missing from the catalog falls back to plain text),
  the "Add anime link" button (searches the local catalog, writes the code
  at the cursor, carries the part for a shared record) and the rules when
  typing by hand.
- "Next in series" left the catalog-field list (retired in 1.1.40); the
  MAL / AniDB line links to the shared-record checkbox. New note: the chain
  name, relations and the chronology notes you added yourself are neither
  catalog fields nor personal — they are this installation's curation
  data; sync leaves them alone and they go into the backup.

### Import / Export page

- **New section: Import Blacklist.** Multi-user site only; the problem it
  solves (a deleted anime came back through imports), how it works
  (automatic on deletion, id-only matching, cuts only unmatched records,
  never blocks an anime that is in the catalog, the last-part rule for
  shared records), the admin page, and that it is not part of the backup.

### Table of contents

Five new entries: *The Add / Edit Form — Tabs*, *Linking Another Anime in
a Synopsis*, *Shared MAL / AniDB Record*, *Relations*, *Import
Blacklist*.

## 2. Corrected misinformation

The warning box in the help said **"If you added markers yourself, they
are lost after the next sync."** That was not true: importing from the
catalog only refreshes the notes that came from the catalog; **notes you
added yourself are not deleted**, and List Settings reminds you of them
with the "not in sync with the catalog" warning. The help was describing
an older version of the code. The text was replaced with the correct one.

## Files

**New:**

```
files/migration/1.1.42/upgrade.sql   (no schema change; version stamp + rationale)
```

**Changed:**

```
files/help.php                    table of contents (+5 entries)
files/help/help_series.php        +2 subsections, new "Relations" section
files/help/help_fields.php        new "Tabs", "Synopsis Link" and "Shared Record" sections
files/help/help_transfer.php      new "Blacklist" section
files/lang/tr.php                 47 new + 10 changed keys
files/lang/en.php                 same
files/version.txt
```

Language-file parity: 1098 = 1098. No new CSS/JS.

## Deployment note

- The help pages print keys from the language files. If the pages go up
  and the language files stay old, nothing crashes but key names show
  instead of text. **Upload `lang/tr.php` and `lang/en.php` first or
  together;** the other direction is harmless (extra keys bother nobody).
- The migration changes no schema; it only moves the version stamp and
  runs on its own.
- **Nothing to do on the central catalog server.**
- **On the distribution server**, the usual two steps: set the published
  `version.txt` to 1.1.42 and publish
  `updates/1.1.42/anime-tracker-1.1.42.zip`.
- Self-hosted and online installations see the same text; the role
  sentences describe both modes.
