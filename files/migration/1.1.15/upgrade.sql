-- Anime Tracker - Migration 1.1.15
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- 1.1.15 adds a second, optional insertion point to each chronology
-- marker: story_after_episode. A marker's after_episode stays the
-- RELEASE-order point (where the related anime aired); the new
-- story_after_episode is the STORY / recommended-watch point, which can
-- differ. Example (Card Captor Sakura): the first film aired after
-- episode 46 but is best watched after episode 35 - so after_episode=46
-- and story_after_episode=35 on the same marker.
--
-- NULL means "no divergence": the story view falls back to after_episode
-- via COALESCE(story_after_episode, after_episode), so existing markers
-- need no backfill and appear at the same spot in both views. Only
-- markers that actually diverge carry a second number.
--
-- The column is NOT part of the UNIQUE KEY (anime_id, after_episode,
-- related_anime_id): it is an attribute of the marker, not part of its
-- identity, so catalog re-push updates it via ON DUPLICATE KEY UPDATE.
--
-- The runner strips these comment lines and executes the single ALTER
-- below; a duplicate-column error (1060) on re-run is ignored, and
-- settings.version is bumped to 1.1.15.

ALTER TABLE `chronology_markers`
  ADD COLUMN `story_after_episode` int(11) DEFAULT NULL AFTER `after_episode`;
