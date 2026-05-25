-- Surum 0.6.1 - Duygu Etiketleri v1 (single-user)
--
-- 0.6 sonrasi ilk patch. Yeni tablo eklemesi; mevcut sema veya
-- veriye DOKUNULMAZ (backward compatible). Bu migration risksiz:
-- 0.6'da yayinda olan her sey yayinda kalir, sadece yeni bir tablo
-- eklenir.
--
-- Surum icerigi:
--   - Yeni tablo: user_anime_emotion
--     Kullanicinin animeye koydugu duygu isaret(ler)i (1-3 adet).
--     Oy/puan DEGIL - isaret. KARARLAR Bolum 8 "Duygu etiketleri"
--     v1 spec'ine gore. Felsefe: "bir anime tek duyguya
--     indirgenmesin" - coklu isaret destegi.
--
-- Veri modeli kararlari:
--   - PRIMARY KEY uclu: (user_id, anime_id, emotion). Her duygu
--     ayri satir - coklu isaret gereksiniminin DB karsiligi
--     (1 anime + 1 user + N farkli emotion = N satir, N <= 3).
--   - Ust sinir 3 PHP tarafinda dogrulanir. DB-level garanti YOK
--     (trigger yerine endpoint kontrolu daha sade ve gozlemlenebilir).
--   - emotion VARCHAR(32), enum DEGIL. Izin verilen liste
--     functions.php emotion_options() helper'inde tek kaynak
--     olarak tutulur. Yeni etiket eklemek icin ALTER MODIFY
--     migration gerekmez - admin karariyla helper guncellenir,
--     deploy sonrasi anlik aktive. (0.6 ASCII migration dersi:
--     enum modify pahali. KARARLAR "set sabit baslar, admin
--     karariyla genisler" felsefesi VARCHAR'i hakli kilar.)
--   - user_id DEFAULT 1: single-user modda her zaman 1. Faz 2
--     multi-user gecisinde ayni tablo paylasilir; yeni kullanicilar
--     kendi id'leri ile eklenir. KARARLAR Yol 4 sirasi geregi
--     migration olmadan multi-user'a tasinir.
--   - users(id) FK YOK su an: single-user modda users tablosu
--     yok. Faz 2'de users tablosu olusunca FK ek migration ile
--     eklenir (CONSTRAINT ADD FOREIGN KEY).
--   - animes(id) FK ON DELETE CASCADE: anime silinirse ona bagli
--     duygu isaretleri de silinir. Catalog reconvergence (Karar 1B
--     patern) burada gecerli DEGIL - duygu isaretleri pure
--     user-scope veridir, paylasimli katalog kavrami yok.
--   - created_at: kullanicinin isareti ne zaman koydugu. Ileride
--     "son N gun icinde isaretlediklerim" gibi sorgular ve veri
--     yedekten geri yukleme cakisma yonetimi icin yararli olabilir.
--   - idx_anime: aggregated dagilim sorgusu (anime_id WHERE -> COUNT
--     per emotion). Single-user'da minimal etki, Faz 2 multi-user'da
--     degerli olur.
--   - idx_emotion: filtre sorgusu icin yer tutucu ("beni guldurmus
--     tum animeler"). Recommendations sayfasi 0.6.2+'da kullanir.
--
-- Idempotency: CREATE TABLE IF NOT EXISTS - migration_manager.php
-- isIdempotentError 1050 (Table already exists) zaten handle eder,
-- ama IF NOT EXISTS niyeti kodda da gosterir. Re-run guvenli.

CREATE TABLE IF NOT EXISTS `user_anime_emotion` (
  `user_id`    int(11) NOT NULL DEFAULT 1,
  `anime_id`   int(11) NOT NULL,
  `emotion`    varchar(32) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `anime_id`, `emotion`),
  KEY `idx_anime`   (`anime_id`),
  KEY `idx_emotion` (`emotion`),
  CONSTRAINT `fk_uae_anime`
    FOREIGN KEY (`anime_id`) REFERENCES `animes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---
-- watch_status sutununa DEFAULT 'PlanToWatch' ekle.
--
-- Sebep: 0.6.1 Docker test sirasinda catalog_import.php INSERT'leri
-- "Data truncated for column 'watch_status'" hatasi verdi. Catalog
-- endpoint'i watch_status alanini DONDURMEZ (kullanici-scope alan,
-- catalog paylasimli degil - KARARLAR Bolum 5). INSERT bu alani
-- atlayinca MariaDB strict mode bos string '' yazmaya calisir,
-- enum'da olmayan deger reddedilir.
--
-- XAMPP MariaDB'sinde strict mode farkli yapilandirildigi icin
-- bu sorun ortaya cikmamis (sessiz uyari + bos enum yazimi olmus
-- olabilir - veri tutarsizligi riski). Docker MariaDB 10.11 strict
-- mode aktif olunca patladi, bug yakalandi.
--
-- Cozum: schema-level DEFAULT 'PlanToWatch'. Yeni anime ekleme
-- semantik olarak da bu deger ile baslamali ("izlemek istiyorum,
-- henuz baslamadim"). Onceki yuklemeler icin (sema 0.5+ olan tum
-- kurulumlar) bu ALTER DEFAULT eklemekle yetinir - veri tipine
-- veya mevcut satirlara dokunmaz, sadece kolon metadata'si
-- guncellenir. Risk: minimal. Idempotent: ayni MODIFY tekrar
-- calistirilirsa aynisini tekrar atar, hata vermez.
-- ---

ALTER TABLE `animes`
  MODIFY COLUMN `watch_status`
    enum('Watched','Watching','PlanToWatch','OnHold')
    NOT NULL DEFAULT 'PlanToWatch';
