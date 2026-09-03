# Anime Tracker 1.1.36

**Release date:** 2026-09-02

This release gives a chain a **name** — and with it, the ability to say "these
two entries are separate tellings of the same story".

## The problem

Since 1.1.25 one series name could hold several chains, but a chain itself was
not a thing: it was only the set that fell out of walking the "next in series"
links. That had three consequences.

**A chain could not be named.** The tabs read "Other Chain 1..N"; the only way
to know which was which was to open it.

**You could not say "these are separate tracks".** All you had was "are they
linked". Two real examples:

- The *Space Adventure Cobra (1982)* film is, per AniDB, an **alternative
  version** of the *Space Cobra* TV series — not its sequel. Neither was
  linked, so each showed a chain saying "1 anime" and no tab ever appeared.
- *Sailor Moon Crystal* (also an alternative version) was the opposite case:
  linked **inside** the 90s chain, right after Sailor Stars. The timeline said
  "watch Sailor Stars, then Crystal", and the spoiler gate counted all eight
  90s entries as unwatched prerequisites for Crystal.

**A one-entry track could not be expressed.** A single record linked to nothing
did not count as a chain — a correct rule, since otherwise every standalone
film sharing a series name would spawn its own tab — but it also made a record
that stands apart *on purpose*, like the Cobra film, invisible.

## The fix: membership from the name, order from the link

The add and edit screens gained one field: **Chain name (optional)**, with
autocomplete over the names already in use.

| Field | What it says |
|---|---|
| Series name | Which series |
| **Chain name** | **Which track inside it** |
| Next anime | The order along that track |

Entries sharing a name count as one chain and get a tab **under that name** on
the series timeline. "Other Chain 1" becomes "Crystal".

For Sailor Moon: remove Sailor Stars' "next anime" link and name the two
tracks. The result:

```
[ 90s Anime ] [ Crystal ] [ Other Chain 1 ] [ Air Date ]
```

For Cobra, without creating a single link — just names:

```
[ Alternative Version (Film) ] [ 1982 TV Series ] [ Air Date ]
```

## A named single entry is a chain

An unnamed single record still does not count — it is just a record. But a
**named** one is a deliberate statement: "this entry is its own track." The
1982 Cobra film is exactly that.

## The spoiler gate stops at the boundary too

The gate now only looks at earlier entries **on the same track**. Opening
Crystal no longer claims you should have watched the 90s run first.

One asymmetry is deliberate: an unlinked member of a named track **appears in
the list** (appended by air date), but the spoiler gate does **not** treat it
as a prerequisite. Showing a record in a list is harmless; saying "watch this
first" is a claim, and it must rest on a link you made by hand. Inventing a
missing link from air dates would turn the warning into a guess.

## Tab order changed

Chain tabs now sit together and **"Air Date" moved to the end**. With names in
play, a tab wedged between them broke the reading. Addresses and behaviour are
unchanged; only the order moved.

## Nothing changes until you name something

Every record written before 1.1.36 has an empty chain name, and empty equals
empty — so no walk shortens, and chain discovery and the spoiler gate produce
**byte-identical** results to 1.1.35. That is measured, not asserted: the
1.1.35 algorithm was kept line by line in the test suite and both versions were
run against the same data.

## Changed files

**New:**

```
files/migration/1.1.36/upgrade.sql
```

**Changed:**

```
files/functions/series_helpers.php   the one chain rule (chain_same) + membership
files/series_timeline.php            tab labels and order
files/add_anime.php                  Chain name field
files/edit_anime.php                 Chain name field
files/list_settings.php              JSON restore carries the chain name
files/lang/tr.php, files/lang/en.php form strings + help page
files/schema.sql
files/version.txt
```

## Deployment note

- `files/functions/series_helpers.php` and `files/series_timeline.php` must
  ship **together**: the page calls `chain_name_norm()`, which does not exist
  in the older helper file.
- The `files/lang/*.php` files belong in the same package, otherwise the new
  field shows a key name instead of a label.
- **Nothing to do on the central catalog server.** The chain name is
  app-local, exactly like "next in series": no new field on the catalog wire,
  no manual `ALTER`, no file changed under `catalog_server/`. A catalog sync
  does **not** overwrite the chain name.
- The migration adds one column and one index and runs on its own.
- **On the distribution server**, the usual two steps: move the published
  `version.txt` to 1.1.36 and publish
  `updates/1.1.36/anime-tracker-1.1.36.zip`.

## What comes next

A chain name lets you write down **what** a track is, but the application still
does not know the **type** of the relation: write "Alternative Version" and a
human understands, the software does not. The next release turns that into
data (sequel, alternative version, side story, summary…), aligned with AniDB's
own distinctions.
