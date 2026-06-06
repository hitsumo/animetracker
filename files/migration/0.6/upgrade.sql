-- Surum 0.6 - Madde B: watch_status Turkce -> ASCII + 4. deger (OnHold)
--
-- Bu surum DB sema degisikligi iceriyor. 0.5.1 sonrasi ilk gercek
-- migration (0.5.5 / 0.5.6 / 0.5.7 / 0.5.8 hepsi bos halkaydi).
--
-- Surum icerigi:
--   - watch_status enum Turkce degerleri ASCII karsiliklarina cevrildi:
--       'Izlendi'           -> 'Watched'
--       'Izleniyor'         -> 'Watching'
--       'Izlenme Planlandi' -> 'PlanToWatch'
--   - 4. enum degeri 'OnHold' eklendi (UI'da "Izleme Ertelendi").
--     Semantik: kullanici izlemeye basladi, ara verdi, ilerleme
--     korunsun. Planlandi (henuz baslamadim) ile karistirilmamali.
--   - UI metni (kullaniciya gorunen) Turkce kalir. Donusum
--     functions.php icindeki watch_status_label() helper ile yapilir
--     (label map katmani).
--
-- Strateji: 3-adimli crash-safe migration.
--   Adim 1: ENUM'u gecici olarak genislet (TR + ASCII + OnHold) -
--           mevcut TR veriler hala gecerli, INSERT/UPDATE devam eder.
--   Adim 2: Mevcut TR verisini ASCII karsiligina UPDATE et.
--   Adim 3: ENUM'u sadece ASCII + OnHold'a daralt.
--
-- Idempotency: 3 adim da idempotent. Re-run guvenli; herhangi bir
-- adimda crash olursa, tekrar calistirma sorunsuz tamamlar.
--   - ALTER MODIFY her seferinde uygulanir, hata firlatmaz (mevcut
--     tipe esit ise no-op).
--   - UPDATE her seferinde calisir; mevcut veri zaten ASCII ise 0
--     satir etkilenir.
--
-- migration_manager.php ile uyum: isIdempotentError() 1060 (duplicate
-- column) icin yazilmis - bu migration ALTER MODIFY kullanir, 1060
-- firlatmaz. Adim 3 eger eksik UPDATE varsa "Data truncated" hatasi
-- firlatir; bu dogru davranis (sessiz veri kaybi yerine acik hata).

-- Adim 1: ENUM'u gecici olarak genislet (TR + ASCII + OnHold)
ALTER TABLE `animes` MODIFY `watch_status`
  enum('İzlendi','İzleniyor','İzlenme Planlandı',
       'Watched','Watching','PlanToWatch','OnHold') NOT NULL;

-- Adim 2: Mevcut TR verisini ASCII karsiligina cevir
UPDATE `animes` SET `watch_status` = 'Watched'     WHERE `watch_status` = 'İzlendi';
UPDATE `animes` SET `watch_status` = 'Watching'    WHERE `watch_status` = 'İzleniyor';
UPDATE `animes` SET `watch_status` = 'PlanToWatch' WHERE `watch_status` = 'İzlenme Planlandı';

-- Adim 3: ENUM'u sadece ASCII + OnHold'a daralt
ALTER TABLE `animes` MODIFY `watch_status`
  enum('Watched','Watching','PlanToWatch','OnHold') NOT NULL;
