# Anime Tracker 0.6.4

**Release date:** 27 May 2026
**Type:** Feature completion (i18n cycle close — all pages now switchable to EN)

This release ships via auto-update. The database schema is untouched;
only code and dictionary files are refreshed. Your existing watch
progress, notes, and language preference are preserved — if you were
running in EN before the upgrade you will still be in EN after.

## Summary

The i18n infrastructure that landed in 0.6.2 (the `lang_init()` / `t()`
helpers and the lang/tr.php / lang/en.php dictionaries) was applied
to only three pages back then (index, anime_details, edit_anime). This
release wires up the remaining **nine pages** to the same dictionary,
so the **entire UI** now actually switches to English when English
is selected.

The language switcher (TR/EN buttons) was already in the top corner;
labels on some pages just looked Turkish because those pages had not
yet been converted to `t()` calls. Now they have.

**The admin side was translated too.** Having a foreign developer
download the repo from GitHub and find the admin pages in Turkish
would be inconsistent, so the admin dashboard (`admin.php`), pending
anime list (`admin_pending.php`), and server push tool (`admin_sync.php`)
were wired up to the same i18n infrastructure. A new pattern was
introduced on the dictionary side: admin keys live in a separate
dictionary file (`lang/admin_tr.php` + `lang/admin_en.php`), so
regular user installations never load them. Details below under
"Plan B - separate admin/user dictionaries".

## Pages translated

| Page | Description | New keys |
|---|---|---|
| add_anime.php | Anime entry form | 91 |
| help.php | Help page (how it works) | 132 |
| statistics.php | Statistics tables | 11 |
| recent.php | Last 5 edited anime | 8 |
| recommendations.php | "What Should I Watch?" picker | 19 |
| about.php | About | 4 |
| chronology.php | Episode-level chronology | 10 |
| series_timeline.php | Series chain timeline | 4 |
| list_settings.php | List Settings (import / export / sync / update) | 66 |
| admin.php | Admin dashboard (localhost-only) | 18 |
| admin_pending.php | Pending anime promotion | 25 |
| admin_sync.php | Catalog push tool (from `admin_sync_example.php` template) | 23 |
| **Total** | | **411** |

The previous 88 keys (0.6.2) plus 411 new = **499 keys**. The TR and
EN dictionaries are exactly parallel. A `t()` call for a missing key
falls back to TR, and if TR is missing too, returns the key itself
(a visible warning for the developer).

## Added / changed behaviour

- **Language switcher was missing on 4 more pages; added.** add_anime.php,
  recommendations.php and list_settings.php now show the TR/EN buttons
  in the top header. help.php, statistics.php, recent.php, about.php,
  chronology.php and series_timeline.php deliberately do not carry a
  switcher (you pick a language from the home page or anime detail;
  the choice is session-persistent).

- **JavaScript messages now come from the dictionary.** The AnimeSchedule
  "Auto-fill" status messages on add_anime, the "(N selected)" counter
  on recommendations, and every alert in the list_settings "Check for
  Update" flow now travel from PHP via a `LANG` JS constant; no
  hard-coded TR strings remain on the client side.

- **DB enum values are still TR (intentional).** The `animes.status`
  column still stores "Yayın Tamamlandı" / "Yayın Devam Ediyor" as
  values (legacy compatibility). Only the **displayed label** is
  translated: on index.php, statistics.php and recent.php a PHP-side
  lookup converts those values to `index.broadcast.finished` /
  `index.broadcast.ongoing`. The `broadcast_day` column follows the
  same pattern — it stores "Pazartesi" / "Salı" etc. and the screen
  shows "Monday" / "Tuesday".

- **Plan B - separate admin/user dictionaries.** Admin pages
  (`admin.php`, `admin_pending.php`, `admin_sync.php`) load their own
  dictionaries: `lang/admin_tr.php` and `lang/admin_en.php`. Regular
  user pages load only `lang/tr.php` and `lang/en.php` — they never
  touch the admin dictionary. Advantage: no dead-weight keys in user
  installations, the user dictionary stays small (433 keys); admin
  pages load their 66 keys in addition. The two dictionaries are
  combined via `array_merge` — on an admin page both a shared key
  like `nav.about` and an admin-only key like `admin.tool.sync.h3`
  are accessible via the same `t()` call.

  The helper for this is a new `lang_init_admin($pdo)` function
  (in functions.php). It first calls the normal `lang_init()` (load
  the user dictionary), then merges the admin dictionary on top with
  `array_merge`. Admin pages put `lang_init_admin($pdo)` at the top
  instead of `lang_init($pdo)` — no other change needed.

## What did NOT change

- The DB schema (columns, indexes, constraints) is **completely
  unchanged**.
- Sync logic, chronology marker logic, and "What Should I Watch?"
  scoring are all unchanged.
- Free-form Turkish content you have already entered — notes, personal
  synopsis, anime titles, sentence tags, etc. — is left exactly as
  written.
- The existing preference (settings.display_language) is preserved;
  after the upgrade the app opens in the same language you used
  before.

## Known behaviour

- **Free-form text in the database does not translate to English.**
  Anime titles, notes, personal synopsis, recommendation sentence
  names ("Okulda geçsin" etc.) — if you entered them as data in
  Turkish they stay Turkish. This is the correct behaviour; a UI
  translation is not a content translation.

- **Form labels in `edit_anime.php` partially missing.** The current
  edit_anime.php calls a few `edit_anime.*` keys that are not yet
  fully covered by the dictionary (residue of an earlier partial
  i18n pass). If the key name itself shows up in the UI, that is
  the `t()` helper's visible warning — it will be filled in on the
  next maintenance pass. Affected pages: 1. Broken functionality: 0
  (the form still works).

## Technical notes

This release has no migration steps (no schema changes). The
upgrade.sql file contains a single `SELECT 1` no-op; the migration
manager still runs it and bumps settings.version to 0.6.4. No manual
step is required when upgrading from an older version.

### i18n discipline (KARARLAR Section 7)

This release closes the "9 more pages to translate" item that
KARARLAR Section 8 had left open. Discipline for any future page:
`lang_init($pdo)` at the top, wrap UI strings in `t('namespace.key')`,
add the key to lang/tr.php **and** lang/en.php in the same edit.
A one-sided omission surfaces visibly to the user via the `t()`
helper's key-itself fallback — that behaviour is deliberate and is
retained (developer warning).

### Adding a new admin page (new)

If you add an admin tool (e.g. `admin_backup.php`):
1. At the top of the file, call `lang_init_admin($pdo)` instead of
   `lang_init($pdo)`.
2. Use the `admin_*` namespace: `t('admin_backup.heading')` etc.
3. Add the keys to `lang/admin_tr.php` **and** `lang/admin_en.php` —
   do not put them in the user dictionary (`lang/tr.php` / `lang/en.php`).
   Admin keys do not belong there; placing them there leaves dead-
   weight strings in every user installation.
4. Shared keys like `nav.about` or `lang.tr_label` already come from
   the user dictionary — do not copy them into the admin dictionary
   (no collision, `array_merge` combines them at request time).

### Maintenance suggestion after dictionary growth

499 keys (433 user + 66 admin) cannot be eyeballed one by one. The next maintenance pass
will consider adding a simple test script — one that verifies the
TR and EN files have exactly the same key set. For now the check
is manual (`grep -c "^    '" lang/tr.php` compared to en.php before
each release; same again for `lang/admin_tr.php` vs `lang/admin_en.php`).
