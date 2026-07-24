-- Anime Tracker - Migration 1.1.21
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- 1.1.21, 1.1.20'de baslayan gecisin IKINCI ve son yarisidir. Gosterim artik
-- dogrudan [xx] dil etiketlerini okur, "Ingilizce basliklari goster" onay
-- kutusu gercek bir BASLIK DILI secimine (Romaji / Ingilizce / Japonca /
-- Turkce / Cince / Korece / Fransizca) doner ve animes.title_english kolonu
-- EMEKLI EDILIR.
--
-- ------------------------------------------------------------------
-- NEDEN KOLON DUSUYOR
-- ------------------------------------------------------------------
-- title_english 0.7.2'den kalmaydi ve yalnizca TEK bir dili anlatabiliyordu.
-- 1.1.20'den beri her alternatif isim kendi dil etiketini tasiyor, yani ayni
-- bilgi iki yerde duruyordu: etiketli listede (kaynak) ve bu kolonda (kopya).
-- Kopyayi tutmak yeni dillerin gosterime baglanmasini engelliyordu - Turkce
-- bir baslik SAKLANABILIYOR ama GOSTERILEMIYORDU. Kolon dustugu icin artik
-- tek kaynak var ve yeni dil eklemek yalnizca title_lang_codes() haritasina
-- bir satir eklemek demek.
--
-- VERI KAYBI YOKTUR: Ingilizce isim 1.1.20'nin geri doldurmasiyla zaten
-- alternative_titles icinde [en] etiketli duruyor. Yine de asagidaki IKI
-- kurtarma UPDATE'i DROP'tan ONCE kosuyor, cunku 1.1.20'den sonra yapilan bir
-- KATALOG SENKRONU alternative_titles'i sunucudaki ETIKETSIZ surumle ezmis
-- olabilir (catalog_import.php katalogu otorite kabul eder) ve o satirlarda
-- isim yeniden yalnizca title_english'te kalmis olabilir.
--
--   1. Ayni metin listede ETIKETSIZ duruyorsa -> yerinde etiketle.
--      (Eklemek ismi iki kez gosterirdi.)
--   2. Listede hic yoksa -> sona [en] etiketli ekle.
--
-- Sira onemli: once (1), sonra (2). Tersi olsaydi (2) etiketli bir kopya
-- ekler, ardindan (1) "zaten [en] var" diye atlar ve isim listede iki kez
-- gorunurdu.
--
-- Ikisi de KENDI KOSULUNU sifirlar (satir sonrasi [en] tasidigi icin
-- "NOT LIKE '%|[en]%'" kosuluna takilir), yani tekrar calistirma guvenlidir.
--
-- ------------------------------------------------------------------
-- TERCIH GOCU: display_title_english -> display_title_lang
-- ------------------------------------------------------------------
-- Eski tercih BOOLEAN'di ('1' = Ingilizce goster, '0'/yok = Romaji). Yenisi
-- bir DIL KODU tutar; bos string = Romaji. '1' olan her kullanici 'en'e
-- tasinir, '0' olanlar icin satir yazilmaz (varsayilan zaten Romaji).
-- Eski satirlar sonra silinir - okuyan kod kalmadi.
--
-- INSERT ... SELECT + ON DUPLICATE KEY: kullanicinin display_title_lang
-- satiri zaten varsa (migration yeniden kosuyorsa) ellenmemesi icin
-- degistirilmeden birakilir.
--
-- ------------------------------------------------------------------
-- MERKEZ KATALOG SUNUCUSUNDA ELLE ISLEM GEREKIR
-- ------------------------------------------------------------------
-- catalog_server/ bu migration'i CALISTIRMAZ - MigrationManager'i yoktur.
-- (Kiyas: 1.1.3 is_adult, 1.1.10 status enum, 1.1.17 country. 1.1.20 elle
-- islem GEREKTIRMEYEN istisnaydi; bu surum yeniden gerektiriyor.)
--
-- SIRA KRITIKTIR - once KOD, sonra ALTER:
--
--   1. catalog_server/catalog.php + admin_push.php YENI surumunu dagit.
--      (Yeni kod title_english'i ARTIK SECMEZ; kolon dursa da calisir.)
--   2. Sunucu DB'sinde:
--        ALTER TABLE `animes` DROP COLUMN `title_english`;
--   3. Uygulamayi dagit (bu migration otomatik kosar).
--   4. Tam katalog push.
--
-- Ters sirada (once ALTER) eski catalog.php dusmus bir kolonu SELECT etmeye
-- calisir ve TUM istemciler icin senkron patlar.
--
-- Sunucuda `catalog_requests` YOKTUR (o tablo yalniz uygulama tarafindadir),
-- bu yuzden sunucuda YALNIZCA yukaridaki tek ALTER calistirilir.
--
-- GERIDE KALAN KURULUMLAR: 1.1.20 ve oncesinde kalan bir istemci katalogtan
-- artik title_english ALAMAZ ve o istemcide Ingilizce basliklar Romaji'ye
-- duser. Veri kaybi degildir - isim etiketli listede gelmeye devam eder;
-- istemci 1.1.21'e gectiginde yeniden gorunur.
--
-- Runner bu yorum satirlarini temizler ve asagidaki ifadeleri sirayla
-- calistirir; tekrar calismada gelen 1091 (kolon yok) hatasi yok sayilir ve
-- settings.version 1.1.21'e tasinir.

-- (0) YENIDEN CALISTIRILABILIRLIK KALKANI.
--
-- Asagidaki kurtarma adimlari title_english'i OKUR. Kolon bu migration
-- tamamlanmadan once kaybolduysa (yarim kalmis bir kosu, ya da operatorun
-- merkez katalog icin verilen elle ALTER'i yanlislikla UYGULAMA veritabaninda
-- calistirmasi) o adimlar 1054 "Unknown column" verir - ve 1054 runner'in
-- idempotent listesinde OLMADIGI icin yukseltme kalici olarak KILITLENIRDI.
--
-- Cozum: kolonu once geri ekle. Zaten duruyorsa MySQL 1060 (duplicate column)
-- verir, runner onu yok sayar ve devam eder. Kayipsa bos (tumu NULL) olarak
-- geri gelir; kurtarma adimlari o satirlarda yapacak is bulamaz (dogru
-- davranis - isimler zaten etiketlenmis olduğu icin kaybolmustu) ve kolon
-- en sonda yine dusurulur. Her iki durumda da sonuc AYNI.
ALTER TABLE `animes` ADD COLUMN `title_english` varchar(255) DEFAULT NULL;

ALTER TABLE `catalog_requests` ADD COLUMN `title_english` varchar(255) DEFAULT NULL;

-- (1) Ayni metin listede etiketsiz duruyorsa: yerinde [en] ile etiketle.
UPDATE `animes`
   SET `alternative_titles` = TRIM(BOTH '|' FROM REPLACE(
           CONCAT('|', `alternative_titles`, '|'),
           CONCAT('|', `title_english`, '|'),
           CONCAT('|[en]', `title_english`, '|')
       ))
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND `alternative_titles` IS NOT NULL
   AND CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%'
   AND CONCAT('|', `alternative_titles`, '|')
       LIKE CONCAT('%|', `title_english`, '|%');

-- (2) Listede hic yoksa: sona [en] etiketli ekle.
UPDATE `animes`
   SET `alternative_titles` =
       CASE WHEN `alternative_titles` IS NULL OR `alternative_titles` = ''
            THEN CONCAT('[en]', `title_english`)
            ELSE CONCAT(`alternative_titles`, '|[en]', `title_english`)
       END
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%');

-- (3) Ayni kurtarma, oneri tablosu icin (animes'in alan ikizi).
UPDATE `catalog_requests`
   SET `alternative_titles` = TRIM(BOTH '|' FROM REPLACE(
           CONCAT('|', `alternative_titles`, '|'),
           CONCAT('|', `title_english`, '|'),
           CONCAT('|[en]', `title_english`, '|')
       ))
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND `alternative_titles` IS NOT NULL
   AND CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%'
   AND CONCAT('|', `alternative_titles`, '|')
       LIKE CONCAT('%|', `title_english`, '|%');

UPDATE `catalog_requests`
   SET `alternative_titles` =
       CASE WHEN `alternative_titles` IS NULL OR `alternative_titles` = ''
            THEN CONCAT('[en]', `title_english`)
            ELSE CONCAT(`alternative_titles`, '|[en]', `title_english`)
       END
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%');

-- (4) Tercih gocu: boolean -> dil kodu.
--
-- Satiri YERINDE yeniden adlandiriyoruz: ayni satir hem anahtar adini hem
-- degerini degistirir. (Ilk yazimda bu bir INSERT ... SELECT + ON DUPLICATE
-- KEY UPDATE idi; kaynak tablo hedefin AYNISI oldugu icin MySQL "Column
-- 'value' in field list is ambiguous" (1052) verip migration'i dusurdu.
-- Tek tablolu UPDATE'te belirsizlik yoktur.)
--
-- IGNORE su nadir durum icin: kullanicinin ZATEN bir display_title_lang
-- satiri varsa yeniden adlandirma birincil anahtari (user_id, name) ihlal
-- ederdi. IGNORE o satiri atlar, asagidaki DELETE eskisini yine temizler -
-- yani mevcut secim korunur. 1062 idempotent listesinde OLMADIGI icin bu
-- onemli: IGNORE olmasaydi migration orada patlardi.
UPDATE IGNORE `user_pref`
   SET `name` = 'display_title_lang', `value` = 'en'
 WHERE `name` = 'display_title_english' AND `value` = '1';

-- Geriye kalan (deger '0' ya da yeniden adlandirilamamis) eski satirlari sil;
-- okuyan kod kalmadi ve varsayilan zaten Romaji.
DELETE FROM `user_pref` WHERE `name` = 'display_title_english';

-- (5) Kolonu emekli et.
ALTER TABLE `animes` DROP COLUMN `title_english`;

ALTER TABLE `catalog_requests` DROP COLUMN `title_english`;
