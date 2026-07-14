-- =====================================================================
-- 1.1.10 - Broadcast status: three new values
-- =====================================================================
-- animes.status (and its catalog_requests twin) started life as a
-- two-value enum: 'Yayın Tamamlandı' (finished) and 'Yayın Devam Ediyor'
-- (ongoing). 1.1.10 appends three states so the lifecycle can be
-- expressed fully:
--   'Yayın Başlamadı'    - not yet aired (upcoming)
--   'Seçim Yapılmadı'    - no selection / unknown (new default fallback)
--   'Yayın İptal Edildi' - cancelled
--
-- Values are APPENDED so existing rows keep their enum ordinal and no
-- data is rewritten. MODIFY is naturally idempotent (re-running sets the
-- same definition, no error), which matches the migration runner's
-- re-run-safe contract.
--
-- NOTE: the central catalog server (catalog_server/) does NOT run this
-- migration - it has no MigrationManager. Its animes.status enum must be
-- ALTERed by hand on the server host to the same five values, otherwise
-- a pushed row carrying a new status is rejected. See KARARLAR /
-- central-catalog notes.
-- =====================================================================

ALTER TABLE `animes`
  MODIFY `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor','Yayın Başlamadı','Seçim Yapılmadı','Yayın İptal Edildi') NOT NULL;

ALTER TABLE `catalog_requests`
  MODIFY `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor','Yayın Başlamadı','Seçim Yapılmadı','Yayın İptal Edildi') DEFAULT NULL;
