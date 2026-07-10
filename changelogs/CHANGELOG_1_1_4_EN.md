# Anime Tracker 1.1.4

**Release date:** 2026-07-09

## New

- **Interface language is now chosen from List Settings.** The interface
  language (Turkish / English) used to be changed with a small TR / EN switcher
  at the top of every page. It is now chosen in one place: the "Interface
  Language" dropdown on the List Settings page.
  - **Single place:** Language selection moved to List Settings; the TR / EN
    switcher was removed from the page headers (six pages: list, add anime,
    edit anime, details, recommendations, list settings).
  - **Applied immediately:** Choosing from the dropdown switches the language
    right away; a "Save" button appears if JavaScript is off.
  - **Independent of title language:** The interface language is separate from
    the "Title Language" preference (showing English anime titles instead of
    Romaji); the two are chosen independently.

## Notes

- No schema or migration change. The language preference (display_language) is
  still stored in the same per-user preference; only the selection point (UI)
  changed, not the underlying mechanism.
- Language writes go through the same endpoint; POST + CSRF protection and the
  same-host redirect hardening are preserved.

## Changed files

- list_settings.php
- index.php, add_anime.php, edit_anime.php, anime_details.php, recommendations.php
- lang/tr.php, lang/en.php
- css/lang.css
- version.txt
