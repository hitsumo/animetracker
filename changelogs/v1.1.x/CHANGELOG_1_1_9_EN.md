# Anime Tracker 1.1.9

**Release date:** 2026-07-13

## Improvement

- **Language-aware "no image" placeholder for posterless animes.** Animes with
  no poster used to render a broken image on the list and detail pages; they now
  show a "no image" placeholder that matches the interface language. When you
  switch the interface language the placeholder switches too: "BURADA RESIM YOK"
  in Turkish, "IMAGE NOT HERE" in English.
  - As soon as you add a real poster to an anime, the placeholder disappears on
    its own (the real poster is shown).
  - Every place a poster is shown is covered: list, anime detail, recently
    added, recommendations, series timeline, and pending.

## How it works (technical)

- The placeholder is NOT written to the database; while image_path is empty it
  is chosen at display time by language (new `poster_src()` helper). So there
  are NO per-anime file copies and NO database writes - just two static images.
- Dual-mode: on a catalog client, a posterless anime shows the placeholder in
  ITS OWN language (the placeholder is never sent over the catalog wire).

## Setup note

- Place the two images under `img/` with these names:
  - `img/no_poster_tr.png` (Turkish - "BURADA RESIM YOK")
  - `img/no_poster_en.png` (English - "IMAGE NOT HERE")

## Fix

- **Poster proportions on the detail page.** The cover area on the anime detail
  page stretched portrait posters into a landscape box, making them look
  squashed (an invalid `object-fit` value). Posters now display at their correct
  aspect ratio (the cover box was made portrait: 400x600). This applies to the
  new "no image" placeholder too - it also shows at the correct ratio.

## Notes

- No schema change in this release (code plus two images only).

## Maintenance / housekeeping

- One-time CLI tools were gathered under `files/tek_kullanimlik/`
  (anilist_isadult_backfill.php was moved there) so they do not mix with the
  main files.

## Changed files

- functions/anime_helpers.php (new poster_src() helper)
- index.php, anime_details.php, recent.php, recommendations.php,
  series_timeline.php, pending.php (poster renders via poster_src)
- css/base.css (.anime-cover object-fit fix + portrait box)
- img/no_poster_tr.png, img/no_poster_en.png (new images)
- tek_kullanimlik/anilist_isadult_backfill.php (moved here)
- version.txt
- migration/1.1.9/upgrade.sql (new, no-op)
