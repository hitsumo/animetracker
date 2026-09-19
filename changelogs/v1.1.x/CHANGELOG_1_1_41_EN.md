# Anime Tracker 1.1.41

**Release date:** 2026-09-13

Schema release; **a manual `ALTER` on the central catalog server is
required.** One piece of work: a MAL or AniDB record may now be **more than
one anime** in the catalog.

## 1. The problem: the sources disagree about what "one anime" is

MAL keeps *Death Note: Rewrite* as **one** two-episode record (2994). AniDB
keeps the same production as **two** one-episode records. The catalog wanted
to follow AniDB — two entries — and point both at MAL 2994, but the MAL
number was unique, so the second entry was refused with "this MAL ID already
exists". The mirror case exists too (AniDB single, MAL split), so the AniDB
number gets the same treatment.

Uniqueness was not just a table constraint. The MAL number served as an
identity key in six places, each assuming "one number = one entry": the
add/edit form, MAL and AniList list import, catalog sync (pull and push), the
JSON backup, the import blacklist and the `[[anime:...]]` synopsis links.
All of them now understand parts.

## 2. The solution: a part number

Every entry now carries a **part number** next to its MAL and AniDB numbers
(`mal_part`, `anidb_part`). Every existing entry is part 1 of its number;
uniqueness is now on the *(number, part)* pair. The world as it was is a
special case of the new one: one part per number, nothing changed.

**A checkbox on the form,** under the MAL link: *"This MAL record corresponds
to more than one anime"* (a separate one for AniDB).

- **Ticked:** the entry takes the **next free part number** for that number
  by itself (2/2, 3/3 …). Nobody types a number.
- **Unticked:** a second entry with the same number is **still refused** —
  an accidental duplicate is still an accident. The refusal page now adds:
  *"if you mean to split on purpose, tick the box."*

The box is **not stored**; whether an entry is shared is derived from the
data (does another entry carry the same number?), so the form can never
disagree with the table. On the edit screen the box comes ticked for a
shared entry with *"(currently part 2/3)"* next to it.

**Unticking:** if other entries carry the number, the save is refused — this
entry cannot go back to being "the only one" while siblings exist; part 1 is
either this entry already or a sibling's. Separate the other parts first. If
nobody else carries the number, the entry settles to part 1: a part 2 whose
sibling was deleted heals itself on its next save.

**Why a real column and not a flag:** everything that addresses an entry by
identity (imports, sync, backup, blacklist, synopsis links) must be able to
say *which* part it means. "2994" is not enough; "2994/2" is.

## 3. Where it shows

**Anime detail.** The External Sites button reads *"MyAnimeList · 2/3"*
(shared entries only; a single-part entry looks exactly as before). And a new
section, **Same Source Record**: the other entries carrying the same MAL /
AniDB number, with their part numbers. Derived from the data; no relation is
needed. (The curator may still add a typed relation such as *alternative
version* between the parts; that is a separate statement, not required.)

**Synopsis links.** The `[[anime:2994/2|label]]` form arrived. The old
`[[anime:2994]]` keeps meaning what it always meant — part 1 — so no existing
synopsis changed meaning. The link picker writes the `/2` suffix only when
the number is shared and shows *"MAL 2994 · 2/3"* in the list; for an
unshared number the code is the bare number, as before.

## 4. The import rule

A MAL or AniList list carries **one line** per number (status + watched
count); the catalog may hold N parts for it. The count cannot be split
between parts (2/3 → one to A and one to B? two to A?), so no invented
number is ever written:

1. **The status goes to every part** (Watched / Watching / Planned /
   Dropped …).
2. **The watched count is left alone.**
3. Two exceptions: a **0/N** line writes 0 to every part (someone who has not
   started has started no part); a **Watched** line writes each part's **own
   total** (someone who finished the whole finished every part) — status
   only where the total is unknown.
4. A separate sentence is appended to the result: *"N entries share a MAL
   identity with other entries; check their episode counts by hand."*

| MAL line | Part A (1 ep) | Part B (1 ep) |
|---|---|---|
| Completed 2/2 | Watched, 1/1 | Watched, 1/1 |
| Watching 1/2 | Watching, count untouched | Watching, untouched |
| Plan to Watch 0/2 | Planned, 0 | Planned, 0 |
| Dropped 1/2 | Dropped, untouched | Dropped, untouched |

For an unshared number the import behaves exactly as it has since 1.1.1; the
rule only engages on a shared one.

## 5. Everywhere else

- **Auto-fill** (AnimeSchedule): with the box ticked the **total / aired
  episode counts are not filled** — the source counts the whole, not this
  part. One line is added to the report; the other fields (status, broadcast
  day/time, dates) still fill.
- **JSON backup** carries the parts; the identity quadruple describing the
  other end of a chronology marker or a relation carries them too. A
  pre-1.1.41 backup reads as part 1 everywhere — it restores *exactly* as it
  did before.
- **Catalog sync.** The central catalog publishes the two part fields; pull
  matches on *(number, part)*, push updates on the same pair. A wire from a
  pre-1.1.41 server reads as part 1; such a server can only hold one entry
  per number anyway, so local parts 2 and 3 count as "not in the catalog"
  and drop to *local* — the right outcome.
- **Blacklist.** A number enters the list only when its **last part** is
  deleted. Deleting part 2 of 2994 while part 1 is still in the catalog
  cannot mean "this number does not belong in the catalog". The deletion is
  still recorded (title), without a number.
- **The suggestion queue** knows no parts (one number = one suggestion); an
  approved suggestion is born as part 1. Chronology markers carried by a
  suggestion carry the other end's part.

## 6. Fresh-install fix

Found while testing this release, closed with it: a **fresh install** from
`schema.sql` replays the whole migration chain, and the chain **broke at
1.1.20** — every self-host installed from scratch since 1.1.21 silently
stayed at 1.1.19. Cause: the 1.0.6 migration creates `catalog_requests` with
`CREATE TABLE IF NOT EXISTS` and a `title_english` column; schema.sql had
already created the table (without that column), so the CREATE is skipped,
the column is never born, and 1.1.20 fails reading it. The fix lives only in
schema.sql: the column is defined there "for replay", the way the personal
columns on `animes` are; 1.1.20 reads it, 1.1.21 drops it at the end. No
migration file was touched; existing installs are unaffected. The full chain
ran on an empty database: **92 migrations, 0.5 → 1.1.41**, the column gone
from both tables at the end.

## Verification

- The migration ran on a **copy** of the local database (8,113 anime) with
  the real MigrationManager logic: 1.1.26 → 1.1.41, 15 migrations. Every row
  part 1; both composite unique keys in place. Version stamp reset to 1.1.40
  and run **a second time** (index dropped and rebuilt, same result); third
  run 0 migrations. `schema.sql` loaded into an empty database with 1.1.41
  run on top: same result.
- 37 helper-level checks: part counting, next part, add and edit decisions
  (ticked / unticked / number changed / lone survivor), the composite key
  refusing a duplicate, sibling list, badge and code generation, the five
  import-rule lines, synopsis links (`2994` → part 1, `2994/2` → part 2,
  unknown part → plain text), remaining-carrier count on delete.
- End to end (local PHP server + curl): adding with the box ticked (2994/2,
  then 2994/3); adding unticked → refusal page with the new hint; edit form
  comes ticked with *"(currently part 2/3)"*; unticking → refused; saving
  ticked keeps the part; detail page shows *"MyAnimeList · 2/3"* and the
  *Same Source Record* section (parts 1 and 3); link-picker results carry
  `2994/1`, `2994/2`, `2994/3` and badges; MAL XML import with four lines
  (Completed 2/2, Watching 1/2, Plan to Watch 0/2, Dropped 1/2) → the table
  above exactly, plus the result sentence; parts in the JSON backup; part 3
  deleted and **restored** from the backup (as part 3; the relation and
  chronology marker attached to it linked to the right end — without parts
  the marker would have resolved to part 1, i.e. itself, and been skipped);
  a backup in the pre-1.1.41 shape (part fields stripped) matched part 1 and
  created no duplicate.
- **Catalog sync against a mock center:** the `catalog_server/` files ran on
  a second local PHP server over a "center" database derived from the 1.1.26
  copy and `ALTER`ed by hand. Full push: 8,113 updated, 2 inserted (2994/2
  and 2994/3 with the right parts on the center); second push 0 inserted,
  8,115 updated. Pull into a fresh 1.1.41 install: 2 new, 8,113 updated,
  parts carried; second pull 0 new. A **pre-1.1.41 server** imitation (no
  part fields, one entry per number): part 1 updated, parts 2 and 3 dropped
  to *local*.
- In the browser: both boxes visible on the edit form, the MAL one ticked
  and labelled *"(currently part 2/3)"*; the new section on the detail page
  with its teal stripe. The pane was unstable again; the DOM was read
  instead of a screenshot.

## Files

**New:**

```
files/functions/identity_helpers.php   part rules, siblings, import rule
files/migration/1.1.41/upgrade.sql     two columns + two composite unique keys
```

**Changed:**

```
files/functions.php                     loads the new helper
files/functions/synopsis_helpers.php    [[anime:N/P]] grammar
files/add_anime.php                     boxes, part assignment, refusal hint
files/edit_anime.php                    boxes (state from data), part decision, refusal
files/anime_details.php                 "· 2/3" badge + Same Source Record section
files/anime_link_search.php             ref + badge
files/js/synopsis_link.js               writes the code from ref, shows the badge
files/js/anime_form.js                  auto-fill sends box state + report line
files/fetch_animeschedule.php           drops episode counts for a shared entry
files/list_settings.php                 backup (parts), MAL/AniList import rule
files/catalog_import.php                (number, part) matching; two fields
files/admin/catalog_push.php            two fields + id_map
files/admin/admin_catalog_requests.php  approval resolves identity part-aware
files/index.php                         delete blacklists only the last part
files/css/series.css                    stripe of the new section
files/schema.sql                        columns + composite unique keys;
                                        catalog_requests.title_english (replay)
files/lang/tr.php, files/lang/en.php    13 new keys each (parity 1051 = 1051)
files/version.txt
catalog_server/catalog.php              publishes the two fields
catalog_server/admin_push.php           (number, part) matching; two fields
```

CSS and JS changed, so the 1.1.24 version stamp applies (upload together
with `version.txt`).

## Deployment note

- **A manual `ALTER` on the central catalog server is required — order is
  critical.** The migration does not run there.
  1. On the center: add the `mal_part` and `anidb_part` columns and make the
     two unique keys composite. The statements are at the end of
     `files/migration/1.1.41/upgrade.sql`; check the index names with
     `SHOW INDEX FROM animes` first.
  2. Deploy the new `catalog_server/catalog.php` and `admin_push.php`.
  3. Deploy the app (the migration runs on the first page load).
  4. Full catalog push.

  In the wrong order: app first, and pushing a shared entry fails on the
  center with "duplicate"; center `ALTER` skipped, and the new `catalog.php`
  selects an unknown column — catalog 503, push fails.
- `files/functions.php` and `files/functions/identity_helpers.php` must be
  uploaded **together**; the loader line without the file takes every page
  down with "undefined function".
- Because the columns are **added** (not dropped), old files keep working on
  the new schema (they do not read the part; the default of 1 carries them).
  New files do not work on the old schema; the migration ships in the same
  package and runs on the first request.
- `files/js/anime_form.js`, `files/js/synopsis_link.js` and
  `files/css/series.css` go together with `version.txt` (stamp).
- **On the distribution server** the usual two steps: the published
  `version.txt` must be moved to 1.1.41 and the
  `updates/1.1.41/anime-tracker-1.1.41.zip` package published.
- Self-host installs run the same migration; no extra step.
