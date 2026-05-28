-- Anime Tracker 0.7.0 migration
-- Per-episode filler tracking (KARARLAR Bolum 8).
--
-- Two schema changes, mirrored in schema.sql (kept in step per the
-- KARARLAR Bolum 2 "schema.sql degisikligi mutlaka upgrade.sql ile
-- eslesik" discipline):
--   1. New table filler_episodes (one row per marked episode).
--   2. New column animes.filler_tracking (per-anime visibility flag).
--
-- Idempotency: migration_manager re-runs migrations on partially-migrated
-- installs, so every statement must be safe to run twice.
--   - CREATE TABLE IF NOT EXISTS is inherently safe.
--   - ALTER ... ADD COLUMN has no portable IF NOT EXISTS on MySQL, so the
--     plain ADD is used; on a re-run it raises error 1060 (duplicate
--     column), which migration_manager's isIdempotentError() whitelist
--     treats as safe and ignores (same pattern as earlier ASCII-enum and
--     end_date/user_synopsis migrations).
-- Yes first filler test with meitantei conan

CREATE TABLE IF NOT EXISTS `filler_episodes` (
  `anime_id`   int(11) NOT NULL,
  `episode_no` int(11) NOT NULL,
  `type`       enum('MangaCanon','AnimeCanon','Mixed','Filler') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`anime_id`, `episode_no`),
  KEY `idx_filler_anime` (`anime_id`),
  CONSTRAINT `fk_filler_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `animes`
  ADD COLUMN `filler_tracking` tinyint(1) NOT NULL DEFAULT 0 AFTER `source`;
