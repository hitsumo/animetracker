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

// Anime ID'sini al
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Master genre list for the dropdown. Fetched via the helper so the
// rest of the page does not need to know which table the data lives
// in. Returns rows with id and name.
$genres = getAllGenres($pdo);

// Seri adlarini cek (datalist auto-complete icin)
$seriesNames = getAllSeriesNames($pdo);

// Tum cumleleri cek (oneri sistemi icin tag input auto-complete kaynagi)
$allTags = getAllTags($pdo);

// Anime bilgilerini çek
$stmt = $pdo->prepare('SELECT * FROM animes WHERE id = ?');
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    header('Location: index.php');
    exit;
}

// "Siradaki anime" dropdown'u icin: tum animeleri cek (mevcut anime haric).
// series_name dolu ise ayni seridekiler basta gosterilir, diger animeler
// de listelenir cunku kullanici farkli bir seriye isaret etmek isteyebilir.
$allAnimesStmt = $pdo->prepare("
    SELECT id, title, series_name, media_type
    FROM animes
    WHERE id != ?
    ORDER BY
        CASE WHEN series_name = ? AND ? IS NOT NULL THEN 0 ELSE 1 END,
        title ASC
");
$allAnimesStmt->execute([(int)$id, $anime['series_name'], $anime['series_name']]);
$allAnimes = $allAnimesStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF kontrolu - form'dan gelen token oturumdaki ile eslesmiyorsa reddet.
    // hash_equals timing-safe karsilastirma yapar (bkz. functions.php csrf_verify).
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.');
    }

    // Mevcut anime bilgilerini kontrol et
    if ($anime['status'] == 'Yayın Tamamlandı') {
        // Eğer anime yayını tamamlandıysa, durumu değiştirmeye izin verme
        $_POST['status'] = 'Yayın Tamamlandı';
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
    // Synopsis mode handling:
    //   Mode 1 (user_synopsis IS NULL): form submitted 'synopsis' field,
    //     user_synopsis stays NULL, we UPDATE synopsis only.
    //   Mode 2 (user_synopsis IS NOT NULL): form submitted 'user_synopsis'
    //     field only (the synopsis textarea was readonly). We keep the
    //     existing synopsis value and UPDATE user_synopsis.
    //
    // Empty user_synopsis from the form is preserved as empty string (not
    // NULL) so the row stays in Mode 2 - deletion is permanent, sync will
    // not restore the personal synopsis. See PROJE_DURUMU.md for the
    // rationale (user can see the warning in the form's help text).
    if ($anime['user_synopsis'] === null) {
        // Mode 1
        $synopsis = $_POST['synopsis'] ?? '';
        $user_synopsis = null;
    } else {
        // Mode 2
        $synopsis = $anime['synopsis'];            // unchanged (readonly)
        $user_synopsis = $_POST['user_synopsis'] ?? '';
        // Note: empty string stays empty string (NOT converted to NULL)
    }
    $release_date = $_POST['release_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;

    // Series relationship fields
    $series_name = $_POST['series_name'] ?? null;
    $media_type = $_POST['media_type'] ?? null;
    $next_in_series = $_POST['next_in_series'] ?? null;

    // MySQL'in TIME / DATE / DATETIME kolonlari bos string kabul etmez,
    // sadece NULL veya gecerli bir tarih/saat. Form bos gonderirse '' gelir,
    // bunu NULL'a cevirerek INSERT/UPDATE hatasini engelliyoruz.
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

    // Circular reference check: A -> B -> A dongusu olusmasin
    if ($next_in_series !== null && !validateNextInSeries($pdo, $id, $next_in_series)) {
        $next_in_series = null;
        error_log('[anime_tracker] Circular next_in_series prevented: anime ' . $id . ' -> ' . $_POST['next_in_series']);
    }

    // Status-based normalization for episode counts.
    // Frontend (JS) already hides aired_episodes when status is 'Yayın Tamamlandı',
    // but we enforce the same rule server-side as a safety net in case JS is
    // disabled or someone posts directly.
    if ($status === 'Yayın Tamamlandı') {
        // If user left total blank but filled aired (e.g. switching an
        // ongoing anime to finished at the end of its run), promote aired
        // into total so the final count is preserved. This is the One Piece
        // archival case: when a long-running series finally ends, the
        // last known aired count becomes the final total.
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
    // (Karar 2 - Secenek A). Bu sayede eski kayitlar bir sonraki edit'te
    // organik olarak temizlenir.
    if ((int)$total_episodes === 1) {
        $end_date = null;
    }

    // Resim yukleme - yeni dosya secildiyse functions.php icindeki guvenli
    // helper ile kaydet, sonra eski resmi sil. Hicbir dosya secilmediyse
    // mevcut image_path korunur.
    try {
        $newImagePath = handleImageUpload($_FILES['image'] ?? null);
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

    if ($newImagePath !== null) {
        // Yeni resim yuklendi - eski resmi sil
        $target_file = $newImagePath;
        if (!empty($anime['image_path']) && file_exists(__DIR__ . '/' . $anime['image_path'])) {
            @unlink(__DIR__ . '/' . $anime['image_path']);
        }
    } else {
        // Yeni resim yok - mevcut yolu koru
        $target_file = $anime['image_path'];
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

    // Animeyi güncelle.
    // Genres no longer live on this row - they are written to the
    // anime_genres join table after the UPDATE via
    // setAnimeGenresByNames(), mirroring the tags handler below.
    $sql = "UPDATE animes SET 
            title = ?,
            alternative_titles = ?,
            status = ?,
            total_episodes = ?,
            aired_episodes = ?,
            watched_episodes = ?,
            notes = ?,
            image_path = ?,
            watch_status = ?,
            next_episode_date = ?,
            anidb_link = ?,
            mal_link = ?,
			anime_schedule_link = ?, 
            episode_interval = ?,
            broadcast_day = ?,
            broadcast_time = ?,
            broadcast_timezone = ?,
            synopsis = ?,
            user_synopsis = ?,
            release_date = ?,
            end_date = ?,
            series_name = ?,
            media_type = ?,
            next_in_series = ?,
            mal_id = ?,
            anidb_id = ?
            WHERE id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        implode('|', $alternative_titles),
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
        $user_synopsis,
        $release_date,
        $end_date,
        $series_name,
        $media_type,
        $next_in_series,
        $mal_id,
        $anidb_id,
        $id
    ]);

    // Turleri guncelle (kanonik taksonomi).
    // setAnimeGenres icinde DELETE + INSERT pattern'i var, mevcut tur
    // baglarini tamamen replace eder. Form'dan gelen isimler
    // findOrCreateGenre ile master listede yoksa olusturulur, sonra
    // anime_genres tablosuna yazilir.
    setAnimeGenresByNames($pdo, $id, $posted_genres);

    // Cumleleri guncelle (oneri sistemi).
    // setAnimeTags icinde DELETE + INSERT pattern'i var, mevcut cumle
    // baglarini tamamen replace eder. Yeni cumleler varsa
    // findOrCreateTag once tags tablosuna ekler.
    $tag_names_raw = $_POST['tags'] ?? '';
    $tag_names = array_filter(array_map('trim', explode(',', $tag_names_raw)));
    $tag_ids = [];
    foreach ($tag_names as $tn) {
        $tid = findOrCreateTag($pdo, $tn);
        if ($tid > 0) {
            $tag_ids[] = $tid;
        }
    }
    setAnimeTags($pdo, $id, $tag_ids);

    header("Location: index.php");
    exit();
}

// Alternatif isimleri diziye çevir
$alternative_titles = !empty($anime['alternative_titles']) ? explode('|', $anime['alternative_titles']) : [];
// Mevcut turleri JOIN tablosundan cek (form yuklenirken rozet ve hidden
// input dolumu icin). Helper id+name doner; alttaki kullanim noktalari
// (hidden input value, JS init) sadece name listesine ihtiyac duyuyor.
$current_genres = getAnimeGenres($pdo, $id);
$selected_genres = array_map(function($g) { return $g['name']; }, $current_genres);
// Mevcut cumleleri cek (form yuklenirken rozet olarak gostermek icin)
$current_tags = getAnimeTags($pdo, $id);
$selected_tag_names = array_map(function($t) { return $t['name']; }, $current_tags);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anime Düzenle</title>
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
            Anime Düzenle
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
                    <input type="text" name="title" value="<?php echo htmlspecialchars($anime['title']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Alternatif İsimler:</label>
                <div class="input-area">
                    <div id="alternative-titles" class="dynamic-fields">
                        <?php foreach ($alternative_titles as $alt_title): ?>
                            <div class="field-group">
                                <input type="text" name="alternative_titles[]" value="<?php echo htmlspecialchars($alt_title); ?>">
                                <button type="button" class="remove-button" onclick="removeField(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="add-button" onclick="addAlternativeTitle()">
                        <i class="fas fa-plus"></i> Alternatif İsim Ekle
                    </button>
                </div>
            </div>

            <?php
            // Synopsis display mode:
            //   Mode 1: user_synopsis IS NULL  -> single "Konu" field, editable, writes to synopsis
            //   Mode 2: user_synopsis is set   -> "Konu" readonly (server metin), "Kisisel Konu" editable
            // See PROJE_DURUMU.md "Kisisel Konu" section for the full rationale.
            $synopsisMode = ($anime['user_synopsis'] === null) ? 1 : 2;
            ?>

            <?php if ($synopsisMode === 1): ?>
                <div class="form-group">
                    <label for="synopsis">Konu:</label>
                    <div class="input-area">
                        <textarea name="synopsis" rows="6" placeholder="Animenin konusunu yazın"><?php echo htmlspecialchars($anime['synopsis'] ?? ''); ?></textarea>
                    </div>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label for="synopsis_readonly">Konu:</label>
                    <div class="input-area">
                        <textarea id="synopsis_readonly" rows="6" readonly
                                  style="background-color: #f5f5f5; color: #555; cursor: not-allowed;"><?php echo htmlspecialchars($anime['synopsis'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">server'dan gelir, sync ile guncellenir</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="user_synopsis">Kisisel Konu:</label>
                    <div class="input-area">
                        <textarea name="user_synopsis" rows="4" placeholder="Kendi yorumunuz, cevirisi, ozeti"><?php echo htmlspecialchars($anime['user_synopsis'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">kullanici konu bolumu - silinirse sync ile geri gelmez</small>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="total_episodes">Toplam Bölüm Sayısı:</label>
                <div class="input-area">
                    <input type="number" name="total_episodes" value="<?php echo htmlspecialchars($anime['total_episodes'] ?? ''); ?>" min="0" placeholder="Bilinmiyorsa boş bırakın" oninput="toggleEndDateBySingleEpisode()">
                </div>
            </div>

            <div id="aired-episodes-section" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="aired_episodes">Yayınlanan Bölüm Sayısı:</label>
                    <div class="input-area">
                        <input type="number" name="aired_episodes" id="aired_episodes" value="<?php echo htmlspecialchars($anime['aired_episodes'] ?? ''); ?>" min="0" placeholder="Şu ana kadar yayınlanan bölüm">
                        <?php
                        // Senkronize butonu sadece MAL ID dolu animelerde gosterilir.
                        // mal_id yoksa AnimeSchedule timetable'da eslestirme yapilamaz,
                        // butonu gostermek anlamsiz olur. Anime durumu kontrolu zaten
                        // parent div ile saglaniyor (sadece "Yayın Devam Ediyor" iken
                        // bu tum bolum gorunur).
                        if (!empty($anime['mal_id'])):
                        ?>
                        <button type="button" id="aired-sync-btn" onclick="syncAiredEpisodes()" style="margin-top:8px; padding:8px 14px; background:#27ae60; color:#fff; border:none; border-radius:4px; cursor:pointer;">
                            <i class="fas fa-sync"></i> Senkronize Et
                        </button>
                        <div id="aired-sync-status" style="margin-top:8px; font-size:13px;"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="release_date">Yayın Tarihi:</label>
                <div class="input-area">
                    <input type="date" name="release_date" id="release_date" 
                           value="<?php echo isset($anime['release_date']) ? date('Y-m-d', strtotime($anime['release_date'])) : ''; ?>">
                </div>
            </div>

            <?php
                // Madde E - Tek bolumlu animede end-date bastan gizli olur.
                // Status finished AND total_episodes != 1 ise gosterilir.
                $endDateInitialDisplay = ($anime['status'] == 'Yayın Tamamlandı'
                                          && (int)($anime['total_episodes'] ?? 0) !== 1)
                                         ? 'block' : 'none';
            ?>
            <div id="end-date-section" style="display: <?php echo $endDateInitialDisplay; ?>;">
                <div class="form-group">
                    <label for="end_date">Yayın Bitiş Tarihi:</label>
                    <div class="input-area">
                        <input type="date" name="end_date" id="end_date"
                               value="<?php echo isset($anime['end_date']) ? date('Y-m-d', strtotime($anime['end_date'])) : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
    <label for="status">Yayın Durumu:</label>
    <div class="input-area">
        <?php if ($anime['status'] == 'Yayın Tamamlandı'): ?>
            <!-- Yayın tamamlandıysa, alan kilitli olsun -->
            <input type="text" name="status" value="Yayın Tamamlandı" readonly class="locked-field">
<div style="margin-top: 10px;"></div>
<input type="hidden" name="status" value="Yayın Tamamlandı">
<small class="form-text text-muted">Bu anime yayını tamamlandığı için durum değiştirilemez.</small>
        <?php else: ?>
            <select name="status" onchange="toggleBroadcastDetails()" required>
                <option value="">Seçiniz</option>
                <option value="Yayın Tamamlandı" <?php echo $anime['status'] == 'Yayın Tamamlandı' ? 'selected' : ''; ?>>Yayın Tamamlandı</option>
                <option value="Yayın Devam Ediyor" <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'selected' : ''; ?>>Yayın Devam Ediyor</option>
            </select>
        <?php endif; ?>
    </div>
</div>

            <div id="broadcast-details" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="episode_interval">Bölümler Arası Süre (Gün):</label>
                    <div class="input-area">
                        <input type="number" name="episode_interval" value="<?php echo htmlspecialchars($anime['episode_interval'] ?? 7); ?>" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_day">Yayın Günü:</label>
                    <div class="input-area">
                        <select name="broadcast_day">
                            <option value="" <?php echo empty($anime['broadcast_day']) ? 'selected' : ''; ?>>Seçiniz</option>
                            <?php
                            $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                            foreach ($days as $day) {
                                $selected = ($anime['broadcast_day'] == $day) ? 'selected' : '';
                                echo "<option value=\"$day\" $selected>$day</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_time">Yayın Saati:</label>
                    <div class="input-area">
                        <input type="time" name="broadcast_time" value="<?php echo htmlspecialchars($anime['broadcast_time'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_timezone">Yayın Saat Dilimi:</label>
                    <div class="input-area">
                        <?php
                        $current_tz = $anime['broadcast_timezone'] ?? 'Asia/Tokyo';
                        $tz_options = [
                            'Asia/Tokyo'         => 'Japonya (Tokyo) - JST',
                            'Europe/Istanbul'    => 'Türkiye (Istanbul) - TRT',
                            'UTC'                => 'UTC',
                            'America/New_York'   => 'ABD Dogu (New York) - ET',
                            'America/Los_Angeles'=> 'ABD Bati (Los Angeles) - PT',
                            'Europe/London'      => 'Birlesik Krallik (London)',
                        ];
                        ?>
                        <select name="broadcast_timezone">
                            <?php foreach ($tz_options as $tz_val => $tz_label): ?>
                                <option value="<?php echo $tz_val; ?>" <?php echo ($current_tz === $tz_val) ? 'selected' : ''; ?>><?php echo $tz_label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="watch_status">İzleme Durumu:</label>
                <div class="input-area">
                    <select name="watch_status" onchange="toggleWatchedEpisodes()" required>
                        <option value="">Seçiniz</option>
                        <option value="İzlendi" <?php echo $anime['watch_status'] == 'İzlendi' ? 'selected' : ''; ?>>İzlendi</option>
                        <option value="İzleniyor" <?php echo $anime['watch_status'] == 'İzleniyor' ? 'selected' : ''; ?>>İzleniyor</option>
                        <option value="İzlenme Planlandı" <?php echo $anime['watch_status'] == 'İzlenme Planlandı' ? 'selected' : ''; ?>>İzlenme Planlandı</option>
                    </select>
                </div>
            </div>

            <div id="watched-episodes-section" style="display: <?php echo $anime['watch_status'] == 'İzleniyor' ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="watched_episodes">İzlenen Bölüm Sayısı:</label>
                    <div class="input-area">
                        <input type="number" name="watched_episodes" value="<?php echo htmlspecialchars($anime['watched_episodes']); ?>" min="0">
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
                        <!-- Seçilen tür etiketleri JavaScript ile doldurulacak -->
                    </div>
                    <input type="hidden" name="genres" id="genres-input" value="<?php echo htmlspecialchars(implode(',', $selected_genres)); ?>">
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
                        <!-- Secilen cumle rozetleri JS ile doldurulacak -->
                    </div>
                    <input type="hidden" name="tags" id="tags-input" value="<?php echo htmlspecialchars(implode(',', $selected_tag_names)); ?>">
                    <small class="form-text text-muted">
                        Yazinca eslesenler gozukur. Eslesme yoksa Enter ile yeni cumle olusturulur.
                        <a href="manage_tags.php">Cumleleri yonet</a>
                    </small>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notlar:</label>
                <div class="input-area">
                    <textarea name="notes" rows="4"><?php echo htmlspecialchars($anime['notes']); ?></textarea>
                    <small class="form-text text-muted">notlar bolumu silinirse sync ile geri gelmez</small>
                </div>
            </div>

            <div class="form-group">
                <label for="series_name">Seri Adı (opsiyonel):</label>
                <div class="input-area">
                    <input type="text" name="series_name" id="series_name" list="series-name-list" value="<?php echo htmlspecialchars($anime['series_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Orn: Detective Conan, Spy x Family">
                    <datalist id="series-name-list">
                        <?php foreach ($seriesNames as $sn): ?>
                            <option value="<?php echo htmlspecialchars($sn, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php endforeach; ?>
                    </datalist>
                    <small class="form-text text-muted">Aynı seriye ait animeler bu adı paylaşır.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="media_type">Medya Türü (opsiyonel):</label>
                <div class="input-area">
                    <select name="media_type" id="media_type">
                        <option value="">Seçiniz</option>
                        <option value="TV" <?php echo ($anime['media_type'] ?? '') === 'TV' ? 'selected' : ''; ?>>TV</option>
                        <option value="Film" <?php echo ($anime['media_type'] ?? '') === 'Film' ? 'selected' : ''; ?>>Film</option>
                        <option value="OVA" <?php echo ($anime['media_type'] ?? '') === 'OVA' ? 'selected' : ''; ?>>OVA</option>
                        <option value="Special" <?php echo ($anime['media_type'] ?? '') === 'Special' ? 'selected' : ''; ?>>Special</option>
                        <option value="ONA" <?php echo ($anime['media_type'] ?? '') === 'ONA' ? 'selected' : ''; ?>>ONA</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="next_in_series">Sıradaki Anime (opsiyonel):</label>
                <div class="input-area">
                    <select name="next_in_series" id="next_in_series">
                        <option value="">Seçiniz</option>
                        <?php foreach ($allAnimes as $a): ?>
                            <option value="<?php echo (int)$a['id']; ?>" <?php echo ((int)($anime['next_in_series'] ?? 0)) === (int)$a['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($a['media_type'])): ?>(<?php echo htmlspecialchars($a['media_type']); ?>)<?php endif; ?>
                                <?php if (!empty($a['series_name']) && $a['series_name'] === ($anime['series_name'] ?? '')): ?>★<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Bu animeyi bitirdikten sonra izlenecek anime. ★ = aynı seri.</small>
                </div>
            </div>

            <div class="form-group">
                <label for="anidb_link">AniDB Linki: <span style="color:#d32f2f;">*</span></label>
                <div class="input-area">
                    <input type="url" name="anidb_link" required placeholder="https://anidb.net/anime/12345 veya /episode/12345" value="<?php echo htmlspecialchars($anime['anidb_link'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="mal_link">MyAnimeList Linki: <span style="color:#d32f2f;">*</span></label>
                <div class="input-area">
                    <input type="url" name="mal_link" required placeholder="https://myanimelist.net/anime/12345" value="<?php echo htmlspecialchars($anime['mal_link'] ?? ''); ?>">
                </div>
            </div>
			<div class="form-group">
                <label for="anime_schedule_link">AnimeSchedule Linki:</label>
                <div class="input-area">
                    <input type="url" name="anime_schedule_link" id="anime_schedule_link" value="<?php echo htmlspecialchars($anime['anime_schedule_link'] ?? ''); ?>" placeholder="https://animeschedule.net/anime/...">
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
                        <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(this)">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-upload"></i> Dosya Seç
                        </label>
                        <span class="file-name-display" id="file-name">
                            <?php echo basename($anime['image_path']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="button-group">
                <input type="submit" value="Güncelle" class="submit-button">
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
            const statusEl = document.querySelector('select[name="status"]');
            if (!statusEl) return; // readonly mode, no select
            const status = statusEl.value;
            const broadcastDetails = document.getElementById('broadcast-details');
            const airedSection = document.getElementById('aired-episodes-section');
            const endDateSection = document.getElementById('end-date-section');

            if (status === 'Yayın Devam Ediyor') {
                broadcastDetails.style.display = 'block';
                airedSection.style.display = 'block';
                endDateSection.style.display = 'none';
            } else if (status === 'Yayın Tamamlandı') {
                broadcastDetails.style.display = 'none';
                airedSection.style.display = 'none';
                // Madde E - Tek bolumde end_date gizli kalir, status finished olsa bile.
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
            // total_episodes degisikliginde end-date gorunurlugunu yeniden hesapla.
            // Edit modunda status select bazen readonly olabilir (yayin tamamlandi
            // animeler icin kilitli alan); o durumda toggleBroadcastDetails erken
            // donus yapar. Bu yuzden gorunurluk kararini burada bagimsiz veriyoruz:
            // status hidden input'tan da okunabilir, sadece total_episodes ve status
            // birlikte degerlendirilir.
            const endDateSection = document.getElementById('end-date-section');
            if (!endDateSection) return;

            const statusSelect = document.querySelector('select[name="status"]');
            const statusHidden = document.querySelector('input[type="hidden"][name="status"]');
            const status = statusSelect ? statusSelect.value
                                        : (statusHidden ? statusHidden.value : '');

            // Status finished AND tek bolum degil ise goster, aksi halde gizle.
            if (status === 'Yayın Tamamlandı' && !isSingleEpisode()) {
                endDateSection.style.display = 'block';
            } else {
                endDateSection.style.display = 'none';
            }
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
                    const aired = document.querySelector('input[name="aired_episodes"]') ?
                                  document.querySelector('input[name="aired_episodes"]').value : '';
                    document.querySelector('input[name="watched_episodes"]').value =
                        total || aired || '0';
                } else if (watchStatus === 'İzlenme Planlandı') {
                    document.querySelector('input[name="watched_episodes"]').value = '0';
                }
            }
        }

        // Tür yönetimi için değişkenler ve fonksiyonlar
        let selectedGenres = <?php echo json_encode($selected_genres); ?>;

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
                fetch('add_genre.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'genre=' + encodeURIComponent(genre)
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
         * Same behaviour as add_anime.php, with one extra: selectedTags
         * is initialised from the anime's existing tag list so the user
         * sees the current tags as badges on page load.
         * --------------------------------------------------------------- */
        const allTags = <?php echo json_encode(array_map(function($t) { return $t['name']; }, $allTags), JSON_UNESCAPED_UNICODE); ?>;
        let selectedTags = <?php echo json_encode($selected_tag_names, JSON_UNESCAPED_UNICODE); ?>;

        const tagInput = document.getElementById('tag-input');
        const tagSuggestions = document.getElementById('tag-suggestions');

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, function(c) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            });
        }

        function renderTagSuggestions() {
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
            if (selectedTags.some(t => t.toLowerCase() === name.toLowerCase())) {
                return;
            }
            selectedTags.push(name);
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
            container.querySelectorAll('button[data-tag-name]').forEach(btn => {
                btn.addEventListener('click', () => removeTag(btn.dataset.tagName));
            });
            hidden.value = selectedTags.join(',');
        }

        tagInput.addEventListener('input', renderTagSuggestions);
        tagInput.addEventListener('focus', renderTagSuggestions);

        tagSuggestions.addEventListener('click', e => {
            const item = e.target.closest('.tag-suggestion-item');
            if (item) {
                addTag(item.dataset.name);
            }
        });

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

        document.addEventListener('click', e => {
            if (!e.target.closest('.tag-input-wrapper')) {
                tagSuggestions.style.display = 'none';
            }
        });

        // Sayfa yüklendiğinde tür etiketlerini göster
        document.addEventListener('DOMContentLoaded', function() {
            updateGenreTags();
            updateSelectedTags();
        });

        // Sayfa yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    updateGenreTags();
    
    // Yayın durumu "Yayın Tamamlandı" ise, ilgili alanları devre dışı bırak
    const status = "<?php echo $anime['status']; ?>";
    if (status === 'Yayın Tamamlandı') {
        // Yayın detayları bölümünü gizle
        document.getElementById('broadcast-details').style.display = 'none';
    }
});

    // ====================================================================
    // AnimeSchedule "Otomatik Doldur" button
    // ====================================================================
    //
    // Identical to add_anime.php. Calls fetch_animeschedule.php with the
    // URL from the anime_schedule_link input. Only fills empty form
    // fields - existing values (which include all the data the user
    // entered in previous edits) are preserved.
    //
    // broadcast_timezone special case: "Asia/Tokyo" is treated as
    // "default / unset" because that's the value setup gives a brand
    // new install. If the user explicitly picked a different timezone
    // (Europe/Istanbul, UTC, etc.) we leave it alone.
    //
    // status: if the anime is locked as "Yayın Tamamlandı" the form
    // uses a readonly text input + a hidden input both named "status".
    // querySelector picks the first one (the readonly text), and
    // assigning to its .value does not change what the form submits
    // (the hidden one carries the canonical value). Effectively this
    // means the API cannot flip a finished anime back to ongoing - the
    // existing locking behaviour is respected.
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

        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

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

            for (const fieldName in fields) {
                if (!Object.prototype.hasOwnProperty.call(fields, fieldName)) continue;
                const value = fields[fieldName];
                const el = document.querySelector('[name="' + fieldName + '"]');
                if (!el) {
                    skipped.push(fieldName + ' (alan bulunamadi)');
                    continue;
                }

                let isEmpty;
                if (fieldName === 'broadcast_timezone') {
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

    // ---------------------------------------------------------------
    // "Senkronize Et" button next to aired_episodes (Madde C)
    // ---------------------------------------------------------------
    //
    // Posts the current anime id to fetch_aired_episodes.php, which
    // queries AnimeSchedule's /timetables/sub endpoint and writes the
    // newest EpisodeNumber it finds into the DB. The response carries
    // both the new and old values so we can show the user what changed.
    //
    // Note that the DB is updated by the server before this AJAX
    // returns - the form's "Kaydet" button is not required for the
    // aired_episodes change to stick. We still update the input value
    // so the user sees the new number, and so any later "Kaydet" does
    // not silently revert it to the old value displayed in the form.
    function syncAiredEpisodes() {
        const btn       = document.getElementById('aired-sync-btn');
        const statusDiv = document.getElementById('aired-sync-status');
        const input     = document.getElementById('aired_episodes');
        if (!btn || !statusDiv || !input) return;

        const csrfInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = csrfInput ? csrfInput.value : '';
        const animeId   = <?php echo (int)$id; ?>;

        btn.disabled = true;
        statusDiv.style.color = '#555';
        statusDiv.textContent = 'AnimeSchedule den bolum sayisi cekiliyor...';

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('anime_id', animeId);

        fetch('fetch_aired_episodes.php', {
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

            // Write the new value into the form input. The server has
            // already saved this to the DB; updating the input keeps
            // the form in sync so a later "Kaydet" sends the same value
            // back instead of overwriting with stale form state.
            input.value = data.aired_episodes;

            const newVal = data.aired_episodes;
            const oldVal = data.old_value;
            const offset = data.week_offset;

            let weekNote = '';
            if (offset === 0) {
                weekNote = ' (bu hafta)';
            } else if (offset === 1) {
                weekNote = ' (gecen hafta)';
            } else {
                weekNote = ' (' + offset + ' hafta once)';
            }

            statusDiv.style.color = '#27ae60';
            if (data.changed) {
                statusDiv.textContent = 'Guncellendi: ' + (oldVal === null ? '?' : oldVal) + ' -> ' + newVal + weekNote;
            } else {
                statusDiv.textContent = 'Mevcut deger zaten guncel: ' + newVal + weekNote;
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