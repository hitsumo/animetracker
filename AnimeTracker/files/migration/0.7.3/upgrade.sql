-- Anime Tracker 0.7.3 migration
-- Adds user_synopsis_en: the English counterpart of the personal synopsis
-- (user_synopsis). From 0.7.3 the personal synopsis is language-specific
-- (TR = user_synopsis, EN = user_synopsis_en), mirroring the catalog
-- synopsis_tr / synopsis_en split introduced in 0.7.1.
--
-- Idempotent: ADD COLUMN throws 1060 (duplicate column) on re-run, which
-- is in migration_manager's whitelist (1050/1060/1061/1091), so a repeated
-- run is safely ignored. No backfill (the column starts NULL).
-- again Hime-chan no Ribbon watching . parallel parallel

ALTER TABLE `animes` ADD COLUMN `user_synopsis_en` text DEFAULT NULL;
