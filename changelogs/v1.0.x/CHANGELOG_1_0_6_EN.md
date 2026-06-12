# Anime Tracker 1.0.6 - Changelog

**Release date:** June 2026 (internal milestone)

> Note: This release affects the online (multi-user) mode only. There is NO
> visible change for self-host (single-user) installs; import and everything
> else work exactly as before. Version numbers are internal development steps.

## Summary

When online members upload their anime list via "Import List", animes that are
in the list but NOT yet in the shared catalog are no longer silently dropped or
duplicated into the catalog. They are queued as a "catalog request"; a
moderator/admin reviews the queue and adds suitable ones to the catalog.

## Online import is now mode-aware

Previously the import wrote animes directly into the `animes` table in both
modes. Online, that caused two problems: an anime missing from the shared
catalog was either duplicated or had its personal watch state attached to the
wrong row. In this release:

- **Online (multi-user):** The imported list is matched against the existing
  catalog by MAL / AniDB id. For matches, only your personal watch state is
  written (the catalog row is untouched). Non-matches are stored in the
  `catalog_requests` table as `pending` - they are NOT added to the catalog
  directly. Re-suggesting the same anime as the same user does not create a
  duplicate.
- **Self-host (single-user):** Previous behaviour is preserved - import is a
  full backup restore; the owner edits the catalog directly, with no request
  queue.

## New: Catalog Requests moderation screen

A new card in the admin panel (`admin.php`): **Catalog Requests**. It shows the
pending request count as a badge and links to `admin_catalog_requests.php`,
where a moderator/admin can:

- Select pending requests in bulk and **Approve** them - the selected animes are
  added to the catalog as `source='local'` rows (which can then be pushed to the
  server via `admin_pending.php` / `admin_sync.php`, and have missing
  image/fields completed from the edit screen).
- Or **Reject** them - the request is marked rejected and kept as an audit
  record.

Access is moderator+ online; self-host serves the page only over localhost.

## Clearer import error messages

When an import failed, the message used to always be "please upload a valid JSON
file" - even when the file could not be read at all. Now an upload failure
(size/server limit) and invalid content are reported separately.

## Database

This release adds a single table: `catalog_requests` (the online request /
pending queue). The migration is applied automatically
(`migration/1.0.6/upgrade.sql`, idempotent). No existing table is touched;
`animes`, the personal watch tables and statistics are unaffected. On self-host
the table simply stays empty.

## What changed for self-host users

Nothing in practice. Import, your list, your watch states - all work as before.
This release's feature is entirely specific to the online mode.
