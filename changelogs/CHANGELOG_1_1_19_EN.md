# Anime Tracker 1.1.19

**Release date:** 2026-07-22

## New: clickable anime links inside a synopsis

- **A synopsis can now link to another anime as clickable text.** For example,
  a film's synopsis can read "the movie recap of X" and turn the "X" into a link
  that opens that anime's detail page.
- **The syntax is a shortcode:**

  ```
  The movie recap of [[anime:52991|Frieren]].
  ```

  Here `52991` is the target anime's **MyAnimeList (MAL) id**, and `Frieren` is
  the link text shown on screen.
- **Leave the text out and the target's own title is used automatically:**
  `[[anime:52991]]` labels the link with the anime's name.
- **Works in both the catalog synopsis and your personal note.**
- The link is shown in the site's normal link colour with an underline, kept
  subtle so it reads as part of the prose rather than a button.

## Why a shortcode and not raw HTML

- **Typing `<a href="...">` directly into a synopsis is not possible, by
  design.** Synopsis text travels over the catalog to every member; allowing raw
  HTML would open stored XSS and let arbitrary external URLs ride along. The
  shortcode carries only a MAL id, everything else stays escaped, and the link
  target is **always the site's own anime detail page**.
- **Why the MAL id and not the local id?** Catalog text is served identically to
  every instance, but each instance assigns its own local anime ids. A raw local
  id would point at the wrong anime elsewhere after a sync. The MAL id is global,
  so the link resolves to the right anime on every instance.
- **If that anime isn't in this catalog the link doesn't break:** your text is
  kept as plain prose and the sentence still reads.

## How it works (technical)

- A new helper file was added: `files/functions/synopsis_helpers.php`.
  `render_synopsis()` renders the synopsis as safe HTML and turns `[[anime:..]]`
  shortcodes into local `anime_details.php` links; `synopsis_plain()` reduces the
  shortcode to its plain label for preview/truncation surfaces.
- The whole string is `htmlspecialchars`-escaped first, and the shortcode is
  turned into a link afterwards, so the previous `nl2br(htmlspecialchars(...))`
  behaviour is preserved exactly for plain synopses.
- MAL ids in the text are resolved to local rows in a single batched query
  (`WHERE mal_id IN (...)`), not one query per link.
- The 200-character synopsis teaser on the surprise recommendation card now goes
  through `synopsis_plain()`, so the teaser never shows raw `[[...]]` and
  truncation never slices a shortcode in half.

## Schema / migration

- `migration/1.1.19/upgrade.sql` only moves the version to 1.1.19; there is **no
  schema change** (no SQL statements to run). The feature lives entirely in the
  render layer. The central catalog is unaffected and no manual step is needed on
  the server.

## Changed / new files

- files/functions/synopsis_helpers.php (new; `render_synopsis` + `synopsis_plain`)
- files/functions.php (synopsis_helpers added to the loader)
- files/anime_details.php (catalog and personal synopsis now rendered via `render_synopsis()`)
- files/recommendations.php (surprise teaser runs through `synopsis_plain()` before truncation)
- files/css/base.css (`.synopsis-link` style)
- files/migration/1.1.19/upgrade.sql (new)
- files/version.txt
