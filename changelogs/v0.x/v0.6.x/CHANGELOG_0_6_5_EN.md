# Anime Tracker 0.6.5 - Changelog

**Release date:** May 2026 (patch on top of 0.6.4.2)

## New: Emotion filter on the Recommendations page

The "What Should I Watch?" page now has an **emotion filter** next to
the existing sentence (tag) filter. The emotion marks introduced in
0.6.1 (`user_anime_emotion` table) are finally consumed on the search
side too.

### Logic - OR (bucket system)

Sentences and emotions act as parallel buckets, symmetric to the
existing tag pattern:

- Pick "Set in school" + "Made me laugh": you get anime tagged with
  this sentence **or** anime you have marked with this emotion
- Score = matched sentence count + matched emotion count
- Higher-score anime float to the top
- Not AND but OR - the result is never empty

### UI

- A new **Show Emotions** button appears below the sentence panel
- Opens into a 9-emotion badge layout
  (colors come from the `emotion-badge-*` classes added in 0.6.1)
- If you have not marked any anime with emotions yet, the panel is
  replaced by an info note that points you to the detail page to add
  marks
- Result cards show matched emotion badges below the existing tag
  pills

### Closing a backlog item

The "filter/recommendations integration" item that was listed as
"open after 0.6.1" in KARARLAR_GECMIS is closed 8 months later. The
spec had said "0.6.3 or later" - it landed in 0.6.5.

## Architectural notes

- The DB-side `idx_emotion` index was added in 0.6.1 with the
  comment "placeholder for filter queries" in schema.sql - 0.6.5
  finally uses this groundwork
- Tag SQL was not touched. Emotion query runs as a separate pass and
  is merged in PHP. No Cartesian-product COUNT inflation risk (we
  never cross-JOIN the two tables)
- Existing tag-only UX is preserved. Old tag keys stay (count,
  no_match, group.matched). When emotion is selected, the
  `_combined` variants kick in
- Single-file change (recommendations.php). schema.sql, helpers, CSS
  classes - none of these changed; everything was already in place
  from 0.6.1

## Other

### Migration

No schema changes in this release. `migration/0.6.5/upgrade.sql`
is an empty placeholder, only there to bump `settings.version`
(KARARLAR Bolum 2 "empty migration rule" - a discipline learned the
hard way from the 0.5.5 near miss and the 0.6.4.1 skip fiasco).

### i18n

8 new keys added to `tr.php` + `en.php` for the recommendations
emotion block:
- `recommendations.emotion.toggle.show` / `hide` / `count_selected`
- `recommendations.emotion.empty_marks`
- `recommendations.matched.emotion_prefix`
- `recommendations.no_match_combined`
- `recommendations.result.count_combined`
- `recommendations.group.matched_combined`

Dictionary size: 453 -> 461 keys (TR/EN parallel).

## Known open items (deferred to 0.6.6 or later)

Other items remaining from 0.6.1 are still open:

- **Statistics.php emotion counts**: "which emotion did I mark
  most?" statistic is missing
- **Index.php quick-tap emotion**: KARARLAR still lists this as an
  "open design question" - no decision yet
- **Passive button opacity 0.45 vs 0.55**: UX decision pending
- **Set expansion**: post-usage decision (candidates: Relaxing /
  Moving / Inspiring / Addictive)
