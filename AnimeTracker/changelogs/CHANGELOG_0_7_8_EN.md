# Anime Tracker 0.7.8 - Changelog

**Release date:** June 2026

## The installation screens are now available in English

The setup wizard and the "installation complete" screen used to be in
Turkish only. These two screens run before the database exists, so the
language mechanism used in the rest of the application does not work
here; for that reason their English versions were added as separate
files, the same way the AI Use Notice page is handled.

There is a language link in the top-right corner of both the setup and
the installation-complete screens: "English" on the Turkish screen,
"Türkçe" on the English one. A user who chooses the English setup stays
in English through the whole flow, from entering the connection details
to finishing the installation.

For existing installations there is no visible change; these screens
appear only during first-time setup. The difference shows up for new
users who want to install the application in English.

## Other

### Schema

This release contains no schema change. The added screens are plain
PHP/HTML pages that touch no database table. `migration/0.7.8` is an
empty migration that only advances the version number; it runs
automatically during auto-update and requires no manual action.

### Files

New: `setup_en.php`, `install_en.php`, `migration/0.7.8/upgrade.sql`.
Changed: `setup.php`, `install.php` (added the language link; the list of
files to delete after installation was updated to also cover the two new
files), `installer.nsi` (the .exe installation now also removes these two
new files automatically).
