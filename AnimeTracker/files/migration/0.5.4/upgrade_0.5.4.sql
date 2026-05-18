-- Anime Tracker 0.5.4 migration
-- Karar 1B: chronology_markers.source kolonu
--
-- Bu kolon, kullanicinin kendi ekledigi kronoloji isaretlerini
-- (source='user') katalogdan gelenlerden (source='catalog') ayirir.
-- Katalogdan Ice Aktar (catalog_import.php) artik sadece
-- source='catalog' satirlari siler; kullanicinin source='user'
-- isaretleri korunur (14 Nisan 2026 marker kaybi bug fix).
--
-- IDEMPOTENT NOT: source kolonu zaten varsa MySQL 1060 (Duplicate
-- column name) hatasi verir. migration_manager.php isIdempotentError()
-- 1060'i guvenli sayip atlar (satir 183-194), migration patlamaz.
-- Bu yuzden Karar 1B'yi elle uygulamis kurulumlarda (kolonu DB'ye
-- onceden eklemis olanlar) bu migration sorunsuz gecer.

ALTER TABLE `chronology_markers`
  ADD COLUMN `source` ENUM('catalog','user') NOT NULL DEFAULT 'user'
  AFTER `note`;
