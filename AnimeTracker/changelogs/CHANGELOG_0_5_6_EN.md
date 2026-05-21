# Anime Tracker 0.5.6

**Release date:** 21 May 2026
**Type:** New feature (automation)

This release ships via auto-update; no manual action required.

## New feature

- **Watch status updates automatically:** Pressing the `+` / `−`
  buttons in the main list now also updates the watch status. You
  no longer need to open the "Edit" form to change status manually.

  - **Pressing `+` while status is "İzlenme Planlandı" (Plan to
    Watch)** automatically switches the status to "İzleniyor"
    (Watching). This rule applies both when you first start an
    anime (first `+` from 0/12) and when you previously paused an
    anime by setting it back to "Plan to Watch" via the Edit form
    (e.g. you stopped at 5/12 months ago and now press `+` — status
    flips back to "Watching").

  - **When you reach the total (or aired) episode count**, the
    status automatically switches to "İzlendi" (Watched). You see
    this on the final `+` press (e.g. 11/12 → `+` → 12/12, and at
    the same moment "Watching" → "Watched").

  - **One press, two steps possible:** If an anime was left as
    "Plan to Watch" and you happen to be at the second-to-last
    episode (e.g. 11/12), a single `+` press performs the full
    "Plan to Watch" → "Watched" transition in one go.

## Notes

- **This release implements forward-only automation.** When you
  press `−` to decrease the watched count, the status stays as
  "Watched"; it does not automatically revert to "Watching". The
  reverse direction will be added in the next release (0.5.7).

- The automatic transition triggers only on anime with a known
  total or aired episode count. For anime without episode data,
  `+` is unavailable in the first place, so the status does not
  change automatically either.

- This release is purely a UI / convenience change; the database
  schema is not modified and no extra steps are required. All your
  existing watch data is preserved as-is.
