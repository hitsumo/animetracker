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
        die('CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.');
    }

    $title = $_POST['title'];
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'] ?? null;
    $aired_episodes = $_POST['aired_episodes'] ?? null;
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
        $validation_errors[] = 'MyAnimeList linki zorunludur.';
    } elseif ($mal_id === null) {
        $validation_errors[] = 'MyAnimeList linki gecersiz format. Ornek: https://myanimelist.net/anime/12345';
    }

    // AniDB: hem /anime/ hem /episode/ URL'leri kabul edilir.
    // /anime/ URL'lerinden anidb_id parse edilir (sync icin).
    // /episode/ URL'lerinde anidb_id NULL kalir, sync mal_id ile calisir.
    if (empty(trim($anidb_link))) {
        $validation_errors[] = 'AniDB linki zorunludur.';
    } elseif (!preg_match('#^https?://anidb\.net/#i', $anidb_link)) {
        $validation_errors[] = 'AniDB linki gecersiz. anidb.net adresi olmali. Ornek: https://anidb.net/anime/12345 veya https://anidb.net/episode/212772';
    }

    if (!empty($validation_errors)) {
        die(
            '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">' .
            '<title>Form Hatasi</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">Form Hatasi</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">Geri don ve duzelt</a></p>' .
            '</body></html>'
        );
    }
    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $broadcast_timezone = $_POST['broadcast_timezone'] ?? 'Asia/Tokyo';
    $synopsis = $_POST['synopsis'] ?? '';
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
        $validation_errors[] = 'Bitis tarihi gecersiz format. Dogru format: YYYY-MM-DD (orn: 2026-09-15)';
    }
    if ($next_episode_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}/', $next_episode_date)) {
        $validation_errors[] = 'Sonraki bolum tarihi gecersiz format.';
    }

    if (!empty($validation_errors)) {
        die(
            '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">' .
            '<title>Form Hatasi</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">Form Hatasi</h1>' .
            '<ul>' . implode('', array_map(function($e) {
                return '<li>' . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . '</li>';
            }, $validation_errors)) . '</ul>' .
            '<p><a href="javascript:history.back()">Geri don ve duzelt</a></p>' .
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
            '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">' .
            '<title>Yukleme Hatasi</title></head><body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">Resim Yukleme Hatasi</h1>' .
            '<p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><a href="javascript:history.back()">Geri don ve tekrar dene</a></p>' .
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
    $sql = "INSERT INTO animes (title, alternative_titles, status, total_episodes, aired_episodes, watched_episodes, notes, image_path, watch_status, next_episode_date, anidb_link, mal_link, anime_schedule_link, episode_interval, broadcast_day, broadcast_time, broadcast_timezone, synopsis, release_date, end_date, series_name, media_type, next_in_series, mal_id, anidb_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
            $synopsis,
            $release_date,
            $end_date,
            $series_name,
            $media_type,
            $next_in_series,
            $mal_id,
            $anidb_id
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
            $fieldLabel = 'MAL ID';
            $duplicateValue = (string)$mal_id;
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE mal_id = ? LIMIT 1");
            $look->execute([$mal_id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_anidb_id' && !empty($anidb_id)) {
            $fieldLabel = 'AniDB ID';
            $duplicateValue = (string)$anidb_id;
            $look = $pdo->prepare("SELECT id, title FROM animes WHERE anidb_id = ? LIMIT 1");
            $look->execute([$anidb_id]);
            $row = $look->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $existingId    = (int)$row['id'];
                $existingTitle = $row['title'];
            }
        } elseif ($indexName === 'idx_catalog_uuid') {
            $fieldLabel = 'Katalog UUID';
        } else {
            $fieldLabel = 'tanimsiz UNIQUE alan';
        }

        // Mesaj parcalari
        $headerMsg = 'Bu anime zaten listenizde mevcut';
        $detailMsg = htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8');
        if ($duplicateValue !== '') {
            $detailMsg .= ' (' . htmlspecialchars($duplicateValue, ENT_QUOTES, 'UTF-8') . ')';
        }
        $detailMsg .= ' zaten kayitli.';
        if ($existingId !== null && $existingTitle !== null) {
            $detailMsg .= ' Mevcut kayit: <strong>'
                . htmlspecialchars($existingTitle, ENT_QUOTES, 'UTF-8')
                . '</strong>';
        }

        $existingLink = '';
        if ($existingId !== null) {
            $existingLink =
                '<p><a href="anime_details.php?id=' . (int)$existingId . '" '
                . 'style="color:#1976d2;">Mevcut kayda git</a></p>';
        }

        die(
            '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">' .
            '<title>Tekrarlanan Veri Hatası</title></head>' .
            '<body style="font-family:Arial;max-width:600px;margin:40px auto;padding:20px;">' .
            '<h1 style="color:#d32f2f;">' . $headerMsg . '</h1>' .
            '<p>' . $detailMsg . '</p>' .
            $existingLink .
            '<p><a href="javascript:history.back()">Geri dön ve düzelt</a></p>' .
            '<p><a href="index.php">Anime listesine git</a></p>' .
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
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Listeye Anime Ekleme</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
   <div class="container">
    <div class="header-section">
        <a href="about.php" class="about-link">Hakkında</a>
    </div>
    <div class="page-title">
        Listeye Anime Ekleme
    </div>

    <div class="button-container">
        <a class="anime-list-button" href="index.php">Anime İzleme Listesi</a>
    </div>
    <div class="button-spacing"></div>
    
    <div class="section-spacing"></div>

    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <div class="form-group">
            <label for="title">Anime İsmi:</label>
            <div class="input-area">
                <input type="text" name="title" required>
            </div>
        </div>

        <div class="form-group">
            <label>Alternatif İsimler:</label>
            <div class="input-area">
                <div id="alternative-titles" class="dynamic-fields">
                    <div class="field-group">
                        <input type="text" name="alternative_titles[]" placeholder="Alternatif isim">
                        <button type="button" class="remove-button" onclick="removeField(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="add-button" onclick="addAlternativeTitle()">
                    <i class="fas fa-plus"></i> Alternatif İsim Ekle
                </button>
            </div>
        </div>

        <div class="form-group">
            <label for="synopsis">Konu:</label>
            <div class="input-area">
                <textarea name="synopsis" rows="6" placeholder="Animenin konusunu yazın"></textarea>
            </div>
        </div>

        <div class="form-group">
            <label for="total_episodes">Toplam Bölüm Sayısı:</label>
            <div class="input-area">
                <input type="number" name="total_episodes" min="0" placeholder="Bilinmiyorsa boş bırakın" oninput="toggleEndDateBySingleEpisode()">
            </div>
        </div>

        <div id="aired-episodes-section" style="display: none;">
            <div class="form-group">
                <label for="aired_episodes">Yayınlanan Bölüm Sayısı:</label>
                <div class="input-area">
                    <input type="number" name="aired_episodes" min="0" placeholder="Şu ana kadar yayınlanan bölüm">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="release_date">Yayın Tarihi:</label>
            <div class="input-area">
                <input type="date" name="release_date" id="release_date">
            </div>
        </div>

        <div id="end-date-section" style="display: none;">
            <div class="form-group">
                <label for="end_date">Yayın Bitiş Tarihi:</label>
                <div class="input-area">
                    <input type="date" name="end_date" id="end_date">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="status">Yayın Durumu:</label>
            <div class="input-area">
                <select name="status" onchange="toggleBroadcastDetails()" required>
                    <option value="">Seçiniz</option>
                    <option value="Yayın Tamamlandı">Yayın Tamamlandı</option>
                    <option value="Yayın Devam Ediyor">Yayın Devam Ediyor</option>
                </select>
            </div>
        </div>

        <div id="broadcast-details" style="display: none;">
            <div class="form-group">
                <label for="episode_interval">Bölümler Arası Süre (Gün):</label>
                <div class="input-area">
                    <input type="number" name="episode_interval" value="7" min="1">
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_day">Yayın Günü:</label>
                <div class="input-area">
                    <select name="broadcast_day">
                        <option value="">Seçiniz</option>
                        <option value="Pazartesi">Pazartesi</option>
                        <option value="Salı">Salı</option>
                        <option value="Çarşamba">Çarşamba</option>
                        <option value="Perşembe">Perşembe</option>
                        <option value="Cuma">Cuma</option>
                        <option value="Cumartesi">Cumartesi</option>
                        <option value="Pazar">Pazar</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_time">Yayın Saati:</label>
                <div class="input-area">
                    <input type="time" name="broadcast_time">
                </div>
            </div>

            <div class="form-group">
                <label for="broadcast_timezone">Yayın Saat Dilimi:</label>
                <div class="input-area">
                    <select name="broadcast_timezone">
                        <option value="Asia/Tokyo" selected>Japonya (Tokyo) - JST</option>
                        <option value="Europe/Istanbul">Türkiye (Istanbul) - TRT</option>
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">ABD Dogu (New York) - ET</option>
                        <option value="America/Los_Angeles">ABD Bati (Los Angeles) - PT</option>
                        <option value="Europe/London">Birlesik Krallik (London)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="watch_status">İzleme Durumu:</label>
            <div class="input-area">
                <select name="watch_status" onchange="toggleWatchedEpisodes()" required>
                    <option value="">Seçiniz</option>
                    <option value="İzlendi">İzlendi</option>
                    <option value="İzleniyor">İzleniyor</option>
                    <option value="İzlenme Planlandı">İzlenme Planlandı</option>
                </select>
            </div>
        </div>

        <div id="watched-episodes-section" style="display: none;">
            <div class="form-group">
                <label for="watched_episodes">İzlenen Bölüm Sayısı:</label>
                <div class="input-area">
                    <input type="number" name="watched_episodes" value="0" min="0">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Türler:</label>
            <div class="input-area">
                <div class="genre-selection-container">
                    <select id="genre-select" onchange="addSelectedGenre(this)">
                        <option value="">Mevcut Türlerden Seç</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?php echo htmlspecialchars($genre['name']); ?>">
                                <?php echo htmlspecialchars($genre['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="new-genre-input">
                        <input type="text" id="new-genre" placeholder="Yeni tür ekle">
                        <button type="button" class="add-button" onclick="addNewGenre()">
                            <i class="fas fa-plus"></i> Ekle
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
            <label>Cumleler:</label>
            <div class="input-area">
                <div class="tag-input-wrapper" style="position: relative;">
                    <input type="text" id="tag-input" autocomplete="off" maxlength="150"
                           placeholder="Cumle ekle (orn: Okulda gecsin, Spor temasi olsun)..."
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
                    Yazinca eslesenler gozukur. Eslesme yoksa Enter ile yeni cumle olusturulur.
                    <a href="manage_tags.php">Cumleleri yonet</a>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="notes">Notlar:</label>
            <div class="input-area">
                <textarea name="notes" rows="4"></textarea>
                <small class="form-text text-muted">notlar bolumu silinirse sync ile geri gelmez</small>
            </div>
        </div>

        <div class="form-group">
            <label for="series_name">Seri Adı (opsiyonel):</label>
            <div class="input-area">
                <input type="text" name="series_name" id="series_name" list="series-name-list" placeholder="Orn: Detective Conan, Spy x Family">
                <datalist id="series-name-list">
                    <?php foreach ($seriesNames as $sn): ?>
                        <option value="<?php echo htmlspecialchars($sn, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endforeach; ?>
                </datalist>
                <small class="form-text text-muted">Aynı seriye ait animeler bu adı paylaşır. Mevcut seriler otomatik önerilir.</small>
            </div>
        </div>

        <div class="form-group">
            <label for="media_type">Medya Türü (opsiyonel):</label>
            <div class="input-area">
                <select name="media_type" id="media_type">
                    <option value="">Seçiniz</option>
                    <option value="TV">TV</option>
                    <option value="Film">Film</option>
                    <option value="OVA">OVA</option>
                    <option value="Special">Special</option>
                    <option value="ONA">ONA</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="anidb_link">AniDB Linki: <span style="color:#d32f2f;">*</span></label>
            <div class="input-area">
                <input type="url" name="anidb_link" required placeholder="https://anidb.net/anime/12345 veya /episode/12345">
            </div>
        </div>

        <div class="form-group">
            <label for="mal_link">MyAnimeList Linki: <span style="color:#d32f2f;">*</span></label>
            <div class="input-area">
                <input type="url" name="mal_link" required placeholder="https://myanimelist.net/anime/12345">
            </div>
        </div>
		<div class="form-group">
    <label for="anime_schedule_link">AnimeSchedule Linki:</label>
    <div class="input-area">
        <input type="url" name="anime_schedule_link" id="anime_schedule_link" placeholder="https://animeschedule.net/anime/...">
        <button type="button" id="animeschedule-fetch-btn" onclick="fetchAnimeScheduleData()" style="margin-top:8px; padding:8px 14px; background:#5a4ed1; color:#fff; border:none; border-radius:4px; cursor:pointer;">
            <i class="fas fa-magic"></i> Otomatik Doldur
        </button>
        <div id="animeschedule-status" style="margin-top:8px; font-size:13px;"></div>
    </div>
</div>

        <div class="form-group">
            <label for="image">Resim Yükle:</label>
            <div class="input-area">
                <div class="file-upload">
                    <input type="file" name="image" id="image" accept="image/*" required onchange="updateFileName(this)">
                    <label for="image" class="file-upload-label">
                        <i class="fas fa-upload"></i> Dosya Seç
                    </label>
                    <span class="file-name-display" id="file-name">Dosya seçilmedi</span>
                </div>
            </div>
        </div>

        <div class="button-group">
            <input type="submit" value="Ekle" class="submit-button">
            <a href="index.php" class="cancel-button">Vazgeç</a>
        </div>
    </form>
    </div>

    <script>
    function updateFileName(input) {
        const fileName = input.files[0]?.name;
        document.getElementById('file-name').textContent = fileName || 'Dosya seçilmedi';
    }

    function addAlternativeTitle() {
        const container = document.getElementById('alternative-titles');
        const newField = document.createElement('div');
        newField.className = 'field-group';
        newField.innerHTML = `
            <input type="text" name="alternative_titles[]" placeholder="Alternatif isim">
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
        if (watchStatus === 'İzleniyor') {
            watchedEpisodesDiv.style.display = 'block';
        } else {
            watchedEpisodesDiv.style.display = 'none';
            if (watchStatus === 'İzlendi') {
                // Fall back to aired_episodes when total is blank (ongoing
                // series where the final count is still unknown).
                const total = document.querySelector('input[name="total_episodes"]').value;
                const aired = document.querySelector('input[name="aired_episodes"]').value;
                document.querySelector('input[name="watched_episodes"]').value =
                    total || aired || '0';
            } else if (watchStatus === 'İzlenme Planlandı') {
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
                    alert('Tür eklenirken bir hata oluştu');
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
                          + Yeni cumle olustur: "${escapeHtml(query)}"</div>`;
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
            statusDiv.textContent = 'Once AnimeSchedule URL ini girin.';
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
        statusDiv.textContent = 'AnimeSchedule den veri cekiliyor...';

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
                statusDiv.textContent = data.error || 'Bilinmeyen hata.';
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
                    skipped.push(fieldName + ' (alan bulunamadi)');
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
                statusDiv.textContent = 'Doldurulacak bos alan bulunamadi (tum alanlar dolu).';
            } else {
                statusDiv.style.color = '#27ae60';
                statusDiv.textContent = filled.length + ' alan dolduruldu: ' + filled.join(', ') + '.';
            }
        })
        .catch(err => {
            statusDiv.style.color = '#c0392b';
            statusDiv.textContent = 'Istek basarisiz: ' + err.message;
        })
        .finally(() => {
            btn.disabled = false;
        });
    }
    </script>
</body>
</html>