# Anime Tracker 1.1.49

**Release date:** 2026-09-24

One job: **a backup for the import blacklist.** The blacklist page gained
"Export" and "Import" buttons; the list can now be backed up as a
separate file and restored. No schema change (empty migration); nothing
to do on the central catalog. Affects multi-user (online) mode only.

## 1. Why

The import blacklist (1.1.35) holds the ids of anime deleted from the
catalog; MAL and AniList imports do not reopen them as catalog
suggestions. But the list lived **only in the application database**:

- it is not sent to the central catalog (by design),
- it is not part of the JSON backup in List Settings,
- the blacklist page had no export or import either.

Result: returning to a clean install from a JSON backup started the list
empty, and the first import re-suggested every anime that had been
deleted on purpose — exactly the problem 1.1.35 closed. Only a database
backup saved the list.

## 2. "Backup" box on the blacklist page

Admin dashboard → **Import Blacklist** (moderators and above), at the
bottom of the page:

- **Export** — downloads the whole list as
  `import_blacklist_YYYY-MM-DD.json`. Each entry: MAL and AniDB id,
  title, entry type (Deleted / Manual), note and the **original date**
  (the list is also the deletion ledger; "when did I delete this" is
  kept). The user who added it is not carried — a user id is
  install-specific; on restored entries the adder is the moderator who
  loaded the file.
- **Import** — only **adds**. Entries already on the list are skipped,
  nothing is removed or changed; loading the same file twice is
  harmless. The result line says "N entries added, M were already on the
  list", and reports unreadable rows separately. Entries are written in
  one go: if something fails, none are written, so no half-restored list
  is left behind.

**Protection against the wrong file:** the backup carries a marker
naming its own kind. The list JSON backup (List Settings) or any other
file is refused on this page — a member's anime list cannot be turned
into blacklist entries by accident.

**How duplicates are told apart:** entries with ids match on MAL /
AniDB id. A file entry that shares only **one** of its ids with an
existing entry counts as "already on the list"; the existing entry keeps
blocking that id. Id-less deletion records (they block nothing, they are
only the ledger) match on title + date, so re-importing does not double
the ledger.

## 3. Why not inside the list JSON backup

Two routes were considered; a separate file was chosen:

- The list JSON backup is **a bare array of anime**. Putting the
  blacklist in it meant changing the file's shape, and 1.1.48 and older
  would reject new backups as "invalid format".
- The list backup is **a member's personal file**; the blacklist is
  catalog policy and concerns moderators only. It must not enter
  members' backups, nor change when a member loads one.

## 4. Help

The blacklist section of the import help was updated: the old "not part
of the JSON backup, lives only in the database backup" sentence now
explains how to take and load the backup, and that **when returning to a
clean install this file must be loaded before the first import**.

## Files

**New:**

```
files/migration/1.1.49/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/functions/blacklist_helpers.php     blacklist_export_rows(), blacklist_import_rows(), BLACKLIST_BACKUP_FORMAT
files/admin/admin_blacklist.php           export / import actions, "Backup" box
files/lang/admin_tr.php                   +10 keys (admin_blacklist.backup.*, btn.export/import, error/result texts)
files/lang/admin_en.php                   same
files/lang/tr.php                         help.blacklist.where text
files/lang/en.php                         same
files/version.txt
```

Language file parity: user 1149 = 1149, admin 321 = 321.

## Deployment note

- **Nothing to do on the central catalog.** The table exists since
  1.1.35; no column was added.
- The migration is empty; it moves the version stamp on the first page
  load.
- On a single-user install the blacklist is not active anyway; the
  backup box does not appear there.
- **On the distribution server** the usual two steps: the published
  `version.txt` goes to 1.1.49 and the
  `updates/1.1.49/anime-tracker-1.1.49.zip` package is published.
