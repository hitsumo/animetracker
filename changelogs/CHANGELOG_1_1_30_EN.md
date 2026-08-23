# Anime Tracker 1.1.30

**Release date:** 2026-08-23

This release introduces the site to search engines — **and explicitly hides
the installations that should never have been introduced to them.** Both are
sides of the same job, so they were done together.

Until now a page `<head>` carried nothing but a title: no description, no
canonical link, no sharing tags. There was no `robots.txt` and no
`sitemap.xml`. So there was no way to tell a search engine "these pages
exist" — and no way to tell it "never look here".

## Privacy first: for people who self-host

**Personal installations are now closed to search engines.** In single-user
(self-host) mode the application has no login — that is by design, whoever
opens the page is the single user. Someone running a personal install on
their own server was, until now, not only publicly reachable but
**indexable**. That was a privacy bug, not an SEO gap.

Indexing now follows the mode:

| Installation | Behaviour |
|---|---|
| **Single-user (self-host)** | Every page says "do not index"; `robots.txt` blocks everything; no sitemap is published |
| **Multi-user (online)** | Pages are indexable; the sitemap is published |

No new setting was added — the decision is tied to the existing
`MULTI_USER_MODE` switch. A personal install should be private without its
owner having to discover a checkbox. Anyone who wants the opposite sets that
switch to `true` in `config.php`.

## The sitemap (`sitemap.xml`)

There is now a `sitemap.xml` address, generated fresh on every request. It
lists:

- the main list page, the about page and the six help pages,
- **the detail page of every anime entry**,
- the **watch order** page of anime that have chronology markers,
- **one** series chronology page per series.

The real reason for a sitemap is paging: the list shows 10 entries per page
by default, so as the catalog grows, the entries deep in it become
practically unreachable by following links. The sitemap names every entry
directly.

**The sitemap splits itself when the catalog grows.** Past 2000 entries it
produces a sitemap index plus chunks instead of one file — the standard
structure search engines expect — and it happens automatically.

**Adult-flagged entries are not listed.** The detail page already hides them
behind an opt-in preference, so an anonymous visitor (and a crawler) only
sees a neutral notice. Listing an address that answers with a placeholder is
worse than not listing it.

## `robots.txt`

It exists now, and its content follows the mode. On a multi-user install the
public pages stay open while login, registration, account, editing, admin
and every "does something when opened" address are closed off. The sitemap
address is announced here too.

There is a separate section for Yandex: the sorting and filter parameters of
the list page are declared with `Clean-param`, so dozens of addresses of the
same list are not mistaken for separate pages.

## Tags added to the page heads

Every page gained a **description**, a **canonical** link and **sharing
tags** (Open Graph / Twitter card). In practice:

- **In search results** a meaningful description now appears under the
  title. On anime detail pages it is built from the **catalog synopsis**.
- **When you paste a link** into WhatsApp, Discord or a forum, you get a
  card with the title, the description and the **poster** instead of a bare
  address.

**Your personal synopsis never appears in these descriptions.** They are
built from the catalog synopsis only; the personal one is your own note,
while a meta description is published to the whole world.

### Canonical: one address per piece of content

- **List page:** sorting, filter, search and page-number parameters all
  point back at the bare list address.
- **Series chronology:** this page draws the same series identically for
  every member, so one page would have had many addresses. One address per
  series is now the official one.
- **Watch order:** the view mode (broadcast order / story order) may appear
  in the address, but the official address is a single one.

### Three pages left out of search

**Recently Updated**, **Statistics** and **What Should I Watch?** are
deliberately kept out of search results: all three change on every visit,
the statistics describe one person's list, and the "surprise" mode returns a
different anime on every request. Links on those pages are still followed —
so crawling effort goes to the detail pages instead.

## A small but real fix

Opening a detail page with a non-existent anime number said "not found"
while the server answered **"everything is fine"**. A search engine reads
that as "this page exists" and treats the one-line error as content. It now
returns a proper "not found" response. Nothing changes on screen.

## Configuration: `SITE_URL` (optional)

Search engines want absolute addresses; the application links relatively
everywhere. The address is now reconstructed from the request itself, so
**you do not have to do anything** — an install in a subdirectory produces
correct addresses too.

The one exception: behind a reverse proxy or a CDN the incoming request may
not reveal the site's real address. In that case `SITE_URL` can be set in
`config.php`. It sits in the example configuration file with its
explanation, commented out by default. Existing `config.php` files keep
working untouched.

## After the release (not code)

For the sitemap to be worth anything it has to be announced: Google Search
Console, Bing Webmaster (the same registration covers DuckDuckGo, Ecosia and
Yahoo) and Yandex Webmaster. All three are given the **same** sitemap
address; no engine needs its own file. If site ownership is verified with a
**DNS TXT record**, no file has to be added to the site at all — that is the
cleanest route.

## Changed files

**New:**

```
files/functions/seo_helpers.php
files/sitemap.php
files/robots.php
files/migration/1.1.30/upgrade.sql (version stamp only)
```

**Changed:**

```
files/.htaccess                    (/robots.txt and /sitemap.xml rewrites)
files/functions.php                (loads the new helper)
files/config_example.php           (optional SITE_URL)
files/index.php                    (meta + canonical)
files/anime_details.php            (meta + canonical + "not found" response)
files/chronology.php               (meta + canonical)
files/series_timeline.php          (meta + one canonical per series)
files/about.php, files/help.php    (meta + canonical)
files/help/help_basics.php, help_fields.php, help_sync.php,
files/help/help_discovery.php, help_series.php, help_timezone.php
files/recent.php, files/statistics.php, files/recommendations.php
                                   (no-index tag)
files/lang/tr.php, files/lang/en.php  (11 new strings each)
files/version.txt
```

## Deployment notes

- `files/functions.php` and `files/functions/seo_helpers.php` must be
  uploaded **together**. If only the pages are updated they call a function
  that does not exist and the page will not open.
- If `files/.htaccess` stays old, `/robots.txt` will not work (the server
  blocks the `.txt` extension across the board); `robots.php` and
  `sitemap.php` still open under their own names.
- If the language files stay old, the key name shows up instead of the
  description text — harmless but ugly.
- The database schema did not change, and nothing has to be done by hand on
  the central catalog server.
- **On the distribution server** the usual two functional steps apply: the
  published `version.txt` must be moved to 1.1.30 — otherwise "Check for
  updates" still thinks 1.1.29 is the latest — and the
  `updates/1.1.30/anime-tracker-1.1.30.zip` package must be published, or
  the "Update" button gets a 404 at the download address.
- One thing to confirm after upload: multi-user mode must be on in the live
  `config.php`, otherwise the site closes itself to search engines.
