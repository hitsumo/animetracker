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
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'] ?? null;
    $aired_episodes = $_POST['aired_episodes'] ?? null;
    // 0.7 - filler bolum izleme gorunurluk bayragi (checkbox). Isaretli
    // degilse 0. Salt gorunurluk; kapali olmasi filler kayitlarini silmez.
    $filler_tracking = isset($_POST['filler_tracking']) ? 1 : 0;
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

    // Sonraki bölüm tarihini hesapla
    if ($status === 'Yayın Devam Ediyor' && !empty($broadcast_day) && !empty($broadcast_time)) {
        $next_episode_date = calculateNextEpisodeDate([
            'status' => $status,
            'broadcast_day' => $broadcast_day,
            'broadcast_time' => $broadcast_time,
            'broadcast_timezone' => $broadcast_timezone
        ]);
    }

    // Animeyi veritabanına ekle. mal_id ve anidb_id kolonlari URL'lerden
    // yukarida parse edildi - katalog senkronizasyonunda kimlik eslesmesi
    // icin kullaniliyorlar.
    //
    // Genres no longer live on this row - they are written to the
    // anime_genres join table after the INSERT, using the new anime's
    // lastInsertId(). See setAnimeGenresByNames() below.
    $sql = "INSERT INTO animes (title, alternative_titles, status, total_episodes, aired_episodes, watched_episodes, notes, image_path, watch_status, next_episode_date, anidb_link, mal_link, anime_schedule_link, episode_interval, broadcast_day, broadcast_time, broadcast_timezone, synopsis_tr, synopsis_en, translation_status, release_date, end_date, series_name, media_type, next_in_series, mal_id, anidb_id, filler_tracking) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);

    // INSERT execute'u try/catch icinde calistir. mal_id, anidb_id ve
    // catalog_uuid UNIQUE oldugu icin ayni MAL/AniDB ID ile ikinci kez
    // anime eklenmeye calisilirsa MySQL 1062 hatasi firlatir (23000).
    // Yakalanmazsa kullaniciya ham fatal error + dosya yolu sizar.
    try {
        $stmt->execute([
            $title,
            !empty($alternative_titles) ? implode('|', $alternative_titles) : '',
            $status,
            $total_episodes,
            $aired_episodes,
            $watched_episodes,
            $notes,
            $target_file,
            $watch_status,
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
            $filler_tracking
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

    header("Location: index.php");
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

        <div class="lang-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('lang.aria_label'), ENT_QUOTES, 'UTF-8'); ?>">
            <form method="POST" action="set_language.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="lang" value="tr">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'add_anime.php', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="lang-switch<?php echo current_lang() === 'tr' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.tr_label'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
            <form method="POST" action="set_language.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="lang" value="en">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'add_anime.php', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="lang-switch<?php echo current_lang() === 'en' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en_label'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </div>
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
                    <option value=""><?php echo htmlspecialchars(t('add_anime.option.choose'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Yayın Tamamlandı"><?php echo htmlspecialchars(t('index.broadcast.finished'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="Yayın Devam Ediyor"><?php echo htmlspecialchars(t('index.broadcast.ongoing'), ENT_QUOTES, 'UTF-8'); ?></option>
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

        <div class="form-group">
            <label><?php echo htmlspecialchars(t('add_anime.label.genres'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="input-area">
                <div class="genre-selection-container">
                    <select id="genre-select" onchange="addSelectedGenre(this)">
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
                    <!-- Seçilen tür etiketleri burada gösterilecek -->
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

    function updateFileName(input) {
        const fileName = input.files[0]?.name;
        document.getElementById('file-name').textContent = fileName || LANG.no_file;
    }

    function addAlternativeTitle() {
        const container = document.getElementById('alternative-titles');
        const newField = document.createElement('div');
        newField.className = 'field-group';
        newField.innerHTML = `
            <input type="text" name="alternative_titles[]" placeholder="${LANG.alternative_title_placeholder}">
            <button type="button" class="remove-button" onclick="removeField(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(newField);
    }

    function removeField(button) {
        button.parentElement.remove();
    }

    function toggleBroadcastDetails() {
        const status = document.querySelector('select[name="status"]').value;
        const broadcastDetails = document.getElementById('broadcast-details');
        const airedSection = document.getElementById('aired-episodes-section');
        const endDateSection = document.getElementById('end-date-section');

        // Broadcast details (interval, day, time) only matter for ongoing anime
        if (status === 'Yayın Devam Ediyor') {
            broadcastDetails.style.display = 'block';
            airedSection.style.display = 'block';
            endDateSection.style.display = 'none';
        } else if (status === 'Yayın Tamamlandı') {
            broadcastDetails.style.display = 'none';
            airedSection.style.display = 'none';
            // Madde E - Tek bolumde end_date gizli kalir, status finished olsa bile.
            // Kararin tek noktada toplanmasi icin helper'a delegate ediyoruz.
            endDateSection.style.display = isSingleEpisode() ? 'none' : 'block';
        } else {
            broadcastDetails.style.display = 'none';
            airedSection.style.display = 'none';
            endDateSection.style.display = 'none';
        }
    }

    // Madde E - Toplam bolum sayisi 1 ise yayin bitis tarihi alani anlamsiz.
    // total_episodes input'undaki her degisiklik bu fonksiyonu tetikler;
    // toggleBroadcastDetails() icindeki status bazli mantikla beraber calisir.
    function isSingleEpisode() {
        const totalEl = document.querySelector('input[name="total_episodes"]');
        if (!totalEl) return false;
        return parseInt(totalEl.value, 10) === 1;
    }

    function toggleEndDateBySingleEpisode() {
        // Status finished olmadigi surece end-date zaten gizli; status finished
        // ise toggleBroadcastDetails() icindeki tek-bolum kontrolu calistirilir.
        // Kullanici total'i 1 yaparsa end-date hemen gizlenir, 2'ye cikarirsa
        // (status finished iken) tekrar gorunur.
        toggleBroadcastDetails();
    }

    function toggleWatchedEpisodes() {
        const watchStatus = document.querySelector('select[name="watch_status"]').value;
        const watchedEpisodesDiv = document.getElementById('watched-episodes-section');
        // Watching ve OnHold: izlenen bolum input'u gorunur, mevcut deger
        // korunur. Watching = aktif izleme, OnHold = ara verildi (ilerleme
        // saklanir). Form davranisi acisindan ayni dali paylasirlar;
        // aralarindaki fark sadece semantik.
        if (watchStatus === 'Watching' || watchStatus === 'OnHold') {
            watchedEpisodesDiv.style.display = 'block';
        } else {
            watchedEpisodesDiv.style.display = 'none';
            if (watchStatus === 'Watched') {
                // Fall back to aired_episodes when total is blank (ongoing
                // series where the final count is still unknown).
                const total = document.querySelector('input[name="total_episodes"]').value;
                const aired = document.querySelector('input[name="aired_episodes"]').value;
                document.querySelector('input[name="watched_episodes"]').value =
                    total || aired || '0';
            } else if (watchStatus === 'PlanToWatch') {
                document.querySelector('input[name="watched_episodes"]').value = '0';
            }
        }
    }

    let selectedGenres = [];

    function addSelectedGenre(select) {
        const genre = select.value;
        if (genre && !selectedGenres.includes(genre)) {
            selectedGenres.push(genre);
            updateGenreTags();
        }
        select.value = '';
    }

    function addNewGenre() {
        const newGenreInput = document.getElementById('new-genre');
        const genre = newGenreInput.value.trim();
        
        if (genre && !selectedGenres.includes(genre)) {
            // CSRF token formdaki gizli input'tan al, fetch body'sine ekle.
            // add_genre.php server tarafinda dogruluyor.
            const csrfInput = document.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : '';
            fetch('add_genre.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'genre=' + encodeURIComponent(genre) + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('genre-select');
                    const option = new Option(genre, genre);
                    select.add(option);
                    
                    selectedGenres.push(genre);
                    updateGenreTags();
                    
                    newGenreInput.value = '';
                } else {
                    alert(LANG.genre_add_failed);
                }
            });
        }
    }

    function removeGenre(genre) {
        selectedGenres = selectedGenres.filter(g => g !== genre);
        updateGenreTags();
    }

    function updateGenreTags() {
        const container = document.getElementById('genre-tags');
        const input = document.getElementById('genres-input');
        
        container.innerHTML = selectedGenres.map(genre => `
            <div class="genre-tag">
                ${genre}
                <button type="button" onclick="removeGenre('${genre}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        
        input.value = selectedGenres.join(',');
    }

    /* ---------------------------------------------------------------
     * Tag input (recommendation system).
     * Standalone from the genre selector above. The user types into a
     * single input; matches from the existing tag library appear in a
     * dropdown; if no match exists the dropdown shows a "create new
     * tag" option. Selected tags appear as removable badges. The
     * hidden #tags-input is what actually posts to the server as a
     * comma-separated list of tag names.
     * --------------------------------------------------------------- */
    const allTags = <?php echo json_encode(array_map(function($t) { return $t['name']; }, $allTags), JSON_UNESCAPED_UNICODE); ?>;
    let selectedTags = [];

    const tagInput = document.getElementById('tag-input');
    const tagSuggestions = document.getElementById('tag-suggestions');

    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(c) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
        });
    }

    function renderSuggestions() {
        const query = tagInput.value.trim();
        if (query === '') {
            tagSuggestions.style.display = 'none';
            return;
        }
        const lower = query.toLowerCase();
        const matches = allTags.filter(t =>
            t.toLowerCase().includes(lower) && !selectedTags.includes(t)
        );

        let html = '';
        matches.slice(0, 10).forEach(t => {
            html += `<div class="tag-suggestion-item" data-name="${escapeHtml(t)}"
                          style="padding: 6px 10px; cursor: pointer;">${escapeHtml(t)}</div>`;
        });

        // Show "create new" option only if exact match (case-insensitive)
        // does not already exist in the library or in current selection.
        const exact = allTags.find(t => t.toLowerCase() === lower);
        const alreadySelected = selectedTags.some(t => t.toLowerCase() === lower);
        if (!exact && !alreadySelected) {
            html += `<div class="tag-suggestion-item tag-suggestion-new" data-name="${escapeHtml(query)}"
                          style="padding: 6px 10px; cursor: pointer; background: #f0f8ff; font-style: italic;">
                          ${LANG.create_new_tag_prefix} "${escapeHtml(query)}"</div>`;
        }

        if (html === '') {
            tagSuggestions.style.display = 'none';
            return;
        }

        tagSuggestions.innerHTML = html;
        tagSuggestions.style.display = 'block';
    }

    function addTag(name) {
        name = name.trim();
        if (name === '') return;
        // Case-insensitive duplicate check on the client side
        if (selectedTags.some(t => t.toLowerCase() === name.toLowerCase())) {
            return;
        }
        selectedTags.push(name);
        // If this is a brand-new tag, add it to the in-memory library
        // so subsequent suggestions include it.
        if (!allTags.some(t => t.toLowerCase() === name.toLowerCase())) {
            allTags.push(name);
        }
        tagInput.value = '';
        tagSuggestions.style.display = 'none';
        updateSelectedTags();
    }

    function removeTag(name) {
        selectedTags = selectedTags.filter(t => t !== name);
        updateSelectedTags();
    }

    function updateSelectedTags() {
        const container = document.getElementById('selected-tags');
        const hidden = document.getElementById('tags-input');
        container.innerHTML = selectedTags.map(t => `
            <div class="genre-tag">
                ${escapeHtml(t)}
                <button type="button" data-tag-name="${escapeHtml(t)}">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `).join('');
        // Wire remove buttons (avoid inline onclick with quoted user data)
        container.querySelectorAll('button[data-tag-name]').forEach(btn => {
            btn.addEventListener('click', () => removeTag(btn.dataset.tagName));
        });
        hidden.value = selectedTags.join(',');
    }

    tagInput.addEventListener('input', renderSuggestions);
    tagInput.addEventListener('focus', renderSuggestions);

    // Click a suggestion = add it
    tagSuggestions.addEventListener('click', e => {
        const item = e.target.closest('.tag-suggestion-item');
        if (item) {
            addTag(item.dataset.name);
        }
    });

    // Enter = add the typed text (matching existing or creating new)
    tagInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const v = tagInput.value.trim();
            if (v !== '') {
                addTag(v);
            }
        } else if (e.key === 'Escape') {
            tagSuggestions.style.display = 'none';
        }
    });

    // Hide dropdown when clicking outside
    document.addEventListener('click', e => {
        if (!e.target.closest('.tag-input-wrapper')) {
            tagSuggestions.style.display = 'none';
        }
    });

    // ====================================================================
    // AnimeSchedule "Otomatik Doldur" button
    // ====================================================================
    //
    // Calls fetch_animeschedule.php with the URL from the anime_schedule_link
    // input. Response.fields is an object of "form_field_name -> value"
    // pairs (only the fields the API could actually fill). We iterate it
    // and write each value into the matching form element ONLY when that
    // element is currently empty - existing manual input is never
    // overwritten.
    //
    // broadcast_timezone has special handling: the form starts pre-filled
    // with "Asia/Tokyo" (default), so "empty" means "still on default".
    // We treat anything else as "user changed it on purpose, do not touch".
    function fetchAnimeScheduleData() {
        const urlInput = document.getElementById('anime_schedule_link');
        const statusDiv = document.getElementById('animeschedule-status');
        const btn = document.getElementById('animeschedule-fetch-btn');

        const url = (urlInput.value || '').trim();
        if (url === '') {
            statusDiv.style.color = '#c0392b';
            statusDiv.textContent = LANG.enter_animeschedule_url;
            return;
        }

        // Read CSRF token from the hidden input that already lives at the
        // top of the form (added when CSRF protection was enabled).
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        // Disable button + show progress so the user knows something is
        // happening. Re-enabled in the finally block.
        btn.disabled = true;
        statusDiv.style.color = '#555';
        statusDiv.textContent = LANG.fetching;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('url', url);

        fetch('fetch_animeschedule.php', {
            method: 'POST',
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                statusDiv.style.color = '#c0392b';
                statusDiv.textContent = data.error || LANG.unknown_error;
                return;
            }

            const fields = data.fields || {};
            const filled = [];
            const skipped = [];

            // Walk every field the API gave us. For each one find the
            // matching DOM element by name attribute and write only
            // when the element is currently empty.
            for (const fieldName in fields) {
                if (!Object.prototype.hasOwnProperty.call(fields, fieldName)) continue;
                const value = fields[fieldName];
                const el = document.querySelector('[name="' + fieldName + '"]');
                if (!el) {
                    skipped.push(fieldName + ' ' + LANG.field_not_found_suffix);
                    continue;
                }

                // Decide whether the element counts as "empty".
                let isEmpty;
                if (fieldName === 'broadcast_timezone') {
                    // Special case: default is Asia/Tokyo. Treat that as
                    // "user has not chosen yet" so we still set the value.
                    // If the user changed it to something else, respect it.
                    isEmpty = (el.value === '' || el.value === 'Asia/Tokyo');
                } else {
                    isEmpty = (el.value === '' || el.value === null);
                }

                if (!isEmpty) {
                    skipped.push(fieldName);
                    continue;
                }

                el.value = value;
                filled.push(fieldName);

                // status drives the visibility of broadcast/aired/end_date
                // sections via toggleBroadcastDetails(). Trigger it so the
                // newly-filled broadcast_day/time become visible.
                if (fieldName === 'status' && typeof toggleBroadcastDetails === 'function') {
                    toggleBroadcastDetails();
                }
            }

            if (filled.length === 0) {
                statusDiv.style.color = '#888';
                statusDiv.textContent = LANG.no_empty_fields;
            } else {
                statusDiv.style.color = '#27ae60';
                statusDiv.textContent = LANG.fields_filled_prefix + ' ' + filled.length + ': ' + filled.join(', ') + '.';
            }
        })
        .catch(err => {
            statusDiv.style.color = '#c0392b';
            statusDiv.textContent = LANG.request_failed_prefix + ' ' + err.message;
        })
        .finally(() => {
            btn.disabled = false;
        });
    }
    </script>
</body>
</html>