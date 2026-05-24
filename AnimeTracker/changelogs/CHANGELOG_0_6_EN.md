# Anime Tracker 0.6

**Release date:** 24 May 2026
**Type:** Feature (new watch status + infrastructure cleanup)

This release ships via auto-update; you do not need to do anything.
During the update the database is migrated once (your existing
watch statuses are preserved and carried over as-is).

## New

- **New watch status: "On Hold" (Izleme Ertelendi).** You started
  watching an anime, you want to take a break, but flipping it
  to "Planned" feels wrong because that would reset your progress.
  "On Hold" is built for exactly this situation. Your watched
  episode count is preserved, the anime does not clutter your
  active "Watching" list, and the moment you hit the `+` button
  the system automatically moves it back to "Watching".

- **Fifth automatic rule.** Previously there were four automatic
  transition rules (Planned ↔ Watching ↔ Watched). 0.6 adds the
  fifth: **"On Hold + `+` → Watching"** (the resume signal).
  Produces the same outcome as Rule 1 (start watching) but from
  a different starting point (resuming after a manual pause).
  The single-click two-step chain works with this rule too:
  On Hold + 11/12 → `+` → Watched.

- **New "Watch Statuses" section in the help page.** Explains
  when to use each of the four statuses (Planned, Watching,
  Watched, On Hold), how they differ from one another, in plain
  language. The question "When should I use On Hold?" is
  answered directly. The automatic transitions table was also
  updated (4 rows → 5 rows).

## Improvements

- **The statistics page always shows four rows.** Previously the
  page used "ORDER BY count" on existing statuses — which meant
  statuses you had not assigned to any anime never appeared.
  Now the order is fixed (Watched → Watching → Planned → On Hold)
  and all four rows show, even with a count of zero.

- **Watch status badges have consistent colors across all pages.**
  In earlier releases the watch status badges could appear without
  color on some pages (the page-local CSS class was missing).
  All five pages now share the same color palette: green
  (Watched), blue (Watching), grey (Planned), amber (On Hold).

## Infrastructure

- **Database schema migrated to ASCII.** The watch_status column's
  internal values are now ASCII (Watched / Watching / PlanToWatch /
  OnHold). The user-facing text stays Turkish — this change only
  affects the database, not the interface. The old watch status
  CSS classes that contained Turkish characters have been
  cleaned up.

- **Single source of truth.** Three helper function families for
  watch status (`watch_status_label`, `watch_status_options`,
  `watch_status_css_class`) are now defined centrally in
  `functions.php`. The previous page-local copies were rewired
  to call these helpers. Adding a new watch status or an English
  interface language in the future is now a one-file change.

## Known Behaviors

- **"On Hold + `−`" does not change status.** Pressing `−` on an
  on-hold anime decrements the watched count by 1 but keeps the
  status as "On Hold". If you wanted to go all the way down to
  zero and flip to "Planned", do it manually from the Edit
  screen. We did not add an automatic transition because this
  is a rare scenario.

- **"On Hold + 0/X" looks odd.** If you set an anime to "On Hold"
  via the Edit screen but leave its watched count at 0, the
  system will not object — but semantically it is strange (an
  anime you have not started watching at all being "on hold" is
  the same as "planned"). Generally "On Hold" is meaningful
  when watched > 0.

- **Existing anime are not automatically moved to "On Hold".**
  The new status is only applied to anime you manually flag
  (or that you `+` your way into from On Hold). All your
  existing records keep their current status after the update.

## Technical Notes

- The DB migration is 3-step (`migration/0.6/upgrade.sql`): the
  enum is first widened (TR + ASCII mixed), existing TR values
  are then UPDATEd to their ASCII counterparts, and finally the
  enum is narrowed to ASCII only. Idempotent — if auto-update
  is interrupted halfway through, it can be re-run safely.

- If you have third-party scripts that write to the database
  directly using the old TR enum values (`İzlendi`, `İzleniyor`,
  `İzlenme Planlandı`), they will need to use the ASCII values
  after 0.6. Anime Tracker's own code was updated automatically.
