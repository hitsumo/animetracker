# Anime Tracker 1.0.11

**Release date:** (fill in on deploy day)

## New

### Catalog changes on online installations are now pushed to the central server automatically
Previously the only moment the central catalog was written to was the
"Promote to Catalog" approval of a pending anime; edits made afterwards
(writing a synopsis, fixing a title or episode info) stayed only in the
online installation's own database. Now:

- When a **catalog anime is edited and saved**, the change is pushed to
  the central catalog automatically — synopsis, title, episode info,
  chronology, genres and tags included.
- An anime **added directly to the catalog** by a moderator or admin
  (skipping the approval queue) is also pushed at the moment it is added.
- Regular user additions still go to the approval queue and are pushed
  on approval; that behavior is unchanged.

### The user is informed when a push fails
A failed push never rolls the save back; the change stays stored on the
installation and a warning is shown at the top of the main list. Saving
the anime again retries the push.

## Notes
- Self-host (single-user) installations are unaffected; the new code
  blocks never run there.
- Anime images are not part of the push; images must be uploaded to the
  central server manually.
- Personal synopsis, notes, emotions and watch status are personal data
  and are never included in any push. Text meant for the central catalog
  must be written into the catalog synopsis field.
- The database schema is unchanged in this release.

## Changed files
- `index.php`
- `add_anime.php`
- `edit_anime.php`
- `version.txt`
- `upgrade.sql`
- `lang/tr.php`
- `lang/en.php`

## New files
- `migration/1.0.11/upgrade.sql`
