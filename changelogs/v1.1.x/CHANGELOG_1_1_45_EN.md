# Anime Tracker 1.1.45

**Release date:** 2026-09-21

Main item: **community emotion distribution on the detail page.** On an
online (multi-user) installation, a line under the emotion buttons shows
the sum of the marks every member put on that anime. Second item:
**"Recently Watched" moved from Statistics to the Recently Updated page**
as a third tab. Alongside them, a small trim: the United States and
France were removed from the production-country list. No schema change;
nothing to do on the central catalog.

## 1. Community emotion distribution

### Why

Emotion marks have been personal since 0.6.1: each member puts at most
three emotions on an anime and sees only their own. On an online
installation what other members put on the same anime was visible
**nowhere**. Yet this was exactly the payoff of this site's deliberate
refusal to score anything: instead of "7.8", "it excited some and bored
others".

### What it does

A line appears right under the emotion buttons on the detail page:

> **3 people marked:** Excited **2** · Thought-provoking **2** · Bored **1**

- **Anonymous.** Only the total per emotion and how many distinct
  members marked; who marked what is never shown.
- **Public.** Guests see it too — the information that stands in for a
  score is as open as a score would be.
- **Order:** most-marked first; ties keep the button order. Two people
  see the same page in the same order.
- **No line when empty.** If nobody has marked anything there is neither
  a line nor a "nobody yet" text; on a small installation most pages are
  empty and the same sentence on every page would be noise.
- **Updates instantly.** Pressing a button brings the new distribution
  back in the server's reply; the line changes without a reload. Drawing
  happens in one place (the server) — the browser only swaps in the ready
  markup and keeps no copy of its own; label translation and colours are
  managed from one spot.
- **No line on a single-user installation on your own computer.** The
  distribution would be your own marks; the buttons above already show
  that. Not even the query runs.

The chips are the read-only emotion badges reserved in 0.6.1 for a
"detail page summary"; this is their first use.

### Cost

Two small queries (`GROUP BY emotion` and `COUNT(DISTINCT user_id)`),
both served by the table's existing `idx_anime` index. No new table,
column or index.

## 2. Help

One paragraph added to the "Emotions" section of the discovery help: what
the line shows, anonymity, guests, the empty case, why a single-user
installation has none.

## 3. "Recently Watched" is now the third tab of Recently Updated

The **Recently Watched** tab of the Statistics page (there since 1.1.1)
moved to the **Recently Updated** page, which now has three tabs:

| Tab | What it lists | Whose |
|---|---|---|
| Episode Updates | The 5 anime whose aired episode count changed most recently | catalog |
| Content Updates | The 5 anime most recently added / edited | catalog |
| **Recently Watched** | The 10 anime whose watch progress you changed most recently | **you** |

Three answers to "what happened lately" on one page; Statistics went
back to what its name says — numbers (two tabs: personal summary, global
distribution).

- Same card layout, same "just now / 3 hours ago" time label; still ten
  rows, only anime with progress (a 0/12 "planned" row is not a watch
  event).
- The personal tab is the **only** one on the page ordered by
  `user_anime`; on the other two, marking an episode moves nothing — that
  rule stands.
- On an online installation a visitor who is not signed in sees "this
  tab is personal, sign in" instead of a list.
- The "Recently Updated tab" preference in List Settings gained the third
  option; the default is still episodes.
- Side fix: the cards on this page now follow your **title language
  preference** (the Statistics table had since 1.1.18; this page had
  not).

Help: the "Recently Updated" section of the list help describes three
tabs and its "not to be confused" box was rewritten around the catalog
tabs / personal tab split; the moved tab's paragraph was removed from the
Statistics help.

## 4. Production country: United States and France removed

The "Production Country" list on the add / edit form now has four
options: China, Japan, South Korea, Taiwan. In a catalog of 7,800+ rows
not a single one carried a US or FR code; the two options only made the
list longer.

Existing data is untouched and nothing is lost: a row that arrives with
an unlisted code (AniList's country of origin, catalog import) keeps the
code in its column; the country line on its page renders empty, and the
country filter — built from the data — simply does not offer it. Adding
one back is one line plus two language keys.

## Files

**New:**

```
files/migration/1.1.45/upgrade.sql   stamp only (no schema change)
```

**Changed:**

```
files/anime_details.php                  distribution line + swap in the toggle script
files/update_emotion.php                 distribution_html in the reply; lang_init (the reply now carries translated text)
files/functions/emotion_helpers.php      emotion_distribution() / emotion_distribution_html()
files/css/emotion.css                    .emotion-dist (hidden when empty)
files/recent.php                         third tab (watched), per-tab text map, display_title
files/statistics.php                     Recently Watched tab / query / CSS removed
files/list_settings.php                  third option in the default-tab setting
files/functions/user_anime_helpers.php   recent_tabs() += 'watched'
files/help/help_discovery.php            emotion paragraph added; Recently Watched removed from the statistics help
files/functions/country_helpers.php      US / FR removed
files/lang/tr.php                        +7 keys, −7 (country.us/fr, statistics.tab.recent_watched, statistics.col.last_watched, statistics.recent_watched.empty, help.stats.recent.h3/.text); 4 changed
files/lang/en.php                        same
files/version.txt
```

Language file parity: 1118 = 1118.

## Deployment note

- **Nothing to do on the central catalog.** Emotion data is local to the
  installation.
- The migration is a stamp only; the version moves to 1.1.45 on the first
  page load.
- `functions/emotion_helpers.php` and `functions/user_anime_helpers.php`
  must ship **together** (whole functions/ folder rule):
  `anime_details.php` / `update_emotion.php` call the new helpers, and
  `recent.php` expects the three values of `recent_tabs()`; with the old
  helper `?tab=watched` silently falls back to the default tab.
- Existing `recent_default_tab` preferences stay valid (episodes /
  content); `watched` is written only for those who choose it.
- The CSS version stamp comes from `version.txt`; the `emotion.css`
  change arrives under a new URL, no cache issue.
- **On the distribution server**, the usual two steps: the published
  `version.txt` must be moved to 1.1.45 and the
  `updates/1.1.45/anime-tracker-1.1.45.zip` package published.
