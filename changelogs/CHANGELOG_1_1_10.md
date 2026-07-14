# Anime Tracker 1.1.10

**Yayin tarihi:** 2026-07-14

## Yeni ozellik

- **Yayin durumuna uc yeni deger.** Bir animenin yayin durumu artik yalniz
  "Yayin Devam Ediyor" ve "Yayin Tamamlandi" degil; su uc durum da secilebilir:
  - **Yayin Baslamadi** - henuz yayina baslamamis (yakinda) animeler.
  - **Secim Yapilmadi** - durumu belirtilmemis / bilinmeyen animeler. Yeni
    animelerde form artik bu deger on-secili gelir; onceki gibi zorunlu bir
    "finished/ongoing" tahmini istemez.
  - **Yayin Iptal Edildi** - yayini iptal edilmis animeler.
- Yeni durumlar ekleme ve duzenleme formundaki durum listesinde, ana sayfadaki
  yayin durumu filtresinde ve istatistik/gosterim ekranlarinda gorunur.
- **"Yayin Baslamadi" -> "Yayin Devam Ediyor" otomatik gecis.** "Yayin Baslamadi"
  bir cikmaz degil: bir anime gercekten yayina basladiginda kendiliginden
  "Yayin Devam Ediyor"a gecer - "Devam Ediyor -> Tamamlandi" gecisinin zaten
  otomatik oldugu gibi. Boylece yayin durumu yasam dongusu tamamlanir.

## Nasil calisir (teknik)

- `animes.status` (ve ikizi `catalog_requests.status`) enum'una uc yeni deger
  EKLENDI (siralari degistirilmeden sona eklendi, mevcut kayitlar aynen kalir).
  Migration `migration/1.1.10/upgrade.sql` ile her kurulumda otomatik uygulanir
  (MODIFY idempotent - tekrar calistirilabilir).
- Yayin durumunun etikete cevrimi artik tek kaynaktan yapilir: yeni
  `broadcast_status_helpers.php` (`broadcast_status_label()` /
  `broadcast_status_options()`) - onceden her ekranda tekrarlanan if/elseif
  bloklari yerine, watch_status yardimci ailesiyle ayni kalip.
- Dile duyarli etiketler: yeni durumlar Ingilizce arayuzde de cevrilir
  (`index.broadcast.not_started` / `.unselected` / `.cancelled`).

## Ice aktarim eslemesi

- **AniList** icin durum artik neredeyse birebir eslenir (onceden bes durum ikiye
  katlaniyordu): FINISHED -> Tamamlandi, RELEASING/HIATUS -> Devam Ediyor,
  NOT_YET_RELEASED -> Baslamadi, CANCELLED -> Iptal Edildi.
- **AnimeSchedule** otomatik doldurmasinda "Upcoming" -> "Yayin Baslamadi".
- Bilinmeyen / durumsuz ice aktarimlar (eski MAL disa aktarimi airing bilgisi
  tasimaz) artik "Tamamlandi" yerine "Secim Yapilmadi" varsayilir.

## Otomatik gecis (Baslamadi -> Devam Ediyor)

- Aired-sync (`syncAllOngoingAiredEpisodes`, sayfadan ve cron'dan `sync_aired.php`
  ile kosar) artik yalniz "Yayin Devam Ediyor" degil, "Yayin Baslamadi"
  satirlarini da tarar. AnimeSchedule timetable'inda bir satirin YAYINLANMIS bir
  bolumu gorundugu an (gelecek tarihli bolumler `isTimetableRowAired` ile zaten
  atlanir) yayin baslamis demektir; o satir "Yayin Devam Ediyor"a terfi eder
  (tum ham yayini coktan bittiyse dogrudan "Yayin Tamamlandi"ya). Ek API cagrisi
  yok - ayni haftalik timetable istegi hem devam edenleri gunceller hem
  baslayanlari yakalar. Yeni `started` sayaci sync ozetine eklendi (web mesaji +
  cron STDOUT).
- Sinir: terfi yalniz `anime_schedule_link` + `mal_id` olan animelerde calisir;
  bunlari olmayan bir "Baslamadi" animeyi elle "Devam Ediyor" yapmak gerekir.
  Terfi `next_episode_date` hesaplamaz (aired-sync zaten hicbir zaman
  hesaplamaz); geri sayim `broadcast_day`/`broadcast_time`'a baglidir (kozmetik).

## Bakim araci

- **`tek_kullanimlik/anilist_airing_backfill.php` 1.1.10'a guncellendi.** 1.1.9'da
  yalnizca "not-finished -> Yayin Devam Ediyor" tek kovaya katliyordu; artik her
  animes.mal_id'yi AniList'e sorup dort HEDEF duruma AYRI UPDATE uretir
  (anilist_airing_status_to_enum ile ayni esleme): NOT_YET_RELEASED -> Yayin
  Baslamadi, CANCELLED -> Yayin Iptal Edildi, RELEASING/HIATUS -> Yayin Devam
  Ediyor, FINISHED -> Yayin Tamamlandi. Bir satir yalniz hedef mevcut durumdan
  FARKLIYSA yazilir. AniList'in ayni idMal icin cift/tutarsiz kayit dondurdugu
  nadir durumlar "elle incele"ye ayrilir (otomatik dokunulmaz). "-> Yayin
  Tamamlandi" blogu yorum satiri olarak cikar (ham durum degisimi aired/total'i
  form gibi uzlastirmaz). Ayni pacing/retry/resume tasarimi korundu.

## Merkez katalog notu (ONEMLI)

- Merkez katalog sunucusunda (`catalog_server/`) MigrationManager CALISMAZ.
  Sunucu tarafindaki `animes.status` enum'u ELLE ayni bes degere ALTER
  edilmelidir; aksi halde yeni bir durum tasiyan push reddedilir:
  ```sql
  ALTER TABLE `animes`
    MODIFY `status` enum('Yayın Tamamlandı','Yayın Devam Ediyor',
      'Yayın Başlamadı','Seçim Yapılmadı','Yayın İptal Edildi') NOT NULL;
  ```

## Degisen dosyalar

- schema.sql (animes.status + catalog_requests.status enum'lari)
- migration/1.1.10/upgrade.sql (yeni - iki tabloyu ALTER)
- functions/broadcast_status_helpers.php (yeni yardimci)
- functions.php (yeni yardimciyi yukler)
- functions/anilist_import_helpers.php (bes durumlu esleme)
- functions/animeschedule_helpers.php (Upcoming eslemesi + Baslamadi tarama/terfi)
- sync_aired.php (cron ozetine started sayaci)
- add_anime.php, edit_anime.php (durum listesi + sunucu tarafi normalizasyon)
- index.php (filtre listesi)
- recent.php, statistics.php, anime_details.php, pending.php,
  admin/admin_pending.php, admin/admin_catalog_requests.php (etiket yardimcisi)
- list_settings.php (whitelist + varsayilanlar + web sync started sayaci)
- catalog_import.php, catalog_server/admin_push.php (varsayilan durum)
- lang/tr.php, lang/en.php (uc yeni etiket anahtari + started sonuc etiketi)
- tek_kullanimlik/anilist_airing_backfill.php (dort-durumlu geri-dolum)
- version.txt
