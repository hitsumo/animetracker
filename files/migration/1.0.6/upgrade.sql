-- =====================================================================
-- catalog_requests  (Faz 2 - online uye oneri/pending kuyrugu)
-- =====================================================================
-- Online uyeler "Listeyi Ice Aktar" yaptiginda, katalogda OLMAYAN
-- animeler buraya "pending" oneri olarak yazilir (animes'e DEGIL).
-- Boylece paylasimli katalog (animes) temiz kalir; moderator onaylayinca
-- animes'e gercek satir olarak gecer. animes'e hic girmedikleri icin ana
-- liste / istatistik / FK'ler etkilenmez, gizlilik tasarimdan gelir.
--
-- suggestion_status: 'pending' incelenmeyi bekliyor; 'approved' animes'e
--   gecirildi (kayit denetim icin durur); 'rejected' reddedildi.
-- suggested_by / reviewed_by: users.id (kullanici silinirse NULL).
-- Alanlar export JSON'undaki katalog kolonlariyla eslesir ki onaylama
-- aninda animes'e dolu bir satir uretilebilsin.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `catalog_requests` (
  `id`                  int(11) NOT NULL AUTO_INCREMENT,
  `mal_id`              int(11) DEFAULT NULL,
  `anidb_id`            int(11) DEFAULT NULL,
  `title`               varchar(255) NOT NULL,
  `title_english`       varchar(255) DEFAULT NULL,
  `alternative_titles`  text DEFAULT NULL,
  `status`              enum('Yayın Tamamlandı','Yayın Devam Ediyor') DEFAULT NULL,
  `total_episodes`      int(11) DEFAULT NULL,
  `mal_link`            varchar(255) DEFAULT NULL,
  `anidb_link`          varchar(255) DEFAULT NULL,
  `anime_schedule_link` varchar(255) DEFAULT NULL,
  `episode_interval`    int(11) DEFAULT 7,
  `broadcast_day`       varchar(20) DEFAULT NULL,
  `broadcast_time`      time DEFAULT NULL,
  `broadcast_timezone`  varchar(64) DEFAULT NULL,
  `synopsis_tr`         text DEFAULT NULL,
  `synopsis_en`         text DEFAULT NULL,
  `release_date`        date DEFAULT NULL,
  `end_date`            date DEFAULT NULL,
  `series_name`         varchar(255) DEFAULT NULL,
  `media_type`          enum('TV','Film','OVA','Special','ONA') DEFAULT NULL,
  `suggested_by`        int(11) DEFAULT NULL,
  `suggestion_status`   enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at`          timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at`         timestamp NULL DEFAULT NULL,
  `reviewed_by`         int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_creq_status` (`suggestion_status`),
  KEY `idx_creq_mal`    (`mal_id`),
  KEY `idx_creq_by`     (`suggested_by`),
  CONSTRAINT `fk_creq_user`
    FOREIGN KEY (`suggested_by`)  REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_creq_reviewer`
    FOREIGN KEY (`reviewed_by`)   REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
