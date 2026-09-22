# Anime Tracker 1.1.46

**Release date:** 2026-09-21

One item: the **watch log.** The application now records every change
of your watched episode count with its time, and the **Recently
Watched** tab on the Recently Updated page gained four views built on
it: last week, last month, all, date range. There is a schema change (a
new, install-local table); nothing to do on the central catalog.

## 1. Why

"What did I watch last week?" had no honest answer. The personal record
carried a single timestamp — "last touch of the row". A note edit, a
status change and an episode increment all wrote the same stamp; a list
imported from MyAnimeList or AniList stamped every record with the
import day. The record knew only "where am I now", never "how did I get
here". The Recently Watched tab from 1.1.45 sorted by that stamp and
could not offer a weekly or monthly view.

## 2. The watch log

A new table (`user_watch_log`) keeps one row per **change** of the
watched episode count: from what, to what, when.

- **One writer.** Every path that writes the episode count already goes
  through the single helper that writes the personal record; the log
  was added to that helper. The +/− buttons on the list and the detail
  page, the edit form, adding an anime, the MAL / AniList / JSON
  imports — all of them land in the log by themselves; none needed code
  of its own.
- **An import is one row.** An import leaves "0 → 24, at the import
  moment" per anime. That is what happened: the day 24 episodes entered
  the count is known, the days they were watched are not, and nothing
  is invented.
- **A "−" is logged too.** Take an episode back and the row reads
  "6 → 5". The period views sum per anime: an episode you added and
  then took back nets to zero and is not counted.
- **No backfill.** On upgrade, existing records get no invented dates.
  The 1.1.44 rule stands: a stamp that was never taken is not made up.
  Your earlier watching stays in the "All" view below, labelled.
- **Install-local.** The log never goes to the central catalog. It does
  go into your backup (see §4).

## 3. Recently Watched: the period strip

A strip appeared under the tab: **Last week · Last month · All** and a
two-date **range** form.

| View | Source | Shows |
|---|---|---|
| Last week | log | anime watched in the last 7 days |
| Last month | log | anime watched in the last 30 days |
| Range | log | anime watched between two dates (both inclusive) |
| All | log + older records | every anime with progress, newest movement first, 10 rows |

- In the week, month and range views each card carries a **net
  episodes** badge (+3 episodes) for the period and the list is headed
  by an **"N anime, M episodes watched"** summary. Only anime with a
  positive net are listed.
- Week and month are **rolling** windows (7 / 30 days back from now) —
  that is what "last week" means in speech; a calendar week would show
  one anime on a Monday morning.
- **All** continues the 1.1.45 list: an anime with log rows is placed
  by its latest log entry, an older anime without any is placed by its
  last-touch time as before and wears a **"pre-log"** label, so the two
  kinds do not blur.
- An empty period says its own sentence ("nothing logged in this
  period"), not the tab's "no watch activity yet".
- The range form needs both dates; reversed dates are swapped, a
  malformed or missing date falls back to "All" — no error page.
- The chosen period lives in the **address** and is not saved. The
  default-tab preference in List Settings stays as it is.
- On an online installation a visitor who is not signed in sees the
  "this tab is personal, sign in" note in all four views.

## 4. Backup

The list export puts a `watch_log` array under each anime (from, to,
when). Restore:

- When the file has the array (a 1.1.46+ backup) the rows come back
  **as they are**; the same row is never written twice, so loading the
  same file twice, or loading over a history that is already half
  there, does not duplicate. The restore jump itself is not logged.
- When the file has no array (an older backup) the restore jump is
  logged as one row — the same rule as a MAL import.

## 5. Help

- The "Recently Updated" section of the list help gained a "Watch log
  and period filters" heading: what the log records, the four views,
  net counting, no backfill, the pre-log label, the backup.
- The export/import help's "what is in the backup" list gained the log.

## 6. Side fix: "Recently Watched" as the default tab

1.1.45 added "Recently Watched" to the "Recently Updated tab" option in
List Settings, but the endpoint that saves it knew only the first two
values; choosing the third silently saved "Episode Updates". The
endpoint now validates against the tab list itself; the choice sticks.

## Files

**New:**

```
files/functions/watch_log_helpers.php    log write / export / import / period bounds / summary
files/migration/1.1.46/upgrade.sql       CREATE TABLE user_watch_log
```

**Changed:**

```
files/functions/user_anime_helpers.php   ua_set_state: log row when the count changes; $logWatch flag
files/functions.php                      loads the new helper file
files/schema.sql                         user_watch_log table (fresh install)
files/recent.php                         period strip, two new queries, summary, two badges
files/list_settings.php                  watch_log in export; restore writes it back and suppresses the auto row (both branches)
files/set_recent_tab_pref.php            accepts 'watched' (recent_tabs() whitelist)
files/help/help_list.php                 log heading + paragraph
files/lang/tr.php                        +16 keys (recent.period.*, help.list.recent.log.*); 1 changed (tab hint); backup list item
files/lang/en.php                        same
files/version.txt
```

Language file parity: 1134 = 1134.

## Deployment note

- **Nothing to do on the central catalog.** The table is install-local;
  the central server does not know it.
- The migration creates the table on the first page load (`CREATE TABLE
  IF NOT EXISTS`, re-runnable). After the upgrade the log is empty; it
  starts filling with the first episode mark. Until then the week /
  month views show their empty sentence — by design.
- The `functions/` folder must ship **whole**: `functions.php` loads
  the new `watch_log_helpers.php` and `user_anime_helpers.php` calls
  its function; with either missing, every episode mark is a fatal
  error.
- **On the distribution server** the usual two steps: pull the published
  `version.txt` to 1.1.46 and publish the
  `updates/1.1.46/anime-tracker-1.1.46.zip` package.
