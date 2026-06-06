-- Anime Tracker 0.7.2 migration
-- Content i18n columns: English taxonomy + English title.
--
-- Three new columns, mirrored in schema.sql (kept in step per the
-- KARARLAR Bolum 2 "schema.sql degisikligi mutlaka upgrade.sql ile
-- eslesik" discipline):
--   1. genres.name_en   - English name of a canonical genre. NULL until
--                         filled. The TR name stays authoritative; EN is
--                         shown only when the UI language is English and
--                         this column is non-empty (otherwise it falls
--                         back to the TR name).
--   2. tags.name_en     - English text of a recommendation sentence. Same
--                         fallback rule as genres.name_en.
--   3. animes.title_english - Optional English/localized title. The Romaji
--                         title stays the default; title_english is shown
--                         only when the user turns on the "show English
--                         titles" preference (display_title_english setting)
--                         and this column is non-empty.
--
-- Sync scope: title_english is part of the catalog sync chain (it lives on
-- the animes row alongside synopsis_tr/_en). genres.name_en and tags.name_en
-- are LOCAL-ONLY for now - the catalog wire format carries genre/tag NAMES
-- (not the EN translation), so distributing name_en to other installs is
-- deferred to Faz 2, mirroring the filler local-only decision. The columns
-- exist and are filled/displayed locally; only their propagation waits.
--
-- Idempotency: migration_manager re-runs migrations on partially-migrated
-- installs, so every statement must be safe to run twice.
--   - ALTER ... ADD COLUMN has no portable IF NOT EXISTS on MySQL/MariaDB,
--     so the plain ADD is used; on a re-run it raises error 1060 (duplicate
--     column), which migration_manager's isIdempotentError() whitelist
--     (1050/1060/1061/1091) treats as safe and ignores - same pattern as
--     the 0.7.1 synopsis and the 0.7 filler migrations.
--   - All three are NULL columns with no data backfill, so there is no
--     UPDATE step to guard (unlike the 0.7.1 synopsis copy).
--  i was liked Champignon no Majo.

ALTER TABLE `genres` ADD COLUMN `name_en` VARCHAR(50) DEFAULT NULL AFTER `name`;
ALTER TABLE `tags` ADD COLUMN `name_en` VARCHAR(150) DEFAULT NULL AFTER `name`;
ALTER TABLE `animes` ADD COLUMN `title_english` VARCHAR(255) DEFAULT NULL AFTER `alternative_titles`;
