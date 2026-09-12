# Anime Tracker 1.1.37

**Release date:** 2026-09-02

No schema change. Four small, independent pieces of work: one about search
engines, two that clear the way for new languages, and one about identifying
ourselves to an outside service.

## 1. Empty entries are no longer announced to search engines

The catalog grows three ways, and two of them — moving an offline list online,
and importing from MAL/AniList — open the record as a **thin stub**: it has a
title and an identity, but no synopsis and no image. Most of the catalog is in
that state today.

Until now the sitemap's only filter was "not adult content", so every one of
those stubs went into it; since IndexNow was added, the same addresses were also
being pushed to search engines actively. With the site recently submitted to
Google, Yandex and Bing, this stopped being a theoretical problem.

From now on an entry is announced only if it has **something to offer a reader
beyond its title**: a synopsis (in either language), an image, or a chronology
marker. An episode count or a date is not enough on its own — those are
catalogue fields, not the content someone expects from a search result.

An empty entry's detail page now carries `noindex, follow`: the page is not
indexed, but its **links are still followed**, so the filled-in entries in the
same series keep being discovered through it.

**Nothing is deleted or hidden.** Inside the site everything looks and behaves
exactly as before; the only change is what gets announced outward. The moment
you add a synopsis or an image, the entry becomes indexable again on its own.

The rule is read from **one place** by the sitemap, IndexNow and the detail
page, so the three cannot drift apart.

Self-hosted installs are unaffected — every page is already excluded from
indexing in that mode.

## 2. Missing translations now fall back to English

Interface strings are looked up in order: the active language, then the fallback
language, then the key itself. The fallback used to be Turkish.

With two languages that made no difference. With a third it does: falling back
to Turkish for an Indonesian speaker is plainly worse than falling back to
English.

This is also what **makes language contributions practical**: a translator does
not have to finish the whole dictionary. Every key left untranslated falls back
to English, nothing on screen breaks, and the file can be completed a piece at a
time.

The admin dictionary was changed the same way.

## 3. AniList requests now identify themselves

Outgoing requests carry `AnimeTracker/<version> (+repository URL)`.

This is not a compliance change — the import already reads only **the user's own
list**, does not browse the catalog and does not collect metadata. The reason is
what happens if someone notices: an unidentified request looks like an unknown
bot and gets blocked silently; an identified one brings an email first.

Not added to AnimeSchedule, whose requests are already signed with an API key.

## 4. A "adding a language" section in the README

Three steps: copy `lang/en.php`, translate the values (leave the keys alone),
add the code to the allowed-languages list.

With one caution: in most strings a small mistake is harmless, but the ones that
carry **consequences** deserve a human check — the adult-content warning, delete
confirmations, the spoiler gate, and the backup/restore warnings. If those are
wrong the result is not merely awkward; a user can lose data or be shown a
spoiler.

## Changed files

**New:**

```
files/migration/1.1.37/upgrade.sql   (no schema change; version stamp + rationale)
```

**Changed:**

```
files/functions/seo_helpers.php          the indexability rule (single source)
files/anime_details.php                  noindex, follow on a thin page
files/functions/i18n_helpers.php         fallback language TR -> EN
files/functions/anilist_import_helpers.php   User-Agent
README.md                                adding a language (TR + EN)
files/version.txt
```

## Deployment note

- `files/anime_details.php` and `files/functions/seo_helpers.php` must ship
  **together**: the page calls a new helper that does not exist in the older
  helper file. The files for the other two pieces are independent.
- **Nothing to do on the central catalog server.** No new field on the catalog
  wire, no manual `ALTER`, no file changed under `catalog_server/`.
- The migration makes no schema change; it only moves the version stamp and runs
  on its own.
- **On the distribution server**, the usual two steps: move the published
  `version.txt` to 1.1.37 and publish
  `updates/1.1.37/anime-tracker-1.1.37.zip`.
- After deployment the search engines need to re-read the sitemap; pages that
  are already indexed will take a while to drop out.
