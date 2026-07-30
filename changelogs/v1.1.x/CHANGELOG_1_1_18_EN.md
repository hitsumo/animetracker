# Anime Tracker 1.1.18

**Release date:** 2026-07-19

## New: poster art in the "Recently Watched" table

- **The "Recently Watched" table on the statistics page now shows each anime's
  poster.** Previously the column held nothing but the name as text.
- **Each anime now spans two rows:** the poster together with watch status,
  episodes watched and last-watched time on the upper row, and the anime's name
  on the row beneath it.
- **The name uses the full width of the table.** Squeezed into the narrow Anime
  column, long titles wrapped over five or six lines; at full width they come
  down to one or two.
- **Clicking either the poster or the name opens the anime's detail page.**
- **Anime without a poster no longer risk a broken image.** When no poster has
  been set, a placeholder in your interface language is shown instead; as soon
  as you add a real poster it takes over on its own.
- The poster box is 80x120 pixels - exactly poster ratio (2:3) - so images are
  shown without being squashed or distorted.

## Fix: Title Language was ignored in the "Recently Watched" table

- **Your "Title Language" preference had no effect on this table.** Even with
  English titles turned on, "Recently Watched" kept showing the original title,
  while the main list and the detail page honoured the preference correctly.
  The table now follows your setting.
- The other tables on the statistics page (media type, broadcast status, watch
  status, emotion distribution) are counter tables and show no anime names, so
  the fix concerns this one table only.

## Cleanup: two unused leftovers from 1.1.15 removed

- The `.marker-episode-story` CSS rule and the
  `anime_details.marker.story_after_episode` language key (Turkish and English)
  were left over from the purple "Story: 35" badge that 1.1.15 removed. Neither
  had a single remaining use anywhere in the project.
- Nothing changes visually; this is dead-code cleanup only. The Turkish and
  English key counts stayed level (the same key was dropped from both).

## How it works (technical)

- The "Recently Watched" query now also selects `a.image_path` and
  `a.title_english`. Both columns already existed on the `animes` table and
  were simply not selected here - which is why the poster could not be shown
  and the title language could not be applied.
- The poster source comes from the existing `poster_src()` helper (1.1.9) and
  the title from the existing `display_title()` helper. No new helper was
  written.
- The style rules were added to the `<style>` block inside `statistics.php`,
  matching that page's existing pattern; no separate CSS file was introduced.

## Schema / migration

- `migration/1.1.18/upgrade.sql` only moves the version to 1.1.18; there is
  **no schema change** (no SQL statement to run). The central catalog is
  unaffected and **no** manual step is needed on the server.

## Changed / new files

- files/statistics.php (image_path + title_english in the query; poster and
  name in the Anime cell; poster rules in the style block)
- files/css/series.css (unused `.marker-episode-story` rule removed)
- files/lang/tr.php, files/lang/en.php (unused
  `anime_details.marker.story_after_episode` key removed)
- files/migration/1.1.18/upgrade.sql (new)
- files/version.txt
