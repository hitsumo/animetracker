# Anime Tracker 1.0.15

**Release date:** 17.06.2026

## New

- Chronology notes from an imported list now reach the moderator. When you
  import a member list online, an anime that is not in the catalog was sent
  to the moderation queue as a request, but the chronology notes attached to
  that anime were lost. These notes are now carried along with the request,
  and when a moderator approves the request the matching notes are linked
  automatically.

  The request list now has a Chronology column showing how many notes each
  row carries, so the moderator can see them before approving. After
  approval the result message gains a line such as "X chronology note(s)
  linked, Y skipped."

  For a note to be linked, the related anime it points to must also be in
  the catalog. If the related anime is missing, that note is skipped and
  counted; the rest of the approval completes normally. If two anime
  approved in the same batch point at each other, those are linked too.

## Notes

- Database schema changed: a pending_markers field was added to the
  catalog_requests table. Take a database backup before running the update.
- The behavior change affects online (multi-user) installs only. On a
  self-host install the request flow never occurs, so behavior is unchanged.
- Interface language updated for both TR and EN.
