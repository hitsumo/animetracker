-- Anime Tracker - Migration 1.1.40
-- https://www.sicakcikolata.com
-- Copyright (C) 2025-2026 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- =====================================================================
-- 1.1.40 - Sira bagi tabloya: `sequel` kenari, next_in_series EMEKLI
-- =====================================================================
--
-- KARARLAR_4 sec.94'te kararlastirilan uc adimlik yolun UCUNCU ve SON
-- adimi:
--
--     1.1.36  chain_name            (hat ADI)
--     1.1.38  anime_relations       (bagin TURU - sirasiz turler)
--     1.1.40  sequel tabloya, next_in_series emekli   <- bu dosya
--
-- (Yol haritasi bu adimi 1.1.39'a yazmisti; 1.1.39 araya giren bir
-- Search Console olcumune gitti. Geriye donuk numara verilemez.)
--
-- SORUN
--
-- 1.1.38'den beri iki kaydin bagi iki AYRI yerde duruyordu: sirasiz
-- turler anime_relations tablosunda, izleme SIRASI ise animes tablosunda
-- tek bir kolonda (next_in_series). O surumde `sequel` bilerek enum'a
-- konmamisti: iki kaynak ayni cift hakkinda celisemesin diye. Ama bu
-- ikilik kendi bedelini tasiyordu:
--
--   * Sira, yedege GIRMIYORDU. next_in_series yerel bir satir kimligidir
--     ve her kurulum satirlarini farkli numaralandirir; JSON geri
--     yuklemede bag sessizce kayboluyordu (1.1.36'nin migration'i bunu
--     acikca "var olan bir bosluk" diye kaydetmisti). Iliskiler ise
--     1.1.38'den beri kimlik dortlusuyle tasiniyor.
--
--   * Ayni soru iki ekrandan soruluyordu: "Siradaki Anime" kutusu ana
--     formda, oteki turler ana formun disindaki panelde. Kuratorun
--     "bu ikisinin bagi ne" sorusuna cevabi yerine gore degisiyordu.
--
--   * Kolon TEKILDI: bir kaydin tek devami olabiliyordu. Onculu birden
--     cok olabiliyordu (birden cok kayit ayni animeyi isaret edebilir),
--     yani asimetri zaten vardi ve kural yazilmisti ("ayni adli ILK
--     komsu, id sirasi").
--
-- COZUM
--
-- Sira artik anime_relations'ta bir KENARDIR:
--
--     (from_anime_id = B, to_anime_id = A, relation_type = 'sequel')
--     okunusu: "B, A'nin DEVAMIDIR"   (eski: A.next_in_series = B)
--
-- Karsi uctan bakinca etiket "Oncesi" olur (sequel <-> prequel), tipki
-- 1.1.38'in yan hikaye <-> ana hikaye ciftinde oldugu gibi. Yon GERCEKTIR
-- ve saklanir; normallestirme (kucuk id one) yalnizca simetrik turlerde
-- kalir.
--
-- "SIRA TEK YERDE" KURALI KORUNDU, YERI DEGISTI. 1.1.38'in celiski
-- gerekcesi bugun de gecerlidir ve baska bir mekanizmayla saglanir: BIR
-- CIFT EN COK BIR ILISKI TASIR (add_anime_relation.php, geri yukleme).
-- "A, B'nin devamidir" ile "A, B'nin alternatif versiyonudur" ayni anda
-- yazilamaz. 1.1.38'in "next_in_series ile bagli cifte iliski
-- kurulamaz" kontrolu (relation_error=chain) bu yuzden EMEKLI oldu -
-- korudugu durum artik var olamaz.
--
-- YURUYUSLER TABLOYU OKUR. Zincir basina donus, ileri yuruyus ve spoiler
-- kapisi (functions/series_helpers.php) artik `sequel` kenarini izler,
-- tek bir sorgu uzerinden (seriesChainNeighbours). 1.1.36'nin kurali
-- AYNEN durur: kenar yalnizca iki ucun zincir adi ayniysa izlenir
-- (chain_same). Adsiz veride davranis 1.1.39 ile birebir aynidir - bu
-- olculdu (asagida).
--
-- DALLANMA. Kolon gidince bir kaydin birden cok devami olabilir. Yeni bir
-- kural YAZILMADI: onculler icin 1.1.25'ten beri gecerli olan "ayni adli
-- ilk komsu (id sirasi)" kurali ileri yone de uygulandi. Hangi dalin
-- cizilecegine PROGRAM degil KURATOR karar verir - zincir adiyla
-- (sec.94'un ongordugu tam olarak buydu). Izlenmeyen dal, adi varsa
-- seriesChainAppendUnlinked() ile yine listeye girer.
--
-- ------------------------------------------------------------------
-- SEMA
-- ------------------------------------------------------------------
--
--   anime_relations.relation_type  enum'a 'sequel' EKLENDI (MODIFY)
--   animes.next_in_series          FK + index + kolon DUSURULDU
--
-- Index adi kurulumdan kuruluma degisir: schema.sql `idx_next_in_series`
-- der, eski kurulumlarda FK ile ayni adli (`fk_next_in_series`) bir KEY
-- durur. Ikisi de denenir (olmayan 1091 ile yutulur); DROP COLUMN zaten
-- yalniz o kolonu tasiyan her index'i kendiliginden goturur.
--
-- Asagidaki INSERT, var olan HER bagi tek seferde kenara cevirir:
--
--   * Iki ucu da var olan baglar (JOIN animes b) - FK zaten bunu
--     garanti ediyordu, JOIN yalnizca guvenlik agi.
--   * Kendini isaret eden bag atlanir (a.id <> a.next_in_series) -
--     validateNextInSeries bunu zaten engelliyordu.
--   * Ciftte ZATEN bir iliski varsa (hangi yonde, hangi turle olursa
--     olsun) bag atlanir - "bir cift, en cok bir iliski". Bu yalnizca
--     UYKUDAKI bir bag icin mumkundur: 1.1.38 aktif (ayni adli) bagli
--     cifte iliski kurdurmuyordu, ama adlari farkli oldugu icin zaten
--     izlenmeyen bir bagin ustune iliski girilebiliyordu. Boyle bir bag
--     zaten hicbir yuruyusu beslemiyordu; kuratorun ELLE yazdigi tur
--     kazanir.
--
-- UYKUDAKI BAGLAR DA CEVRILIR. Zincir adlari farkli oldugu icin
-- izlenmeyen bir bag (1.1.36'nin "uykudaki bag"i) silinmez, kenara
-- donusur; yuruyusler onu eskisi gibi YINE izlemez (chain_same), ama
-- detay sayfasindaki "Iliskili Animeler" bolumu onu "Devami"/"Oncesi"
-- basligi altinda GOSTERIR - cunku o bolum ham veridir. Kuratorun
-- girdigi veriyi migration'in kendi basina silmesi dogru olmazdi; ekranda
-- goren kurator istemiyorsa panelden siler.
--
-- ------------------------------------------------------------------
-- YENIDEN CALISTIRILABILIRLIK
-- ------------------------------------------------------------------
--
-- Runner (migration_manager.php) yorumlari temizler, `;` ile boler ve
-- 1050/1060/1061/1091 kodlarini yutar. Bu dosya 1.1.21'in kalibini
-- izler: kolonu okuyan bir migration, kolon onceden dusmusse 1054 verir
-- ve yukseltmeyi kilitler. O yuzden ILK ifade kolonu geri ekler (zaten
-- varsa 1060 yutulur), INSERT onu okur, SON ifadeler dusurur (zaten
-- yoksa 1091 yutulur). Yarim kalmis bir kosunun ardindan ikinci kosu:
-- MODIFY ayni sonucu verir, ADD COLUMN bos kolonu geri getirir, INSERT
-- 0 satir secer (kolon bos; ustelik NOT EXISTS zaten cevrilmis satirlari
-- da atlar), DROP'lar yine temizler. settings.version 1.1.40'a tasinir.
--
-- ------------------------------------------------------------------
-- DOGRULAMA
-- ------------------------------------------------------------------
-- Migration yerel veritabaninin bir KOPYASINDA gercek MigrationManager
-- mantigiyla kosuldu (1.1.26 -> 1.1.40, 14 migration), sonra IKINCI KEZ
-- kosuldu: 0 migration, tablo dokumu ayni. Cevrilen kenar sayisi kolondaki
-- bag sayisina esit; zincir kesfi ve spoiler oncul listesi, 1.1.39'un
-- yardimcilari ayni kopya uzerinde kosturularak BIREBIR karsilastirildi.
-- Ayrintili vaka listesi CHANGELOG_1_1_40.md ve proje_durumu_91.md'de.
--
-- ------------------------------------------------------------------
-- UYGULAMAYA OZELDIR
-- ------------------------------------------------------------------
-- next_in_series merkeze hic gitmiyordu; anime_relations da gitmiyor.
-- Katalog telinde degisen alan yok, merkez veritabaninda ELLE ALTER
-- GEREKMEZ, catalog_server/ altinda degisen dosya yok. (Merkez sunucunun
-- kendi animes tablosunda next_in_series kolonu varsa orada DURABILIR -
-- hicbir istemci kodu onu okumaz, catalog.php de secmez.)
--
-- ------------------------------------------------------------------
-- YARIM YUKLEME RISKI - DIKKAT
-- ------------------------------------------------------------------
-- Kolon dustugu icin ESKI bir dosya sunucuda kalirsa SELECT/INSERT/UPDATE
-- 1054 "Unknown column" ile duser:
--
--   edit_anime.php        UPDATE ... next_in_series = ?     kayit patlar
--   add_anime.php         INSERT ... next_in_series          ekleme patlar
--   catalog_import.php    INSERT ... next_in_series          KATALOG SENKRONU patlar
--   anime_details.php     SELECT ... WHERE next_in_series    detay patlar
--   functions/series_helpers.php  zincir yuruyusleri          seri sayfasi patlar
--
-- Ayrica functions/relation_helpers.php yeni turu tanimaz kalirsa
-- 'sequel' satirlari "Diger Iliski" diye etiketlenir (cokmez, yanlis
-- gosterir). Dosyalarin TAMAMI birlikte gitmeli; migration ilk sayfa
-- acilisinda kendiliginden kosar.
-- =====================================================================

ALTER TABLE `anime_relations`
  MODIFY `relation_type` enum('alternative_version','alternative_setting','side_story','summary','other','sequel') NOT NULL DEFAULT 'other';

ALTER TABLE `animes`
  ADD COLUMN `next_in_series` int(11) DEFAULT NULL;

INSERT INTO `anime_relations` (`from_anime_id`, `to_anime_id`, `relation_type`)
SELECT a.`next_in_series`, a.`id`, 'sequel'
  FROM `animes` a
  JOIN `animes` b ON b.`id` = a.`next_in_series`
 WHERE a.`next_in_series` IS NOT NULL
   AND a.`next_in_series` <> a.`id`
   AND NOT EXISTS (
         SELECT 1 FROM `anime_relations` r
          WHERE (r.`from_anime_id` = a.`next_in_series` AND r.`to_anime_id` = a.`id`)
             OR (r.`from_anime_id` = a.`id` AND r.`to_anime_id` = a.`next_in_series`)
       );

ALTER TABLE `animes` DROP FOREIGN KEY `fk_next_in_series`;

ALTER TABLE `animes` DROP INDEX `idx_next_in_series`;

ALTER TABLE `animes` DROP INDEX `fk_next_in_series`;

ALTER TABLE `animes` DROP COLUMN `next_in_series`;
