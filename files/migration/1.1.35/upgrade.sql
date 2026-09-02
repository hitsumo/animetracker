-- Anime Tracker - Migration 1.1.35
-- https://www.sicakcikolata.com
-- Copyright (C) 2025-2026 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- =====================================================================
-- 1.1.35 - Ice aktarma kara listesi: silinen anime geri gelmesin
-- =====================================================================
--
-- SORUN
--
-- Katalog sahibi kataloga tohumu ACIK BIR ANILIST LISTESINDEN atiyor,
-- sonra elle AYIKLIYOR: AniDB karsiligi olmayan satirlari animes'ten
-- siliyor. Bu is akisi iki yerden birden bozuluyordu ve aslinda ikisi
-- ayni kusurun iki yuzu:
--
--   1. NE SILINDIGI HICBIR YERDE YAZMIYORDU. Ayiklama karari yalnizca
--      kuratorun aklindaydi; silinen satir arkasinda iz birakmaz. Iki
--      hafta sonra "bunu bilerek mi cikarmistim, yoksa hic gelmedi mi"
--      sorusunun cevabi yoktu.
--
--   2. BIR SONRAKI ICE AKTARMA HEPSINI GERI GETIRIYORDU. Bir ice
--      aktarma girdisi "eslesmemis" sayilir CUNKU katalogda oyle bir
--      satir yoktur - ki silme tam olarak o durumu uretir. Yani silmek,
--      animeyi bir sonraki ice aktarmanin TAZE ADAYI haline getiriyordu.
--      Ayiklama her seferinde bastan yapiliyordu.
--
-- Ayni kusurun ucuncu bir yuzu daha vardi ve o da bu surumde kapaniyor:
-- catalog_requests'teki tekillestirme yalnizca 'pending' satirlara
-- bakiyordu (list_settings.php). Yani REDDEDILMIS bir oneri de her ice
-- aktarmada yeniden aciliyordu - reddetmek "bir daha sorma" demiyordu.
-- Artik reddedilen bir animeyi kara listeye almak o donguyu kesiyor.
--
-- COZUM: SILME KENDI KAYDINI TUTAR, ICE AKTARMA O KAYDA BAKAR
--
-- Tek bir tablo iki isi birden goruyor:
--
--   - index.php'deki silme, satiri silmeden ONCE okur (image_path zaten
--     boyle okunuyordu) ve DELETE'ten sonra kimligini
--     `import_blacklist`e yazar. Kara listenin kendisi SILME DEFTERIDIR.
--   - AniList / MAL ice aktarmasi, eslesmeyen bir girdi icin
--     catalog_requests satiri acmadan once ayni tabloya sorar.
--
-- NEDEN AYRI BIR TABLO, catalog_requests'te BIR DURUM DEGIL
--
-- Akla ilk gelen "suggestion_status'e 'blacklisted' ekle" olurdu.
-- Yanlis olurdu: catalog_requests bir UYENIN ONERISIDIR ve `suggested_by`
-- ile o uyeye baglidir. Kara liste ise uyeden bagimsiz bir KATALOG
-- POLITIKASIDIR - "bu baslik yayimladigim katalogda yer almaz". Ustelik
-- kuratorun sildigi animelerin cogu icin ortada hic oneri satiri yoktur
-- (kendi ekledigi, kendi ice aktardigi satirlardir). Politikayi bir
-- kullanici oneri kuyruguna yamamak, iki farkli sorunun tek tabloda
-- bogusmasi demekti.
--
-- NEDEN YALNIZCA ONLINE
--
-- functions/blacklist_helpers.php icindeki her fonksiyon, MULTI_USER_MODE
-- kapaliysa hicbir sey yapmadan doner. Liste PAYLASILAN KATALOG icin bir
-- kuratorluk kararidir; tek kullanicili bir kurulumun paylasilan katalogu
-- ve kuratoru yoktur, sahibinin silmeleri KISISELDIR ve ice aktarmasi
-- kendi listesinde ne varsa getirmeye devam etmelidir. Kapi tek yerde
-- (yardimci dosyada) durdugu icin bu soz DENETLENEBILIR: blacklist_active()
-- aranir, self-host davranisinin degismedigi kanitlanir.
--
-- Kullanicinin istegi de birebir buydu: "yonetici icin kara liste gibi
-- birsey, buradaki animeler ice aktarda dusmesin; single kullanicida
-- dusebilir."
--
-- NEYE GORE ESLESIR
--
-- mal_id ve anidb_id - animes ile catalog_requests'in zaten anahtar
-- kabul ettigi kararli kimlikler. BASLIGA GORE ESLESMEZ: birbiriyle
-- ilgisiz iki yapim ayni basligi fazlasiyla sik paylasir ve eski bir
-- silmenin adi yuzunden mesru bir animeyi sessizce dusuren bir ice
-- aktarma, cozdugu sorundan daha kotu olurdu.
--
-- Iki kimlik de bos olan bir silme YINE DE kaydedilir (kuratorun neyi
-- cikardiginin defteridir) ama hicbir seyi engelleyemez - eslestirilecek
-- anahtar yoktur. Yonetici sayfasi bunu "Engellemez" rozetiyle acikca
-- soyler, oyle degilmis gibi yapmaz. ELLE eklemede ise en az bir kimlik
-- ZORUNLUDUR: orada niyet engellemektir, engellemeyen bir kayit sessiz
-- bir yalan olurdu.
--
-- KAPI YALNIZCA "ESLESMEYEN" DALDA DURUR
--
-- Katalogda GERCEKTEN duran bir anime kara listede olsa bile uyenin
-- listesine eklenebilir. Kara liste "katalogda olmasin" demektir,
-- "kimse izleyemesin" demek degil. Pratikte bu durum yalnizca silinip
-- sonra elle geri eklenmis bir animede olusur; yonetici sayfasi o satiri
-- "Katalogda var" rozetiyle isaretler ki kurator temizleyebilsin.
--
-- SEMA: TEK YENI TABLO, KOLON DEGISIKLIGI YOK
--
--   import_blacklist(id, mal_id, anidb_id, title, reason, note,
--                    created_by, created_at)
--
-- mal_id ve anidb_id UNIQUE ve NULL OLABILIR. MySQL bir UNIQUE sutunda
-- istenildigi kadar NULL'a izin verir - kimliksiz silme kayitlarinin
-- yan yana durabilmesini saglayan sey tam olarak budur (animes'teki
-- idx_mal_id / idx_anidb_id ile ayni kalip).
--
-- reason: 'deleted' (silme isleyicisi yazar) / 'manual' (yonetici
-- sayfasinda elle girilir). Ayni satirin nasil olustugu, ileride
-- "otomatik dusenleri temizle" gibi bir isteme cevap verebilmek icin
-- saklaniyor.
--
-- created_by: users.id, kullanici silinirse NULL'a duser
-- (catalog_requests.suggested_by ile ayni davranis).
--
-- YARIM YUKSELTMEDE FATAL YOK
--
-- Yardimcidaki her sorgu sarilidir. Dosyalar kopyalanip migration
-- kosmadiysa tablo yoktur ve bunun durust karsiligi "kara liste bos"tur:
-- okumalar bos doner, yazmalar gunluge yazilip birakilir. Alternatif
-- 1.1.31'in hata bicimiydi - eksik tek bir kolon butun sayfayi 503 ile
-- goturmustu. BIR ANIMEYI SILMEK, DEFTERININ TABLOSU HENUZ YOK DIYE
-- BASARISIZ OLMAMALIDIR. Yonetici sayfasi da fatal vermez, "migration
-- kosmamis olabilir" der.
--
-- ------------------------------------------------------------------
-- MERKEZ KATALOG SUNUCUSUNDA ELLE ALTER GEREKMEZ
-- ------------------------------------------------------------------
-- 1.1.32 ile ayni: tablo yalnizca UYGULAMANIN kendi veritabanindadir.
-- Katalog telinde yeni alan yoktur, push bicimi degismez, catalog_server/
-- altindaki hicbir dosya bu surumde degismedi. Kara liste MERKEZE
-- GONDERILMEZ - self-host bir kullanici katalogu senkronladiginda
-- kuratorun neyi kara listeye aldigindan etkilenmez.
--
-- (1.1.31'in dersi: merkezdeki ALTER atlanirsa catalog 503 verir ve push
-- duser. Bu surumde atlanacak bir ALTER yok.)
--
-- ------------------------------------------------------------------
-- DOGRULAMA
-- ------------------------------------------------------------------
-- Migration bu semanin bir KOPYASINDA gercek MigrationManager ile
-- kosuldu, sonra IKINCI KEZ kosuldu: birinci kosuda 1 migration,
-- ikincisinde 0; kolon/index dokumu birebir ayni kaldi ve ikinci kosudan
-- once eklenen satir yerinde durdu (tablo yeniden olusturulmamis).
-- Ayrintili vaka listesi CHANGELOG_1_1_35.md ve proje_durumu_86.md'de.
--
-- Runner asagidaki yorum satirlarini temizler ve tek ifadeyi calistirir;
-- tablo zaten varsa IF NOT EXISTS (ve gelirse 1050 hatasi) yok sayilir,
-- settings.version 1.1.35'e tasinir.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `import_blacklist` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `mal_id`     int(11) DEFAULT NULL,
  `anidb_id`   int(11) DEFAULT NULL,
  `title`      varchar(255) NOT NULL,
  `reason`     enum('deleted','manual') NOT NULL DEFAULT 'manual',
  `note`       varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blacklist_mal`   (`mal_id`),
  UNIQUE KEY `uq_blacklist_anidb` (`anidb_id`),
  KEY `idx_blacklist_created` (`created_at`),
  CONSTRAINT `fk_blacklist_user`
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
