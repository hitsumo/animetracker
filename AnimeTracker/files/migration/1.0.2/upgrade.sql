-- Anime Tracker 1.0.2 migration (Faz 2, Milestone 1 - part 2b, the copy)
-- Moves existing PERSONAL data into the per-user tables created in 1.0.1.
-- This migration is applied TOGETHER with the endpoint refactor that
-- starts reading and writing user_anime / user_pref. On the first page
-- load after deploying both, migration_manager (in db.php) runs this copy
-- BEFORE the page code reads user_anime, so the data is already in place.
--
-- IMPORTANT: do not deploy this migration without the matching code. If
-- the copy runs but the endpoints still write to animes, user_anime goes
-- stale and the next read shows the snapshot instead of fresh data.
--
-- Idempotency (migration_manager whitelist is 1050/1060/1061/1091 only):
--   - INSERT IGNORE user_anime: re-run skips the duplicate PRIMARY KEY
--     (user_id, anime_id) rows. A plain INSERT would raise 1062, which is
--     NOT whitelisted and would fail the migration.
--   - INSERT IGNORE user_pref + DELETE FROM settings: on a re-run the
--     settings rows are already gone, so the SELECT copies nothing and
--     the DELETE affects zero rows - both no-ops, no error.

-- 1) Personal columns animes -> user_anime for the self-host owner (id=1).
--    In single-user mode every anime is in the owner's list, so every row
--    is copied. The 'Dropped' enum value exists in user_anime but is not
--    produced here (no animes row carries it) - it stays reserved for the
--    later personal-state milestone.
INSERT IGNORE INTO `user_anime`
    (`user_id`, `anime_id`, `watch_status`, `watched_episodes`,
     `notes`, `user_synopsis`, `user_synopsis_en`)
SELECT 1, `id`, `watch_status`, `watched_episodes`,
       `notes`, `user_synopsis`, `user_synopsis_en`
  FROM `animes`;

-- 2) Per-user preference keys settings -> user_pref (owner id=1), then
--    drop them from settings so each key has a single home. Global keys
--    (version, last_catalog_sync, last_aired_sync, synopsis_edit_override)
--    are NOT touched and stay in settings.
INSERT IGNORE INTO `user_pref` (`user_id`, `name`, `value`)
SELECT 1, `name`, `value`
  FROM `settings`
 WHERE `name` IN ('display_language', 'display_title_english');

DELETE FROM `settings`
 WHERE `name` IN ('display_language', 'display_title_english');
