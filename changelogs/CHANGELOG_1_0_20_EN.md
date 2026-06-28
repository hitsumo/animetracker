# Anime Tracker 1.0.20

**Release date:** 27.06.2026

## New

- When registration is invite-only, visitors who do not have an invite can now
  request one. The registration page has a new "Request an Invite" link; the
  visitor enters their email address and why they want an invite. Requests are
  collected under a new "Invite Requests" tab on the invite-management page,
  where you can generate an invite for a request with a single click.
- When a request arrives, a short email is sent to the notification address you
  configure (the requester's email + their reason). The notification address is
  set on the invite-management page; if it is left empty, no email is sent and
  the requests still appear on the tab.

## Notes

- Invite requests only work in multi-user mode and while the registration mode
  is "invite". A single-user (self-host) install has no registration or invites,
  so this feature is not shown.
- The request form is protected against spam submissions (a hidden field plus a
  per-IP hourly submission limit).
- The notification email is sent on a best-effort basis since the request is
  already saved on the tab; the request is never lost even if sending fails.
- For the notification email to be delivered, your server's outbound email
  (local mail service and domain DNS records) must be working.
- This release adds one new database table (invite requests).
