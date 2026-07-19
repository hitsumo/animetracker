-- Anime Tracker - Migration 1.1.17
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- 1.1.17 animelere bir YAPIM ULKESI alani ekler ve index.php'ye ulkeye
-- gore filtreleme getirir.
--
-- NEDEN char(2) VE NEDEN KOD SAKLANIYOR:
-- Sutun ISO 3166-1 alpha-2 kodu tutar (JP, CN, KR, TW, US, FR). Kullanici
-- hicbir zaman kod GIRMEZ - ekle/duzenle formunda "Japonya" secer, DB'ye
-- JP yazilir, ekranda yine "Japonya" (Ingilizce arayuzde "Japan") gorunur.
-- Kod<->ad esleme country_helpers.php + lang dosyalarindadir.
--
-- Serbest metin yerine kod tercih edildi cunku animes tablosu MERKEZ
-- KATALOG'tur, yani tum uyeler ayni satirlari paylasir. Serbest metinde
-- "Japonya" / "Japan" / "japonya" ayni ulke icin uc ayri filtre degeri
-- uretirdi ve dil destegi mumkun olmazdi.
--
-- NULL = ulke girilmemis. Mevcut satirlar BILINCLI olarak bos birakilir
-- (backfill YOK): filtre listesi animes.country uzerinde DISTINCT ile
-- uretildigi icin, filtre once bos gorunur ve moderator doldurdukca
-- ulkeler tek tek belirir. Boylece hicbir animeye tahmin yoluyla yanlis
-- ulke damgalanmis olmaz.
--
-- catalog_requests AYNI SUTUNU ALIR cunku o tablo animes'in alan-alan
-- ikizidir (uye oneri akisi). Sutun orada olmazsa bir uyenin onerdigi
-- animenin ulkesi onay sirasinda sessizce dusardi.
--
-- ------------------------------------------------------------------
-- MERKEZ KATALOG SUNUCUSUNDA ELLE ALTER GEREKIR
-- ------------------------------------------------------------------
-- catalog_server/ bu migration'i CALISTIRMAZ - MigrationManager'i yoktur.
-- Sunucu host'unda asagidaki ALTER elle uygulanmali, YOKSA ulke tasiyan
-- push REDDEDILIR (ayni kalip: 1.1.3 is_adult, 1.1.10 status enum):
--
--   ALTER TABLE `animes` ADD COLUMN `country` char(2) DEFAULT NULL AFTER `media_type`;
--
-- Sira onemlidir: once sunucuda ALTER, sonra uygulama dagitimi, sonra push.
--
-- Runner bu yorum satirlarini temizler ve asagidaki iki ALTER'i calistirir;
-- tekrar calismada gelen "duplicate column" (1060) hatasi yok sayilir ve
-- settings.version 1.1.17'ye tasinir.

ALTER TABLE `animes`
  ADD COLUMN `country` char(2) DEFAULT NULL AFTER `media_type`;

ALTER TABLE `catalog_requests`
  ADD COLUMN `country` char(2) DEFAULT NULL AFTER `media_type`;
