-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 16 Şub 2025, 11:51:18
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `anime_tracker`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `animes`
--

CREATE TABLE `animes` (
  `id` int(11) NOT NULL,
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
  `release_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `genres`
--

CREATE TABLE `genres` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `genres`
--

INSERT INTO `genres` (`id`, `name`, `created_at`) VALUES
(1, 'Aksiyon', '2025-02-15 19:27:52'),
(2, 'Macera', '2025-02-15 19:27:52'),
(3, 'Komedi', '2025-02-15 19:27:52'),
(4, 'Dram', '2025-02-15 19:27:52'),
(5, 'Fantezi', '2025-02-15 19:27:52'),
(6, 'Korku', '2025-02-15 19:27:52'),
(7, 'Gizem', '2025-02-15 19:27:52'),
(8, 'Psikolojik', '2025-02-15 19:27:52'),
(9, 'Romantik', '2025-02-15 19:27:52'),
(10, 'Bilim Kurgu', '2025-02-15 19:27:52'),
(11, 'Dilim of Life', '2025-02-15 19:27:52'),
(12, 'Spor', '2025-02-15 19:27:52'),
(13, 'Doğaüstü', '2025-02-15 19:27:52'),
(14, 'Gerilim', '2025-02-15 19:27:52');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `animes`
--
ALTER TABLE `animes`
  ADD PRIMARY KEY (`id`);

--
-- Tablo için indeksler `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `animes`
--
ALTER TABLE `animes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `genres`
--
ALTER TABLE `genres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
