# Anime Tracker 1.1.32

**Release date:** 2026-08-24

This release answers one question: **when does a search engine find out that
a page changed?**

1.1.30 gave the site a sitemap. A sitemap answers "what exists here", and a
crawler reads it on its own schedule — days, sometimes weeks. A newly added
anime stays unknown until that read; a deleted address stays in the results
until the crawler comes back.

**IndexNow** answers the other question: *what changed just now?* It is a
notification protocol that Bing and Yandex share — you tell one endpoint and
the participants pass it on. Google does not take part, so IndexNow does not
replace the sitemap; it sits next to it.

## Nothing changed on screen

This release is for whoever runs the site, not for whoever keeps a list on
it. There is no new button, no new field, no page that looks different. And
on a personal single-user install it never engages at all: an installation
with no login already says "do not index me", so announcing its addresses
would contradict itself.

## When an announcement happens

| What you do | What is announced |
|---|---|
| Add an anime | The new anime's addresses |
| Edit an anime | That anime's addresses |
| Delete an anime | The removed addresses (the engine returns, sees 404, drops them) |
| Add / change / delete a chronology note | The affected chronology page |
| Approve a member suggestion | The approved anime's addresses |
| Import from the catalog | Only the records that **actually changed** |

Which addresses go out follows the sitemap's own rule — whatever the sitemap
would list: adult-flagged rows excluded, the chronology page only for an
anime that has notes, the series timeline only for the entry that represents
its series.

The list page is deliberately left out. It changes on nearly every write,
crawlers visit it often anyway, and a new anime is discovered through its own
detail address; announcing it every time would spend the budget on the page
that needs it least.

## Saving never waits for anything

The obvious implementation — call the search engine when you press save —
was wrong in three separate ways:

- It would put a third-party network round trip inside your save. A slow
  endpoint would feel like a slow form.
- A failed request would simply be lost. There is no retry, and the address
  would never be announced.
- A 500-row import would fire 500 requests, and editing one anime five times
  in a minute would fire five — which is exactly what the protocol asks
  submitters *not* to do.

So all a save does is note that an address changed. A separate scheduled job
sends what has accumulated, once an hour, in batches — the same arrangement
the aired-episode sync has used since 1.0.19. One page changed ten times in a
day is still one line, and one announcement.

What that means in practice: **there is never a moment where you have to
wonder whether to ping now.** You save as usual and the rest happens on its
own.

## Setup (once)

The feature is **off by default** and wants two lines of configuration:

1. Generate a key:

   ```
   php indexnow_ping.php --genkey
   ```

2. Put the lines it prints into `config.php`:

   ```php
   define('INDEXNOW_KEY', '...');
   define('SITE_URL', 'https://your-site');
   ```

   `SITE_URL` is **required** here. Over the web the site's address can be
   worked out from the request itself, but a command line carries no such
   information; without it every address would come out as
   `http://localhost/...` and be rejected. The script refuses to run rather
   than send those.

3. Check that `https://your-site/<key>.txt` returns the key as plain text.
   (That file is generated the same way `robots.txt` is — you do not have to
   place a `.txt` on disk yourself.)

4. Schedule the job. Hourly is plenty:

   ```
   0 * * * * php /path/to/indexnow_ping.php >> /var/log/anime_indexnow.log 2>&1
   ```

With no key defined, nothing is queued, nothing is sent, and the rest of the
application is unaffected.

## Four commands for the administrator

```
php indexnow_ping.php             send what is waiting
php indexnow_ping.php --status    configuration + queue report, sends nothing
php indexnow_ping.php --dry-run   print the next batch's addresses, sends nothing
php indexnow_ping.php --retry     clear the counters on stuck rows, then send
```

`--status` looks like this:

```
indexnow status
  mode:          online (indexable)
  key:           a1b2... (32 chars)
  site url:      https://your-site
  key file:      https://your-site/a1b2....txt
  curl:          yes
  queued:        14
  stuck:         0 (attempts >= 5; use --retry)
  last ping:     2026-08-24 09:00:00 UTC
  last count:    31
```

## If something goes wrong

A failed submission does **not** discard the queue. A temporary problem —
rate limiting, a server error, a dropped connection — is made up for on the
next run by itself. A permanent one — a wrong key, an address that does not
match — parks those lines after five attempts so they cannot block the newer
addresses behind them; the lines are not deleted, `--status` counts them as
`stuck`, and once the cause is fixed `--retry` brings them all back.

Every failure is written to the server error log with its detail.

## Changed files

**New:**

```
files/functions/indexnow_helpers.php    (queue + submission logic)
files/indexnow_ping.php                 (the cron / command-line job)
files/indexnow_key.php                  (serves the key file)
files/migration/1.1.32/upgrade.sql
```

**Changed:**

```
files/functions.php                     (loads the new helper file)
files/functions/seo_helpers.php         (address rule for a single anime)
files/add_anime.php                     (queues after an add)
files/edit_anime.php                    (queues after an edit)
files/index.php                         (queues BEFORE a delete)
files/catalog_import.php                (queues only what really changed)
files/admin/admin_catalog_requests.php  (queues on approval)
files/add_chronology_marker.php         (a chronology page may be gained)
files/update_chronology_marker.php      (chronology content changed)
files/delete_chronology_marker.php      (the last note may be gone)
files/.htaccess                         (key file rewrite)
files/config_example.php                (INDEXNOW_KEY documentation)
files/schema.sql
files/version.txt
```

## Deployment note

- `files/functions.php` and `files/functions/indexnow_helpers.php` must ship
  **together**. Without the loader line the file is never read and every page
  that queues an address fails to open.
- `files/functions/seo_helpers.php` belongs in the same package: the address
  rule lives there, and the old file does not have that function.
- If `files/.htaccess` is not refreshed, the key file address answers 403 and
  submissions are silently rejected. The site keeps working; only IndexNow
  does nothing useful.
- **Nothing to do on the central catalog server.** Unlike 1.1.31 there is no
  new field on the catalog wire and no manual `ALTER`.
- The migration runs automatically and creates a single table.
- **On the distribution server**, the usual two functional steps: the
  published `version.txt` must be moved to 1.1.32 — otherwise "Check for
  updates" still believes 1.1.31 is the latest — and the
  `updates/1.1.32/anime-tracker-1.1.32.zip` package must be published, or the
  "Update" button gets a 404 at the download address. Open the package and
  confirm it carries the three new files.
