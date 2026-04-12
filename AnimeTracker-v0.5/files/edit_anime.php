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

// Türleri veritabanından çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

// Seri adlarini cek (datalist auto-complete icin)
$seriesNames = getAllSeriesNames($pdo);

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
    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $broadcast_timezone = $_POST['broadcast_timezone'] ?? 'Asia/Tokyo';
    $synopsis = $_POST['synopsis'] ?? '';
    $release_date = $_POST['release_date'] ?? null;

    // Series relationship fields
    $series_name = $_POST['series_name'] ?? null;
    $media_type = $_POST['media_type'] ?? null;
    $next_in_series = $_POST['next_in_series'] ?? null;

    // MySQL'in TIME / DATE / DATETIME kolonlari bos string kabul etmez,
    // sadece NULL veya gecerli bir tarih/saat. Form bos gonderirse '' gelir,
    // bunu NULL'a cevirerek INSERT/UPDATE hatasini engelliyoruz.
    if ($broadcast_time === '') { $broadcast_time = null; }
    if ($release_date === '')   { $release_date = null; }
    if ($next_episode_date === '') { $next_episode_date = null; }

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

    // Animeyi güncelle
    $sql = "UPDATE animes SET 
            title = ?,
            alternative_titles = ?,
            status = ?,
            total_episodes = ?,
            aired_episodes = ?,
            watched_episodes = ?,
            notes = ?,
            genres = ?,
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
            release_date = ?,
            series_name = ?,
            media_type = ?,
            next_in_series = ?
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
        implode(',', $posted_genres),
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
        $series_name,
        $media_type,
        $next_in_series,
        $id
    ]);

    header("Location: index.php");
    exit();
}

// Alternatif isimleri diziye çevir
$alternative_titles = !empty($anime['alternative_titles']) ? explode('|', $anime['alternative_titles']) : [];
// Türleri diziye çevir
$selected_genres = !empty($anime['genres']) ? explode(',', $anime['genres']) : [];
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

            <div class="form-group">
                <label for="synopsis">Konu:</label>
                <div class="input-area">
                    <textarea name="synopsis" rows="6" placeholder="Animenin konusunu yazın"><?php echo htmlspecialchars($anime['synopsis'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="total_episodes">Toplam Bölüm Sayısı:</label>
                <div class="input-area">
                    <input type="number" name="total_episodes" value="<?php echo htmlspecialchars($anime['total_episodes'] ?? ''); ?>" min="0" placeholder="Bilinmiyorsa boş bırakın">
                </div>
            </div>

            <div id="aired-episodes-section" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>;">
                <div class="form-group">
                    <label for="aired_episodes">Yayınlanan Bölüm Sayısı:</label>
                    <div class="input-area">
                        <input type="number" name="aired_episodes" value="<?php echo htmlspecialchars($anime['aired_episodes'] ?? ''); ?>" min="0" placeholder="Şu ana kadar yayınlanan bölüm">
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
                <label for="notes">Notlar:</label>
                <div class="input-area">
                    <textarea name="notes" rows="4"><?php echo htmlspecialchars($anime['notes']); ?></textarea>
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
                <label for="anidb_link">AniDB Linki:</label>
                <div class="input-area">
                    <input type="url" name="anidb_link" value="<?php echo htmlspecialchars($anime['anidb_link'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="mal_link">MyAnimeList Linki:</label>
                <div class="input-area">
                    <input type="url" name="mal_link" value="<?php echo htmlspecialchars($anime['mal_link'] ?? ''); ?>">
                </div>
            </div>
			<div class="form-group">
                <label for="anime_schedule_link">AnimeSchedule Linki:</label>
                <div class="input-area">
                    <input type="url" name="anime_schedule_link" value="<?php echo htmlspecialchars($anime['anime_schedule_link'] ?? ''); ?>" placeholder="https://animeschedule.net/anime/...">
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
            const status = document.querySelector('select[name="status"]').value;
            const broadcastDetails = document.getElementById('broadcast-details');
            const airedSection = document.getElementById('aired-episodes-section');

            // Broadcast details (interval, day, time) only matter for ongoing anime
            if (status === 'Yayın Devam Ediyor') {
                broadcastDetails.style.display = 'block';
            } else {
                broadcastDetails.style.display = 'none';
            }

            // Aired episodes field only makes sense for ongoing anime.
            // For finished anime the total episode count is used instead.
            if (status === 'Yayın Devam Ediyor') {
                airedSection.style.display = 'block';
            } else {
                airedSection.style.display = 'none';
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

        // Sayfa yüklendiğinde tür etiketlerini göster
        document.addEventListener('DOMContentLoaded', function() {
            updateGenreTags();
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
    </script>
</body>
</html>