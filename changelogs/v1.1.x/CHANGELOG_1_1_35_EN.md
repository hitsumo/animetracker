# Anime Tracker 1.1.35

**Release date:** 2026-08-31

This release answers one question: **why does an anime you deleted from the
catalog come back on the next import?**

The catalog owner seeds the catalog from a public AniList list and then
prunes it by hand — deleting entries that have no AniDB counterpart. That
workflow broke in two places, and they turn out to be the same fault seen
from two sides:

1. **Nothing recorded what had been deleted.** The pruning decision lived
   only in the curator's head; a deleted row left no trace behind. Two weeks
   later there was no way to answer "did I take this one out on purpose, or
   did it never arrive?"

2. **The very next import brought all of them back.** A row is bucketed as
   "unmatched" *because* the catalog has no such entry — which is exactly the
   state a deletion produces. So deleting an anime made it a fresh candidate
   for re-adding.

There was a third face to the same fault: the dedup on catalog requests only
looked at pending rows. So **a request you rejected** was raised again on
every import; rejecting did not mean "stop asking".

## The blacklist

Deleting an anime from the catalog now records its identity on a list.
Titles on that list are **never** raised as catalog requests by an AniList or
MyAnimeList import.

The list does two jobs at once: it is the *ledger of what you deleted* and
the *gate that keeps it out*. There is a new card in the admin panel —
**Import Blacklist**.

On that page you can:

- See deleted animes newest first (who deleted them, MAL / AniDB id, any note)
- Search by title or by id
- **Remove an entry** when you change your mind — that anime may return on
  the next import
- **Block an anime by hand** that was never added in the first place (useful
  for a request you rejected)

## What the import preview shows

A new line appears under the preview summary:

> 2 of these are on the admin blacklist and will be skipped.

And the result message gains a sentence when the import finishes:

> 2 animes were skipped because they are on the admin blacklist.

Neither line is printed when the blacklist is empty or nothing was blocked.

## What the blacklist does not block

**An anime that is actually in the catalog** can still be added to a
member's list, even if it is blacklisted. The blacklist means "keep it out
of the catalog", not "let nobody watch it". The gate stands only in the
*unmatched* branch.

In practice this only happens for an anime that was deleted and later
re-added by hand; the admin page marks such an entry with an **"In catalog"**
badge so you can clear it up.

## Matching is by id, not by title

The list matches on `mal_id` and `anidb_id` — the stable identities the
catalog already keys on. **It does not match on title:** two unrelated shows
share a title often enough that an import which silently dropped a
legitimate anime because of an old deletion's name would be worse than the
problem being solved.

Deleting an anime that has neither id is **still recorded**, but such an
entry cannot block anything — there is no key to match against. The page
says so plainly with a **"Cannot block"** badge rather than pretending
otherwise. Adding by hand requires at least one id.

## Nothing changes on a single-user install

The blacklist is active in **multi-user (online) mode only**. A single-user
install has no shared catalog and no curator; its owner's deletions are
personal, and its import must keep bringing whatever their own list
contains.

On such an install: deleting records nothing, importing blocks nothing, and
the new page says the feature is not active here. A user syncing the catalog
is likewise unaffected by what the curator blacklisted — the list is **never
pushed** to the central server.

## Changed files

**New:**

```
files/functions/blacklist_helpers.php   (the one place the rule lives)
files/admin/admin_blacklist.php         (admin page)
files/migration/1.1.35/upgrade.sql
```

**Changed:**

```
files/functions.php                     (loads the new helper)
files/index.php                         (records a deletion)
files/list_settings.php                 (the AniList + MAL import gate)
files/admin/admin.php                   (new card)
files/lang/tr.php, files/lang/en.php    (import notices)
files/lang/admin_tr.php, admin_en.php   (admin page strings)
files/schema.sql
files/version.txt
```

## Deployment note

- `files/functions.php` and `files/functions/blacklist_helpers.php` must ship
  **together**. Without the loader line the file is never read and the delete
  and import pages will not open.
- The `files/lang/*.php` files must ship with
  `files/admin/admin_blacklist.php` and `files/list_settings.php`, otherwise
  key names show up instead of text.
- **Nothing to do on the central catalog server.** No new field on the
  catalog wire, no manual `ALTER`, and no file under `catalog_server/`
  changed.
- The migration runs on its own and creates a single table. If the files are
  uploaded before the migration has run, the app does **not** break: deleting
  still works (nothing is recorded, the reason goes to the server log),
  importing blocks nothing, and the admin page says the table could not be
  read. The migration runs by itself on the next page load and everything
  falls into place.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must move to 1.1.35 — otherwise "Check for updates" still
  believes 1.1.34 is the latest — and the
  `updates/1.1.35/anime-tracker-1.1.35.zip` package must be published, or the
  "Update" button gets a 404 at the download address.
