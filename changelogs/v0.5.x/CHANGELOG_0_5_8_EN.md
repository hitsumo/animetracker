# Anime Tracker 0.5.8

**Release date:** 23 May 2026
**Type:** Improvement (help documentation + UI polish)

This release ships via auto-update; you do not need to do anything.

## New

- **The help page now has a "Quick Watch Buttons (+/-)" section.**
  The automatic watch status transitions introduced in 0.5.6 and
  0.5.7 are now properly documented in the help page: which
  automatic transition fires in which situation, when it does not
  fire, and how it behaves for anime with unknown episode counts.
  All four rules are explained with a table and examples;
  single-click two-step transitions (Planned → Watching → Watched,
  and its mirror) are also highlighted.

## Improvements

- **The main page title is now more compact.** The "Anime İzleme
  Listesi" title used to loom over the list; it is now smaller
  and better balanced with the search box. Other pages (Edit, Add,
  List Settings, What Should I Watch?, etc.) keep their original
  larger title — only the main page changed.

- **The search box width has been reduced.** The main page's
  search box is now better proportioned with the title.

- **The help page info boxes now have proper color.** The
  information boxes in the Time Zone section now appear in light
  blue, matching the visual style of the warning (yellow), safe
  (green), and danger (red) boxes.

## Fixes

- Two small typos fixed in the help page:
  - Chronology section: "boleumden" → "bolumden"
  - Deletion Warnings section: "size özel yukledilmiş poster" →
    "kendi yüklediğiniz poster"

## Notes

- **No database schema changes.** Your watch progress, notes,
  and posters are preserved as-is.

- **Interface and documentation only.** The anime list, the
  behavior of the `+` / `−` buttons, sync logic, and auto-update
  all stay the same. The existing behavior is now simply better
  documented in the help page.
