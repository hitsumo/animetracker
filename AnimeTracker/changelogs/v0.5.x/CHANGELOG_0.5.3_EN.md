# Anime Tracker 0.5.3

**Release date:** May 16, 2026
**Type:** Data protection and bug fixes

This release is delivered via automatic update; no action is required
on your part.

## What's new

- **Chronology marker protection:** The "after episode X, watch this
  anime" chronology markers you add by hand to an anime are no longer
  deleted when you run "Import from Catalog". Previously, markers you
  had not yet pushed to the catalog could be lost during an import.
  From now on your own markers are preserved, while markers coming
  from the catalog are updated automatically.

- **Pre-import notice:** Before you run "Import from Catalog", if you
  have local chronology markers that are not in sync with the catalog,
  you now see an informational note and a confirmation warning. The
  import is safe — your own markers are not deleted; the warning is
  informational only and does not block the operation.

## For those running their own server

If you run the catalog/synchronization system on your own server,
this release also includes a server-side improvement:

- **Server-side chronology protection:** The catalog update logic on
  the server was updated to protect your own chronology markers as
  well; it now behaves symmetrically with the client side. This
  release requires a small column to be added to the server database
  **manually** (see the deployment notes). (admin_push.php — this is
  an admin-side file; it is not included in the automatic update and
  must be updated manually.)

## Notes

This release adds a small column to the database (to distinguish the
origin of chronology markers). Your database is upgraded automatically
during the auto-update; your existing data is preserved as is.

After updating, it is recommended to run an Import from Catalog once:
this labels the catalog markers that existed before the upgrade
correctly. Markers you added yourself are not affected by this step.
