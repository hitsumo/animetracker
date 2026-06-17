-- =====================================================================
-- 1.0.15 - carry list-import chronology markers into the moderation queue
-- =====================================================================
-- An online list import sends animes that are not in the catalog to the
-- catalog_requests queue. Until now the markers attached to such an anime
-- were dropped, so a moderator never saw the user's chronology and the
-- notes were lost. This column stores those markers (as JSON, the related
-- anime carried by stable identity: mal_id / anidb_id / catalog_uuid /
-- title) so they survive in the queue and are re-linked when the request
-- is approved.
--
-- Plain ADD COLUMN (no IF NOT EXISTS): the migration runner makes this
-- idempotent by catching the "duplicate column" error (1060) on re-run.
-- Column-level IF NOT EXISTS is MariaDB-only and is a syntax error on
-- MySQL, so it must not be used here.
ALTER TABLE `catalog_requests`
  ADD COLUMN `pending_markers` text DEFAULT NULL AFTER `reviewed_by`;
