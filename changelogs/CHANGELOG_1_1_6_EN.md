# Anime Tracker 1.1.6

**Release date:** 2026-07-11

## New

- **Import your AniList list.** You can now bring your AniList anime list into
  the app. In the new "Import AniList List" section on the List Settings page,
  enter your AniList **username**; your public anime list is fetched from
  AniList.
  - **Preview first, write after:** A preview is shown first - how many entries
    were read, how many matched the catalog, how many are already in your list,
    and how many are not in the catalog. Nothing is saved until you confirm.
  - **Pick by status:** From the preview you can choose which watch statuses to
    import (Watching, Completed, On Hold, Dropped, Plan to Watch). AniList's
    "rewatching" maps to Watching and "paused" to On Hold.
  - **Two import types:** Choose in the preview - "import the list with watch
    state" (status, episodes, dates and notes are written to your list) or
    "import content only" (the anime are added only to your catalog/database; no
    personal watch state is imported). "Content only" is handy for seeding the
    catalog from a public list without inheriting its watch history. **The
    default type is "content only"**; pick "with watch state" if you want to
    import your own list with its statuses.
  - **Overwrite option:** By default entries already in your list are skipped;
    an "overwrite" option updates them instead. (This option applies only to the
    "with watch state" type.)
  - **Entries not in the catalog:** In the online edition they are sent as
    catalog suggestions (for moderator approval); in a personal (self-host)
    install they are added locally - the same behavior as the MyAnimeList
    import.
  - Watch status, watched episode count, start/finish dates and notes are
    imported.
  - **Correct airing status:** For an anime not in the catalog, its airing
    status (finished / ongoing) is taken from AniList data - added directly on a
    personal install, or carried on the catalog suggestion and used at approval
    time online. So a still-airing anime is not mistakenly recorded as
    "finished". (This does not apply to the MyAnimeList import, as the file does
    not carry airing status.)

## Notes

- Your AniList list must be **public** for this to work.
- The server contacts AniList during the import, so an internet connection is
  required. Very long lists are fetched page by page.
- No schema or migration change (migration/1.1.6 is a no-op ring). Matching uses
  the same identity (MAL id) as the MyAnimeList import; no new tables or
  columns are added.

## Changed files

- functions/anilist_import_helpers.php (new)
- functions.php
- list_settings.php
- lang/tr.php, lang/en.php
- css/components.css
- version.txt
- migration/1.1.6/upgrade.sql
