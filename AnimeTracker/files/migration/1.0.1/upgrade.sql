-- Anime Tracker 1.0.1 migration (Faz 2, Milestone 1 - part 2a)
-- Creates the user_anime and user_pref tables. TABLES ONLY: no data is
-- copied and no application code reads these yet, so applying this
-- migration changes nothing the user can observe (the site behaves
-- exactly as before).
--
-- The data copy (existing animes personal columns -> user_anime for
-- user_id=1) and the settings -> user_pref move happen in the NEXT
-- migration, together with the endpoint refactor that starts reading and
-- writing these tables. Doing the copy here, before reads switch over,
-- would leave data in two places at once.
--
-- Idempotency: CREATE TABLE IF NOT EXISTS (code 1050 is whitelisted by
-- migration_manager), so a re-run is a no-op. There are no INSERTs here.
--
-- FK note: both tables reference users(id), which exists from 1.0, and
-- user_anime also references animes(id). On a fresh install schema.sql
-- creates users/animes before these; in this migration they already
-- exist, so the foreign keys resolve.

CREATE TABLE IF NOT EXISTS `user_anime` (
  `user_id`          int(11) NOT NULL,
  `anime_id`         int(11) NOT NULL,
  `watch_status`     enum('Watched','Watching','PlanToWatch','OnHold','Dropped') NOT NULL DEFAULT 'PlanToWatch',
  `watched_episodes` int(11) NOT NULL DEFAULT 0,
  `notes`            text    DEFAULT NULL,
  `user_synopsis`    text    DEFAULT NULL,
  `user_synopsis_en` text    DEFAULT NULL,
  `created_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`       timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `anime_id`),
  KEY `idx_anime` (`anime_id`),
  CONSTRAINT `fk_ua_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_ua_anime` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_pref` (
  `user_id`    int(11)     NOT NULL,
  `name`       varchar(50) NOT NULL,
  `value`      text        DEFAULT NULL,
  `created_at` timestamp   NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp   NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `name`),
  CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
