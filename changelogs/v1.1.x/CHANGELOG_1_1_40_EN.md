# Anime Tracker 1.1.40

**Release date:** 2026-09-12

Schema release; nothing to do on the central catalog. One piece of work: the
watch order is now a **relation type**. The "Next Anime" box and the
`next_in_series` column behind it are retired; the order lives in the relations
table introduced in 1.1.38, as **Sequel / Prequel**. This is the third and last
step of the road laid out in 1.1.36: chain name (1.1.36) → typed relations
(1.1.38) → order into the table (1.1.40).

## 1. "Sequel" / "Prequel" — the order is a relation too

Since 1.1.38 the link between two entries lived in two places: the orderless
types (alternative version, side story, …) in the **Relations** panel, the
watch order in the **Next Anime** box of the main form. `sequel` was
deliberately left out of the relation types back then, so that two sources
could never disagree about the same pair. But the split had its own cost:

- **The order never made it into a backup.** `next_in_series` was a local row
  number and every install numbers its rows differently; restoring a JSON
  backup silently dropped every chain link. Relations, on the other hand, have
  travelled by identity quadruple (MAL / AniDB / catalog id / title) since
  1.1.38.
- **The same question was asked on two screens.** The answer to "how are these
  two related?" depended on which box you looked at.
- **The column was single-valued.** An entry could have one sequel, while it
  could already have several prequels.

Now there is one place. Two new choices in the Relations panel:

| Choice | Meaning |
|---|---|
| **Sequel** (watched after this one) | Exactly what the old "Next Anime" box did |
| **Prequel** (watched before this one) | The same link, entered from the other end |

An entry you mark as "Sequel" on one end shows up as "Prequel" on the other —
the same way side story / parent story already worked.

**The "one place for the order" rule is kept; only the place moved.** The
1.1.38 contradiction argument still holds and is now enforced by a different
mechanism: **a pair carries at most one relation.** "A is B's sequel" and "A is
B's alternative version" cannot both be entered. The 1.1.38 error "these two are
linked with Next Anime" is therefore gone — the situation it guarded against can
no longer arise.

### Your existing links move by themselves

The migration turns every "Next Anime" link in the catalog into a **Sequel**
relation in one pass. Nothing to do by hand. Two exceptions:

- A link pointing at its own entry (the app already refused those) is skipped.
- If the pair already has a relation, the link is skipped and the relation
  stays. That was only possible for a **dormant** link: 1.1.38 allowed a
  relation on top of a link that was never followed because the chain names
  differed. The type you entered by hand wins.

**Dormant links are converted too.** A link that the timeline ignored because
the chain names differed is not deleted; it becomes a Sequel relation. The
series timeline and the spoiler guard still **ignore** it (the chain-name rule
is unchanged), but the **Related Anime** section on the detail page **shows**
it under "Sequel" / "Prequel" — that section is the raw list of every recorded
link. A migration deleting data you entered would have been wrong; if you do
not want it, one click in the edit panel removes it.

### What did not change

- **The chain-name rule (1.1.36) stands.** A link is followed only when both
  ends carry the same chain name. The chain tab of the series timeline, the
  "Other Chain" tabs and the synopsis spoiler guard give **exactly** the
  1.1.39 result on unnamed data — measured, not claimed (below).
- **The "Next Up" card on the detail page** is still there. It now reads the
  relations table and obeys the chain-name rule: a "sequel" on another track
  does not appear in the card, only in the Related Anime section.
- **Nothing goes to the central catalog.** `next_in_series` never did;
  relations do not either.

### Branching

With the column gone an entry may have several sequels. No new rule was
written: the "first same-named neighbour" rule that predecessors have followed
since 1.1.25 now applies forward as well. Which branch is drawn is decided by
the **curator**, not the program — through the chain name. A branch that is
not followed still appears in the list if it carries a name (membership comes
from the name, 1.1.36).

### Side benefit: chain links are now in the backup

The JSON backup carries Sequel relations; on restore the far end is resolved by
the identity quadruple. Restoring the same backup twice creates no new rows. A
1.1.38 install does not recognise the "sequel" rows of a 1.1.40 backup and
**skips** them — it does not misfile them as "other" (1.1.38 was written that
way on purpose).

## 2. Edit screen

- The **Next Anime box is gone** from the "Series & Relations" tab; the tab
  keeps series name, chain name and the Relations panel.
- The target list of the Relations panel is now **rendered on the server**. In
  1.1.38 it arrived empty and the browser cloned it from the Next Anime box
  (rendering the same list twice would have doubled the page). With the box
  gone it is the only list on the page; the cloning JavaScript was removed.
- The panel hint and form hint were rewritten; the help entry "Next in Series"
  became "Watch Order (Sequel / Prequel)" and says how it is set.

## Verification

- The migration ran on a **copy** of the local database (8,113 anime, 14
  links) with the real MigrationManager logic: 1.1.26 → 1.1.40, 14 migrations.
  12 links converted; 1 self-link and 1 dormant link that already had a
  relation were skipped — exactly as intended. The version stamp was reset to
  1.1.39 and the migration ran a **second time**: no new rows, identical table
  dump; a third run applied 0 migrations.
- **Side-by-side comparison:** the 1.1.36 `series_helpers.php` (the last one
  that changed) was run against the copy in its 1.1.39 state, the new one
  against the same copy in its 1.1.40 state — chain discovery, timeline rows,
  chain start and spoiler predecessor lists for 24 series were dumped to JSON
  and the `diff` is **empty**.
- 31 helper-level checks: type order, labels from both ends, choice parsing,
  endpoint direction (Sequel → from = picked, Prequel → from = edited), the old
  `chain` error code falling back to the generic sentence, the "Next Up" card,
  a dormant link converted but not followed, a pair with an existing relation
  skipped, the self-link dropped, **branching** (two unnamed sequels → lowest
  id; a named branch skipped), walks terminating under a **transitive cycle**
  (A→B→…→A), the spoiler gate.
- End to end (local PHP server + curl): detail page with "Next Up" card +
  Prequel/Sequel groups + "Series Chronology" button; spoiler gate ("… and 1
  more entry in the chain"); adding a Sequel (`exists` refusal), adding a
  Prequel (direction correct), deleting; TR and EN views; adding and editing an
  anime (INSERT/UPDATE without the column); an 11-link chain on the series
  timeline and an unlinked member of a named chain appended at the end; JSON
  export with 12 `sequel` rows and no `next_in_series` key; a deleted Sequel
  link **coming back** from the backup, and a second restore creating nothing.
- Browser: the detail page was seen at low resolution (Next Up card, Series
  Chronology button, Prequel/Sequel groups stacked, layout intact). The browser
  pane was unstable again; no high-resolution capture.

## Files

**New:**

```
files/migration/1.1.40/upgrade.sql   sequel into the enum; convert links; drop the column
```

**Changed:**

```
files/functions/relation_helpers.php  sequel type, Prequel label, choices;
                                      chain-conflict check removed
files/functions/series_helpers.php    walks read the table
                                      (seriesChainNeighbours / seriesChainStep);
                                      validateNextInSeries removed
files/edit_anime.php                  Next Anime box removed; target list
                                      server-rendered; column out of UPDATE
files/add_anime.php                   column out of INSERT
files/anime_details.php               "Next Up" card + chronology button from
                                      relations
files/add_anime_relation.php          chain-conflict refusal removed
files/catalog_import.php              column out of INSERT
files/list_settings.php               comments only
files/series_timeline.php             comments only
files/set_series_timeline_mode.php    comments only
files/anime_link_search.php           comments only
files/help/help_series.php            comments only
files/js/anime_form.js                option-cloning block removed
files/lang/tr.php, files/lang/en.php  4 new keys, 3 keys removed,
                                      6 texts updated (parity 1038 = 1038)
files/schema.sql                      column/FK/index removed; enum
files/version.txt
```

No new CSS. The JS changed, so the 1.1.24 version stamp applies (upload
together with `version.txt`).

## Deployment note

- **Because the column is dropped, all files must go up together.** A stale
  file left on the server fails with "Unknown column next_in_series":
  `edit_anime.php` (saving), `add_anime.php` (adding), `catalog_import.php`
  (**catalog sync**), `anime_details.php` (detail),
  `functions/series_helpers.php` (series page and spoiler gate). A stale
  `functions/relation_helpers.php` does not crash but labels Sequel rows as
  "Other Relation".
- The migration runs by itself on the first page load and is **re-runnable**:
  it re-adds the column before reading it (ignored if present) and drops it at
  the end; a second run after a partial one creates no rows.
- **Nothing to do on the central catalog server.** No new field on the wire,
  no manual `ALTER`, no file under `catalog_server/` changed. If the central
  server's own table still has a `next_in_series` column it can stay; no client
  reads it.
- **On the distribution server**, the usual two steps: set the published
  `version.txt` to 1.1.40 and publish `updates/1.1.40/anime-tracker-1.1.40.zip`.
- Self-host installs run the same migration; no extra step.
