# Anime Tracker 1.2.5

**Release date:** 2026-10-05

Main change: **account deletion.** On an online installation an admin can
delete a member from User Management, and a member can delete their own
account from the Account page. Alongside it: the open session of a member
who was deleted or suspended now ends on their next request, and the About
page now lists the data sources with an AniDB attribution. No schema change
(empty migration); nothing to do on the central catalog. A single-user
installation has no account deletion or session check; the About page note
shows there too.

## 1. Why

The privacy page said "write to have your account deleted", but the
application had no way to delete an account: an admin could only suspend a
member, and deletion was a manual job in the database.

## 2. What is deleted and what stays

The deletion happens in one go; if a step fails, nothing is deleted.

- **Deleted:** the account, the list (watch status, episodes, notes,
  personal synopsis), the watch log, list settings, AniList import records,
  and invite requests left with the member's e-mail address.
- **Stays without a name:**
  - **Emotion marks** stay in the anonymous counts on anime pages and in
    What to Watch; they are no longer tied to any account. The numbers and
    "how many people marked it" do not change.
  - **Correction suggestions** stay; the sender and the IP address are
    removed.
  - **Catalog requests** (those created while importing a list and those
    the member reviewed) stay; who suggested or reviewed them is cleared.
  - **Blacklist entries** stay; who added them is cleared.
- **Invite codes:** the code the member used to register stays "used" and
  loses its e-mail address. It does not become usable again. Codes the
  member generated stay valid; only who generated them is cleared.

## 3. Deletion by an admin (User Management)

- Each row has a collapsed **Delete** box. Opening it asks for the username
  to be typed as confirmation; if it does not match, nothing is deleted and
  a warning says so.
- An admin does not see this box on their own row.
- The installation owner's account (id 1) cannot be deleted: single-user
  mode uses this account, and it is needed if the installation is switched
  back to that mode.
- If only one active admin is left, that account cannot be deleted.

## 4. A member deleting their own account (Account page)

- The Account page has a **Delete My Account** section at the bottom. It
  asks for the password and, as confirmation, the username; the same place
  says what is deleted and what stays without a name.
- After deletion the session ends and the sign-in page says "Your account
  has been deleted."
- Id 1 and the last active admin see why they cannot be deleted instead of
  the form.

## 5. The session is now checked on every request

The account status used to be checked only at sign-in. A suspended member
could keep using the site on their open session. Now, on an online
installation, every request checks that the account still exists and is
active; if not, the session ends and the request continues as a guest.

## 6. Texts

- The "Your rights" part of the privacy page describes the self-service
  deletion and what is deleted or kept without a name.
- Help → The Account Page mentions the Delete My Account section.
- Turkish and English.

## 7. Data sources and AniDB attribution

AniDB's terms of use tie the use of its information in another service to
the **CC BY-NC-SA** license: the source must be credited, it must not be
used commercially, and it must be shared under the same license. Part of
the catalog, including the relations between anime, is compiled from AniDB,
but the site did not say so anywhere.

- **A "Data sources" paragraph on the About page:** it says that catalog
  information is compiled from several public sources and from members'
  contributions, that part of it comes from AniDB, that information compiled
  from AniDB is shared under CC BY-NC-SA, and that the code is separately
  licensed under GPL-2.0. It links to AniDB and to its terms of use page.
  Turkish and English.
- Since the catalog goes from the central server to every installation, the
  note shows on every installation's About page.
- The same license note was added to the "Catalog model" section of the
  repository README.

## 8. How it was tested

Six test accounts with data in every table were created on a test
database, and 71 checks were made with real HTTP requests (sign-in, form
posts, redirects); all passed. Some of them:

- Deleting with the wrong username deleted nothing.
- The deleted member's list, log, settings and AniList records were gone;
  emotion counts and "how many people marked it" did not change; their
  suggestion stayed without a name or IP; the invite code they registered
  with stayed used and did not become valid again; an invite request left
  with their e-mail in capital letters was deleted too.
- An admin could not delete themselves from User Management; id 1 could not
  be deleted by another admin either; deletion was blocked when only one
  active admin was left.
- A member could not delete with a wrong password or a wrong username,
  deleted with the right ones and landed on the sign-in page.
- When a member with an open session was deleted and another was
  suspended, both sessions ended on the next request.
- No PHP warnings on the pages and no server errors.
- The new About paragraph was opened in Turkish and English, its links are
  correct, and it causes no overflow at 375 pixels.

## Files

**New:**

```
files/functions/account_helpers.php      account deletion (check + one-go delete)
files/migration/1.2.5/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/functions.php                      loads account_helpers.php; session check on every request
files/functions/auth_helpers.php         ends the session of a deleted / suspended account
files/admin/admin_users.php              User Management: per-row Delete box
files/account.php                        Delete My Account section
files/login.php                          "Your account has been deleted." notice
files/about.php                          "Data sources" paragraph (AniDB attribution + license)
files/lang/tr.php, files/lang/en.php     account, sign-in, privacy, help and About texts
files/lang/admin_tr.php, admin_en.php    User Management texts
files/version.txt
```

In the repository (not in the package): `README.md` — catalog data license note.

## Deployment notes

- **Nothing to do on the central catalog.**
- The migration is empty; it moves the version stamp on the first page
  load.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.5 and publish
  `updates/1.2.5/anime-tracker-1.2.5.zip`.
