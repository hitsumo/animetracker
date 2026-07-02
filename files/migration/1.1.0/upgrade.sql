-- Anime Tracker - Migration 1.1.0
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- Personal watch dates (manual entry, MVP). Adds two nullable DATE
-- columns to user_anime: watch_start_date and watch_finish_date.
-- Both NULL by default; existing rows are untouched (no backfill).
--
-- First real schema change since 1.0.20 (1.0.21 and 1.0.22 were no-op
-- rings). Each column is a separate ALTER so a partially applied run
-- re-runs cleanly: the runner strips comment lines, splits on ';', and
-- ignores error 1060 (duplicate column) per statement, so an already
-- added column is skipped while a missing one is still added. After the
-- statements run, migration_manager bumps settings.version to 1.1.0.

ALTER TABLE `user_anime` ADD COLUMN `watch_start_date` DATE DEFAULT NULL AFTER `user_synopsis_en`;

ALTER TABLE `user_anime` ADD COLUMN `watch_finish_date` DATE DEFAULT NULL AFTER `watch_start_date`;
