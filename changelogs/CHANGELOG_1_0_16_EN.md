# Anime Tracker 1.0.16

**Release date:** 21.06.2026

## Fixes

- The "Manage Genres" button that had disappeared from the List Settings page
  is back. For the last few versions an empty area was left between the Title
  Language and Sentence Management sections; the Genre Management section there
  had been removed by mistake. The section, together with its "Manage Genres"
  button, has been restored. On a self-host install it is visible directly; on
  an online install it is shown to users who may manage genres.

- On an online install, the controls for adding and deleting chronology notes
  are now shown only to authorized users. A regular member used to see these
  controls even though the action was rejected; an unusable control is no
  longer shown. Existing chronology notes remain visible to everyone in
  read-only form.

## Notes

- The database schema did not change.
- On a self-host install the only visible change is the return of the genre
  management button. Hiding the chronology controls affects online
  (multi-user) installs only.
- No new translations were added; the existing TR and EN keys are used.
