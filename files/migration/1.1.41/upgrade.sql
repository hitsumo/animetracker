-- Anime Tracker - Migration 1.1.41
-- https://www.sicakcikolata.com
-- Copyright (C) 2025-2026 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- =====================================================================
-- 1.1.41 - Paylasimli MAL / AniDB kimligi: parca numarasi
-- =====================================================================
--
-- SORUN
--
-- Kaynaklar ayni yapimi farkli boyutta kaydediyor. MAL, Death Note:
-- Rewrite'i iki bolumluk TEK kayit (2994) olarak tutar; AniDB ayni
-- yapimi birer bolumluk IKI kayit olarak. Katalog AniDB gibi iki kayit
-- acmak, ikisini de MAL 2994'e baglamak istiyordu - ama animes.mal_id
-- UNIQUE oldugu icin ikinci kayit 1062 ile reddediliyordu ("bu MAL ID
-- zaten var"). Ters durum (AniDB tek, MAL iki) da var; anidb_id icin de
-- ayni cozum.
--
-- COZUM: PARCA NUMARASI
--
--   animes.mal_part    tinyint unsigned NOT NULL DEFAULT 1
--   animes.anidb_part  tinyint unsigned NOT NULL DEFAULT 1
--   UNIQUE idx_mal_id   (mal_id)    ->  UNIQUE idx_mal_id   (mal_id, mal_part)
--   UNIQUE idx_anidb_id (anidb_id)  ->  UNIQUE idx_anidb_id (anidb_id, anidb_part)
--
-- Var olan her satir kimliginin 1. parcasidir: DEFAULT 1 ile eski dunya
-- yeni dunyanin ozel hali olur (her kimlikte tek parca), bilesik anahtar
-- eski tek kolonlu anahtar gibi davranir, 8.000 satir icin hicbir sey
-- degismez. VERI TASIMA YOK.
--
-- Paylasim BILINCLI bir beyandir: formda kutu isaretlenmezse ayni
-- kimlikle ikinci kayit yine reddedilir (kazara cift kayit hala
-- engellenir); isaretlenirse kayit o kimlik icin siradaki bos parca
-- numarasini kendisi alir (MAX + 1). Kutu SAKLANMAZ - "paylasimli mi"
-- sorusu veriden turer (ayni kimligi tasiyan baska satir var mi), boylece
-- form ile tablo birbirinden ayri dusemez.
--
-- Neden bayrak degil gercek kolon: kimlikle satir adresleyen her yer (ice
-- aktarma, katalog senkronu, JSON yedek, kara liste, konu baglantisi)
-- HANGI parcayi kastettigini soyleyebilmeli. "2994" yetmez, "2994/2"
-- gerekir. Ayrintili gerekce ve kurallar: functions/identity_helpers.php.
--
-- ------------------------------------------------------------------
-- MERKEZ KATALOG - ELLE ISLEM GEREKIR (sira KRITIK)
-- ------------------------------------------------------------------
-- Iki kolon ve iki UNIQUE degisimi katalog teline giriyor: catalog.php
-- parcalari secer, admin_push.php parcayla eslestirir ve yazar. Merkez
-- sunucuda MigrationManager CALISMAZ; oradaki animes tablosuna ayni
-- ALTER'lar ELLE uygulanmali (1.1.3 / 1.1.17 / 1.1.31 kalibi):
--
--   1. Merkezde: ADD COLUMN mal_part, ADD COLUMN anidb_part (asagidaki
--      ilk iki ifade), sonra UNIQUE'leri bilesik yap (son dort ifade).
--      Once `SHOW INDEX FROM animes` ile index ADLARINA bakilir - eski
--      kurulumda ad farkliysa DROP satirindaki ad ona gore yazilir.
--   2. catalog_server/catalog.php + admin_push.php yeni surumu dagitilir.
--   3. Uygulama dagitilir (bu migration ilk sayfa acilisinda kosar).
--   4. Tam katalog push.
--
-- Ters sirada: once uygulama giderse parcali bir kaydin push'u merkezde
-- 1062 ile duser (merkez hala tek kolonlu UNIQUE); merkez ALTER
-- atlanirsa yeni catalog.php bilinmeyen kolonu secmeye calisir ve
-- catalog 503 + push duser (1.1.31'in dersi).
--
-- ------------------------------------------------------------------
-- YENIDEN CALISTIRILABILIRLIK
-- ------------------------------------------------------------------
-- Runner yorumlari temizler, `;` ile boler, 1050/1060/1061/1091'i yutar.
-- ADD COLUMN ikinci kosuda 1060 verir (yutulur). DROP INDEX ikinci
-- kosuda BILESIK index'i dusurur, ardindan ADD UNIQUE onu yeniden kurar -
-- sonuc ayni. Index hic yoksa DROP 1091 verir (yutulur), ADD UNIQUE
-- yine kurar. Bilesik ADD UNIQUE hicbir zaman 1062 (cift kayit) veremez:
-- eski tek kolonlu UNIQUE zaten her mal_id'nin tek oldugunu garanti
-- ediyordu ve butun parcalar 1'dir. Veri tasima yok, yarim kalmis kosu
-- yeni bir durum uretmez.
--
-- Index adi kurulumlarda `idx_mal_id` / `idx_anidb_id` (schema.sql'in
-- ilk gununden beri); 1.1.40'taki `idx_`/`fk_` ikiligi burada yok.
--
-- ------------------------------------------------------------------
-- YARIM YUKLEME RISKI
-- ------------------------------------------------------------------
-- Kolon EKLENDIGI icin (dusmedigi icin) eski dosyalar yeni semada
-- calismaya devam eder - parcayi okumazlar, DEFAULT 1 onlari tasir.
-- Tersi de calisir: yeni dosyalar eski semada... CALISMAZ - `mal_part`
-- kolonunu secen her sorgu 1054 verir. Bu yuzden migration dosyalarla
-- ayni pakette gider ve db.php uzerinden ILK istekte kosar; dosyalar
-- once, migration hemen arkasindan - normal akis.
--
-- functions.php ile functions/identity_helpers.php BIRLIKTE gitmeli:
-- yoksa "undefined function" ile her sayfa duser (1.1.38'in
-- relation_helpers kalibi).
-- =====================================================================

ALTER TABLE `animes`
  ADD COLUMN `mal_part` tinyint unsigned NOT NULL DEFAULT 1 AFTER `mal_id`;

ALTER TABLE `animes`
  ADD COLUMN `anidb_part` tinyint unsigned NOT NULL DEFAULT 1 AFTER `anidb_id`;

ALTER TABLE `animes` DROP INDEX `idx_mal_id`;

ALTER TABLE `animes` ADD UNIQUE KEY `idx_mal_id` (`mal_id`, `mal_part`);

ALTER TABLE `animes` DROP INDEX `idx_anidb_id`;

ALTER TABLE `animes` ADD UNIQUE KEY `idx_anidb_id` (`anidb_id`, `anidb_part`);
