-- Anime Tracker 1.0 migration (Faz 2, Milestone 1 - part 1)
-- Adds the users table and seeds the self-host owner (id=1).
--
-- This is the first real schema change of the online transition. The
-- users table is created in BOTH modes so the schema is identical whether
-- MULTI_USER_MODE is false (self-host) or true (online); the mode only
-- decides whether login is enforced, not the shape of the schema.
--
-- Idempotency (migration_manager re-runs partial migrations and only
-- ignores codes 1050/1060/1061/1091):
--   - CREATE TABLE IF NOT EXISTS  -> no error if the table already exists.
--   - INSERT IGNORE               -> a re-run does NOT raise 1062
--                                    (duplicate primary key id=1); the
--                                    duplicate is silently skipped. A plain
--                                    INSERT here would fail the migration,
--                                    since 1062 is not whitelisted.
--
-- Later milestones (separate migrations) add user_anime, user_pref, the
-- user_anime_emotion FK, and drop the personal columns from animes.

CREATE TABLE IF NOT EXISTS `users` (
  `id`            int(11)      NOT NULL AUTO_INCREMENT,
  `username`      varchar(32)  NOT NULL,
  `email`         varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role`          enum('admin','moderator','trusted','user')     NOT NULL DEFAULT 'user',
  `status`        enum('pending','active','suspended','deleted')  NOT NULL DEFAULT 'active',
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  `updated_at`    timestamp    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'owner', NULL, NULL, 'admin', 'active');
