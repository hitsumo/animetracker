# Anime Tracker 0.6.2

**Release date:** 26 May 2026
**Type:** Feature (English language support)

This release ships via auto-update; you do not need to do anything.
The database schema is untouched, your existing data is untouched.

## New

- **English language support.** The anime list, detail and edit
  pages can now be displayed in English. A small TR/EN button pair
  lives in the top-right corner of each page: click, the page
  reloads, the language switches. The choice is persistent - close
  the browser and reopen it, the setting is remembered.

- **Status badges and emotion tags are translated too.** In EN
  mode, instead of "İzlendi / İzleniyor / İzlenme Planlandı /
  İzleme Ertelendi" you see "Watched / Watching / Plan to Watch /
  On Hold"; the same applies to emotion tags ("Saddened / Excited /
  Bored / ..."). The countdown to a series' next episode in the
  list view is also translated.

- **Form validation messages are bilingual.** When you fill in a
  field incorrectly on the edit page (e.g. invalid date format,
  missing AniDB link), the error message appears in the language
  you have selected.

## Known behaviour

- **Three pages are translated for now.** This release covers the
  main list, the detail page and the edit form. Other pages (What
  Should I Watch, Recently Edited, List Settings, Statistics, Help,
  About and Series Chronology) still display in Turkish. This is on
  purpose: keeping the scope narrow lowers the risk of mistakes.
  More pages will follow in future releases.

- **The language toggle is shared across devices.** Your choice is
  stored in the database; if you connect to the same installation
  from a different browser or session, it opens in the language you
  chose. This will become especially useful when multi-user mode
  arrives later (each user will store their own preference).

- **Broadcast day and timezone selectors keep Turkish values, only
  the labels are bilingual.** Values like "Pazartesi / Salı / ..."
  remain in Turkish in the database; only what you see is translated.
  So in EN mode you pick "Monday" but the database stores "Pazartesi"
  - this guarantees compatibility with your existing records.

- **Broadcast status ("Yayın Tamamlandı" etc.) works the same way.**
  The database value stays in Turkish, only dropdown labels and
  page text are translated to English.

## Technical notes

- No database schema changes. The language preference is stored as
  one row in the same general settings table used by other settings
  (auto-created the first time you switch language).

- A new `lang/` folder was added (`tr.php` and `en.php`). If
  another language is ever wanted, the same structure takes one
  more file - no code changes required.

- The language switch buttons are CSRF-protected mini forms (not
  links) - they use POST instead of GET, so external links or
  browser caching cannot accidentally change your language.
