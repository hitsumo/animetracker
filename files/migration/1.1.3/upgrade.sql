-- Anime Tracker - Migration 1.1.3
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- Extends the adult-content flag to the two catalog taxonomies:
-- genres.is_adult and tags.is_adult (tags are the recommendation
-- "sentences" / cumle).
--
-- Method A (display-layer). While the per-user show_adult_content
-- preference is off, an adult-flagged genre or tag is dropped from the
-- filter dropdown (index), the recommendation sentence picker
-- (recommendations) and the detail-page badges. The anime rows
-- themselves stay governed by animes.is_adult (1.1.2): flagging a term
-- does NOT hide the anime that carries it.
--
-- No new preference is added: the existing show_adult_content toggle
-- governs all three (animes, genres, tags).
--
-- Idempotency: these are real ALTERs. If a column already exists the
-- runner swallows error 1060 (duplicate column) - see
-- migration_manager.php isIdempotentError() - so a partial or repeated
-- run is safe and simply advances settings.version to 1.1.3.

ALTER TABLE `genres` ADD COLUMN `is_adult` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `tags` ADD COLUMN `is_adult` TINYINT(1) NOT NULL DEFAULT 0;
