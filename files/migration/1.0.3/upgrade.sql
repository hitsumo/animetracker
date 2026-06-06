-- Anime Tracker 1.0.3 migration (Faz 2, Milestone 1 - part 3, the drop)
-- Removes the now-vestigial PERSONAL columns from the shared animes row.
-- They were copied into user_anime in 1.0.2, and as of the 1.0.2 code every
-- endpoint reads and writes user_anime (verified on the test database), so
-- these columns are dead weight on the catalog row.
--
-- ORDER: this runs only AFTER the 1.0.2 copy and the endpoint refactor are
-- deployed and verified. Dropping these while any code still read them would
-- break the app.
--
-- IDEMPOTENCY: each DROP is its own statement. migration_manager swallows
-- error 1091 (Cannot drop, structure does not exist), so re-running after a
-- partial drop is safe - an already-removed column just no-ops. We do NOT use
-- "DROP COLUMN IF EXISTS" because MySQL does not support it; the 1091 swallow
-- is the portable equivalent. No index references these columns (only
-- series_name, next_in_series, mal_id, anidb_id, catalog_uuid and the PK are
-- indexed), so the drops are clean.
--
-- SCOPE: self-host / multi-user database only. The sicakcikolata.com catalog
-- server keeps its animes columns - its schema is maintained by hand and
-- never enters the Faz 2 split.

ALTER TABLE `animes` DROP COLUMN `watched_episodes`;
ALTER TABLE `animes` DROP COLUMN `notes`;
ALTER TABLE `animes` DROP COLUMN `watch_status`;
ALTER TABLE `animes` DROP COLUMN `user_synopsis`;
ALTER TABLE `animes` DROP COLUMN `user_synopsis_en`;
