<?php

/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sümer]
 
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
 published by the Free Software Foundation.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.
 
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 MA 02110-1301, USA.
 */


require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Sayfa dilini baslat (i18n).
// Sozlukleri yukler + current_lang() degerini cache'ler.
lang_init($pdo);

// Adding an anime writes to the shared catalog (animes) with source='local',
// which lands it in the approval queue (admin_pending.php) until a moderator
// promotes it to source='catalog'. So any logged-in user may add (mode=true);
// anonymous visitors cannot (the "add" button is hidden from them via
// can('add_anime')). No-op in self-host, where the owner adds directly and the
// row is visible immediately (single owner, no approval).
require_login();

// Master genre list for the dropdown. Fetched via the helper so the
// rest of the page does not need to know which table the data lives
// in. Returns rows with id and name.
$genres = getAllGenres($pdo);

// Seri adlarini cek (datalist auto-complete icin)
$seriesNames = getAllSeriesNames($pdo);

// Tum cumleleri cek (oneri sistemi icin tag input auto-complete kaynagi)
$allTags = getAllTags($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF kontrolu - form'dan gelen token oturumdaki ile eslesmiyorsa reddet.
    // hash_equals timing-safe karsilastirma yapar (bkz. functions.php csrf_verify).
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('add_anime.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
    }

    $title = $_POST['title'];
    $title_english = trim($_POST['title_english'] ?? '');
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'] ?? null;
    $aired_episodes = $_POST['aired_episodes'] ?? null;
    // 0.7 - filler bolum izleme gorunurluk bayragi (checkbox). Isaretli
    // degilse 0. Salt gorunurluk; kapali olmasi filler kayitlarini silmez.
    $filler_tracking = isset($_POST['filler_tracking']) ? 1 : 0;
    // 1.1.2 - yetiskin (+18) icerik bayragi (checkbox). Isaretli degilse 0.
    // Katalog meta verisi; gorunurluk ayri bir kullanici tercihiyle
    // (show_adult_content) yonetilir, bu bayrak yalniz animeyi +18 damgalar.
    $is_adult = isset($_POST['is_adult']) ? 1 : 0;
    $watched_episodes = $_POST['watched_episodes'] ?? 0;
    if ($watched_episodes === '') { $watched_episodes = 0; }
    $notes = $_POST['notes'];
    $alternative_titles = isset($_POST['alternative_titles']) ? array_filter($_POST['alternative_titles']) : [];
    // POST'tan gelen secilen turler. Bu degisken adi DB'den cekilen tum turler
    // listesi ($genres) ile cakismamasi icin kasten "posted_genres" olarak
    // adlandirildi - aksi halde form render asamasinda dropdown icin gereken
    // tum turler listesi silinirdi (variable shadowing).
    $posted_genres = !empty($_POST['genres']) ? explode(',', $_POST['genres']) : [];
    $watch_status = $_POST['watch_status'];
    // 1.0.10: '__unselected__' form sentineli NULL'a cevrilir - kullanici
    // bilincli olarak izleme durumu secmemistir; user_anime satiri NULL
    // watch_status ile dogar ("secim yapilmamis").
    if ($watch_status === '__unselected__') {
        $watch_status = null;
    }
    // Kisisel izleme tarihleri (1.1.0, elle giris). Bos string -> NULL
    // (DATE kolonu bos string kabul etmez).
    $watch_start_date  = ($_POST['watch_start_date']  ?? '') !== '' ? $_POST['watch_start_date']  : null;
    $watch_finish_date = ($_POST['watch_finish_date'] ?? '') !== '' ? $_POST['watch_finish_date'] : null;
    $next_episode_date = $_POST['next_episode_date'] ?? null;
    $anidb_link = $_POST['anidb_link'] ?? '';
    $mal_link = $_POST['mal_link'] ?? '';
	$anime_schedule_link = $_POST['anime_schedule_link'] ?? '';

    // MAL ve AniDB linkleri zorunlu - katalog senkronizasyonunda local
    // ile sunucu arasindaki eslesmeyi saglayan kimlik alanlari bunlardan
    // parse ediliyor.
    $validation_errors = [];
    $mal_id = parseMalId($mal_link);
    $anidb_id = parseAnidbId($anidb_link);

    if (empty(trim($mal_link))) {
        $validation_errors[] = t('add_anime.error.mal_link_required');
    } elseif ($mal_id === null) {
        $validation_errors[] = t('add_anime.error.mal_link_invalid');
    }

    // AniDB: hem /anime/ hem /episode/ URL'leri kabul edilir.
    // /anime/ URL'lerinden anidb_id parse edilir (sync icin).
    // /episode/ URL'lerinde anidb_id NULL kalir, sync mal_id ile calisir.
    if (empty(trim($anidb_link))) {
        $validation_errors[] = t('add_anime.error.anidb_link_required');
    } elseif (!preg_match('#^https?://anidb\.net/#i', $anidb_link)) {
        $validation_errors[] = t('add_anime.error.anidb_link_invalid');
    }

    if (!empty($validation_errors)) {
        die(
            '<!DOCTYPE html><html lang="' . current_lang() . '"><head><meta charset="UTF-8">' .
            '<title>' . htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8') . '</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">' . htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '</body></html>'
        );
    }
    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $broadcast_timezone = $_POST['broadcast_timezone'] ?? 'Asia/Tokyo';
    // Catalog synopsis is multi-language: synopsis_tr + synopsis_en +
    // translation_status. On a fresh add there is no prior Turkish text to
    // compare, so the status is simply 'none' when there is no English yet,
    // otherwise 'ai' (curator-pasted AI translation; 'reviewed' is only set
    // later from edit_anime).
    $synopsis_tr = $_POST['synopsis_tr'] ?? '';
    $synopsis_en = $_POST['synopsis_en'] ?? '';
    $translation_status = (trim($synopsis_en) === '') ? 'none' : 'ai';
    $release_date = $_POST['release_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Series relationship fields (v0.5 mid-cycle)
    $series_name = $_POST['series_name'] ?? null;
    $media_type = $_POST['media_type'] ?? null;
    $next_in_series = $_POST['next_in_series'] ?? null;

    // MySQL'in TIME / DATE / DATETIME kolonlari bos string kabul etmez,
    // sadece NULL veya gecerli bir tarih/saat. Form bos gonderirse '' gelir,
    // bunu NULL'a cevirerek INSERT hatasini engelliyoruz.
    if ($broadcast_time === '') { $broadcast_time = null; }
    if ($release_date === '')   { $release_date = null; }
    if ($end_date === '')       { $end_date = null; }
    if ($next_episode_date === '') { $next_episode_date = null; }

    // Tarih format kontrolu: HTML date input YYYY-MM-DD gondermeli.
    // Tarayici hatalari (orn. 5 haneli yil 20026) veya manuel giris
    // yuzunden gecersiz tarih DB'ye ulasmadan yakalanir.
    if ($release_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $release_date)) {
        $validation_errors[] = 'Yayin tarihi gecersiz format. Dogru format: YYYY-MM-DD (orn: 2026-04-08)';
    }
    if ($end_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
        $validation_errors[] = t('add_anime.error.end_date_invalid');
    }
    if ($next_episode_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}/', $next_episode_date)) {
        $validation_errors[] = t('add_anime.error.next_episode_date_invalid');
    }

    if (!empty($validation_errors)) {
        die(
            '<!DOCTYPE html><html lang="' . current_lang() . '"><head><meta charset="UTF-8">' .
            '<title>' . htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . htmlspecialchars(t('add_anime.error_page.form_error_title'), ENT_QUOTES, 'UTF-8') . '</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">' . htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '</body></html>'
        );
    }

    // Episode fields: bos string'leri NULL'a cevir (v0.5 ile total artik nullable)
    if ($total_episodes === '') { $total_episodes = null; }
    if ($aired_episodes === '') { $aired_episodes = null; }

    // Series fields: bos string'leri NULL'a cevir
    if ($series_name === '') { $series_name = null; }
    if ($media_type === '')  { $media_type = null; }
    if ($next_in_series === '' || $next_in_series === '0') { $next_in_series = null; }

    // Status-based normalization for episode counts.
    // Frontend (JS) already hides aired_episodes when status is 'Yayın Tamamlandı',
    // but we enforce the same rule server-side as a safety net in case JS is
    // disabled or someone posts directly.
    if ($status === 'Yayın Tamamlandı') {
        // If user left total blank but filled aired (e.g. switching an
        // ongoing anime to finished at the end of its run), promote aired
        // into total so the final count is preserved.
        if ($total_episodes === null && $aired_episodes !== null) {
            $total_episodes = $aired_episodes;
        }
        // aired_episodes is meaningless for finished anime - clear it.
        $aired_episodes = null;
    } elseif ($status !== 'Yayın Devam Ediyor') {
        // 1.1.10: aired_episodes only applies to an actively airing show.
        // For the non-airing states (Başlamadı / Seçim Yapılmadı / İptal
        // Edildi) clear any leftover value the hidden field may have posted.
        $aired_episodes = null;
    }

    // Madde E - Tek bolumlu animede yayin bitis tarihi anlamsiz.
    // Film, OVA, Special veya tek bolumlu herhangi bir icerik tek seferde
    // yayinlandigi icin baslangic = bitis. Frontend (JS) zaten bu durumda
    // end-date alanini gizliyor, server-side de ayni kurali uyguluyoruz
    // (JS kapaliysa veya direkt POST yapilirsa savunma).
    // Kullanici 2->1 degisikligi yaparsa eski end_date degeri NULL'a cevrilir
    // (Karar 2 - Secenek A).
    if ((int)$total_episodes === 1) {
        $end_date = null;
    }

    // Resim yukleme - functions.php icindeki guvenli helper kullaniliyor
    try {
        $target_file = handleImageUpload($_FILES['image'] ?? null);
    } catch (Exception $e) {
        die(
            '<!DOCTYPE html><html lang="' . current_lang() . '"><head><meta charset="UTF-8">' .
            '<title>' . htmlspecialchars(t('add_anime.error_page.image_error_title'), ENT_QUOTES, 'UTF-8') . '</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . htmlspecialchars(t('add_anime.error_page.image_error_title'), ENT_QUOTES, 'UTF-8') . '</h1>' .
            '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><a href="javascript:history.back()">' . htmlspecialchars(t('add_anime.error_page.go_back_and_retry'), ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '</body></html>'
        );
    }

    // Calculate the next episode date
    if ($status === 'Yayın Devam Ediyor' && !empty($broadcast_day) && !empty($broadcast_time)) {
        $next_episode_date = calculateNextEpisodeDate([
            'status' => $status,
            'broadcast_day' => $broadcast_day,
            'broadcast_time' => $broadcast_time,
            'broadcast_timezone' => $broadcast_timezone
        ]);
    }

    // Insert the anime into the database. mal_id and anidb_id columns were
    // parsed from the URLs above - they are used for identity matching during
    // catalog sync.
    //
    // Genres no longer live on this row - they are written to the
    // anime_genres join table after the INSERT, using the new anime's
    // lastInsertId(). See setAnimeGenresByNames() below.
    //
    // Personal columns (watched_episodes, notes, watch_status) no longer
    // live on this row either (1.0.1) - they are written to user_anime for
    // the current user just after the INSERT (see ua_set_state() below).
    // animes now carries catalog data only.
    // Catalog-add flow. In multi-user mode a moderator/admin add goes straight
    // into the catalog (source='catalog', visible immediately); a regular
    // user's add goes to the approval queue (source='local', listed on
    // pending.php until a moderator promotes it). In self-host (mode=false) the
    // row is 'local' exactly as before and is always visible (single owner, no
    // approval), so the historical default is preserved.
    $source = (MULTI_USER_MODE && can($pdo, 'moderate')) ? 'catalog' : 'local';

    $sql = "INSERT INTO animes (title, title_english, alternative_titles, status, total_episodes, aired_episodes, image_path, next_episode_date, anidb_link, mal_link, anime_schedule_link, episode_interval, broadcast_day, broadcast_time, broadcast_timezone, synopsis_tr, synopsis_en, translation_status, release_date, end_date, series_name, media_type, next_in_series, mal_id, anidb_id, filler_tracking, is_adult, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // INSERT execute'u try/catch icinde calistir. mal_id, anidb_id ve
    // catalog_uuid UNIQUE oldugu icin ayni MAL/AniDB ID ile ikinci kez
    // anime eklenmeye calisilirsa MySQL 1062 hatasi firlatir (23000).
    // Yakalanmazsa kullaniciya ham fatal error + dosya yolu sizar.
    try {
        $stmt->execute([
            $title,
            $title_english !== '' ? $title_english : null,
            !empty($alternative_titles) ? implode('|', $alternative_titles) : '',
            $status,
            $total_episodes,
            $aired_episodes,
            $target_file,
            $next_episode_date,
            $anidb_link,
            $mal_link,
            $anime_schedule_link,
            $episode_interval,
            $broadcast_day,
            $broadcast_time,
            $broadcast_timezone,
            $synopsis_tr,
            $synopsis_en,
            $translation_status,
            $release_date,
            $end_date,
            $series_name,
            $media_type,
            $next_in_series,
            $mal_id,
            $anidb_id,
            $filler_tracking,
            $is_adult,
            $source
        ]);
    } catch (PDOException $e) {
        // 23000 = integrity constraint violation. Anime UNIQUE alanlari:
        //   idx_mal_id, idx_anidb_id, idx_catalog_uuid
        // Diger 23000 senaryolari (yabanci anahtar vb) burada beklenmiyor
        // - bu sadece INSERT animes icin, FK ihlali olusturabilecek
        // tablolar (anime_genres, anime_tags) sonradan ve guvenli ID ile
        // yaziliyor.
        if ($e->getCode() !== '23000') {
            // Beklenmedik bir hata - INSERT cevresine ozel mantik yok,
            // bu durumda eski davranisa donelim (fatal error). En azindan
            // log'a yaz, debug edilebilsin.
            error_log('[anime_tracker] add_anime INSERT failed: ' . $e->getMessage());
            throw $e;
        }

        // INSERT patladiysa yetim resim kaldi. proje_durumu_01 madde 17
        // "Bilinen kalan iki problem" listesindeki #1 burada otomatik
        // cozulur - INSERT basarisizliginda upload edilmis dosya silinir.
        if (!empty($target_file) && file_exists(__DIR__ . '/' . $target_file)) {
            @unlink(__DIR__ . '/' . $target_file);
        }

        // Hangi UNIQUE index patladi? MySQL error mesajindan parse et.
        // Ornek: "Duplicate entry '20583' for key 'idx_mal_id'"
        $indexName = '';
        if (preg_match("/for key '([^']+)'/", $e->getMessage(), $m)) {
            $indexName = $m[1];
        }

        // Hangi alan oldugunu ve mevcut kaydi cek - kullaniciya net mesaj
        // verip edit linki sunabilelim.
        $fieldLabel  = '';
        $duplicateValue = '';
        $existingId    = null;
        $existingTitle = null;

        if ($indexName === 'idx_mal_id' && !empty($mal_id)) {
            $fieldLabel = t('add_anime.duplicate.field_mal_id');
            $duplicateValue = (string)$mal_id;
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE mal_id = ? LIMIT 1");
            $look->execute([$mal_id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_anidb_id' && !empty($anidb_id)) {
            $fieldLabel = t('add_anime.duplicate.field_anidb_id');
            $duplicateValue = (string)$anidb_id;
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE anidb_id = ? LIMIT 1");
            $look->execute([$anidb_id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_catalog_uuid') {
            $fieldLabel = t('add_anime.duplicate.field_catalog_uuid');
        } else {
            $fieldLabel = t('add_anime.duplicate.field_unknown');
        }

        // Mesaj parcalari
        $headerMsg = t('add_anime.error_page.duplicate_heading');
        $detailMsg = htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8');
        if ($duplicateValue !== '') {
            $detailMsg .= ' (' . htmlspecialchars($duplicateValue, ENT_QUOTES, 'UTF-8') . ')';
        }
        $detailMsg .= ' ' . t('add_anime.duplicate.already_exists_suffix');
        if ($existingId !== null && $existingTitle !== null) {
            $detailMsg .= ' ' . htmlspecialchars(t('add_anime.duplicate.existing_record_prefix'), ENT_QUOTES, 'UTF-8')
                . ' <strong>'
                . htmlspecialchars($existingTitle, ENT_QUOTES, 'UTF-8')
                . '</strong>';
        }

        $existingLink = '';
        if ($existingId !== null) {
            $existingLink =
                '<p><a href="anime_details.php?id=' . (int)$existingId . '" '
                . 'style="color:#1976d2;">' . htmlspecialchars(t('add_anime.error_page.go_to_existing'), ENT_QUOTES, 'UTF-8') . '</a></p>';
        }

        die(
            '<!DOCTYPE html><html lang="' . current_lang() . '"><head><meta charset="UTF-8">' .
            '<title>' . htmlspecialchars(t('add_anime.error_page.duplicate_title'), ENT_QUOTES, 'UTF-8') . '</title></head>' .
            '<body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . htmlspecialchars($headerMsg, ENT_QUOTES, 'UTF-8') . '</h1>' .
            '<p>' . $detailMsg . '</p>' .
            $existingLink .
            '<p><a href="javascript:history.back()">' . htmlspecialchars(t('add_anime.error_page.go_back_and_fix'), ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '<p><a href="index.php">' . htmlspecialchars(t('add_anime.error_page.go_to_list'), ENT_QUOTES, 'UTF-8') . '</a></p>' .
            '</body></html>'
        );
    }

    // Yeni animenin ID'sini al - hem genres hem tags eklemek icin gerekli.
    $new_anime_id = (int)$pdo->lastInsertId();

    // Kisisel izleme durumu artik user_anime'da (1.0.1). Yeni anime icin
    // mevcut kullanicinin satirini olustur (bayrak kapaliyken user 1). Bu,
    // INSERT'ten cikarilan watched_episodes / notes / watch_status'un yeni
    // evi.
    ua_set_state($pdo, current_user_id(), $new_anime_id, [
        'watch_status'      => $watch_status,
        'watched_episodes'  => $watched_episodes,
        'notes'             => $notes,
        'watch_start_date'  => $watch_start_date,
        'watch_finish_date' => $watch_finish_date,
    ]);

    // Turleri kaydet (kanonik taksonomi).
    // Form bize secilen tur isimlerini virgulle ayrilmis sekilde gonderir
    // (#genres-input hidden alani). setAnimeGenresByNames her ismi
    // findOrCreateGenre ile ID'ye cevirir, sonra anime_genres tablosuna
    // yazar. Master genres tablosunda olmayan isimler otomatik olarak
    // eklenir - bu sayede kullanici add_anime ekraninda yeni tur
    // ekleyebilir (mevcut "Yeni tur ekle" butonu add_genre.php uzerinden
    // de yeni satir uretiyor; her iki yol da ayni sonuca varir).
    if (!empty($posted_genres)) {
        setAnimeGenresByNames($pdo, $new_anime_id, $posted_genres);
    }

    // Cumleleri kaydet (oneri sistemi).
    // Form bize virgulle ayrilmis cumle listesi gonderir. Her cumle icin
    // findOrCreateTag cagrilir - mevcut cumle varsa ID'si alinir, yoksa
    // yenisi olusturulur. Bu sayede kullanici add_anime sayfasinda yeni
    // cumle olusturmak icin manage_tags.php'ye gitmek zorunda kalmaz.
    $tag_names_raw = $_POST['tags'] ?? '';
    $tag_names = array_filter(array_map('trim', explode(',', $tag_names_raw)));
    $tag_ids = [];
    foreach ($tag_names as $tn) {
        $tid = findOrCreateTag($pdo, $tn);
        if ($tid > 0) {
            $tag_ids[] = $tid;
        }
    }
    if (!empty($tag_ids)) {
        setAnimeTags($pdo, $new_anime_id, $tag_ids);
    }

    // 1.0.11: moderator/admin'in DOGRUDAN kataloga ekledigi anime
    // (source='catalog', pending'e ugramaz) merkez sunucuya otomatik
    // gider - karar 14'te "sonraki adim" diye ertelenen kapsam burada
    // kapanir. Normal kullanici eklemesi (source='local') push ETMEZ;
    // o kayit terfi aninda admin_pending uzerinden gonderilir.
    // Self-host'ta source her zaman 'local' oldugundan blok hic calismaz.
    // Basarisiz push kaydi geri almaz; index'te uyari bandi gosterilir,
    // ayrinti error_log'a yazilir; animeyi edit'ten yeniden kaydetmek
    // push'u tekrar dener.
    $pushFailed = false;
    if (MULTI_USER_MODE && $source === 'catalog') {
        $pushHelper = __DIR__ . '/admin/catalog_push.php';
        if (is_file($pushHelper)) {
            require_once $pushHelper;
            // 1.1.8: scoped push - only the new anime's series (or just it),
            // not the whole catalog. Cheap on a large catalog.
            $push = catalog_push_to_server($pdo, (int)$new_anime_id);
            if (empty($push['ok'])) {
                $pushFailed = true;
                error_log('[anime_tracker] add_anime catalog push failed: '
                    . (isset($push['message']) ? $push['message'] : 'unknown'));
            }
        } else {
            $pushFailed = true;
            error_log('[anime_tracker] add_anime catalog push skipped: helper missing');
        }
    }

    // 1.1.5: ekleme sonrasi index yerine YENI animenin duzenleme sayfasina
    // git (eklediginle duzenlemeye/gozden gecirmeye devam - edit ile ayni
    // "ayni sayfada kal" davranisi). PRG korunur. ?updated=1 basari bandi,
    // push bayragi edit sayfasinda gosterilir. $new_anime_id yukarida
    // lastInsertId ile alindi.
    //
    // ONEMLI (online regresyon korumasi): edit_anime.php moderator-kapilidir
    // (require_role 'moderator'). add_anime ise yalniz require_login ister, yani
    // online normal uye de anime ekleyebilir (source='local'). Uyeyi edit
    // sayfasina atarsak geri seker. Bu yuzden hedef role gore secilir - kurator
    // (self-host sahibi / online moderator+) edit sayfasina, normal uye eski
    // davranisla listeye doner. Kosul add_anime satir 260'taki can('moderate')
    // ile ayni (source='catalog' karari orada da bu yeteneğe baglidir).
    if (can($pdo, 'moderate')) {
        header("Location: edit_anime.php?id=" . (int)$new_anime_id . "&updated=1"
            . ($pushFailed ? '&catalog_push=failed' : ''));
    } else {
        header("Location: index.php" . ($pushFailed ? '?catalog_push=failed' : ''));
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('add_anime.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
   <div class="container">
    <div class="header-section">
        <a href="about.php" class="about-link"><?php echo htmlspecialchars(t('nav.about'), ENT_QUOTES, 'UTF-8'); ?></a>

        <?php echo auth_nav_links(); ?>
    </div>
    <div class="page-title">
        <?php echo htmlspecialchars(t('add_anime.heading'), ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <div class="button-container">
        <a class="anime-list-button" href="index.php"><?php echo htmlspecialchars(t('index.list_title'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <div class="button-spacing"></div>
    
    <div class="section-spacing"></div>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="form-group">
            <label for="title"><?php echo htmlspecialchars(t('add_anime.label.title'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <input type="text" name="title" required>
            </div>
        </div>

        <div class="form-group">
            <label><?php echo htmlspecialchars(t('add_anime.label.alternative_titles'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div id="alternative-titles" class="dynamic-fields">
                    <div class="field-group">
                        <input type="text" name="alternative_titles[]" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.alternative_title'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="remove-button" onclick="removeField(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="add-button" onclick="addAlternativeTitle()">
                    <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('add_anime.btn.add_alternative_title'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="title_english"><?php echo htmlspecialchars(t('add_anime.label.title_english'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <input type="text" id="title_english" name="title_english" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.title_english'), ENT_QUOTES, 'UTF-8'); ?>">
                <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.title_english'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>

        <div class="form-group">
            <label for="synopsis_tr"><?php echo htmlspecialchars(t('add_anime.label.synopsis'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <textarea id="synopsis_tr" name="synopsis_tr" rows="6" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.synopsis'), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="synopsis_en"><?php echo htmlspecialchars(t('add_anime.label.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <textarea id="synopsis_en" name="synopsis_en" rows="6" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?>"></textarea>
                <small class="form-text text-muted"><?php echo htmlspecialchars(t('edit_anime.hint.synopsis_en'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>

        <div class="form-group">
            <label for="total_episodes"><?php echo htmlspecialchars(t('add_anime.label.total_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <input type="number" name="total_episodes" min="0" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.total_episodes'), ENT_QUOTES, 'UTF-8'); ?>" oninput="toggleEndDateBySingleEpisode()">
            </div>
        </div>

        <div id="aired-episodes-section" style="display: none;">
            <div class="form-group">
                <label for="aired_episodes"><?php echo htmlspecialchars(t('add_anime.label.aired_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="number" name="aired_episodes" min="0" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.aired_episodes'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>
            </div>
        </div>

        <?php // 0.7 - filler bolum izleme gorunurluk toggle'i. Default
              // kapali; acilinca anime_details.php'de filler ozeti + Duzenle
              // linki gorunur. Standart form-group deseni: label sol +
              // input-area sag. KARARLAR Bolum 8. ?>
        <div class="form-group">
            <label for="filler_tracking_chk"><?php echo htmlspecialchars(t('add_anime.label.filler_tracking'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <label class="filler-toggle">
                    <input type="checkbox" name="filler_tracking" id="filler_tracking_chk" value="1">
                    <span class="filler-toggle-hint"><?php echo htmlspecialchars(t('add_anime.hint.filler_tracking'), ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
            </div>
        </div>

        <?php // 1.1.2 - yetiskin (+18) icerik bayragi (checkbox). Isaretlenen
              // anime katalogta +18 damgalanir ve gorunurlugu ayar kapaliyken
              // gizlenir. Gorsel duzen filler-toggle sinifiyla paylasilir
              // (jenerik checkbox + hint). Standart form-group deseni. ?>
        <div class="form-group">
            <label for="is_adult_chk"><?php echo htmlspecialchars(t('add_anime.label.is_adult'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <label class="filler-toggle">
                    <input type="checkbox" name="is_adult" id="is_adult_chk" value="1">
                    <span class="filler-toggle-hint"><?php echo htmlspecialchars(t('add_anime.hint.is_adult'), ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="release_date"><?php echo htmlspecialchars(t('add_anime.label.release_date'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <input type="date" name="release_date" id="release_date">
            </div>
        </div>

        <div id="end-date-section" style="display: none;">
            <div class="form-group">
                <label for="end_date"><?php echo htmlspecialchars(t('add_anime.label.end_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="date" name="end_date" id="end_date">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="status"><?php echo htmlspecialchars(t('add_anime.label.status'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <select name="status" onchange="toggleBroadcastDetails()" required>
                    <?php // 1.1.10: five states via the broadcast_status helper. The
                          // "not selected" default is preselected so a fresh form no
                          // longer forces a finished/ongoing guess. ?>
                    <?php foreach (broadcast_status_options() as $bs_value => $bs_label): ?>
                    <option value="<?php echo htmlspecialchars($bs_value, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $bs_value === 'Seçim Yapılmadı' ? ' selected' : ''; ?>><?php echo htmlspecialchars($bs_label, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div id="broadcast-details" style="display: none;">
            <div class="form-group">
                <label for="episode_interval"><?php echo htmlspecialchars(t('add_anime.label.episode_interval'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="number" name="episode_interval" value="7" min="1">
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_day"><?php echo htmlspecialchars(t('add_anime.label.broadcast_day'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <select name="broadcast_day">
                        <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Pazartesi"><?php echo htmlspecialchars(t('add_anime.day.monday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Salı"><?php echo htmlspecialchars(t('add_anime.day.tuesday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Çarşamba"><?php echo htmlspecialchars(t('add_anime.day.wednesday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Perşembe"><?php echo htmlspecialchars(t('add_anime.day.thursday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Cuma"><?php echo htmlspecialchars(t('add_anime.day.friday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Cumartesi"><?php echo htmlspecialchars(t('add_anime.day.saturday'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Pazar"><?php echo htmlspecialchars(t('add_anime.day.sunday'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_time"><?php echo htmlspecialchars(t('add_anime.label.broadcast_time'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="time" name="broadcast_time">
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_timezone"><?php echo htmlspecialchars(t('add_anime.label.broadcast_timezone'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <select name="broadcast_timezone">
                        <option value="Asia/Tokyo" selected><?php echo htmlspecialchars(t('add_anime.tz.tokyo'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Europe/Istanbul"><?php echo htmlspecialchars(t('add_anime.tz.istanbul'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="UTC"><?php echo htmlspecialchars(t('add_anime.tz.utc'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="America/New_York"><?php echo htmlspecialchars(t('add_anime.tz.new_york'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="America/Los_Angeles"><?php echo htmlspecialchars(t('add_anime.tz.los_angeles'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="Europe/London"><?php echo htmlspecialchars(t('add_anime.tz.london'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="watch_status"><?php echo htmlspecialchars(t('add_anime.label.watch_status'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <select name="watch_status" onchange="toggleWatchedEpisodes()" required>
                    <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php foreach (watch_status_options() as $ws_value => $ws_label): ?>
                        <option value="<?php echo htmlspecialchars($ws_value); ?>"><?php echo htmlspecialchars($ws_label); ?></option>
                    <?php endforeach; ?>
                    <option value="__unselected__"><?php echo htmlspecialchars(watch_status_label('__unselected__')); ?></option>
                </select>
            </div>
        </div>

        <div id="watched-episodes-section" style="display: none;">
            <div class="form-group">
                <label for="watched_episodes"><?php echo htmlspecialchars(t('add_anime.label.watched_episodes'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="input-area">
                    <input type="number" name="watched_episodes" value="0" min="0">
                </div>
            </div>
        </div>

        <?php /* 1.1.0: kisisel izleme tarihleri (elle giris, opsiyonel). */ ?>
        <div class="form-group">
            <label><?php echo htmlspecialchars(t('add_anime.label.watch_dates'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div class="watch-date-row">
                    <label for="watch_start_date" class="watch-date-sublabel"><?php echo htmlspecialchars(t('add_anime.label.watch_start_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="date" name="watch_start_date" id="watch_start_date" value="" onchange="checkWatchDateOrder()">
                </div>
                <div class="watch-date-row">
                    <label for="watch_finish_date" class="watch-date-sublabel"><?php echo htmlspecialchars(t('add_anime.label.watch_finish_date'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="date" name="watch_finish_date" id="watch_finish_date" value="" onchange="checkWatchDateOrder()">
                </div>
                <small id="watch-date-warning" class="form-text" style="display:none; color:#d32f2f;"><?php echo htmlspecialchars(t('add_anime.warn.date_order'), ENT_QUOTES, 'UTF-8'); ?></small>
                <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.watch_dates'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>

        <div class="form-group">
            <label><?php echo htmlspecialchars(t('add_anime.label.genres'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div class="genre-selection-container">
                    <?php // data-no-enhance: this picker resets its own value and gains
                          // options at runtime (anime_form.js), so it is left native. ?>
                    <select id="genre-select" onchange="addSelectedGenre(this)" data-no-enhance>
                        <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose_from_existing'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>">
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="new-genre-input">
                        <input type="text" id="new-genre" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.new_genre'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="button" class="add-button" onclick="addNewGenre()">
                            <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('add_anime.btn.add_genre'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </div>
                </div>
                <div id="genre-tags" class="genre-tags">
                    <!-- Selected genre tags are shown here -->
                </div>
                <input type="hidden" name="genres" id="genres-input" value="">
            </div>
        </div>

        <div class="form-group">
            <label><?php echo htmlspecialchars(t('add_anime.label.tags'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div class="tag-input-wrapper" style="position: relative;">
                    <input type="text" id="tag-input" autocomplete="off" maxlength="150"
                           placeholder="<?php echo htmlspecialchars(t('add_anime.ph.tag_input'), ENT_QUOTES, 'UTF-8'); ?>"
                           style="width: 100%; padding: 8px;">
                    <div id="tag-suggestions" class="tag-suggestions"
                         style="display: none; position: absolute; top: 100%; left: 0; right: 0;
                                background: #fff; border: 1px solid #ccc; border-top: none;
                                max-height: 200px; overflow-y: auto; z-index: 100;"></div>
                </div>
                <div id="selected-tags" class="genre-tags" style="margin-top: 8px;">
                    <!-- Secilen cumle rozetleri burada gozukur -->
                </div>
                <input type="hidden" name="tags" id="tags-input" value="">
                <small class="form-text text-muted">
                    <?php echo htmlspecialchars(t('add_anime.hint.tags'), ENT_QUOTES, 'UTF-8'); ?>
                    <a href="manage_tags.php"><?php echo htmlspecialchars(t('add_anime.link.manage_tags'), ENT_QUOTES, 'UTF-8'); ?></a>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="notes"><?php echo htmlspecialchars(t('add_anime.label.notes'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <textarea name="notes" rows="4"></textarea>
                <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.notes'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>

        <div class="form-group">
            <label for="series_name"><?php echo htmlspecialchars(t('add_anime.label.series_name'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <input type="text" name="series_name" id="series_name" list="series-name-list" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.series_name'), ENT_QUOTES, 'UTF-8'); ?>">
                <datalist id="series-name-list">
                    <?php foreach ($seriesNames as $sn): ?>
                        <option value="<?php echo htmlspecialchars($sn, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </datalist>
                <small class="form-text text-muted"><?php echo htmlspecialchars(t('add_anime.hint.series_name'), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
        </div>

        <div class="form-group">
            <label for="media_type"><?php echo htmlspecialchars(t('add_anime.label.media_type'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <select name="media_type" id="media_type">
                    <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="TV">TV</option>
                    <option value="Film">Film</option>
                    <option value="OVA">OVA</option>
                    <option value="Special">Special</option>
                    <option value="ONA">ONA</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="anidb_link"><?php echo htmlspecialchars(t('add_anime.label.anidb_link'), ENT_QUOTES, 'UTF-8'); ?> <span style="color:#d32f2f;">*</span></label>
            <div class="input-area">
                <input type="url" name="anidb_link" required placeholder="<?php echo htmlspecialchars(t('add_anime.ph.anidb_link'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="mal_link"><?php echo htmlspecialchars(t('add_anime.label.mal_link'), ENT_QUOTES, 'UTF-8'); ?> <span style="color:#d32f2f;">*</span></label>
            <div class="input-area">
                <input type="url" name="mal_link" required placeholder="<?php echo htmlspecialchars(t('add_anime.ph.mal_link'), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
        </div>
		<div class="form-group">
    <label for="anime_schedule_link"><?php echo htmlspecialchars(t('add_anime.label.animeschedule_link'), ENT_QUOTES, 'UTF-8'); ?></label>
    <div class="input-area">
        <input type="url" name="anime_schedule_link" id="anime_schedule_link" placeholder="<?php echo htmlspecialchars(t('add_anime.ph.animeschedule_link'), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="button" id="animeschedule-fetch-btn" onclick="fetchAnimeScheduleData()" style="margin-top:8px; padding:8px 14px; background:#5a4ed1; color:#fff; border:none; border-radius:4px; cursor:pointer;">
            <i class="fas fa-magic"></i> <?php echo htmlspecialchars(t('add_anime.btn.animeschedule_fetch'), ENT_QUOTES, 'UTF-8'); ?>
        </button>
        <div id="animeschedule-status" style="margin-top:8px; font-size:13px;"></div>
    </div>
</div>

        <div class="form-group">
            <label for="image"><?php echo htmlspecialchars(t('add_anime.label.image'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div class="file-upload">
                    <input type="file" name="image" id="image" accept="image/*" required onchange="updateFileName(this)">
                    <label for="image" class="file-upload-label">
                        <i class="fas fa-upload"></i> <?php echo htmlspecialchars(t('add_anime.btn.choose_file'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <span class="file-name-display" id="file-name"><?php echo htmlspecialchars(t('add_anime.file.no_file'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>

        <div class="button-group">
            <input type="submit" value="<?php echo htmlspecialchars(t('add_anime.btn.submit'), ENT_QUOTES, 'UTF-8'); ?>" class="submit-button">
            <a href="index.php" class="cancel-button"><?php echo htmlspecialchars(t('add_anime.btn.cancel'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </form>
    </div>

    <script>
    const LANG = <?php echo json_encode([
        'genre_add_failed'             => t('add_anime.js.genre_add_failed'),
        'create_new_tag_prefix'        => t('add_anime.js.create_new_tag_prefix'),
        'no_file'                      => t('add_anime.file.no_file'),
        'alternative_title_placeholder' => t('add_anime.ph.alternative_title'),
        'enter_animeschedule_url'      => t('add_anime.js.enter_animeschedule_url'),
        'fetching'                     => t('add_anime.js.fetching'),
        'unknown_error'                => t('add_anime.js.unknown_error'),
        'field_not_found_suffix'       => t('add_anime.js.field_not_found_suffix'),
        'no_empty_fields'              => t('add_anime.js.no_empty_fields'),
        'fields_filled_prefix'         => t('add_anime.js.fields_filled_prefix'),
        'request_failed_prefix'        => t('add_anime.js.request_failed_prefix'),
    ], JSON_UNESCAPED_UNICODE); ?>;

    // Paylasilan form JS'i icin baslangic durumu (add: bos secimler).
    const ANIME_FORM = {
        allTags: <?php echo json_encode(array_map(function($t) { return $t['name']; }, $allTags), JSON_UNESCAPED_UNICODE); ?>,
        genres: [],
        tags: []
    };
    </script>
    <script src="js/anime_form.js"></script>
    <script src="js/select_enhance.js" defer></script>
</body>
</html>
