# Anime Tracker 1.1.16

**Release date:** 2026-07-17

## New: Interface language choice for non-member visitors

- **Signed-out visitors can now choose the interface language.** In
  multi-user (online) mode a visitor without an account previously saw the
  interface in Turkish only, with no way to change it. They can now switch
  between **Turkish / English**.
- **The choice is made from a small language switcher on the sign-in /
  register / request-invite pages.** It is a dropdown at the top-right, above
  the page card; picking a language switches the interface to it instantly.
- **The choice persists for the session.** Once selected, it applies across
  every page you visit for the rest of your browser session; you do not have
  to pick it again on each page.
- **Nothing changes for signed-in users or self-host (single-user)
  installs.** They still choose the interface language from the "Interface
  Language" section on the "List Settings" page; this small switcher is not
  shown to them.

## New: The registration announcement is now per language

- **The registration-screen announcement (register.php) can now be written
  separately in Turkish and English.** Previously there was a single text that
  showed regardless of the selected language; the admin panel (Invites) now has
  two fields: "Announcement text (Turkish)" and "Announcement text (English)".
- **If the English field is left empty, the Turkish announcement is shown on
  the English interface too.** So for an operator who writes a single-language
  announcement, behaviour is unchanged - it shows in both languages. An
  operator who fills both fields gives each interface its own language-specific
  text.

## Change: Email is now required when generating an invite

- **An email address is now required when generating an invite code.**
  Previously it was "(optional)" and could be left blank, which left it unclear
  who each invite was for. Every invite is now tied to a recipient email.
- **The invite is still sent manually - no automatic email is sent.** The email
  only records who the invite is for; you deliver the code to that person
  yourself. Submitting "Generate code" with an empty or invalid email creates
  no code and the form shows a warning.

## How it works (technical)

- A signed-in user's language preference is stored in the `user_pref` table.
  Because an anonymous visitor has no user id (and therefore no `user_pref`
  row), the choice is kept in the PHP session (`$_SESSION`) instead.
  `lang_init()` reads the guest choice from the session; `set_language.php`
  writes it there. The `set_language.php` endpoint, its form shape and its
  CSRF protection are unchanged.
- The guest switcher is produced by the `guest_lang_switcher()` helper, which
  returns HTML only when the visitor is "multi-user mode + not signed in";
  in every other case it returns an empty string, so pages look exactly as
  before. It reuses the same translation keys as the settings-page switcher
  (no new copy was added).

## Schema / migration

- `migration/1.1.16/upgrade.sql` only advances the version to 1.1.16; **there
  is no schema change** (no SQL statement to run). The fix lives entirely in
  the application layer. The central catalog is unaffected and no manual step
  is required on the server.

## Changed / new files

- functions/i18n_helpers.php (lang_init: read the guest language from the
  session; new `guest_lang_switcher()` helper)
- set_language.php (write the guest choice to the session instead of
  `user_pref`)
- login.php, register.php, request_invite.php (show the guest language
  switcher; register/request_invite: `.auth-alt` links styled as buttons)
- register.php (registration announcement: Turkish/English text by selected
  language, falling back to Turkish when the English text is empty)
- admin/admin_invites.php (a second, English text field for the announcement;
  saving writes both fields at once - email required when generating an invite:
  server-side validation + form `required` + warning on invalid submit)
- lang/admin_tr.php, lang/admin_en.php (English announcement field labels:
  announce.label / label_en / placeholder_en / hint_en; invite email strings:
  generate.desc / email_label / err_email)
- css/lang.css (guest language switcher styles; the file emptied in 1.1.4 now
  holds one rule again)
- migration/1.1.16/upgrade.sql (new)
- version.txt

Note: the English announcement text is stored under the `register_announcement_en`
settings key. The `settings` table is a key-value store and the row is created
on first save; no new table/column is required (no schema change).
