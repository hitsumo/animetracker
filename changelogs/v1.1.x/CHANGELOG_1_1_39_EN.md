# Anime Tracker 1.1.39

**Release date:** 2026-09-08

No schema change. One piece of work: the thousands of addresses that render the
same list no longer look like separate pages to a search engine.

## 1. List duplicates are no longer indexed

1.1.37 answered the question "is this **entry** worth indexing?". The other half
of the same question was left open: how many **addresses** show the same
entries?

Every control on the list page adds a parameter to the address — letter filter,
sort column, direction, page number, page size, tab. A crawler clicks all of
them and treats each combination as a separate page. Measured against the live
catalog, that is **1,568 distinct (letter, page) pairs × 10 sort combinations ≈
15,700 addresses**; the six page-size values push it to ~94,000.

The measurement (8 September 2026, Search Console's 1,000-address sample):

| Share | What |
|---|---|
| 65% | thin stub detail page — already closed by 1.1.37; dropping them depends on a re-crawl |
| **29%** | **list duplicate** — the subject of this release |
| 5% | page with real content |
| 1% | chronology / series / help |

On the same day the sitemap described **564** addresses; the index held
**5,463**.

These duplicates do **not** open a new entry point: none of them answers a
search query — nobody searches for "letter H, by watch status descending, page
43". What they do is queue up in front of the entry point that already exists.
Crawl budget is fixed, and the measurement showed that only one twentieth of it
was reaching the pages we actually want indexed.

From now on the **bare list page** is indexed; the moment a filter, sort, search
or page number is supplied, the address carries `noindex, follow`. The page is
not indexed, but its **links are still followed** — pagination is the only
internal path to the whole catalog, so that part is essential.

**Nothing changed inside the site.** Filters, sorting, paging and tabs work
exactly as before; the only change is what these addresses say to a search
engine. A visitor sees no difference.

### Why `canonical` was not enough

These pages have carried a correct `canonical` since 1.1.30 — all of them point
at the bare list page. But `canonical` is a **hint**; the measurement showed
Google ignoring it and indexing the duplicates anyway. Yandex receives the same
information as `Clean-param` and does honour it.

### Why no `robots.txt` ban was added

A rule like `Disallow: /*?sort=` closes the address to **crawling**. A `noindex`
on a blocked address cannot be read, so the thousands of duplicates already in
the index would stay there. They have to be read first, then dropped.

### The same fix on the series timeline

`series_timeline.php` draws the same timeline for **every** member of a series —
five members means five addresses for one page, and the view and chain tabs
multiply that again. The `canonical` here was correct too, and again only a
hint. An address that is not the canonical one now carries `noindex, follow`.
The address the sitemap lists is indexed as before; **the sitemap output did not
change.**

## What to expect after deploying

Duplicates do **not** leave the index immediately, and the timescale is weeks:
Google has to re-crawl the address, see the `noindex`, and then drop it.

The expected curve in Search Console: **"Not indexed" goes up first** (every
dropped page moves there under "excluded by noindex tag") and "Indexed" goes
down. **That is success, not a fault.**

The thing to measure is not the page count but **impressions and clicks** in the
Performance report. The dropped addresses had close to zero impressions, so the
total is not expected to fall.

## Deliberately out of scope

- **The page number is still unbounded.** `?page=780` returns the last page of
  the list, not an empty screen. That behaviour is right for a visitor editing
  the address by hand; the search-engine cost of it is already covered by
  `noindex`.
- **The chronology page was left alone** — its only parameter is the record id,
  and that is already canonical.
- **Recent, statistics and recommendations** have been excluded from indexing
  since 1.1.30.

## Files

**New:**

```
files/migration/1.1.39/upgrade.sql   (schemaless; version stamp + rationale)
```

**Changed:**

```
files/functions/seo_helpers.php   the rule (single place)
files/index.php                   noindex, follow on a shaped address
files/series_timeline.php         noindex, follow on a non-canonical address
files/robots.php                  Clean-param list now read from one place
files/version.txt
```

No new language keys — no visible interface text changed. No new CSS or JS
either.

## Deployment note

- `files/functions/seo_helpers.php`, `files/index.php` and `files/robots.php`
  must be uploaded **together**. If the old copy of the helper stays on the
  server, the home page and `robots.txt` both fail (two new functions cannot be
  found). The reverse order is safe: helper first, then the rest — the new
  helper works with the old pages too.
- `files/series_timeline.php` calls no new function and can go on its own.
- **Nothing to do on the central catalog server.** No new field on the catalog
  wire, no manual `ALTER`, and no file under `catalog_server/` changed.
- The migration changes no schema; it only carries the version stamp and runs on
  its own.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must be moved to 1.1.39 and the
  `updates/1.1.39/anime-tracker-1.1.39.zip` package published.
- Self-hosted installs are unaffected — every page is already excluded from
  indexing in that mode.
