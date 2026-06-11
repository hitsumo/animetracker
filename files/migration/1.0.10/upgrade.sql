-- =====================================================================
-- Surum 1.0.10 - watch_status NULL destegi ("secim yapilmamis")
-- =====================================================================
--
-- user_anime.watch_status NULL kabul eder, kolon DEFAULT'u NULL olur.
-- NULL = "secim yapilmamis": kullanicinin animesi var (not/bolum icin
-- satir olusmus olabilir) ama izleme durumu secimi yapilmamistir.
-- Satirin hic olmamasi LEFT JOIN uzerinden ayni NULL olarak okunur;
-- iki bicim de ayni sekilde render edilir.
--
-- Mevcut satirlara DOKUNULMAZ: eldeki 'PlanToWatch' degerlerinin
-- bilincli secim mi yoksa eski kolon DEFAULT'unun urunu mu oldugu
-- geriye donuk ayirt edilemez; veri donusumu yapilmaz, yalnizca kolon
-- tanimi degisir. Bundan sonra kismi yazimlar (orn. yalniz not) yeni
-- satirda PlanToWatch uretmez, NULL birakir.
--
-- MODIFY tekrar calistirilabilir (idempotent) - ayni tanima cekmek
-- hata uretmez.

ALTER TABLE `user_anime`
  MODIFY `watch_status` enum('Watched','Watching','PlanToWatch','OnHold','Dropped') DEFAULT NULL;
