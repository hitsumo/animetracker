# Anime Tracker 1.1.5

**Release date:** 2026-07-11

## New

- **Adding and editing now stay on the same page after saving.** Previously the
  add and edit anime forms returned to the list page (index) after "Add" /
  "Update". Now:
  - **Editing:** after "Update" you stay on the same edit page; a "Changes saved"
    banner appears at the top and the saved values are reloaded fresh.
  - **Detail button:** the edit page now has a button ("Anime Details") that opens
    the anime's own detail page (direct access to the detail while staying here).
  - **Adding:** after saving you go to the new anime's edit page (keep editing /
    reviewing what you just added). Online, only those who can edit (moderator and
    above); a regular member returns to the list as before.
  - **Refresh-safe:** it is still Post-Redirect-Get - refreshing the page (F5)
    does not resubmit the form.

- **Clicking an emotion in statistics lists the anime with that emotion.** On the
  personal emotion distribution in the statistics page, clicking an emotion badge
  filters the list page to the anime you marked with that emotion.
  - **No bloat with many anime:** the list goes through index's existing
    pagination / sorting / search; there is no separate list, so even with many
    anime it paginates 10 at a time (or the chosen page size).
  - **Active filter banner:** while the filter is on, an "Emotion filter: X" banner
    and a "Clear filter" link are shown at the top.
  - **Personal + safe:** the filter is scoped to your own marks; the global emotion
    distribution (other users' data) is not clickable. The emotion value is
    validated against a known list.

## Notes

- No schema or migration change (migration/1.1.5 is a no-op ring). Both features
  only change existing tables and the UI / redirect flow.
- The after-save redirect preserves the POST + CSRF and same-host flow; the add
  redirect is chosen by role (because edit_anime is moderator-gated, a regular
  member is not sent to the new anime's edit page - they return to the list).
- The emotion filter works together with the existing filters (genre, watch status,
  letter, search, sorting, pagination) and is preserved across pages.

## Changed files

- add_anime.php, edit_anime.php
- index.php, statistics.php
- lang/tr.php, lang/en.php
- css/components.css
- version.txt
- migration/1.1.5/upgrade.sql
