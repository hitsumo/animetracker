# Anime Tracker 0.5.7

**Release date:** 22 May 2026
**Type:** New feature (automation, symmetric completion)

This release ships via auto-update; no manual action required.

## New feature

- **Watch status now updates automatically in the reverse direction
  too:** 0.5.6 introduced automatic status transitions when pressing
  `+` ("İzlenme Planlandı" / Plan to Watch → "İzleniyor" / Watching,
  and reaching the ceiling → "İzlendi" / Watched). 0.5.7 adds the
  symmetric reverse: pressing `−` now pulls the status back when
  appropriate.

  - **Pressing `−` while status is "İzlendi" (Watched)** to drop the
    count below the ceiling automatically reverts the status to
    "İzleniyor" (Watching). So İzlendi + 12/12 → `−` becomes
    İzleniyor + 11/12. This works both in the normal case (you
    finished an anime and rewound a bit) and in the edge case (an
    anime was manually marked Watched while below ceiling, and you
    press `−`).

  - **Pressing `−` while status is "İzleniyor" (Watching)** and the
    count drops to 0 automatically reverts the status to "İzlenme
    Planlandı" (Plan to Watch). So İzleniyor + 1/12 → `−` becomes
    İzlenme Planlandı + 0/12. Meaning: "I've stepped all the way
    back to before I started".

  - **One press, two steps possible:** If you marked an anime as
    "İzlendi" and you press `−` while at the second-to-last
    position (e.g. 1/12), a single `−` performs the full "İzlendi"
    → "İzleniyor" → "İzlenme Planlandı" transition in one go. This
    is the mirror of 0.5.6's "İzlenme Planlandı + 11/12 → `+`
    triggers Planlandı → İzleniyor → İzlendi" chain.

## Notes

- **Both directions now work.** 0.5.6 covered the forward direction
  on `+` (start + reaching ceiling); 0.5.7 covers the reverse on
  `−` (dropping below ceiling + reaching zero).

- **Mid-range steps trigger no transition.** A change like İzleniyor
  + 7/12 → `−` → İzleniyor + 6/12 leaves the status untouched. The
  automation only fires at boundary crossings (ceiling or zero).

- **Manual editing is always free.** Automatic status transitions
  fire only when pressing the list `+` / `−` buttons. You can
  always set the status manually via the "Düzenle" (Edit) form;
  the automation does not interfere with that path.

- **For anime without ceiling info, the "leave İzlendi" rule is
  skipped.** If an anime has no known total or aired episode count
  and was manually set to "İzlendi", pressing `−` keeps the status
  as İzlendi (the system can neither detect a ceiling drop nor make
  a safe transition, so the manual state is preserved). The 0-count
  case still works (İzleniyor → İzlenme Planlandı on absolute zero,
  independent of ceiling).

- **This release is purely a UI / convenience change; the database
  schema is not modified and no extra steps are required.** All
  your existing watch data is preserved as-is.
