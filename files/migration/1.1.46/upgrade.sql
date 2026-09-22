-- Anime Tracker - Migration 1.1.46
-- https://www.sicakcikolata.com
-- Copyright (C) 2025-2026 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- =====================================================================
-- 1.1.46 - IZLEME GUNLUGU: user_watch_log + Son Izlenenler donem filtreleri
-- =====================================================================
--
-- SORUN
--
-- "Gecen hafta ne izledim?" sorusunun durust bir cevabi yoktu.
-- user_anime.updated_at TEK damgadir: satira dokunan her yazma onu
-- tazeler - not duzenlemesi, durum degisimi, izlenen bolum artisi hepsi
-- ayni damgaya yazar; MAL / AniList ice aktarimi ise butun satirlari
-- aktarim gunuyle damgalar. Satir yalniz "simdi neredeyim"i bilir,
-- "oraya nasil geldim"i bilmez. 1.1.45'in Son Izlenenler sekmesi bu
-- damgaya gore siraliyordu ve bir haftalik / aylik gorunum sunamiyordu.
--
-- COZUM: BIR GUNLUK TABLOSU
--
--   user_watch_log(id, user_id, anime_id, episode_from, episode_to, logged_at)
--
-- izlenen bolum sayisinin HER degisimine bir satir. Tek yazan yer
-- ua_set_state() (user_anime_helpers.php): yazdigi deger saklanandan
-- farkliysa satiri ekler. Bolum sayisini yazan her yol zaten oradan
-- gecer (liste ve detaydaki +/- ucu, duzenleme formu, add_anime,
-- MAL / AniList / JSON ice aktarimi); ice aktarim animeye TEK satir
-- birakir (0 -> 24, aktarim ani) - olan da budur. "-" de yazilir;
-- donem gorunumleri anime basina toplar, geri alinan +1 sifira iner.
--
-- recent.php?tab=watched altinda filtre seridi: 1 hafta / 1 ay / hepsi /
-- tarih araligi. Hafta, ay ve aralik YALNIZ gunlukten okur ve "N animede
-- M bolum" ozeti verir. "Hepsi" gunlugu olan animeleri son gunluk
-- satiriyla, gunlugu olmayan eski animeleri user_anime.updated_at ile
-- ve "gunluk oncesi" etiketiyle birlikte listeler (10 satir, 1.1.45
-- gibi). Filtre adreste yasar, tercih olarak saklanmaz.
--
-- TOHUM YOK (1.1.44 §104 Karar 6 ilkesi). Var olan user_anime satirlari
-- icin sahte gunluk satiri uretilmez: "updated_at'te su kadar bolum
-- izledi" demek, bilinmeyen bir seyi bilir gibi yapmak olurdu. Eski
-- izlemeler yalniz "hepsi" gorunumunde, etiketli gorunur; gunluk ilk
-- bolum isaretlemeyle dolmaya baslar.
--
-- YEDEK: liste disa aktarimi her animenin altina 'watch_log' dizisini
-- koyar; geri yukleme onu oldugu gibi (tekrarsiz) geri yazar ve o kayit
-- icin otomatik gunluk satirini kapatir. 1.1.46 oncesi bir yedekte dizi
-- yoktur -> geri yukleme sicramasi tek satir olarak yazilir.
--
-- MERKEZ KATALOG: ALTER GEREKMEZ. Tablo kurulum-yereldir, tele girmez;
-- catalog.php / admin_push.php bu tabloyu bilmez.
--
-- Runner yorumlari temizler, tek CREATE'i calistirir; IF NOT EXISTS
-- oldugu icin yeniden calistirilabilir (ayrica 1050 yutulur) ve
-- settings.version 1.1.46'ya tasinir. schema.sql'de de ayni tablo var
-- (taze kurulum zinciri).
-- =====================================================================

CREATE TABLE IF NOT EXISTS `user_watch_log` (
  `id`           int(11) NOT NULL AUTO_INCREMENT,
  `user_id`      int(11) NOT NULL,
  `anime_id`     int(11) NOT NULL,
  `episode_from` int(11) NOT NULL,
  `episode_to`   int(11) NOT NULL,
  `logged_at`    datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_uwl_user_time`  (`user_id`, `logged_at`),
  KEY `idx_uwl_user_anime` (`user_id`, `anime_id`),
  CONSTRAINT `fk_uwl_user`  FOREIGN KEY (`user_id`)  REFERENCES `users` (`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_uwl_anime` FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
