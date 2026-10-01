# Anime Tracker 0.5.4

**Release date:** May 18, 2026
**Type:** Hotfix

This release is delivered via automatic update; no action is required
on your part.

## Fix

- **Chronology marker protection now actually works:** In 0.5.3 we
  said "the chronology markers you add yourself will no longer be
  deleted on import". However, the 0.5.3 package shipped with the old
  version of the relevant file, so this protection never actually
  took effect — "Import from Catalog" was still deleting all markers.
  0.5.4 fixes this packaging error; the chronology markers you add
  yourself are now genuinely preserved.

## Notes

If you are updating from 0.5.3, no extra database step is needed; the
required field was already added in 0.5.3. After updating, running an
Import from Catalog once is recommended so markers are labeled
correctly. The markers you added yourself are not affected by this
step.
