# Anime Tracker 0.7.6 - Changelog

**Release date:** June 2026

## Recommendation tag labels now respect the language

On the recommendations page, each matched anime card shows small pills below
it indicating which sentences (tags) matched. Until now these pills always
showed the Turkish sentence name.

Now, on the English interface, if an English version of a sentence has been
entered, the pill shows that English name. If no English version exists, or
the interface is Turkish, the Turkish name is shown as before; no behavior
changes there.

With this, anime main titles, related anime titles, and genre/sentence labels
all follow the same language preference.

## Other

### Schema

This release contains no schema change. `migration/0.7.6` is an empty
migration that only advances the version number; it runs automatically
during auto-update and requires no manual action.

### Files

Changed: `recommendations.php`.
New: `migration/0.7.6/upgrade.sql`.
