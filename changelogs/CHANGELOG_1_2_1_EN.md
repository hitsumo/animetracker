# Anime Tracker 1.2.1

**Release date:** 2026-09-28

One job: **relations can be fixed without deleting them.** Every row in
the Relations panel of the edit form gained a pencil button; the type
and the direction of a relation are changed in place. No schema change
(empty migration); nothing to do on the central catalog.

## 1. Why

Up to 1.2.0 the only way to fix a wrongly entered relation was to delete
it and add it again: remove the row with ×, find the other record again
in a long list and pick the right type.

The most common mistake is a **reversed direction**. The form asks one
question: *"The anime you pick is the ___ of the anime you are
editing."* Entered from the wrong page, the stored row says the
opposite. Examples found in the live catalog:

- Alps no Shoujo Heidi (1974 TV) was recorded as the **summary** of the
  1993 OVAs. The truth: the OVAs summarise the TV series.
- In Himitsu no Akko-chan the 1989 film was recorded as the **prequel**
  of the 1988 series. The truth: the film is the sequel.

When a Sequel / Prequel link is reversed, the series timeline shows the
order backwards, and the spoiler guard's "watch this first" points at
the wrong records.

## 2. The pencil button

Edit form → **Series & Relations** tab → **Relations** panel. Each
relation has a pencil next to its delete button:

- The pencil opens a small form under the row: *"<other record> is this
  anime's: [type]"*. The question is the one the add form asks, and the
  row's **current reading comes preselected**.
- Pick the right type and press **Save**. To fix a link entered the
  wrong way round, pick its mirror: Sequel ↔ Prequel, Side Story ↔
  Parent Story, Summary ↔ Full Story, Special ↔ Main Entry.
- The type can change too (e.g. turning a remake that was entered as
  "Sequel" into "Alternative Version").
- **The other record does not change.** Pointing a relation at a
  different anime is still delete + add.

Saving without changing anything writes nothing. Editing needs the same
permission as adding and deleting (moderator and above on the online
site), and both ends' pages are queued for IndexNow.

## 3. Help

In the series help's "Rules" list, the "delete it first to change the
type" sentence is gone; it now explains the pencil button and how to fix
a reversed direction. The "These two animes already have a relation"
warning now points at the pencil button instead of deleting.

## Files

**New:**

```
files/update_anime_relation.php          endpoint that changes a relation's type / direction (POST)
files/migration/1.2.1/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/edit_anime.php                     per-row pencil + edit form in the Relations panel
files/functions/relation_helpers.php     anime_relation_choice_key(): the form choice for a stored row
files/css/series.css                     .relation-edit* styles
files/lang/tr.php                        +3 keys (relation.edit_tooltip, relation.edit.prompt, relation.edit.submit); relation.error.exists and help.rel.rules.list texts
files/lang/en.php                        same
files/robots.php                         /update_anime_relation.php added to the Disallow list
files/version.txt
```

Language file parity: 1169 = 1169.

## Deployment notes

- **Nothing to do on the central catalog.** Relations are not sent to
  it; the table exists since 1.1.38.
- The migration is empty; it moves the version stamp on the first page
  load.
- If 1.1.50, 1.1.51 and 1.2.0 are not live yet, they can go out in
  order together with this release.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.1 and publish
  `updates/1.2.1/anime-tracker-1.2.1.zip`.
