# Anime Tracker 0.6.6 - Changelog

**Release date:** May 2026 (patch on top of 0.6.5)

## New: Emotion distribution on the Statistics page

The Statistics page now has a **"By Emotion"** card showing which
emotions you have marked most, ranked high to low. The emotion marks
introduced in 0.6.1 (`user_anime_emotion`) are now summarized on the
statistics side too.

- A full-width card below the existing media / broadcast / watch cards
- Each emotion shown in its own badge color with how many times you
  marked it
- A summary line on top: total marks + how many distinct anime
- If you have not marked any anime with emotions yet, the table is
  replaced by an info note pointing you to the detail page
- Only emotions you have marked are listed; unmarked ones are hidden
  (the full emotion set is already visible on the detail and
  recommendations pages)

## Improvement: Locked emotion buttons are more legible

After you mark 3 emotions on an anime, the remaining buttons become
disabled (cap reached). Their dimming was changed from 0.45 to 0.70 -
the label text is now easier to read. Marked (active) buttons are shown
filled, so the active / locked distinction stays clear.

## Other

### Migration

No schema changes in this release. `migration/0.6.6/upgrade.sql` is an
empty placeholder, only there to bump the version number.

### i18n

4 new keys added to `tr.php` + `en.php` (for the statistics emotion
card):
- `statistics.section.by_emotion`
- `statistics.col.emotion`
- `statistics.emotion.summary`
- `statistics.emotion.empty`

Dictionary size: 461 -> 465 keys (TR/EN parallel).

### Single-file change

Only `statistics.php` (plus the two dictionaries and one CSS line)
changed. Helpers, the table schema and the emotion badge colors have
been in place since 0.6.1 - no new groundwork was added.
