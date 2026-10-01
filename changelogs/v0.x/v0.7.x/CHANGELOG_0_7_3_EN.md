# Anime Tracker 0.7.3 - Changelog

**Release date:** May 2026

## Personal synopsis is now separate for Turkish and English

Previously there was a single "Personal Synopsis" field. It is now kept
separately for Turkish and English: **Personal Synopsis (TR)** and
**Personal Synopsis (EN)**. An anime's Turkish personal note and its
English personal note are now independent; you can fill one and leave the
other empty.

On the anime detail page, the personal synopsis of the current interface
language is shown as a separate row **below** the catalog synopsis. The
catalog synopsis (the official summary) always stays on top; your personal
note does not replace it, it is shown in addition.

## Your edited synopsis is no longer lost

An important fix: when you edited an anime's catalog synopsis (Synopsis TR
or Synopsis EN) yourself, that change used to silently disappear on the
next catalog sync. It no longer does.

During sync the system now checks each language separately: if you changed
that language's catalog synopsis, **your edited text is moved into the
Personal Synopsis field** (a Turkish change to Personal Synopsis TR, an
English change to Personal Synopsis EN), then the catalog synopsis is
refreshed from the server. So your work is preserved and the catalog stays
up to date.

The move happens only for the language you changed. If you changed only
Turkish, only Turkish is moved; the English catalog synopsis stays as it
is and remains editable.

Once a language's synopsis has been moved to Personal Synopsis, that
language's catalog synopsis is locked (read-only). This preserves the
"the synopsis comes from the server" principle. (The catalog owner/curator
can unlock this from the Admin Capabilities page.)

## Other

### Schema

This release does change the schema: a new `animes.user_synopsis_en`
column is added (optional, empty to begin with). `migration/0.7.3` runs
automatically during auto-update.

### Files

New: `migration/0.7.3/upgrade.sql`.

Changed: `schema.sql`, `catalog_import.php`, `edit_anime.php`,
`anime_details.php`, `catalog.php`, `tr.php`, `en.php`.
