-- Anime Tracker - Database Schema
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------
-- Table: animes
-- Stores the user's anime list with watch progress and broadcast info.
--
-- Episode fields (introduced in v0.5):
--   total_episodes  - Final episode count. NULL for ongoing anime when
--                     the final count is unknown (e.g. One Piece).
--                     Required for finished anime.
--   aired_episodes  - Episodes aired so far in Japan. Meaningful only
--                     while status = 'Yayın Devam Ediyor'. NULL for
--                     finished anime (the value is copied to
--                     total_episodes when status becomes 'Yayın Tamamlandı').
--   watched_episodes - How many episodes the user has watched.
--
-- Series grouping fields:
--   series_name     - Free-text franchise identifier used to group related
--                     entries (e.g. "Detective Conan" covers S1, movies,
--                     OVAs). NULL for standalone entries. Used by
--                     getRelatedAnimes() and the "same series" section
--                     on anime_details.php.
--   media_type      - One of TV / Film / OVA / Special / ONA. Used to
--                     group entries within a series on the detail page.
--                     NULL allowed for legacy rows.
--   next_in_series  - Optional pointer to the next anime.id the user
--                     should watch after finishing this one. Validated
--                     by validateNextInSeries() in functions.php.
--
-- Title fields:
--   title             - Romaji title (Latin-script Japanese), used as the
--                       default everywhere and language-independent.
--   alternative_titles - Pipe-separated alternates, language-unspecified.
--   title_english     - Optional English/localized title (0.7.2). Shown
--                       instead of the Romaji title ONLY when the user
--                       enables the "show English titles" preference
--                       (settings key display_title_english) AND this
--                       column is non-empty; otherwise the Romaji title
--                       is shown. Independent of the UI language - the
--                       preference is a separate toggle. Part of the
--                       catalog sync chain (lives on this row).
--
-- External links:
--   anidb_link, mal_link, anime_schedule_link - Optional URLs to the
--                     corresponding pages on AniDB, MyAnimeList and
--                     AnimeSchedule. safe_url() is used before rendering.
--
-- Date fields:
--   release_date    - First episode air date.
--   end_date        - Last episode air date. Only meaningful when
--                     status = 'Yayin Tamamlandi'. NULL for ongoing anime.
--
-- Synopsis fields:
--   synopsis        - DEPRECATED (0.7.1). Legacy single-language plot
--                     summary. Kept (not dropped) for re-run-safe
--                     migrations and rollback, mirroring the filler
--                     MangaCanon "reserve, do not drop" rule. No longer
--                     read or written by application code; superseded by
--                     synopsis_tr / synopsis_en below.
--   synopsis_tr     - Turkish plot summary, sourced from the catalog
--                     (the curator's original editorial text). Overwritten
--                     on every catalog_import sync (the catalog is
--                     authoritative).
--   synopsis_en     - English plot summary. Produced by AI translation via
--                     external web tools and pasted manually by the curator
--                     (no in-code AI/API). Empty until filled. Shown with
--                     an "Auto-translated from Turkish" label on the detail
--                     page. Also catalog-authoritative (part of sync).
--   translation_status - Status of synopsis_en:
--                     'none'     -> no EN / external source; no label.
--                     'ai'       -> AI translation; "Auto-translated" label.
--                     'reviewed' -> curator-approved; reserved for future
--                                   use (currently not set in practice).
--                     Falls back to 'ai' automatically when synopsis_tr is
--                     updated, so a changed Turkish text is not paired with
--                     a stale "approved" English text. Catalog-authoritative
--                     (part of sync).
--   user_synopsis   - Optional per-user Turkish personal synopsis. From
--                     0.7.3 this is the TR side of a language-specific
--                     pair (see user_synopsis_en). NEVER touched by catalog
--                     sync, never sent to the server by admin_sync. When a
--                     user edits the catalog synopsis_tr, the prior text is
--                     MOVED here by catalog_import (see that file). An empty
--                     string ('') means "intentionally cleared - do not let
--                     sync restore it"; NULL means "still catalog-managed".
--   user_synopsis_en - English counterpart of user_synopsis (0.7.3). Same
--                     rules, independent of the TR side: an anime can have
--                     a personal TR synopsis while EN is still catalog.
--
-- Catalog identity fields (for future sync with remote catalog API):
--   mal_id          - Numeric MyAnimeList ID parsed from mal_link. Primary
--                     cross-install identifier. UNIQUE so the same anime
--                     cannot be added twice. NULL if the MAL link is
--                     missing or unparseable.
--   anidb_id        - Numeric AniDB ID parsed from anidb_link. Secondary
--                     cross-install identifier (some niche / older titles
--                     only have AniDB entries). UNIQUE.
--   catalog_uuid    - Fallback identifier assigned by the remote catalog
--                     when neither mal_id nor anidb_id exists. UNIQUE.
--   source          - 'catalog' for rows that came from the remote catalog
--                     sync, 'local' for rows the user added manually.
--                     Determines sync behaviour (see catalog sync docs).
--
-- Genres are NO LONGER stored as a CSV column. The legacy animes.genres
-- TEXT column was dropped in the v0.5 in-place patch (see
-- genres_relational_upgrade.sql). Use the anime_genres join table.
-- --------------------------------------------------------

-- NOTE (Faz 2 / 1.0.x): five columns below are PERSONAL watch state, not
-- catalog data - watched_episodes, notes, watch_status, user_synopsis and
-- user_synopsis_en. As of 1.0.x they live per user in the user_anime table.
-- They are still DEFINED here on purpose: a fresh install records version
-- '0.5' (see the settings seed at the end of this file) and replays every
-- migration in order, and migration 1.0.2 reads these columns to copy the
-- existing single-user data into user_anime. Migration 1.0.3 then DROPs them,
-- so the live (post-migration) animes table does NOT carry them. Do not write
-- new code against animes.watch_status / watched_episodes / notes /
-- user_synopsis(_en) - read and write user_anime through the helpers
-- (ua_get_state / ua_set_state, get_user_pref / set_user_pref).
CREATE TABLE IF NOT EXISTS `animes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alternative_titles` text DEFAULT NULL,
  `title_english` varchar(255) DEFAULT NULL,
  `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor') NOT NULL,
  `total_episodes` int(11) DEFAULT NULL,
  `aired_episodes` int(11) DEFAULT NULL,
  `watched_episodes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `watch_status` enum('Watched','Watching','PlanToWatch','OnHold') NOT NULL DEFAULT 'PlanToWatch',
  `next_episode_date` datetime DEFAULT NULL,
  `anidb_link` varchar(255) DEFAULT NULL,
  `mal_link` varchar(255) DEFAULT NULL,
  `anime_schedule_link` varchar(255) DEFAULT NULL,
  `episode_interval` int(11) DEFAULT 7,
  `broadcast_day` varchar(20) DEFAULT NULL,
  `broadcast_time` time DEFAULT NULL,
  `broadcast_timezone` varchar(64) NOT NULL DEFAULT 'Asia/Tokyo',
  `synopsis` text DEFAULT NULL,
  `synopsis_tr` text DEFAULT NULL,
  `synopsis_en` text DEFAULT NULL,
  `translation_status` enum('none','ai','reviewed') NOT NULL DEFAULT 'none',
  `user_synopsis` text DEFAULT NULL,
  `user_synopsis_en` text DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `series_name` varchar(255) DEFAULT NULL,
  `media_type` enum('TV','Film','OVA','Special','ONA') DEFAULT NULL,
  `next_in_series` int(11) DEFAULT NULL,
  `mal_id` int(11) DEFAULT NULL,
  `anidb_id` int(11) DEFAULT NULL,
  `catalog_uuid` varchar(36) DEFAULT NULL,
  `source` enum('catalog','local') NOT NULL DEFAULT 'local',
  `filler_tracking` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_series_name` (`series_name`),
  KEY `idx_next_in_series` (`next_in_series`),
  UNIQUE KEY `idx_mal_id` (`mal_id`),
  UNIQUE KEY `idx_anidb_id` (`anidb_id`),
  UNIQUE KEY `idx_catalog_uuid` (`catalog_uuid`),
  CONSTRAINT `fk_next_in_series`
    FOREIGN KEY (`next_in_series`) REFERENCES `animes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: genres
-- Master list of canonical anime genres (Action, Comedy, Drama, ...).
-- The user manages this list via manage_genres.php; new genres added
-- by catalog_import.php via findOrCreateGenre() are visible here too.
-- Linked to animes via the anime_genres join table.
--
--   name_en - English genre name (0.7.2). NULL until filled. The TR
--             name stays authoritative; name_en is shown only when the
--             UI language is English and this column is non-empty,
--             otherwise the TR name is used. LOCAL-ONLY: not carried by
--             the catalog wire format yet (deferred to Faz 2, mirroring
--             the filler local-only decision); the catalog still syncs
--             genres by their TR name.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `name_en` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: anime_genres
-- Many-to-many link between animes and genres. ON DELETE CASCADE on
-- both sides keeps the table free of orphan rows when an anime or
-- genre is removed. Mirrors the anime_tags pattern.
--
-- Used by:
--   - functions.php: getAnimeGenres(), setAnimeGenres(), findOrCreateGenre()
--   - index.php: genre filter (JOIN against genres.name)
--   - anime_details.php: badge rendering
--   - add_anime.php / edit_anime.php: form submission
--   - catalog_import.php: sync mapping
--   - admin_sync*.php: outbound CSV serialization for the catalog API
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `anime_genres` (
  `anime_id` int(11) NOT NULL,
  `genre_id` int(11) NOT NULL,
  PRIMARY KEY (`anime_id`, `genre_id`),
  KEY `idx_anime_genres_genre_id` (`genre_id`),
  CONSTRAINT `fk_anime_genres_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_anime_genres_genre`
    FOREIGN KEY (`genre_id`) REFERENCES `genres` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: settings
-- Generic key-value store for application settings.
-- Used by migration_manager.php and check_update.php (for 'version'),
-- catalog_import.php (for 'last_catalog_sync'), and functions.php
-- (for 'last_aired_sync', written by syncAllOngoingAiredEpisodes).
--
-- Keys written at runtime (created on first use, not seeded by this file):
--   last_catalog_sync  - UTC timestamp of last successful catalog import
--   last_aired_sync    - UTC timestamp of last successful aired_episodes
--                        sync (Madde C, written by list_settings.php
--                        silent daily run or "Simdi Senkronize Et")
--   display_timezone   - IANA TZ for displaying broadcast times to the
--                        user (e.g. 'Europe/Istanbul'). Optional; if
--                        absent, broadcast times are shown in their
--                        native broadcast_timezone (usually Asia/Tokyo).
--                        NOTE: This key is currently legacy - written
--                        by older versions of list_settings.php and
--                        read by date display helpers, but the form
--                        UI for setting it may not be present in
--                        every install (planned full integration
--                        in a later release).
--
-- All keys use INSERT ... ON DUPLICATE KEY UPDATE pattern, so missing
-- rows are created on demand and existing rows are overwritten.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: chronology_markers
-- Episode-level watch-order hints for a given anime. Example use case:
-- "After episode 23 of Detective Conan, watch Movie 1". A single host
-- anime can have many markers, each pointing to another anime that
-- should be inserted into the watch order at that point.
--
-- Used by:
--   - add_chronology_marker.php / delete_chronology_marker.php (write)
--   - chronology.php (list view)
--   - anime_details.php (inline section + active alert)
--   - functions.php: getChronologyMarkers(), getActiveChronologyAlert()
--
-- Foreign keys cascade on anime deletion so markers never become
-- orphans. The UNIQUE KEY prevents duplicate markers from double-submit
-- (add_chronology_marker.php catches the 23000 error code and reports
-- "already exists" to the user).
--
-- The `source` column (added in 0.5.3, Karar 1B) separates catalog
-- markers from locally-created ones. catalog_import.php only deletes
-- WHERE source='catalog' before reloading, so an admin's own markers
-- (source='user') are never wiped by a "Katalogdan Ice Aktar" that
-- runs before those markers have been pushed to the catalog. This
-- prevents the 14 Nisan 2026 marker-loss incident from recurring.
-- New markers default to 'user'; admin_push.php marks server-imported
-- markers as 'catalog'.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `chronology_markers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anime_id` int(11) NOT NULL,
  `after_episode` int(11) NOT NULL,
  `related_anime_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `source` enum('catalog','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_marker` (`anime_id`, `after_episode`, `related_anime_id`),
  KEY `idx_related_anime_id` (`related_anime_id`),
  CONSTRAINT `fk_marker_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_marker_related_anime`
    FOREIGN KEY (`related_anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: tags
-- Free-form descriptive sentences used by the recommendation system
-- (recommendations.php). These are intentionally separate from the
-- `genres` table:
--   - genres   = canonical anime genres (Action, Comedy, ...) usually
--                sourced from MAL/AniDB and shared with the catalog.
--                Linked via anime_genres.
--   - tags     = admin-curated descriptive sentences (e.g. "Okulda
--                gecsin", "Spor temasi olsun", "Buyu olsun") used purely
--                to power the "Ne Izlesem?" recommender. Each tag is one
--                bucket in the bucket metaphor: the user picks several
--                sentences, each sentence pulls its matching anime from
--                the pool, overlapping anime rank higher (OR + score,
--                not AND).
--
-- The `name` column holds the full sentence as it will be shown to the
-- end user on the recommendation page (so it must read as a complete
-- phrase, not a one-word label). 150 characters is enough for any
-- natural Turkish sentence describing an anime trait.
--
--   name_en - English sentence (0.7.2). NULL until filled. Same fallback
--             rule as genres.name_en: shown only when the UI language is
--             English and non-empty, otherwise the TR sentence is used.
--             LOCAL-ONLY (not in the catalog wire format yet, Faz 2).
--
-- Tags are admin-managed (manage_tags.php) and propagated to clients
-- via the catalog API just like chronology_markers.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `name_en` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_tag_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: anime_tags
-- Many-to-many link between animes and tags. ON DELETE CASCADE on both
-- sides keeps the table free of orphan rows when an anime or tag is
-- removed. Used by recommendations.php to score the bucket-overlap
-- ranking.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `anime_tags` (
  `anime_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`anime_id`, `tag_id`),
  KEY `idx_anime_tags_tag_id` (`tag_id`),
  CONSTRAINT `fk_anime_tags_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_anime_tags_tag`
    FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: users (1.0 - Faz 2, Milestone 1)
-- The account table. Present in BOTH modes:
--   - Single-user / self-host (MULTI_USER_MODE = false): one seeded
--     "owner" row (id=1, seeded below). No login; current_user_id()
--     always returns 1, so every user_id FK in the user-scope tables
--     resolves. Today's behaviour is preserved exactly.
--   - Multi-user / online (MULTI_USER_MODE = true): one row per
--     registered account.
--
-- Having users in BOTH modes (rather than "no users table in single-user
-- mode") is deliberate: the schema is identical in both modes, the mode
-- difference is only "is login enforced", not the shape of the schema.
-- This kills a whole class of migration bugs (e.g. the old
-- user_anime_emotion "users table does not exist yet, FK later" problem).
--
-- Column notes:
--   username      - login + current display name. A separate display_name
--                   is a cheap ADD COLUMN later if ever needed; not
--                   pre-loaded now.
--   email         - for password reset. NULL allowed: the self-host owner
--                   row carries no email. A UNIQUE index permits multiple
--                   NULLs in MySQL/MariaDB, so this is fine.
--   password_hash - password_hash() output (algorithm chosen in the auth
--                   milestone). NULL on the self-host owner (login is not
--                   enforced there).
--   role          - all 4 values from the start. Adding an ENUM value
--                   later is expensive (the 0.6 ASCII enum lesson), so
--                   'trusted'/'moderator' are reserved now; 'trusted'
--                   behaviour activates in Faz 3.
--   status        - all 4 values from the start (same ENUM lesson):
--                   'active' normal, 'suspended' moderation, 'deleted'
--                   GDPR soft-delete, 'pending' Faz 3 email verification
--                   (reserved now even if unused).
--
-- The self-host owner is seeded at the bottom of this file (id=1).
-- -------------------------------------------------------- 

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

-- --------------------------------------------------------
-- Table: user_anime (1.0.1 - Faz 2, Milestone 1)
-- The per-user personal state for an anime. One row per (user, anime).
-- The row's EXISTENCE means "this anime is in my list"; an anime that is
-- in the catalog but not in a user's list simply has no row for that user.
--
-- This is the core single-user -> multi-user split: the personal columns
-- that used to live on animes (watch_status, watched_episodes, notes,
-- user_synopsis, user_synopsis_en) move here, so the same catalog row in
-- animes can be shared by everyone while each user keeps private state.
--
--   - PRIMARY KEY (user_id, anime_id): one personal record per user/anime.
--   - watch_status: 5 values - the existing 4 plus 'Dropped'. 'Dropped'
--     ("gave up, not coming back") is the opposite intent of 'OnHold'
--     ("paused, will return"); neither replaces the other. The enum is
--     born with 5 values here (the animes.watch_status enum had 4 and is
--     dropped with that column in a later migration).
--   - notes, user_synopsis, user_synopsis_en are PRIVATE: never synced,
--     never public. user_synopsis = "Kisisel Konu" (TR), _en its EN side.
--   - idx_anime: "who follows this anime" / aggregation.
--   - FK user_id -> users(id) ON DELETE CASCADE: safety net only; user
--     deletion is a soft-delete routine (see users), this cascade guards
--     an unexpected hard-delete.
--   - FK anime_id -> animes(id) ON DELETE CASCADE: deleting a catalog
--     anime drops everyone's personal rows for it (matches today's
--     behaviour where deleting an anime removed its personal columns).
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table: user_pref (1.0.1 - Faz 2, Milestone 1)
-- Per-user preferences: the user-scope twin of the global settings table,
-- same key-value shape so the code stays familiar (no new pattern).
--
-- A new preference is a new row, never an ALTER (same enum/schema-churn
-- avoidance philosophy as settings). Self-host writes user_id=1; reads and
-- writes go through current_user_id(), so single-user behaviour is
-- identical to today's global setting.
--
-- Keys that move here from settings (KISIYE OZEL): display_language,
-- display_title_english. Keys that STAY in settings (GLOBAL/instance):
-- version, last_catalog_sync, last_aired_sync, synopsis_edit_override.
-- (The move itself happens in the refactor migration, not here.)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_pref` (
  `user_id`    int(11)     NOT NULL,
  `name`       varchar(50) NOT NULL,
  `value`      text        DEFAULT NULL,
  `created_at` timestamp   NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp   NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`, `name`),
  CONSTRAINT `fk_up_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: invites (1.0.4 - Faz 2, Milestone 2)
-- Single-use registration tokens for invite-mode signup. When
-- settings.registration_mode = 'invite' a new account must present a valid,
-- unused token; in 'open' mode this table simply sits unused.
--
--   - token: the single-use secret, generated by an admin/moderator. UNIQUE
--     so the same token can never exist twice.
--   - email: optional intended recipient, informational only (not enforced).
--   - created_by / used_by: users.id by VALUE, NO foreign key on purpose - an
--     invite must outlive the deletion of its creator or its consumer (audit
--     trail), and user deletion is a soft-delete routine that never
--     hard-deletes rows anyway.
--   - used_at NULL + used_by NULL: an unused invite. Both are set when the
--     token is consumed at registration.
--
-- registration_mode itself is an instance setting (settings table, seeded to
-- 'invite' by migration 1.0.4), not a column here - it is operator policy, not
-- per-invite data.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `invites` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `token`      varchar(64)  NOT NULL,
  `email`      varchar(255) DEFAULT NULL,
  `created_by` int(11)      DEFAULT NULL,
  `used_by`    int(11)      DEFAULT NULL,
  `used_at`    timestamp    NULL DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: suggestions (1.0.5 - Faz 2, Milestone 2)
-- Free-text correction suggestions for a catalog anime. Anyone (anonymous or
-- signed-in) may submit one via suggest.php; every submission lands here as
-- status='pending' for a moderator to accept/reject. Applying an accepted
-- suggestion to the catalog is MANUAL (a moderator opens the anime and edits)
-- - there is no field-level auto-apply yet (field/proposed_value columns are a
-- later milestone; for now this is a note only).
--
--   - anime_id: the catalog row the suggestion is about. FK to animes with
--     ON DELETE CASCADE - if the anime is removed, its suggestions go too.
--   - note: the free-text suggestion body.
--   - submitter_user_id: users.id of the signed-in submitter, or NULL for an
--     anonymous submission. NO foreign key on purpose: an anonymous (NULL) or
--     deleted-user suggestion must be preserved.
--   - ip: submitter IP (IPv6-safe length). Used only for abuse handling and
--     per-IP rate limiting on submit; not shown to end users.
--   - status: pending (queue) -> accepted / rejected by a moderator.
--
-- idx_ip_created backs the per-IP rate-limit lookup (count this IP's recent
-- rows). idx_anime / idx_status back the moderation queue and per-anime views.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `suggestions` (
  `id`                int(11)     NOT NULL AUTO_INCREMENT,
  `anime_id`          int(11)     NOT NULL,
  `note`              text        NOT NULL,
  `submitter_user_id` int(11)     DEFAULT NULL,
  `ip`                varchar(45) DEFAULT NULL,
  `status`            enum('pending','accepted','rejected') NOT NULL DEFAULT 'pending',
  `created_at`        timestamp   NOT NULL DEFAULT current_timestamp(),
  `updated_at`        timestamp   NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_anime` (`anime_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ip_created` (`ip`, `created_at`),
  CONSTRAINT `fk_sug_anime` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: user_anime_emotion
-- The user's emotional reactions to anime, recorded as marks (not
-- scores). Each row is one (user, anime, emotion) triple; a user can
-- mark up to 3 different emotions per anime. This is intentionally
-- NOT a rating system - there is no 1-10 score, no star count, no
-- "good vs bad" axis. The philosophy: a single score forces a complex
-- subjective response into one number and loses information.
--
-- Relationship to anime_tags (objective descriptive sentences set by
-- the admin) vs user_anime_emotion (subjective emotional marks set by
-- each user):
--   - anime_tags    = "interest filter" (what the anime is ABOUT;
--                     e.g. "Okulda gecsin"). Admin-curated, shared
--                     via catalog. Used by recommendations.php.
--   - user_anime_emotion = "mood signal" (what the anime MAKES YOU
--                     FEEL; e.g. "Huzunlendirdi"). User-set, private
--                     per user. Aggregated public distribution shown
--                     on the detail page (Faz 2 / 0.8 onwards; in
--                     single-user mode the aggregation is just the
--                     owner's own marks, kept as personal reference).
--
-- Schema decisions (24 May 2026, KARARLAR Bolum 8):
--   - PRIMARY KEY (user_id, anime_id, emotion) - one row per mark,
--     enforces uniqueness of a single emotion per anime per user.
--   - Multi-mark cap of 3 is enforced at the PHP layer (endpoint),
--     not by a DB trigger. Endpoint counts existing rows for
--     (user_id, anime_id) before INSERT.
--   - emotion VARCHAR(32), NOT an ENUM. The canonical list is
--     maintained in functions.php emotion_options() as a single
--     source of truth (helper-family pattern, watch_status_label
--     precedent). Extending the set later becomes a one-line helper
--     change rather than an ALTER MODIFY migration. Lesson from the
--     0.6 ASCII migration: ENUM modifies are expensive.
--   - user_id DEFAULT 1: single-user mode always writes user_id=1.
--     When Faz 2 / 0.8 introduces multi-user, the same table is
--     shared - existing rows belong to the original admin (id=1)
--     and new users get their own ids. No data migration needed.
--   - No FK on user_id: the users table does not exist in single-user
--     mode. Faz 2 will add the FK in a follow-up migration once the
--     users table is created.
--   - FK anime_id -> animes(id) ON DELETE CASCADE: emotion marks
--     belong to the anime; if the anime is removed they go with it.
--     There is no catalog reconvergence here (cf. Karar 1B
--     chronology_markers.source); emotion marks are pure user-scope
--     data and never sync with the catalog in either direction.
--   - created_at TIMESTAMP: when the user placed this mark. Useful
--     for "recently marked" queries and for resolving conflicts on
--     JSON re-import (Senaryo A) in Faz 2.
--   - idx_anime: supports aggregated distribution queries
--     (SELECT emotion, COUNT(*) FROM user_anime_emotion WHERE
--     anime_id = ? GROUP BY emotion). Minimal effect in single-user
--     mode, valuable in multi-user mode.
--   - idx_emotion: placeholder for filter queries
--     ("show me everything that made me laugh"). To be used by
--     recommendations.php in a future release.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `user_anime_emotion` (
  `user_id`    int(11) NOT NULL DEFAULT 1,
  `anime_id`   int(11) NOT NULL,
  `emotion`    varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`, `anime_id`, `emotion`),
  KEY `idx_anime`   (`anime_id`),
  KEY `idx_emotion` (`emotion`),
  CONSTRAINT `fk_uae_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: filler_episodes (0.6.8)
--
-- Per-episode filler classification (KARARLAR Bolum 8). One row per
-- MARKED episode; an unmarked episode (no row) means "assume canon" -
-- the default - so only exceptions are stored and most episodes leave
-- no row. Catalog-level data (tied to the anime, curator-maintained),
-- NOT user-scoped like user_anime_emotion - every user sees the same
-- filler info.
--
--   - PRIMARY KEY (anime_id, episode_no): one classification per episode,
--     absolute numbering (1..N) on the same axis as total/aired/watched
--     _episodes. Multi-season series are kept as a single record (the
--     user does not split seasons - KARARLAR Bolum 8); if season
--     splitting is introduced the numbering semantics get revisited.
--   - type enum: the four exception types. Range display ("5-6, 18") is
--     derived at render time (filler_summary) - the table stays
--     per-episode.
--   - FK ON DELETE CASCADE: deleting an anime drops its filler rows.
--   - Visibility is governed by animes.filler_tracking (a flag), NOT by
--     the presence of rows. Turning tracking off hides the data; it does
--     not delete these rows (KARARLAR Bolum 8 - "kapatmak veri SILMEZ").
--
-- Catalog sync (admin_push / catalog_import) is intentionally out of
-- scope for the first cut - filler is local-only until the Faz 2 catalog
-- wiring lands.
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Default data: genres
-- --------------------------------------------------------

INSERT IGNORE INTO `genres` (`name`) VALUES
('Aksiyon'),
('Macera'),
('Bilim Kurgu'),
('Doğaüstü'),
('Dram'),
('Fantezi'),
('Gerilim'),
('Gizem'),
('Komedi'),
('Korku'),
('Psikolojik'),
('Romantik'),
('Slice of Life'),
('Spor');

-- --------------------------------------------------------
-- Default data: settings
-- Only the version row is seeded here (required by migration_manager
-- on first boot). Other keys (last_catalog_sync, last_aired_sync,
-- display_timezone) are created at runtime on first use - see the
-- settings table comment above for details.
-- --------------------------------------------------------

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES
('version', '0.5');

-- --------------------------------------------------------
-- Default data: users (1.0 - Faz 2, Milestone 1)
-- Seed the self-host owner (id=1). INSERT IGNORE so re-running schema.sql
-- on an existing install never errors on the duplicate primary key.
-- In multi-user mode this same row is the first/admin account.
-- --------------------------------------------------------

INSERT IGNORE INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`) VALUES
(1, 'owner', NULL, NULL, 'admin', 'active');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
