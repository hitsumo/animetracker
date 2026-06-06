# Anime Tracker 1.0.5 - Changes

**Release date:** June 2026 (internal milestone)

> Note: This release continues multi-user (online) mode. It adds two things:
> CORRECTION SUGGESTIONS (users reporting catalog errors + moderation) and
> USER CATALOG ADDITIONS (signed-in users can add anime, which go to an approval
> queue). For self-host (single-user) installs there is NO visible change. The
> version numbers are internal development steps.

## Correction suggestions

Anyone who spots wrong or missing information on an anime - including anonymous
visitors - can now submit a correction suggestion. A **"Suggest a Correction"**
form (free-text note) was added to the anime detail page. Every submission lands
in a moderation queue as "pending".

A new **"Correction Suggestions"** screen was added to the admin panel (moderator
and above). It has pending / accepted / rejected tabs, accept / reject / reopen
buttons per suggestion, and an "edit" link to the relevant anime. Applying an
accepted suggestion to the catalog is MANUAL (the moderator opens the anime and
edits it); there is no automatic apply.

For anti-spam there are two measures: a hidden trap field in the form (invisible
to people, filled by bots) and a per-IP hourly submission limit. The submitter's
IP is stored for rate-limiting/abuse handling; it is shown only on the moderation
screen, never to users on the site.

## User catalog additions

In online mode EVERY signed-in user can now add a new anime (previously only
moderators and above could). What happens depends on the adder's role:

- When a **moderator / admin** adds, the anime appears in the catalog DIRECTLY
  (no approval needed).
- When a **regular user** adds, the anime goes to APPROVAL: it does NOT appear in
  the main catalog list. Instead it is listed on a public **"Pending Additions"**
  page (the home page has a "Pending additions (N)" link to it). Once a
  moderator/admin approves it, the anime moves into the main catalog.

Anonymous visitors cannot add; they are not shown the "Add" link.

## Updates

The in-app "Check for Updates" (automatic ZIP update) is now for self-host
installs only. On a multi-user (online) install that section is replaced by a
link to the source repository (GitHub); online installs are updated via
git/Docker. The automatic ZIP update is also refused server-side in online mode,
so no partial/mismatched update (new core + old admin) can occur.

## What changed for self-host users

Nothing. The suggestion form, the moderation screen and the "pending approval"
flow are only visible when multi-user mode is on. On a single-user install the
owner adds anime directly as before (no approval), and the suggestion feature is
not shown.

## Other

### Schema

This release includes a schema change, but it is applied automatically by the
update mechanism - you do not need to do anything by hand. Added: the
`suggestions` table (the correction-suggestion queue). `migration/1.0.5` runs
automatically and is idempotent. When multi-user mode is off the table simply
stays empty; it has no effect on self-host.

> The sicakcikolata.com catalog server is NOT affected by this change.

### Files

New: `suggest.php` (suggestion submit), `admin_suggestions.php` (suggestion
moderation queue), `pending.php` (pending additions - public list). Changed:
`anime_details.php` (suggestion form), `add_anime.php` (opened to every signed-in
user; direct-to-catalog or to-approval by role), `index.php` (keeping pending
items out of the main list + "Pending additions" link + hiding the "Add" link),
`update.php` + `list_settings.php` (GitHub link instead of ZIP update in online
mode), `admin.php` (new admin-panel card) and the language files.
