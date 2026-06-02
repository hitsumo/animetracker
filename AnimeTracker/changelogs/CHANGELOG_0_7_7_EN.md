# Anime Tracker 0.7.7 - Changelog

**Release date:** June 2026

## English names for genres and sentences now travel with the catalog

You could already enter an English version of a genre or recommendation
sentence (tag), but those English names stayed only on your own
installation; they did not travel to other installations during catalog
sync.

Now the catalog also carries the English name of a genre or sentence when
one has been entered. This way, installations fed by the same catalog
share the English versions of genres and sentences too.

The rule is: when the catalog sends an English name, the local value is
updated to match it. When the catalog does not send one, an English name
you entered yourself is left untouched; a sync never erases a name you
typed.

On a single-user installation there is no visible difference, since you
already enter and see your own names. The difference shows up when more
than one installation is fed by the same catalog.

## Admin: corrected the file name in a sync error message

On the admin sync page, the error shown when the HMAC secret is not
defined said the secret should be in `config.php`. The secret is actually
read from `admin_secret.php`. The message now names the correct file.
This affects the admin side only; there is no change to the end-user
interface.

## Other

### Schema

This release contains no schema change. The genre/sentence English-name
columns it uses were added in an earlier release. `migration/0.7.7` is an
empty migration that only advances the version number; it runs
automatically during auto-update and requires no manual action.

### Files

Changed: `catalog.php`, `catalog_import.php`, `admin_sync_example.php`,
`lang/admin_tr.php`, `lang/admin_en.php`.
New: `migration/0.7.7/upgrade.sql`.
