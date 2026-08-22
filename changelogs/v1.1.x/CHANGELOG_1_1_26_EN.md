# Anime Tracker 1.1.26

**Release date:** 2026-08-05

## New: linking another anime from a synopsis no longer means typing the code

- **Synopsis fields now carry an "Add anime link" button.** Since 1.1.19 a
  synopsis could link to another anime, but the only way to write that link was
  **by hand** — and you had to know the target's MAL number from memory. Now you
  press the button and type the title.
- **It searches the catalog as you type.** Each result shows the title, the
  year, the media type (TV/Film/OVA…) and the MAL number, so remakes and movie
  versions sharing a title can be told apart.
- **The anime you pick is written at the caret.** If you are mid-sentence, the
  link lands exactly there and the rest of the text is untouched. The inserted
  code looks like `[[anime:52991|Sousou no Frieren]]`, labelled with **the title
  you are reading** (whatever your Title Language preference is).
- **Where it appears:** the Turkish and English catalog synopsis on the Add
  Anime page, plus the Turkish and English personal synopsis on the Edit Anime
  page. A read-only synopsis box gets no button.
- **The list only offers anime that can actually be linked.** The shortcode
  addresses its target by MAL number, so a record without one never appears —
  picking it would produce a code that resolves to nothing.
- **Your adult-content preference applies here too.** While adult content is
  hidden, those anime do not appear in the search results, exactly as on the
  listing pages.
- **Typing the code by hand still works.** The picker is only a convenience.

## New: version number on the About page

- The About page now states the installed version ("Version 1.1.26"). Until now
  nothing in the interface told you which version you were running.

## How it works (technical)

- The search runs against a new endpoint, `anime_link_search.php`. It starts at
  two characters, fires ~0.25s after you stop typing rather than on every
  keystroke, and returns at most 12 results. A late answer never overwrites the
  result of a newer query.
- The endpoint is gated at **the weaker of the two hosting pages**: Edit Anime
  requires a moderator, but Add Anime is open to any member, so the endpoint
  requires only a logged-in user. This opens nothing up — every row it returns
  is one the member can already reach through the catalog listing.
- The anime list is **not embedded in the page**. The edit page already ships a
  list for the "Next in Series" box; shipping a second full copy (this time with
  MAL numbers and alternative titles) on every form load would grow the page
  along with the catalog. A search endpoint stays the same size however large
  the catalog gets.
- The search covers both the main title and the alternative titles, with
  main-title matches lifted to the top. A `%` or `_` in what you type is
  searched for literally, not as a wildcard.
- `[`, `]` and `|` are stripped from the label: those are the shortcode's own
  delimiters and would break the code if left in.
- The picker is **progressive enhancement**: if the script fails to load or the
  search errors, the synopsis fields behave exactly as in the previous version.
  Nothing about saving changed.
- The version on the About page is read from `files/version.txt`, not from the
  database version row: the question being answered is which **code** is on the
  server. If the file cannot be read, the line is not printed at all.

## Schema / migration

- **No schema change.** No new table, column or preference.
  `migration/1.1.26/upgrade.sql` carries only the version stamp.
- **No manual action is needed on the central catalog server** — the catalog
  wire was not touched.

## Changed / new files

- files/anime_link_search.php (new — search endpoint)
- files/js/synopsis_link.js (new — link picker)
- files/about.php (version line)
- files/add_anime.php (synopsis fields marked, language keys, script)
- files/edit_anime.php (synopsis fields marked, language keys, script)
- files/css/components.css (picker styles)
- files/lang/tr.php, files/lang/en.php
- files/migration/1.1.26/upgrade.sql (new, version stamp only)
- files/version.txt

## Deployment note

- There are two **new** files: `files/anime_link_search.php` and
  `files/js/synopsis_link.js`. They must be uploaded **together with**
  `add_anime.php`, `edit_anime.php` and `css/components.css`; if one is missing
  the button either never appears or its search does not work. A missing piece
  does not break the synopsis box — the code can still be typed by hand.
