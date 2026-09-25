# Anime Tracker 1.1.47

**Release date:** 2026-09-22

One item: **two more relation types.** The vocabulary that describes the
link between two records gained **Same Setting** and **Shared
Character**. There is a schema change (one enum widened, install-local);
nothing to do on the central catalog.

## 1. Why

The relation vocabulary had five types in 1.1.38 and six in 1.1.40;
AniDB's has eleven. Two of the missing ones were the two ways two works
can touch **without sharing a story**, and both fell into "Other
Relation". The help text even used them as the examples for "Other": "a
shared universe, a guest character". So the right answer was known but
could not be chosen, and picking the "closest" type would have corrupted
the vocabulary.

The triggering case: **Hana no Ko Lunlun** (1979) and **Hua Xianzi: Mofa
Xiang Dui Lun** (2026). The same flower-fairy world, a new heroine two
generations on. The producer calls it a sequel; the content is a new
heroine in the same world — and the series ended in 1980, the new work
is not watched as its continuation. Until now the closest available
type, "Alternative Setting", had been chosen, which says the exact
opposite (same characters, another world).

## 2. The two types

| Type | Meaning | Example |
|---|---|---|
| **Same Setting** | the same world, **wholly different** characters | Lunlun ↔ Hua Xianzi |
| **Shared Character** | a character or two in common, separate stories | a guest character, a crossover scene |

- Both are **undirected**: "A is set in the same world as B" reads the
  same from either end. Like Alternative Version / Alternative Setting /
  Other they are stored as one record with no inverse label; in the form
  it does not matter which side you start from.
- **They state no order.** The "Next Up" box, the chain tab of the
  Series Chronology and the spoiler guard follow the "Sequel" link and
  nothing else; these two types are invisible to them. A generation jump
  the producer calls a "sequel" but that is not watched as one belongs
  here.
- **Do not confuse Same Setting with Alternative Setting** — each is the
  other's mirror image. Alternative Setting: same characters, another
  world ("what if" stories, school-life re-imaginings). Same Setting:
  same world, other characters. The help text explains them side by side.
- In the "Related Anime" section of the detail page the two types get
  their own headings, after Summary and before Other Relation. They sit
  in the same place in the add-relation form.
- **Existing data is not converted.** The program cannot know which
  "Other" (or provisional "Alternative Setting") row is really Same
  Setting / Shared Character; the curator who knows deletes the link in
  the panel and recreates it with the right type. The upgrade touches no
  row.
- **"Other Relation" got narrower.** Its help examples are now "a
  connected work by the same creator, two productions drawn from one
  source work that fit no pattern". Shared universe and guest character
  moved to their own types.

## 3. Backup

The list backup carries relations by type name; the two new types travel
the same way. Loading a 1.1.47 backup into a pre-1.1.47 install counts
the unknown type as "skipped" — no crash.

## 4. Help

- Two items in the "Relation Types" list of the series help (Same
  Setting, Shared Character); the "Other Relation" item's examples
  updated.
- In "Direction: the Form Asks One Question" the count of undirected
  types goes from three to five.

## Files

**New:**

```
files/migration/1.1.47/upgrade.sql       two values added to the relation_type enum (MODIFY)
```

**Changed:**

```
files/functions/relation_helpers.php     type list + undirected list + form options; header comment
files/schema.sql                         same enum (fresh install) + table comment
files/lang/tr.php                        +4 keys (relation.type.*, relation.opt.*); help list 2 items + 2 texts
files/lang/en.php                        same
files/version.txt
```

Language file parity: 1138 = 1138.

## Deployment note

- **Nothing to do on the central catalog.** Relations are install-local;
  the central server does not know the table, the catalog wire format has
  no field for it.
- The migration widens the enum on the first page load (`MODIFY`,
  re-runnable; existing rows stay as they are).
- `functions/relation_helpers.php` and the language files must go
  **together**: with the old helper the new rows show as "Other Relation"
  and cannot be picked in the form (no crash); with an old language file
  the two options show as key names.
- The one known row to convert on the live site is Lunlun ↔ Hua Xianzi:
  delete the "Alternative Setting" link in the panel and recreate it as
  "Same Setting".
- **On the distribution server** the usual two steps: the published
  `version.txt` goes to 1.1.47 and the `updates/1.1.47/anime-tracker-1.1.47.zip`
  package is published.
