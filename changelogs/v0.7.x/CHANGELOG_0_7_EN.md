# Anime Tracker 0.7 - Changelog

**Release date:** May 2026

## New: Per-episode filler / canon tracking

You can now mark, episode by episode, which episodes of an anime are
**filler** and which are **canon**. Any episode you leave unmarked is
treated as canon - so you only need to mark the exceptions, and most
episodes need no attention at all.

### Turning it on / off

The add and edit anime forms now have a **"Filler episode tracking"**
option. It is off by default; when you turn it on for an anime, a filler
summary appears on that anime's detail page. Turning it off does **not**
delete your marks - it only hides them, and they come back when you turn
it on again.

### Grid editor

The **"Edit"** button on the detail page opens the episode grid. There is
one cell per episode; each click cycles its type:

- Unmarked (neutral) -> Manga Canon -> Anime Canon -> Mixed -> Filler -> unmarked again

Colors follow a traffic-light scheme: canon types green, Mixed (partial
filler) amber, Filler red, unmarked neutral. Once you have made your
marks, a single **"Save"** button stores them all together.

### Summary on the detail page

For anime with filler tracking on, the detail page shows a compact COUNT
summary under an "Episode details" label, for example:

> 635 Manga Canon, 1 Anime Canon, 567 Filler

Only how many episodes of each type are shown - a per-episode range list
would be far too long for long-running series. If nothing has been marked
yet, a short info note is shown instead. The "Edit" button next to it
(always green) opens the grid editor.

### Episode count required

The grid needs the anime's total or aired episode count to be set. If
both are empty, the editor asks you to enter an episode count first.

## New: One-click import from AnimeFillerList

In the grid editor, paste an **AnimeFillerList URL** and click "Import" to
automatically load that show's entire filler/canon classification into the
grid. The animefillerlist.com categories map directly to our types: Manga
Canon, Anime Canon, Mixed Canon/Filler (Mixed) and Filler.

- Example URL: `https://www.animefillerlist.com/shows/detective-conan`
- Import fills the grid but does NOT save - you review and click "Save"
  yourself; you stay in control
- Episodes beyond the anime's episode count are skipped, and the number
  skipped is reported (raise the episode count first if needed)
- The page is fetched server-side (no browser cross-origin limits); only
  the episode-to-type mapping is taken, not episode titles

## Other

### Migration

This release includes a real schema change:

- New table: `filler_episodes` (marked episodes per anime)
- New column: `animes.filler_tracking` (visibility flag)

`migration/0.7/upgrade.sql` applies these. It runs automatically during
auto-update; no manual steps are needed.

### i18n

25 new keys added to `tr.php` + `en.php` (filler editor + detail summary
+ form toggle + AnimeFillerList import). Dictionary size: 465 -> 490 keys
(TR/EN parallel).

### Files

New: `functions/filler_helpers.php`, `update_filler.php`, `filler_edit.php`,
`fetch_filler.php`, `css/filler.css`. Changed: `add_anime.php`,
`edit_anime.php`, `anime_details.php`, `schema.sql`, `functions.php`,
`style.css`, the two dictionary files.
