# Anime Tracker 1.1.44

**Release date:** 2026-09-19

One piece of work: **the "Recently Updated" page is split into two tabs** —
*Episode Updates* and *Content Updates*. Which one it opens in is chosen in
List Settings; the default is **episodes**. There is one schema change
(`animes.episodes_updated_at`), local to the installation; nothing to do on
the central catalog.

## 1. Two tabs

### Why

The page listed "the five anime most recently added to or edited in the
catalog" by `animes.updated_at`. But `updated_at` is MySQL's automatic
stamp: **every** write that touches the row refreshes it. When the daily
broadcast sync counted one more episode, the anime jumped to the top of
the list — not because its content changed, but because one more episode
had aired. Two different questions were fighting over one list:

- **"What changed in the catalog?"** — a new entry, a corrected synopsis,
  an added date.
- **"Which show got a new episode?"** — the question people ask daily.

Mid-season the second buried the first completely: the page showed the
five series the sync had counted that day, and an entry edited a week
earlier was nowhere to be seen.

### What it does

| Tab | Lists | Timestamp |
|---|---|---|
| **Episode Updates** | The five anime whose aired episode count changed most recently | `episodes_updated_at` (new) |
| **Content Updates** | The five anime most recently added to, or edited in, the catalog | `updated_at` |

On the episode tab every card leads with the number that changed:
**"Latest episode: 12"**. The tabs are plain links
(`recent.php?tab=episodes` / `?tab=content`); the choice in the address
applies to that view only and writes nothing persistent.

**Who writes the episode count:** the daily broadcast sync
(AnimeSchedule), the edit form and a catalog import. All three stamp the
new column **only when the number really changes**; a sync that brings
back the same number does not move the list.

**The content tab is now really about content:** episode-only writers
(the broadcast sync, the next-episode date computed on page load, the
episode step of a catalog import, the promote / demote flags for the
catalog) no longer touch `updated_at`. The edit form is a content edit
and stamps it as before; if you change only the episode count in the
form, the anime shows up on both tabs — which is correct.

### Default tab setting

List Settings → General Settings → **"Recently Updated Tab"**: episodes /
content. A personal preference that affects only you; default
**episodes**. The tabs on the page switch temporarily without overriding
it. Same pattern as the series chronology view setting (1.1.23).

### The first day after upgrading

The new column is empty on existing rows; the episode tab fills after the
first broadcast sync (once a day, on opening the main page). Until then the
tab says "No episode update recorded yet". Seeding was left out **on
purpose**: copying `updated_at` would have assumed "the last touch was an
episode increment" — and not knowing that was the whole problem.

### Side effect: sitemap `lastmod`

Since `updated_at` is now content time only, the sitemap's `lastmod` takes
the later of the two stamps, so a detail page whose episode count changed
still looks "changed" to search engines. The IndexNow announcement keeps
covering episode changes too.

## 2. Help

The "Recently Updated" section of the list-page help was rewritten to
describe the two tabs, who writes to each, and the setting; the "not to be
confused with Recently Watched" box stays as it was.

## Files

**New:**

```
files/migration/1.1.44/upgrade.sql   animes.episodes_updated_at (ADD COLUMN, re-runnable)
files/set_recent_tab_pref.php        default-tab endpoint (POST + CSRF)
```

**Changed:**

```
files/recent.php                          two tabs, per-tab query, "Latest episode" badge, empty-state text
files/list_settings.php                   "Recently Updated Tab" setting
files/functions/user_anime_helpers.php    recent_tabs() / recent_default_tab()
files/functions/animeschedule_helpers.php airedEpisodesUpdateSql(): all five episode writes from one SQL pattern
files/functions/anime_helpers.php         next-episode date writes leave updated_at alone
files/functions/seo_helpers.php           sitemap lastmod = later of the two stamps
files/edit_anime.php                      episodes_updated_at when the episode count changes
files/catalog_import.php                  episode count in its own step (own stamp), content UPDATE pure content
files/admin/admin_pending.php             promote / demote leave updated_at alone
files/robots.php                          set_recent_tab_pref.php on the disallow list
files/schema.sql                          episodes_updated_at column (fresh install)
files/lang/tr.php                         +9 keys, 1 changed (help text)
files/lang/en.php                         same
files/version.txt
```

Language file parity: 1118 = 1118. No new CSS/JS (the tab style lives in
the page).

## Deployment note

- **Nothing to do on the central catalog.** The new column is local to the
  installation; the push and catalog endpoints list their columns
  explicitly, so it never enters the wire.
- The migration runs on the first page load; a single
  `ALTER TABLE ... ADD COLUMN`, ignored if the column already exists.
  Tested as the 1.1.26 → 1.1.44 chain on an 8,113-row copy and as
  `schema.sql` + the full chain on an empty database; both clean.
- `functions/animeschedule_helpers.php`, `anime_helpers.php`,
  `user_anime_helpers.php` and `seo_helpers.php` must ship **together**
  (the whole-functions-folder rule): `recent.php` and `list_settings.php`
  call the new helpers.
- The episode tab is empty until the first sync; that is expected.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must be bumped to 1.1.44 and the
  `updates/1.1.44/anime-tracker-1.1.44.zip` package published.
