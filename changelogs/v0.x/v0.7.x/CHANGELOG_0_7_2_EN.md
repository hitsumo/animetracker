# Anime Tracker 0.7.2 - Changelog

**Release date:** May 2026

## English title support

You can now enter an optional **English title** for an anime. On the
add and edit anime screens there is a new "English Title" field below
the alternative titles.

If you turn on **"Show English titles"** in the new **"Title Language"**
section of the List Settings page, anime that have an English title are
shown with it instead of the Romaji title on the list and detail pages.
This preference is independent of the interface language: you can show
English titles even on the Turkish interface, or stay on Romaji on the
English interface. Anime with an empty English title always fall back to
the Romaji title.

## English text for genres and sentences

On the **Genre Management** and **Sentence Management** pages you can now
enter an **English equivalent** for each genre and each sentence. When the
interface language is English, genres and sentences that have an English
equivalent are shown in English everywhere (detail-page genre badges, the
list genre filter, the recommendation sentence list). Items with no
English equivalent fall back to the Turkish name.

In Genre Management you enter and save the English name from a small field
added to each row. In Sentence Management the English equivalent was added
to the existing "Rename" form; a single save updates both the Turkish
sentence and its English equivalent.

## Other

### i18n

This release adds **11 new text keys** (English title field and hint,
Genre Management English-name field, Sentence Management English-equivalent
field, List Settings title-language section). Total dictionary size:
535 -> 546 keys (TR/EN parallel).

### Schema

This release **does change the schema**. Three new columns are added:
`genres.name_en`, `tags.name_en`, `animes.title_english` (all optional,
empty to begin with). `migration/0.7.2` runs automatically during
auto-update; the columns are created on first load and nothing manual is
required.

Only `title_english` travels over the catalog data exchange with the
server. The English equivalents for genres and sentences are local for
now; sharing them with the server is left to a later stage (display works
fully on the current install, sharing is pending).

### Files

New: `migration/0.7.2/upgrade.sql`, `set_title_pref.php`.

Changed: `schema.sql`, `functions/taxonomy_helpers.php`,
`functions/anime_helpers.php`, `catalog.php`, `admin_push.php`,
`catalog_import.php`, `add_anime.php`, `edit_anime.php`,
`anime_details.php`, `index.php`, `recommendations.php`,
`manage_genres.php`, `manage_tags.php`, `list_settings.php`,
`css/components.css`, `tr.php`, `en.php`.
