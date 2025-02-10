-- Veritabanı yapısı
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Anime tablosu
CREATE TABLE IF NOT EXISTS `animes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `alternative_titles` text DEFAULT NULL,
  `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor') NOT NULL,
  `total_episodes` int(11) NOT NULL,
  `watched_episodes` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `genres` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `watch_status` enum('İzlendi','İzleniyor','İzlenme Planlandı') NOT NULL,
  `next_episode_date` datetime DEFAULT NULL,
  `anidb_link` varchar(255) DEFAULT NULL,
  `mal_link` varchar(255) DEFAULT NULL,
  `episode_interval` int(11) DEFAULT 7,
  `broadcast_day` varchar(20) DEFAULT NULL,
  `broadcast_time` time DEFAULT NULL,
  `synopsis` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Türler tablosu
CREATE TABLE IF NOT EXISTS `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Örnek türler
INSERT INTO `genres` (`name`) VALUES
('Aksiyon'),
('Macera'),
('Komedi'),
('Dram'),
('Fantezi'),
('Korku'),
('Gizem'),
('Psikolojik'),
('Romantik'),
('Bilim Kurgu'),
('Dilim of Life'),
('Spor'),
('Doğaüstü'),
('Gerilim');