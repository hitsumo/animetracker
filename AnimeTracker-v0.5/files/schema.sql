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
  `genres` text DEFAULT NULL,
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
  `release_date` date DEFAULT NULL,
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
-- Stores the available genre list
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: settings
-- Stores application settings such as the current schema version
-- Used by migration_manager.php and check_update.php
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
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `chronology_markers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `anime_id` int(11) NOT NULL,
  `after_episode` int(11) NOT NULL,
  `related_anime_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
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
-- The version row is required by migration_manager and check_update
-- --------------------------------------------------------

INSERT IGNORE INTO `settings` (`name`, `value`) VALUES
('version', '0.5');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
