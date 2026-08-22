# Anime Tracker 1.1.22

**Release date:** 2026-07-25

## New: AniList import now produces tagged names

- **Animes imported from AniList no longer arrive with a single name.**
  Until now the import kept only the Romaji name and threw away the English
  and native names AniList already provides. Those names are now written
  into the alternative-titles list with language tags:

      [en]Frieren: Beyond Journey's End|[ja]葬送のフリーレン

- **The visible payoff:** the Title Language preference (1.1.21) now works
  for imported animes too. A user whose preference is English or Japanese
  sees an imported anime in that language — previously such animes rendered
  as Romaji for everyone.
- **The native name's language is derived from the country.** AniList never
  states the native title's language, only the country of origin; the bridge
  is: Japan → Japanese, China/Taiwan → Chinese, Korea → Korean. For a country
  we cannot bridge, the name is stored **untagged** — a guessed tag would
  show the wrong title to users of that language, while an untagged name is
  honest.
- **Where it lands:** in online mode, on the suggestion record — when a
  moderator approves it, the tagged names travel into the catalog with the
  anime (the approval page already carried this field). In self-host, the
  locally added anime is born with the tags directly.

## Fixed: List Settings rendered unstyled on slow connections

- **The page-specific styles (tab bar, blue buttons, white section cards)
  sat at the very end of the page.** Every other page in the app carries
  these styles inside `<head>`; List Settings was the lone exception, with
  its style block just above `</body>`. On a slow or interrupted load the
  browser painted the content **unstyled** until the page's final bytes
  arrived — tabs showed as bare buttons, buttons as default grey (the
  global stylesheet still applied, so the header card looked normal and
  the breakage hit only this page's own parts).
- The style block moved into `<head>`, where every other page keeps it.
  Content and rule order are unchanged; the browser is now guaranteed to
  receive the styles **before** the content they style.

## Why MAL is unchanged

- **MAL's XML export carries a single name** (`series_title`); the file has
  no second field for an English or Japanese title. With no tag to derive,
  the MAL import path stays as it was.

## How it works (technical)

- The AniList query now fetches `title { romaji english native }` (`native`
  is new). The main-title choice is unchanged: Romaji, falling back to
  English.
- The English name is skipped when it is the same as the main title or just
  a case variant of it (AniList repeats names like "One Piece" in both
  fields). The same dedup applies to the native name.
- Tagged text goes through the same hygiene the add/edit form uses
  (`build_alt_titles`): pipes become spaces, a hand-typed tag at the front
  of a name is stripped, an unknown language code degrades to untagged. A
  row written by the import is as well-formed as one typed into the form.
- **Animes already in the catalog (matched entries) are untouched** — the
  import does not backfill missing tags; catalog content is curator
  territory.
- An older session draft (previewed on 1.1.21, committed on 1.1.22) lacks
  the new field; it silently stays empty — the same defensive pattern the
  country field used in 1.1.17.

## Schema / migration

- **No schema change.** The `alternative_titles` columns already existed;
  the only change is that the import writer now fills them.
  `migration/1.1.22/upgrade.sql` is a version stamp only.
- **No manual work on the central catalog server.** The tagged text rides
  inside the existing column, over the existing catalog wire.

## Changed / new files

- files/functions/anilist_import_helpers.php (native added to the query; anilist_native_lang + anilist_alt_titles are new)
- files/list_settings.php (both AniList commit write paths carry alternative_titles; page styles moved into head)
- files/migration/1.1.22/upgrade.sql (new, version stamp only)
- files/version.txt
