# Anime Tracker 1.1.21

**Release date:** 2026-07-24

## New: Title Language — no longer English-only

- **The "show English titles" checkbox in List Settings is now a real language
  picker.** Choose which language you want anime titles shown in: **Romaji**
  (default), English, Japanese, Turkish, Chinese, Korean, French.
- **Anime with no title in the language you picked stay in Romaji.** Choosing a
  language the catalog does not carry yet is harmless — nothing ever renders
  blank.
- **Where do those languages come from?** From what 1.1.20 added: the language
  box next to each alternative title on the add/edit form. Give an anime a
  Japanese name and mark it "Japanese", and everyone with Title Language set to
  Japanese sees it.
- The preference is yours alone and **independent of the interface language** —
  you can use a Turkish interface while reading Japanese titles.

## Fixed: the statistics page ignored your title language

- **The "Recently Watched" table always showed Romaji, whatever your preference
  was.** 1.1.18 reworked that cell to support the title-language preference but
  never added the call that loads it, so the table silently fell back to the
  default. It now honours the setting.

## Removed: the title_english column

- **The column we said was "staying for now" in 1.1.20 has been retired.** A
  title's language now lives in exactly one place: the tagged
  alternative-titles list.
- **Why did it stay, and why is it gone now?** In 1.1.20 the column was a
  display shortcut derived from the `[en]` tag on save, which is what let that
  release ship without touching a single title-rendering page. But it could
  only ever describe English, so other languages could be **stored** and not
  **shown**. Now that display reads the tags directly, the column had no job
  left.
- **No data is lost.** English names already live in the alternative-titles
  list as `[en]` entries, and the migration tags any row that slipped through
  before dropping the column.

## How it works (technical)

- The preference now holds a language code (`display_title_lang`); it used to
  be a boolean (`display_title_english`). The migration moves everyone who had
  it switched on to English.
- `display_title()` looks for the chosen language's tag and falls back to the
  Romaji title when there isn't one. With the default Romaji preference the
  tagged list is **never parsed at all**, so the most common case costs nothing.
- Every query that renders a title now selects `alternative_titles` instead of
  `title_english`: anime details, statistics, series timeline, chronology,
  pending approvals, and in-synopsis anime links.
- Adult masking got stronger: it used to clear only the English title, and now
  clears the whole tagged list — otherwise an anime whose name is hidden could
  leak it to a viewer reading titles in another language.

## Schema / migration

- `migration/1.1.21/upgrade.sql` **drops the column** (from `animes` and
  `catalog_requests`). Two rescue steps run first: if the English name sits in
  the list untagged it is tagged in place, and if it is missing entirely it is
  appended as `[en]`. This is necessary because a catalog sync performed after
  1.1.20 may have overwritten the tags with the server's older copy.
- The preference migration is in the same file: users with
  `display_title_english = '1'` become `display_title_lang = 'en'`, and the old
  rows are deleted.
- **The migration is restartable.** Its first act is to re-add the column (a
  "duplicate column" error is ignored when it is still there). So a half-finished
  upgrade, or a column dropped by hand, cannot lock the upgrade out — the rescue
  steps find a column to read, it is dropped again at the end, and the outcome is
  identical either way.

## Manual work IS required on the central catalog server

1.1.20 was the exception that needed none; this release needs it again.
**The order is critical:**

1. Deploy the new `catalog_server/` (`catalog.php` + `admin_push.php`).
2. On the server database: `ALTER TABLE animes DROP COLUMN title_english;`
3. Deploy the app (the migration runs by itself).
4. Full catalog push.

In the reverse order (ALTER first) the old `catalog.php` would try to read a
dropped column and **sync would break for every client**. The server has no
`catalog_requests` table, so only that single `ALTER` runs there.

**Installs left behind:** a client still on 1.1.20 or earlier no longer
receives an English title from the catalog, so titles fall back to Romaji
there. Nothing is lost — the name still arrives in the tagged list, and it
reappears once that client updates.

## Changed / new files

- files/functions/anime_helpers.php (preference family now holds a language code; display_title reads tags; adult_mask_related clears the tagged list)
- files/functions/title_lang_helpers.php (alt_titles_for_form removed — its job moved into the migration)
- files/functions/series_helpers.php (three queries + the display_related_title bridge)
- files/functions/synopsis_helpers.php (in-synopsis link query)
- files/set_title_pref.php (a whitelisted language code instead of a boolean)
- files/list_settings.php (checkbox → language picker; column dropped from import/export)
- files/statistics.php (missing title_pref_init added + query)
- files/anime_details.php, files/chronology.php, files/series_timeline.php, files/pending.php (queries)
- files/add_anime.php, files/edit_anime.php (the column is no longer written)
- files/catalog_import.php, files/admin/catalog_push.php, files/admin/admin_catalog_requests.php, files/admin/admin_sync_example.php (removed from the catalog wire)
- catalog_server/catalog.php, catalog_server/admin_push.php (server-side wire)
- files/css/components.css (a class comment that had become misleading)
- files/lang/tr.php, files/lang/en.php (picker strings + help page)
- files/schema.sql (column definitions and notes removed)
- files/migration/1.1.21/upgrade.sql (new)
- files/version.txt
