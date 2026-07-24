-- Anime Tracker - Migration 1.1.20
-- https://www.sicakcikolata.com
-- Copyright (C) 2025 Okan Sumer
-- Licensed under GNU General Public License v2
--
-- 1.1.20 ekle/duzenle formundaki ayri "Ingilizce Baslik" kutusunu kaldirir.
-- Bir animenin isimleri artik TEK bir yerde girilir: alternatif isim
-- listesinde, her satirin yanindaki dil kutusuyla.
--
--     Anime Ismi        : Tonari no Totoro
--     Alternatif Isimler: My Neighbor Totoro   [Ingilizce]
--                         となりのトトロ         [Japonca]
--                         Totoro               [Dil belirtilmedi]
--
-- SEMA DEGISIKLIGI YOKTUR. Yeni tablo, kolon veya ayar anahtari gerekmez.
-- Dil bilgisi mevcut animes.alternative_titles metnine, her ismin onune
-- konan istege bagli bir [xx] etiketiyle yazilir:
--
--     [en]My Neighbor Totoro|[ja]となりのトトロ|Totoro
--
-- NEDEN KOLON DEGIL DE ETIKET:
-- title_english kolonu 0.7.2'den kalmadir ve yalnizca Ingilizceyi anlatir.
-- Turkce bir baslik istendiginde ayni yol turkish_title kolonu,
-- display_title()'a yeni bir dal, katalog teline yeni bir alan ve merkez
-- katalog host'unda elle bir ALTER demek olurdu - her dil icin bir kez.
-- Dil listenin ICINE tasindiginda yeni dil eklemek
-- files/functions/title_lang_helpers.php icindeki title_lang_codes()
-- haritasina tek satir eklemekten ibaret hale gelir.
--
-- NEDEN [xx] BICIMI:
-- Yalin "en:" oneki gercek basliklari yanlis okurdu - "Re:Zero kara
-- Hajimeru Isekai Seikatsu" tam olarak iki harf ve iki nokta ust uste ile
-- baslar. Koseli parantez + beyaz liste kontrolu (parse_alt_titles() yalniz
-- title_lang_codes() icindeki bir kodu etiket sayar) etiketsiz bir basligin
-- asla etiketli sanilmamasini garanti eder.
--
-- title_english KOLONU DURUYOR ve display_title() hala onu okuyor; ama artik
-- kullanici DEGIL, kaydetme kodu doldurur: listedeki [en] etiketli isimden
-- turetilir. Boylece bu surum tek bir gosterim yuzeyine dokunmaz. Gosterimin
-- etiketlere tasinmasi ve kolonun emekli edilmesi 1.1.21'e birakildi.
--
-- ------------------------------------------------------------------
-- MERKEZ KATALOG SUNUCUSUNDA ELLE ISLEM GEREKMEZ
-- ------------------------------------------------------------------
-- alternative_titles zaten bir metin kolonudur ve tipi degismedi; etiketli
-- metin mevcut senkron zincirinden oldugu gibi gecer. catalog_server/
-- tarafinda ALTER YOK (kiyas: 1.1.3 is_adult, 1.1.10 status enum, 1.1.17
-- country - onlarin hepsi elle ALTER istiyordu, bu istemiyor).
--
-- Tek gozlem: bu kurulum katalogu push ettikten sonra HENUZ 1.1.20'ye
-- gecmemis bir istemci [en] onekini ham metin olarak gorur (yalniz kendi
-- duzenleme formunda; arama ve gosterim etkilenmez). Istemci guncellenince
-- onek etikete donusur; veri kaybi yoktur.
--
-- ------------------------------------------------------------------
-- ASAGIDAKI GERI DOLDURMA (BACKFILL) NE YAPAR
-- ------------------------------------------------------------------
-- Mevcut satirlarda Ingilizce isim yalnizca title_english'te duruyor,
-- listede degil. Iki UPDATE onu listeye [en] etiketli olarak ekler. Kazanci
-- somut: index.php aramasi title + alternative_titles uzerinde calisir, yani
-- "My Neighbor Totoro" aramasi bugune kadar SONUC VERMIYORDU; backfill'den
-- sonra veriyor.
--
-- Iki WHERE kosulu de atlama (skip) kosuludur:
--   1. Listede zaten bir [en] etiketi varsa dokunma - kurator elle girmis.
--   2. Ayni metin listede etiketsiz duruyorsa dokunma - eklemek ayni ismi
--      iki kez gosterirdi. Bu satirlar zararsiz kalir: duzenleme formu
--      alt_titles_for_form() ile onlari yerinde Ingilizce isaretler ve ilk
--      kayitta etiket kaliciya doner.
--
-- Kosul 2'deki LIKE, title_english icindeki % ve _ karakterlerini joker
-- olarak yorumlar; nadiren "zaten var" yanilgisi uretebilir. Sonuc zararsiz:
-- o satir etiketsiz kalir, formu acan kurator yine Ingilizce isaretli gorur.
--
-- Her iki UPDATE de KENDI KOSULUNU SIFIRLAR (calistiktan sonra satir [en]
-- tasidigi icin kosul 1'e takilir), dolayisiyla migration tekrar
-- calistirilsa bile ikinci bir kopya eklenmez.
--
-- catalog_requests AYNI ISLEMI ALIR cunku o tablo animes'in alan-alan
-- ikizidir (uye oneri akisi); atlanirsa onaydaki bir onerinin Ingilizce ismi
-- kabul aninda etiketsiz kalirdi.
--
-- Runner bu yorum satirlarini temizler, asagidaki iki UPDATE'i calistirir ve
-- settings.version'i 1.1.20'ye tasir.

UPDATE `animes`
   SET `alternative_titles` =
       CASE WHEN `alternative_titles` IS NULL OR `alternative_titles` = ''
            THEN CONCAT('[en]', `title_english`)
            ELSE CONCAT(`alternative_titles`, '|[en]', `title_english`)
       END
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%')
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|')
           NOT LIKE CONCAT('%|', `title_english`, '|%'));

UPDATE `catalog_requests`
   SET `alternative_titles` =
       CASE WHEN `alternative_titles` IS NULL OR `alternative_titles` = ''
            THEN CONCAT('[en]', `title_english`)
            ELSE CONCAT(`alternative_titles`, '|[en]', `title_english`)
       END
 WHERE `title_english` IS NOT NULL
   AND TRIM(`title_english`) <> ''
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|') NOT LIKE '%|[en]%')
   AND (`alternative_titles` IS NULL
        OR CONCAT('|', `alternative_titles`, '|')
           NOT LIKE CONCAT('%|', `title_english`, '|%'));
