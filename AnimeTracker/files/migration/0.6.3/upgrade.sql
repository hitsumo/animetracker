-- Surum 0.6.3 - Sema senkronizasyon migration'i
--
-- 0.6.2 ve onceki manuel ALTER'larin migration kayit altina alinmasi.
-- Iki kolon (end_date, user_synopsis) onceki bir gelistirme oturumunda
-- schema.sql'e eklendi (yeni kurulumlar dogru aldi) ama upgrade.sql
-- migration'i yazilmadi (mevcut kurulumlar atladi). Bu yarim deploy
-- 26 Mayis 2026 oturumunda kesfedildi: sicakcikolata.com DESCRIBE'inda
-- iki kolonun yoklugu admin_sync.php push'unu kirdi.
--
-- Bu migration tum mevcut kurulumlari lokal sema ile esitler.
-- Idempotent: kolonlar zaten varsa MariaDB error code 1060 (Duplicate
-- column) doner, migration_manager.php isIdempotentError() whitelist'inde
-- (1050/1060/1061/1091). Re-run guvenli.
--
-- proje_durumu_15.md detay (26 Mayis 2026 aksam oturumu).

-- end_date: Last episode air date. Only meaningful when
-- status = 'Yayın Tamamlandı'. NULL for ongoing anime.
-- Pozisyon: release_date'in hemen alti (mantiksal date pair).
ALTER TABLE animes ADD COLUMN end_date DATE DEFAULT NULL AFTER release_date;

-- user_synopsis: Optional per-user alternative or personal take on
-- the plot. NEVER touched by catalog sync, NEVER sent to the server
-- by admin_sync. Appears as a second "Kendi Yorumum" box on
-- anime_details.php when non-empty, hidden when NULL.
-- Pozisyon: synopsis'in hemen alti.
ALTER TABLE animes ADD COLUMN user_synopsis TEXT DEFAULT NULL AFTER synopsis;
