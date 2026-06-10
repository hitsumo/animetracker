# Anime Tracker 1.0.9 - Changelog

**Release date:** June 2026 (internal milestone)

> Note: This release concerns the statistics page, the watch-status display
> in the anime list, and a small reminder for the catalog owner. Version
> numbers are internal development steps. This release contains no schema
> (database structure) change.

## Summary

This release splits the statistics page into two tabs (user and global);
shows the emotion distribution across all users on the global tab; and adds
a new "Not Selected" watch state - anime the user has not touched yet now
show as "Not Selected" instead of "Plan to Watch".

## Statistics page now has two tabs

The statistics page now has two tabs. The "User Statistics" tab holds
per-user data: total watched episodes, distribution by watch status, and
distribution by emotion. The "Global Statistics" tab holds catalog-wide
data: total anime, distribution by media type, and by broadcast status.
The total watched episode count is personal data, so it appears only on the
user tab and is not shown on the global tab.

## Global emotion distribution

The distribution by emotion now appears on both tabs. The user tab counts
only your own marks; the global tab aggregates the marks of all users. On a
single-user (self-host) install the two views are identical; on a
multi-user install the global view is everyone's total.

## "Not Selected" watch state

Previously, when a user first opened the list, every anime they had not
touched showed as "Plan to Watch" - even though the user had not made any
choice yet. Untouched anime now show as "Not Selected"; once the user
changes the watch status (for example by marking an episode with +), the
status moves to its real value. "Plan to Watch" is now only a state the
user has deliberately chosen.

This state was also added to the list filter (a "Not Selected" option in
the watch-status filter) and is counted as a separate row in the
watch-status distribution on the statistics user tab.

## Curator note (admin page)

A reminder was added to the admin page intro text, stating that when an
anime is added online or offline its image must be uploaded to the central
server manually. This concerns only the admin page, which only the catalog
owner sees.

## What changed for self-host users

Two things: (1) the statistics page now has two tabs (user / global); on a
single-user install the global and user views show the same data. (2) In
the list, anime you have not touched now show as "Not Selected" instead of
"Plan to Watch"; behaviour (marking episodes with + / -) is as before unless
noted otherwise.
