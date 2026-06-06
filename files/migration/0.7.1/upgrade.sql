-- Anime Tracker 0.7.1 migration
-- Synopsis (Konu) multi-language support.
--
-- Three schema changes, mirrored in schema.sql (kept in step per the
-- KARARLAR Bolum 2 "schema.sql degisikligi mutlaka upgrade.sql ile
-- eslesik" discipline):
--   1. New column animes.synopsis_tr  (Turkish curator text).
--   2. New column animes.synopsis_en  (English, manual AI copy-paste).
--   3. New column animes.translation_status ENUM('none','ai','reviewed').
--
-- The legacy animes.synopsis column is DEPRECATED, not dropped. This
-- mirrors the filler MangaCanon decision (reserve, do not drop) and keeps
-- the migration re-run safe: dropping synopsis would make the copy UPDATE
-- below reference a missing column on re-run, raising error 1054 which is
-- NOT on migration_manager's idempotent whitelist. Keeping synopsis means
-- the copy is always valid and becomes a no-op on re-run.
--
-- Idempotency: migration_manager re-runs migrations on partially-migrated
-- installs, so every statement must be safe to run twice.
--   - ALTER ... ADD COLUMN has no portable IF NOT EXISTS on MySQL, so the
--     plain ADD is used; on a re-run it raises error 1060 (duplicate
--     column), which migration_manager's isIdempotentError() whitelist
--     treats as safe and ignores (same pattern as the 0.7 filler and the
--     earlier ASCII-enum / end_date / user_synopsis migrations).
--   - The copy UPDATE is guarded by WHERE synopsis_tr IS NULL, so it only
--     fills rows that have not been copied yet; a re-run touches nothing.

ALTER TABLE `animes` ADD COLUMN `synopsis_tr` TEXT DEFAULT NULL AFTER `synopsis`;
ALTER TABLE `animes` ADD COLUMN `synopsis_en` TEXT DEFAULT NULL AFTER `synopsis_tr`;
ALTER TABLE `animes` ADD COLUMN `translation_status` ENUM('none','ai','reviewed') NOT NULL DEFAULT 'none' AFTER `synopsis_en`;

UPDATE `animes` SET `synopsis_tr` = `synopsis` WHERE `synopsis_tr` IS NULL AND `synopsis` IS NOT NULL;
