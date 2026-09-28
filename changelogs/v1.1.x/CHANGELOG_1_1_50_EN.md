# Anime Tracker 1.1.50

**Release date:** 2026-09-24

One change: **a new relation type, "Special"**, and a **"Specials"**
section of its own on the main entry's detail page. Small extras such as
the short shown with a film, a Blu-ray bonus or a "manner movie" are now
recorded as an add-on of the work they belong to, and listed there as
S1, S2… Schema change: one value added to
`anime_relations.relation_type`. Nothing to do on the central catalog.

## 1. Why

MAL and AniList keep these small extras as separate records, and since
the catalog is fed from them it does the same. But there was no proper
relation type to say which work an extra belongs to: the choices were
"Side Story" or "Other". Both say the wrong thing — a side story is a
work of its own, a special is an add-on to one. On the detail page these
small records also sat in the same list as the sequels, with the same
weight.

Example: the *Boku no Hero Academia the Movie: You're Next* film and its
specials such as "A Piece of Cake" — each its own record.

## 2. New type: Special / Main Entry

Two new choices in the Relations panel on the edit form's **Series &
Relations** tab:

- **Special (bonus episode, short extra)** — the anime you picked is an
  extra of the anime you are editing.
- **Main entry (this anime is its special)** — the same link, created
  from the extra's page.

The type is **directional** (like Side Story / Parent Story): the link is
one record and shows on the two pages with two labels. Whichever end you
create it from, the row is stored the right way round.

**It states no order.** The chain tab of the Series Chronology, the
"Next Up" box and the spoiler guard follow only the Sequel / Prequel
link; a special is not part of the watch order.

## 3. "Specials" section on the detail page

On the main entry's detail page specials are **not** listed under
"Relations" but in their own section:

- numbered S1, S2, S3… **by air date** (undated ones last, ties by
  title),
- each row shows the title, media type, date (only the year or month
  when that is all that is known), episode count and your watch status.

The number is not stored; it is worked out from the order on every
view, so adding an extra with an earlier date shifts the others. That is
how AniDB's list of special episodes reads too.

The special's own page keeps a link back under "Relations", headed
**Main Entry**.

## 4. Series diagram

On the **Diagram** tab of the Series Chronology, special links are drawn
as a dashed gold line, and the legend gains a "Special / Main Entry"
row. As with the other directional types the arrow points at the
derived work: main entry → special (the legend note and the Diagram
help were updated accordingly).

## 5. Help

The "Relation Types" list gains a Special / Main Entry item (how it
differs from a side story, and the Specials section). The direction
section now speaks of four directional types instead of three, and "How
a Chain Is Built" was reworded on which type to pick for records outside
any chain (bonus episode → Special, a side OVA that stands on its own →
Side Story).

## 6. Existing data

This release does **not** convert any relation by itself. The program
cannot tell which "Side Story" or "Other" link is really a special; in
the panel, delete the old link with × and add it again as "Special".

## Files

**New:**

```
files/migration/1.1.50/upgrade.sql        'special' added to the relation_type enum
```

**Changed:**

```
files/functions/relation_helpers.php      type list, inverse label, form choices, anime_relations_split_specials()
files/anime_details.php                   "Specials" section
files/css/series.css                      .specials-section, .special-number, .special-meta
files/functions/series_graph_helpers.php  diagram arrow colour
files/series_timeline.php                 diagram line pattern
files/schema.sql                          enum + comment (fresh install)
files/lang/tr.php                         +8 keys; relation and diagram help, diagram legend note
files/lang/en.php                         same
files/version.txt
```

Language file parity: 1157 = 1157.

## Deployment notes

- **Nothing to do on the central catalog.** Relations are local to each
  installation and are not sent to the catalog; no column was added.
- The migration is a single `MODIFY` statement and can be re-run safely.
- The JSON backup carries the type by name. If a 1.1.50 backup is loaded
  into an older installation, special links are counted as "skipped";
  nothing breaks.
- Ship the files together: if an old `relation_helpers.php` stays on the
  server, special links show as "Other Relation" (no crash).
- **On the distribution server**, the usual two steps: move the
  published `version.txt` to 1.1.50 and publish the
  `updates/1.1.50/anime-tracker-1.1.50.zip` package.
