# Anime Tracker 1.2.4

**Release date:** 2026-10-01

Main job: **a privacy and terms of use page.** The site now explains on
one page which information it keeps and why, how long it keeps it and
whom to contact. Alongside it: **IP addresses on invite requests and
suggestions are deleted after 30 days.** No schema change (empty
migration); nothing to do on the central catalog.

## 1. Why

An online installation keeps personal information: members' email
addresses, the email, reason and IP address of people who request an
invite, and the IP address of people who send a suggestion. Nowhere on
the site did it say what these are, why they are kept or how to have
them deleted.

## 2. Privacy page (`privacy.php`)

- **The text depends on the mode the installation runs in.**
  - **Online installation:** what is kept, what it is used for, who
    sees it, cookies, external resources (Google Fonts, cdnjs), how long
    it is kept and your rights (export, correction, deletion).
  - **Single-user installation:** says that no account, email or IP is
    kept and lists where the application connects to: catalog sync,
    update check, install counter, optional features.
- **Short terms of use:** information is not guaranteed to be accurate,
  posters belong to their rights holders, abuse may get an account
  suspended, the site is provided as is.
- **Copies installed on other servers:** each installation is the
  responsibility of the person who runs it. The software's developer is
  not responsible for installations they do not operate.
- **Turkish and English;** listed in the sitemap, fits a phone.
- **Links:** the About page, the registration form, the invite request
  form and the Suggest a Correction box on the anime page.

## 3. Contact address from the admin panel

The address shown on the privacy page is not hard-coded. Whoever runs an
installation is responsible for its data, so they enter it.

- **Admin Capabilities → Contact** (admin only, online installations
  only).
- It is separate from the invite notification address: that one receives
  invite requests, this one is the site's contact address. If left empty,
  the page says "contact the site administrator" without an address.
- An invalid address is not saved, and the card shows a warning saying so.
- **The same address is shown at the top of the Help page.** It used to
  be a fixed address there, the same on every installation; now it comes
  from this setting, and with the setting empty (and on a single-user
  installation) no contact line appears.

## 4. IP addresses deleted after 30 days

On invite requests and suggestions the IP address is used only for the
spam limit: at most 5 submissions from one address per hour. An address
older than an hour does nothing, yet addresses were kept forever.

- **Addresses older than 30 days are deleted;** the text of the request
  or suggestion stays.
- The cleanup runs on its own when a new request or suggestion arrives
  and when an admin opens those lists. The first visit after the update
  removes all old addresses at once.
- The cleanup does not change the records' "last updated" time.

## 5. How it was tested

- On a freshly installed test database, records with 45, 31, 29 and
  5-day-old IPs were created. Opening the admin page deleted the 45 and
  31-day ones and kept the 29 and 5-day ones. Update times did not change.
- The invite request submission path gave the same result.
- The contact address was tried: a valid address was saved and shown on
  the page, an invalid one was not saved and the warning appeared, saving
  an empty value removed it. With the notification address set and the
  contact address empty, the privacy page does not show the notification
  address. The Help page shows no line with the setting empty and the set
  address otherwise (Turkish and English); no line on single-user
  installations.
- The privacy page was opened in Turkish, in English and in single-user
  mode. No overflow at 375 pixels.

## Files

**New:**

```
files/privacy.php                        privacy + terms of use (text by mode)
files/functions/privacy_helpers.php      IP retention + contact address
files/migration/1.2.4/upgrade.sql        empty (version stamp)
```

**Changed:**

```
files/functions.php                      loads privacy_helpers.php
files/functions/auth_helpers.php         old IPs cleared before an invite request is stored
files/suggest.php                        old IPs cleared before a suggestion is stored
files/admin/admin_capabilities.php       Contact card (the address on the privacy page)
files/admin/admin_invites.php            IP cleanup before listing
files/help.php                           contact line from the setting (fixed address removed)
files/admin/admin_suggestions.php        IP cleanup before listing
files/about.php                          privacy link
files/register.php                       privacy link
files/request_invite.php                 privacy link
files/anime_details.php                  privacy link in the Suggest a Correction box
files/functions/seo_helpers.php          privacy.php in the sitemap
files/lang/tr.php, files/lang/en.php     page texts
files/lang/admin_tr.php, admin_en.php    admin card texts
files/version.txt
```

## Deployment notes

- **Nothing to do on the central catalog.**
- The migration is empty; it moves the version stamp on the first page
  load.
- After updating, fill in **Admin Capabilities → Contact**. If it is left
  empty, the privacy page shows no address.
- **On the distribution server**, the usual two steps: bump the published
  `version.txt` to 1.2.4 and publish
  `updates/1.2.4/anime-tracker-1.2.4.zip`.
