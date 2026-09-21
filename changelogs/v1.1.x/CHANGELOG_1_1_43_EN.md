# Anime Tracker 1.1.43

**Release date:** 2026-09-19

No schema change. One piece of work: an **install counter** — a way for the
project to see, roughly, how many Anime Tracker installations exist. **One**
request per installation; no identity, no personal data, one line to switch
off.

## 1. Install counter

### Why

The project is open source and self-hostable. Until now there was **no
automatic point** at which an install had to talk to the project: a
personal install may never pull the central catalog, and the update check
only runs when its button is pressed. A copy downloaded from GitHub and
installed somewhere was simply never known to exist. "How much have we
grown?" had no answer.

### What it does

The install sends one small request to the project counter **once only** —
after the first time the home page is opened. It carries three things:

| Field | What | From |
|---|---|---|
| `id` | Random 32-character install id | Generated with `random_bytes` at send time, stored in `settings.install_id`. Not derived from the address, the database or the person. |
| `v` | Installed version | `version.txt` |
| `m` | `single` (personal) / `multi` (multi-user) | `MULTI_USER_MODE` |

**Not sent:** site address, member count, anime, watch data, notes, server
name. The counter **does not store the IP address**. The id exists only so
that a repeat (a retry, a restored backup) is not counted as a new install;
an install that deletes the `install_id` and `install_ping_done` rows from
`settings` becomes a new install on the counter.

**Why once, not daily:** the first draft was a daily heartbeat (it would
also have given "active in the last 30 days" next to the total). Decision:
the smallest possible footprint — once per install, "we extend it if we
ever need to". The gate is built so that going daily is a one-line change.

### How it works

- **The page does not wait.** `index.php` only checks "has the ping
  succeeded, was it attempted today?" (`settings.install_ping_done` /
  `last_install_ping`). If due, it prints a one-line
  `fetch('install_ping.php')` at the end of the page; that endpoint makes the
  outbound request with a 3-second timeout. A slow or dead counter server
  never affects the page the user is looking at.
- **At most one attempt a day until the first success, then never again.**
  The endpoint marks the day **before** sending; on HTTP 200 it writes
  `install_ping_done` and the gate closes for good. An install set up while
  the counter is down is not lost — it is counted the next day, and no page
  load in between fires an outbound request.
- **POST + CSRF.** The same pattern as every other state-changing endpoint.
  A second request (two tabs, a crawler running the page's JS) returns 204
  and does nothing.
- **Switching off:** `define('INSTALL_PING', false);` in `config.php`. A
  missing constant means on (the `MULTI_USER_MODE` convention for older
  `config.php` files). New installs get the line written as `true` by
  `setup.php` and the Docker entrypoint so the switch is visible.

### Where the numbers show

- **Admin dashboard → "Install Counter" card:** total, personal /
  multi-user split, which version each install started with. The
  card fetches from the browser (fetching server-side would make the admin
  page depend on a remote host). If the counter is off on this install, the
  card says so.
- **Public:** `ping.php?stats=1` returns totals only, never ids. Decision:
  there is nothing in the number to protect; an "N installs" badge on the
  About page may follow.

### Transparency

An open-source project does not get a "silent" counter. What goes out is
written in four places: `README.md` (TR+EN, new "Install counter" section),
the help (Sync and Updates → new **Install Counter** section: what is sent,
what is not, how to switch off, why it exists), `config_example.php`, and
the helper file's header comment. The number is roughly right, not proof:
installs that switch it off are invisible, one that resets its id is counted
twice — the help says so too.

## 2. "Edit" on the detail page only for those who can edit

The detail page showed the "Edit" button to everyone; an anonymous visitor
clicking it was redirected by `edit_anime.php` to the login page (the endpoint
has always been moderator-gated). The list page had shown the same button to
moderators only for a long time; the detail page had been missed. The damage
showed up in search: Search Console had accumulated 1,034 "page with redirect"
entries, all `edit_anime.php?id=…`. The button is now moderator-and-above on
the detail page too. On a personal install the owner is always allowed, so
nothing changes there.

## 3. "Watch Order" in the series chronology page title

For the search "tensei shitara slime izleme sırası" (watch order), Search
Console showed the movie's detail page rather than the series chronology page:
the page title said "Series Chronology", the searcher typed "watch order". The
`<title>` and meta description now carry both — *"X Watch Order - Series
Chronology"*; the description adds "which movie to watch after which episode".
The page name in the UI and the help texts are unchanged.

## Files

**New:**

```
files/functions/install_ping_helpers.php   logic; INSTALL_PING_URL constant
files/install_ping.php                      local AJAX endpoint (POST + CSRF)
files/migration/1.1.43/upgrade.sql          no schema change; version stamp + rationale
catalog_server/ping.php                     CENTRAL: record + ?stats=1
```

**Changed:**

```
files/index.php                    trigger (printed only when a ping is due)
files/anime_details.php            "Edit" button moderator-only (§2)
files/series_timeline.php          <title> and seo_head title from the new pattern (§3)
files/functions.php                loads the new helper
files/admin/admin.php              "Install Counter" card
files/lang/admin_tr.php            +9 keys
files/lang/admin_en.php            +9 keys
files/help/help_sync.php           new "Install Counter" section
files/help.php                     table of contents (+1 entry)
files/lang/tr.php                  +12 keys, 1 changed (seo.series.description_fmt)
files/lang/en.php                  same
files/config_example.php           INSTALL_PING documented
files/setup.php, setup_en.php      INSTALL_PING line in the generated config.php
files/schema.sql                   install_id / last_install_ping / install_ping_done in the settings key list
files/robots.php                   install_ping.php in the disallow list
files/version.txt
docker-entrypoint.sh               INSTALL_PING line in the generated config.php
catalog_server/catalog_server_README.md   ping.php section + table SQL
README.md                          "Install counter" section (TR+EN)
```

Language-file parity: 1109 = 1109, admin 312 = 312. No new CSS/JS.

## Deployment note

- **Two steps on the central catalog server:** (1) create the `installs`
  table (SQL in `catalog_server_README.md` and in
  `migration/1.1.43/upgrade.sql`), (2) publish `catalog_server/ping.php`.
  The endpoint uses the database credentials from
  `private/admin_push_config.php` (a user with write access) and creates
  `private/rate_limit/` itself.
- **Order does not matter.** If the application goes first, the ping gets a
  404, logs it and tries again tomorrow (once a day until it succeeds); pages
  are unaffected. If the server goes first, the table waits empty.
- The application-side migration changes no schema; `install_id` is
  generated by PHP on the first ping. The fresh-install replay is unaffected.
- `functions/install_ping_helpers.php` and `functions.php` must go up
  **together**: `functions.php` `require_once`s the new file, and every page
  fatals if it is missing (the "whole functions/ directory" rule since 0.6.7).
- The admin card fetches `INSTALL_PING_URL?stats=1` from the browser; until
  the central endpoint is published the card shows "Could not reach the
  counter server" and nothing else breaks.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must be moved to 1.1.43 and the
  `updates/1.1.43/anime-tracker-1.1.43.zip` package published.
- Your own installs (local XAMPP included) are counted too. Set
  `INSTALL_PING` to `false` on any copy you do not want counted.
