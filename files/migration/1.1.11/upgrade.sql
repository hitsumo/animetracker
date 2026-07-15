-- =====================================================================
-- 1.1.11 - AniList import source limit
-- =====================================================================
-- Online (multi-user) mode caps a normal member at N DISTINCT AniList
-- source usernames (settings.anilist_import_source_limit, default 3) so
-- one member cannot pull an unbounded number of other people's public
-- lists and flood the moderation queue / catalog. Self-host and
-- moderator+ are exempt.
--
-- This migration adds the app-side ledger that tracks each user's used
-- sources. One row per (user, normalized username); the UNIQUE key +
-- INSERT IGNORE let the same account re-sync without opening a new slot.
-- CREATE TABLE IF NOT EXISTS is idempotent (re-run-safe), matching the
-- migration runner's contract.
--
-- The tunable itself (anilist_import_source_limit) lives in the generic
-- settings key/value store, so it needs NO schema change - it is created
-- on first write from the admin panel and read with a default of 3.
--
-- NOTE: purely app-side. The central catalog server (catalog_server/)
-- never sees this table, so - unlike the enum/is_adult work - there is
-- NO manual ALTER to run on the catalog host.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `anilist_import_sources` (
  `id`               int(11) NOT NULL AUTO_INCREMENT,
  `user_id`          int(11) NOT NULL,
  `anilist_username` varchar(50) NOT NULL,
  `first_used_at`    timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_src` (`user_id`, `anilist_username`),
  KEY `idx_ais_user` (`user_id`),
  CONSTRAINT `fk_ais_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
