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
--   synopsis        - Official plot summary, sourced from the catalog.
--                     Overwritten on every catalog_import sync (the
--                     catalog is authoritative).
--   user_synopsis   - Optional per-user alternative or personal take on
--                     the plot. NEVER touched by catalog sync, never
--                     sent to the server by admin_sync. Appears as a
--                     second "Kendi Yorumum" box on the detail page
--                     when non-empty, hidden when NULL.
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

CREATE TABLE IF NOT EXISTS `animes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alternative_titles` text DEFAULT NULL,
  `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor') NOT NULL,
  `total_episodes` int(11) DEFAULT NULL,
  `aired_episodes` int(11) DEFAULT NULL,
  `watched_episodes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `watch_status` enum('İzlendi','İzleniyor','İzlenme Planlandı') NOT NULL,
  `next_episode_date` datetime DEFAULT NULL,
  `anidb_link` varchar(255) DEFAULT NULL,
  `mal_link` varchar(255) DEFAULT NULL,
  `anime_schedule_link` varchar(255) DEFAULT NULL,
  `episode_interval` int(11) DEFAULT 7,
  `broadcast_day` varchar(20) DEFAULT NULL,
  `broadcast_time` time DEFAULT NULL,
  `broadcast_timezone` varchar(64) NOT NULL DEFAULT 'Asia/Tokyo',
  `synopsis` text DEFAULT NULL,
  `user_synopsis` text DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `series_name` varchar(255) DEFAULT NULL,
  `media_type` enum('TV','Film','OVA','Special','ONA') DEFAULT NULL,
  `next_in_series` int(11) DEFAULT NULL,
  `mal_id` int(11) DEFAULT NULL,
  `anidb_id` int(11) DEFAULT NULL,
  `catalog_uuid` varchar(36) DEFAULT NULL,
  `source` enum('catalog','local') NOT NULL DEFAULT 'local',
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
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
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
-- Tags are admin-managed (manage_tags.php) and propagated to clients
-- via the catalog API just like chronology_markers.
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
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

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
