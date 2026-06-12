# Anime Tracker 1.0.8 - Changelog

**Release date:** June 2026 (internal milestone)

> Note: This release mostly concerns small fixes in online / multi-user
> installs and the mobile view. For self-host (single-user) installs there are
> two visible changes: the list table no longer overflows on mobile, and list
> import now works against a populated catalog (see below). Version numbers are
> internal development steps. This release contains no schema (database
> structure) change.

## Summary

This release fixes the anime list table overflowing on mobile; shows logged-out
visitors a read-only episode column (total count only); restricts deleting a
catalog anime to authorized users; and makes list import match the existing
catalog so it also works against a populated database.

## Mobile list table fix

On narrow screens (phones) the anime list table columns did not fit and text
overlapped (title, status and episode text spilled into neighbouring columns).
The table is now horizontally scrollable on mobile; columns keep a readable width
and text no longer overlaps. The desktop view is unchanged.

## Episode column for logged-out visitors

For a logged-out visitor the notion of "watched episodes" has no meaning (no
personal watch state is kept). These visitors now see only the total episode
count in the episode column; the column header reads "Episode Count" and the
increment/decrement (+/-) controls are not shown. For logged-in users and on
self-host installs everything is as before (watched/total display and +/-
controls).

## Deleting from the catalog now requires authorization

In an online (multi-user) install, the "Delete" action in the anime list was
protected only by request verification (CSRF), which meant a logged-out visitor
could also delete a catalog anime. Deleting now requires server-side
authorization (moderator and above); the "Delete" and "Edit" buttons are not
shown to visitors who lack permission. On self-host the owner already has full
rights, so behaviour is unchanged.

## List import works against a populated catalog

Previously self-host list import tried to add every anime in the list as a new
record. If the catalog sync (pulling anime from the server) had already added the
same anime, the import collided with those records and failed, with no rows
imported (the user saw "invalid file"). Import now first matches an existing anime
(by MAL / AniDB id or catalog id): if the anime exists it writes the watch status,
notes and emotions onto that record; otherwise it adds a new one. Importing a list
into a populated database (for example an install that has pulled the catalog) now
works smoothly; a full restore into an empty database still works as before.

## What changed for self-host users

Two things: (1) the list table no longer overflows on mobile; (2) list import,
when the anime in the list already exist in the database, matches them and writes
your personal data (watch status, notes, emotions) onto them - it used to collide
and return empty. Your list, watch states, adding and editing work as before
unless noted otherwise.
