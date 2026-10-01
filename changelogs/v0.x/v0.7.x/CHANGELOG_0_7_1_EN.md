# Anime Tracker 0.7.1 - Changelog

**Release date:** May 2026

## "Manage Sentences" button on List Settings

The List Settings page now has a **"Manage Sentences"** button next to
Genre Management. Previously the sentence (tag) library could only be
reached through the link on the add/edit anime screen; you can now open
it directly from List Settings.

## Sentence and Genre management pages now in English

The **Sentence Management** and **Genre Management** pages are now shown
in TR/EN according to the selected language. Previously these two pages
were Turkish-only; when you switch the interface to English, the page
titles, table headers, buttons and all warning/confirmation messages are
translated as well.

Since the language choice (the language button on the Home or List
Settings page) persists for the session, these pages open in that
language automatically.

## Synopsis now in Turkish + English

The anime synopsis is now bilingual. Each anime keeps a separate
**Synopsis (TR)** and **Synopsis (EN)**; the detail page shows whichever
matches the interface language.

- The English synopsis is translated with an AI tool and pasted in
  manually (there is no AI integration in the system). On the add/edit
  anime screen, a "Copy" button under the Turkish text speeds this up.
- When English text is shown, a small grey "Auto-translated from Turkish"
  label appears below it, linking to the Translation Status section on the
  Help page.
- If no English synopsis has been entered yet, the English interface shows
  the Turkish original with a short note.
- The edit screen has a "Mark as reviewed" option; changing the Turkish
  text clears that mark automatically (the English text is not deleted,
  only the status flag changes).

A **Translation Status** section explaining how these translations are
produced was added to the Help page.

## Other

### i18n

This release adds **45 new text keys** in total (Sentence Management,
Genre Management, the List Settings button, plus the Synopsis TR/EN and
translation-status text). Total dictionary size: 490 -> 535 keys (TR/EN
parallel).

### Schema

This release includes a **schema change**: the `animes` table gains
`synopsis_tr`, `synopsis_en` and `translation_status` columns; the old
single `synopsis` column is kept (not dropped) and its data is copied to
`synopsis_tr`. `migration/0.7.1` is now a real migration that performs
this change; it runs automatically during auto-update, nothing manual is
required (the operation is idempotent and safe to run twice).

### Files

Changed: `list_settings.php`, `manage_tags.php`, `manage_genres.php`,
`add_anime.php`, `edit_anime.php`, `anime_details.php`,
`recommendations.php`, `help.php`, `catalog.php`, `catalog_import.php`,
`admin_push.php`, `schema.sql`, `css/base.css`, `tr.php`, `en.php`.
New: `migration/0.7.1/upgrade.sql`.
