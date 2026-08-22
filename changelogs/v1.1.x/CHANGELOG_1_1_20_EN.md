# Anime Tracker 1.1.20

**Release date:** 2026-07-24

## Changed: the "English Title" field is gone; language moved onto alternative titles

- **The separate "English Title" field has been removed from the add/edit
  form.** All of an anime's names are now entered in one place: the
  alternative-titles list, using the language box next to each row.
- **Every alternative title can be marked with a language:**

  ```
  Anime Title       : Tonari no Totoro
  Alternative Titles: My Neighbor Totoro    [English]
                      となりのトトロ          [Japanese]
                      Totoro                [No language set]
  ```

- **The name you mark "English" does exactly what that field used to do:** it
  is shown instead of the Romaji title while "show English titles" is enabled
  in List Settings. Same behaviour, one place to type it.
- **Picking a language is optional.** Leave the box on "— No language set" and
  the alternative title behaves as it always did.
- **Languages available:** English, Japanese, Turkish, Chinese, Korean, French.
- **Bonus - search got wider.** The list's search box looks inside the anime
  title and the alternative titles, but never inside the English Title field.
  Now that the English name is part of the list, searching for
  "My Neighbor Totoro" finds the anime.

## Why not just another field per language

- **Wanting a Turkish title is where the old design dead-ended.** The "English
  Title" field only ever described one language; Turkish would need a second
  field, Japanese a third. Each one meant a new database column, a new branch
  in the title-display code, a new field on the catalog wire and a manual
  change on the central catalog host - once per language.
- **So the language moved into the list.** Adding a language is now a one-line
  change: one entry in the map in `title_lang_helpers.php` and one line in the
  language files. The database is not touched.

## How it is stored (technical)

- The language rides in the existing `animes.alternative_titles` text as an
  optional `[xx]` tag in front of each name:

  ```
  [en]My Neighbor Totoro|[ja]となりのトトロ|Totoro
  ```

- **Why `[en]` and not `en:`?** A bare "two letters and a colon" prefix would
  misread real titles - `Re:Zero kara Hajimeru Isekai Seikatsu` starts with
  exactly that pattern. The bracket form plus a whitelist check (only a known
  language code counts as a tag) guarantees an untagged title can never be
  mistaken for a tagged one. Names like `[TV] Bleach` also survive intact.
- New helper file: `files/functions/title_lang_helpers.php`.
  `build_alt_titles()` produces the column value, `parse_alt_titles()` turns it
  back into form rows, and `alt_title_for_lang()` reads one language's name.
- A hand-typed `[en]` prefix is stripped on save (the dropdown is the single
  authority), and a `|` typed into a name becomes a space, since it is the
  separator and would otherwise split the name in two.
- The `animes.title_english` column **stays**, but the user no longer fills it:
  it is derived on save from the `[en]`-tagged entry in the list. That is why
  this release touches no title-rendering page at all. Moving display onto the
  tags and retiring the column is left to 1.1.21.
- Rows that arrive from the catalog without tags but with a populated
  `title_english` are shown pre-marked as English in the edit form. Without
  that, opening the form and saving would silently wipe the English name.

## Schema / migration

- `migration/1.1.20/upgrade.sql` makes **no schema change** - no new table,
  column or settings key. Its two `UPDATE` statements add the English name,
  which until now lived only in `title_english`, to the list as an `[en]`-tagged
  entry so search picks it up immediately. Both statements clear their own
  condition, so re-running the migration cannot add a second copy.
- **No manual work is needed on the central catalog server.**
  `alternative_titles` was already a text column and its type did not change,
  so tagged text rides the existing sync chain untouched. (Unlike 1.1.3, 1.1.10
  and 1.1.17, which each required a manual ALTER.)
- One note: after this install pushes to the catalog, a client still on 1.1.19
  sees the `[en]` prefix as raw text in its own edit form. Search and display
  are unaffected and no data is lost; the prefix becomes a tag once that client
  updates.

## Changed / new files

- files/functions/title_lang_helpers.php (new; tag parsing and building)
- files/functions.php (title_lang_helpers added to the loader)
- files/add_anime.php ("English Title" field removed; per-row language box)
- files/edit_anime.php (same, plus restoring existing rows' tags)
- files/js/anime_form.js (a new row is now built with its language box)
- files/css/list.css (language box width + row wrapping on narrow screens)
- files/lang/tr.php, files/lang/en.php (language names; title_english keys removed)
- files/schema.sql (alternative_titles and title_english notes updated)
- files/migration/1.1.20/upgrade.sql (new)
- files/version.txt
