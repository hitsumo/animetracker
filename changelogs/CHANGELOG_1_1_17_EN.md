# Anime Tracker 1.1.17

**Release date:** 2026-07-19

## New: Filter by country

- **The main list can now be filtered by country.** A new "Filter by Country"
  dropdown sits in the filter box, just below the broadcast-status filter. It
  combines with every other filter (genre, watch status, broadcast status,
  letter, year).
- **The dropdown is not a fixed country list: it shows only countries actually
  entered in the catalog.** If the catalog holds no Korean production, "South
  Korea" does not appear, so you cannot pick a filter that returns nothing.
  Options appear on their own as you fill the field in.
- **When no anime has a country yet**, the dropdown is replaced by a short
  "No countries recorded yet." note.
- **The adult-content preference applies here too.** With adult content off, a
  country that appears only on 18+ titles stays out of the list - the same
  behaviour as the year filter.
- **The filter survives sorting and pagination.** Changing the sort column,
  picking a letter, moving to another page or running a search does not drop
  your country selection. That includes the "By Watched Episodes" sort.

## New: Country of origin on an anime

- **The add and edit forms gained an optional "Country of Origin" field**,
  directly below Media Type.
- **The country is picked from a list, not typed.** Initial list: China,
  France, Japan, South Korea, Taiwan, United States.
- **The country name follows the interface language** - "Japan" in English,
  "Japonya" in Turkish. That is possible because the database stores the
  international country code (JP, CN, KR, ...) rather than a name. You never
  type or see the code; every screen shows the country's name.
- **A list was chosen over free text** because the catalog is shared: with free
  text one country would produce three separate filter values ("Japan",
  "japan", "Japonya") and could not be translated.
- **The anime detail page shows the country** below the broadcast dates. When
  no country is set the row is omitted entirely - no empty "Not set" line is
  added.
- **Adding a country to the list is a one-line change:** one entry in the
  `country_codes()` map in `functions/country_helpers.php` plus the country's
  name in `lang/tr.php` and `lang/en.php`.

## Existing anime

- **No existing anime is assigned a country.** The upgrade only adds the
  field; no row is stamped by guesswork. The country filter therefore starts
  out empty and fills up as you enter data.
- The reason is that the catalog can also hold Chinese and Korean productions;
  assuming "everything is Japan" would leave overlooked titles filed under the
  wrong country.

## Bulk backfill tool (one-time, optional)

- **`tek_kullanimlik/anilist_country_backfill.php`** asks AniList about every
  anime that still has no country and produces `UPDATE` statements for you to
  run. Nothing is guessed: AniList's `countryOfOrigin` is structured data in
  exactly the form we store (ISO 3166-1 alpha-2).
- **It does not touch the database.** You review the generated SQL and run it
  in phpMyAdmin yourself:
  `php anilist_country_backfill.php > country_backfill.sql`
- **It cannot overwrite a country you entered by hand** - it only queries rows
  where `country IS NULL`, and the generated `UPDATE` carries the same
  condition.
- **A code outside the country list is reported, never written.** An unmapped
  code in the database would make that anime invisible to every filter. You
  decide whether to add the country to the list and re-run.
- Limit: only anime carrying a `mal_id` can be matched. Unmatched rows and rows
  without a `mal_id` are reported as counts and stay manual.

## AniList import now brings the country with it

- **Every anime imported from AniList arrives with its country.** AniList
  already exposes `countryOfOrigin`; the import query now asks for it and
  carries the value into the record. A donghua you import is born in the
  catalog as "China" instead of waiting to be filled in later.
- This applies both online (written to the suggestion row, passed into the
  catalog on moderator approval) and self-host (written straight to the local
  record).
- **A code outside the country list is not written; the field is left empty.**
  Storing an unmapped code would make that anime invisible to every filter.
- MyAnimeList (XML) imports carry no country data, so titles from that path
  stay country-less and are filled in by hand or with the bulk tool.

## Where the country travels

Country is catalog data about the anime, and it is preserved everywhere the
data moves:

- **Pushing to and pulling from the central catalog** (online mode).
- **Backup export / restore** (self-host). The backup file carries the
  country; backups taken before 1.1.17 have no such field, so those rows are
  restored without one.
- **Member suggestions and moderator approval.** A suggestion row can carry a
  country, and approval passes it into the catalog.

## Schema / migration

- `migration/1.1.17/upgrade.sql` adds the `country` column to `animes` and
  `catalog_requests` (`char(2)`, NULL = no country entered) and bumps the
  version to 1.1.17. A duplicate-column error on re-run is ignored.
- **Manual step for the central catalog (online only):** the central catalog
  database is a separate installation with no automatic migration runner.
  Before pushing country data to it, run this once on the central catalog DB:
  `ALTER TABLE animes ADD COLUMN country CHAR(2) DEFAULT NULL AFTER media_type;`
  Pushing before that is done makes the request fail. Order matters: this
  statement on the server first, then the application update, then the push.
  Self-host installs are unaffected (the local migration runs on its own).

## Changed / new files

- files/migration/1.1.17/upgrade.sql (new: country column, two tables)
- files/schema.sql (column for fresh installs; animes + catalog_requests)
- files/functions/country_helpers.php (new: country_codes / country_label /
  country_options / country_sort_key / is_valid_country_code)
- files/functions.php (load the new helper)
- files/tek_kullanimlik/anilist_country_backfill.php (new: one-time bulk
  backfill tool; emits SQL, never writes to the DB)
- files/index.php (list of countries present in the catalog; country filter
  box; the filter predicate is added to `select_from` so the "By Watched
  Episodes" sort branch inherits it; selection preserved across sort, letter,
  year and search links)
- files/add_anime.php, files/edit_anime.php (Country of Origin dropdown; code
  validated before saving)
- files/anime_details.php (country row; omitted when empty)
- files/functions/anilist_import_helpers.php (countryOfOrigin in the import
  query; country on the entry - an unmapped code is left empty)
- files/list_settings.php (carry country through backup restore, member
  suggestion creation, and both branches of the AniList import)
- files/catalog_import.php, files/admin/catalog_push.php,
  files/admin/admin_sync_example.php, files/admin/admin_catalog_requests.php,
  catalog_server/catalog.php, catalog_server/admin_push.php
  (country across the catalog wire format)
- files/lang/tr.php, files/lang/en.php (country names; form and filter labels;
  detail-page label)
- files/version.txt

Note: country names are sorted according to the interface language. In the
Turkish UI "Çin" lands correctly between "C" and "D"; PHP's default comparison
would push that letter to the end of the list, so the ordering follows the
Turkish alphabet explicitly.
