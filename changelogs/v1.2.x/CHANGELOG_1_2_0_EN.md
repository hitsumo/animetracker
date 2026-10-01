# Anime Tracker 1.2.0

**Release date:** 2026-09-27

One change: **the site's language now travels in the address.** Every
page has its English version at its own address (`?lang=en`), and on an
online installation search engines are told about both languages. No
schema change (empty migration); nothing to do on the central catalog.
Language selection on a single-user installation works as before.

## 1. Why

The interface language lived only in the session (guests) or the account
preference (members); the address was the same whatever the language.
Search engines carry no cookies, so they only ever saw the default
(Turkish) version of each page: to them the English version did not
exist. Search Console showed it: over the last three months, impressions
from outside Turkey sat between positions 44 and 72 and received almost
no clicks. English searches were being answered with a Turkish page.

## 2. Language in the address

- The address without a parameter is Turkish, unchanged. Existing Turkish
  rankings are not affected.
- Adding `?lang=en` to the same address opens the page in English
  (e.g. `series_timeline.php?id=56&lang=en`).
- **Guests:** the choice is also written to the session. A visitor
  arriving from an English search stays in English while following the
  site's links.
- **Members:** the address only shows that page in that language; the
  language preference on the account does not change. Opening an English
  link can never change your saved preference for you.

## 3. TR | EN links

On an online installation, visitors who are not signed in see small
**TR | EN** links at the top right of the public pages (list, details,
chronology, series chronology, About, help, Recently Updated,
Statistics, What to Watch). The link goes to the page you are on and only
switches the language. Members still choose the language in List
Settings; on a single-user installation the links do not appear.

## 4. Search engines

Online installations only (a single-user installation is never indexed
at all):

- **hreflang:** every indexable page lists both language versions plus
  `x-default` (English: for a visitor who speaks neither of our
  languages). The list is identical on both versions of the page.
- **Canonical address** is written in the active language: the Turkish
  version without a parameter, the English version with `?lang=en`.
- **The sitemap** lists every address in both languages, each carrying
  its language alternates (`xhtml:link`). **IndexNow** announces both
  languages' addresses too.
- **An entry whose synopsis exists only in Turkish:** its English page
  shows the Turkish synopsis and so is a mixed-language page; that
  entry's English version is not indexed and the Turkish version does not
  point to it. An entry with no synopsis at all but with a poster or
  chronology notes is indexed in both languages.
- `?lang=tr` and an unrecognised `?lang=` value are copies of the page
  without a parameter; those addresses are not indexed ("noindex,
  follow").
- **English name in the English title:** English searches mostly use the
  English name. When an entry has an `[en]`-tagged alternative title, it
  is added to the English page's title, e.g. "B-gata H-kei (B Gata H Kei:
  Yamada's First Time) Watch Order". This applies to the details,
  chronology and series chronology pages; on the series page the name
  comes from the entry that represents the series.

## 5. Help

The "Interface Language" text in the preferences help was updated:
members choose the language in List Settings, guests use the TR | EN
links, every page's English address is the same address with `?lang=en`,
and sharing an English link does not change anyone's saved preference.
(The old text referred to a "language picker at the top right" that has
not existed since 1.1.4.)

## Files

**New:**

```
files/migration/1.2.0/upgrade.sql      empty (version stamp)
```

**Changed:**

```
files/functions/i18n_helpers.php       ?lang= reading, lang_supported / lang_default / lang_url_param / lang_path, guest_lang_links()
files/functions/seo_helpers.php        hreflang, canonical per language, og:locale:alternate, language rule (seo_row_indexable_in_lang), English name, sitemap + IndexNow languages
files/sitemap.php                      one <url> per language + xhtml:link
files/anime_details.php                language rule, English name, TR | EN
files/series_timeline.php              English name, TR | EN
files/chronology.php                   English name, TR | EN
files/index.php                        TR | EN
files/about.php                        TR | EN
files/recent.php                       TR | EN
files/statistics.php                   TR | EN
files/recommendations.php              TR | EN
files/help.php                         TR | EN
files/help/help_*.php (10 files)       TR | EN
files/css/lang.css                     .guest-lang-links
files/lang/tr.php                      help.prefs.ui_lang.text
files/lang/en.php                      same
files/version.txt
```

Language file parity: 1166 = 1166 (no new keys).

## Deployment notes

- **Nothing to do on the central catalog.**
- The migration is empty; it moves the version stamp on the first page
  load.
- The sitemap roughly doubles in size; resubmitting it in Search Console
  is enough.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.0 and publish the
  `updates/1.2.0/anime-tracker-1.2.0.zip` package.
