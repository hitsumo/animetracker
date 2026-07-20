# Anime Tracker 1.1.12

**Release date:** 2026-07-15

## New: Invite request limit

- **The admin can cap how many invite requests may sit in the queue at once
  (e.g. 50, 70, 100).** Once the number of pending requests reaches the limit,
  the public invite-request form (request_invite.php) closes and no new request
  is accepted. Visitors see a "quota full, try again later" notice.
- **The limit counts PENDING requests, so it is self-healing.** When the admin
  invites or rejects a queued request, that slot reopens automatically. The cap
  is based on what is currently waiting, not on all requests ever submitted.
- **You can remove the limit at any time.** Setting the field to `0` removes the
  cap (unlimited) and reopens the form fully.
- The check has two lines: the form is hidden on GET when closed, and the submit
  (POST) side re-validates on the server (it cannot be bypassed with a direct
  POST).

## New: Registration announcement

- **The admin can write a free-text announcement shown on the registration page
  (register.php).** For example: "Invites are limited to 50 people." or "A new
  invite slot opens in one week." The text is not a fixed template - whatever the
  admin types is what appears.
- **Leaving it empty hides the announcement.** With the field blank, no notice is
  shown on the registration screen.
- The text is stored raw and safely escaped on output (no HTML injection); line
  breaks are preserved.

## Admin controls

- Both settings live on the **Registration & Invites** page (admin_invites.php)
  and can only be changed by an **admin**:
  - "Invite Request Limit" card (Invite Requests tab): numeric field 0..100000,
    0 = unlimited. The card shows live status (pending / limit, open or closed).
  - "Registration Announcement" card (Invites tab): free text up to 2000
    characters.

## How it works (technical)

- Two new values are kept in the `settings` key/value table
  (`invite_request_limit`, `register_announcement`); no dedicated schema field
  is needed.
- A new helper `invite_request_limit_state($pdo)` reads the limit and the
  pending count to decide whether the form is open or closed; on a query error
  it fails safe (leaves the form OPEN so legitimate visitors are not locked out).
- **No central-catalog impact** - the settings are app-side only, never sent to
  the central catalog server; NO manual step is required on that host.

## Fix: Turkish characters on the Help pages

- **The Help pages (help.php and the help/ sub-pages) were written in ASCII-safe
  form** (e.g. "Izleme Durumlari", "Nasil Calisir?", "Yardim Icindekiler"). All
  Help text now renders with proper Turkish characters (İ, ç, ğ, ı, ö, ş, ü). The
  content lives in the `help.*` keys of `lang/tr.php`; HTML tags, code samples,
  `#anchor` links, and technical terms (JST, TZ, AnimeSchedule, broadcast_day,
  etc.) are preserved. The English help (`lang/en.php`) was unchanged.
- **The "GitHub page" phrase in the Help footer is now a clickable link**
  (https://github.com/hitsumo/animetracker); it was previously plain text. In
  both the Turkish and English help.
- **A contact email was added to the Help index (help.php):** just below the
  blue line under the title and above the intro text,
  `at@animetracker.uzakdiyarlar.com` (clickable mailto). In both Turkish and
  English (`help.contact`).

## Schema / migration

- `migration/1.1.12/upgrade.sql` seeds the two setting keys with their defaults
  (`INSERT IGNORE`, never clobbers an existing value) and advances the version
  to 1.1.12. There is no schema change.

## Changed / new files

- functions/auth_helpers.php (invite_request_limit_state + 'full' in submit)
- request_invite.php (hide form + closed notice when the queue is full)
- admin/admin_invites.php (limit + announcement cards, admin-only POSTs)
- register.php (announcement banner)
- help.php (contact email line)
- lang/tr.php (invites + help Turkish-character fix + help.contact),
  lang/en.php (invites + footer/contact links), lang/admin_tr.php,
  lang/admin_en.php
- migration/1.1.12/upgrade.sql (new)
- version.txt
