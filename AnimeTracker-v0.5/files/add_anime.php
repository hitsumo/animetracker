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

// Türleri veritabanından çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

// Seri adlarini cek (datalist auto-complete icin)
$seriesNames = getAllSeriesNames($pdo);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
    // parse ediliyor. URL'ler bos veya tanimadigimiz formatta ise kullaniciyi
    // geri yonlendir, hata goster.
    $validation_errors = [];
    $mal_id = parseMalId($mal_link);
    $anidb_id = parseAnidbId($anidb_link);

    if (empty(trim($mal_link))) {
        $validation_errors[] = 'MyAnimeList linki zorunludur.';
    } elseif ($mal_id === null) {
        $validation_errors[] = 'MyAnimeList linki gecersiz format. Ornek: https://myanimelist.net/anime/12345';
    }

    if (empty(trim($anidb_link))) {
        $validation_errors[] = 'AniDB linki zorunludur.';
    } elseif ($anidb_id === null) {
        $validation_errors[] = 'AniDB linki gecersiz format. Ornek: https://anidb.net/anime/12345';
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

    // Series relationship fields (v0.5 mid-cycle)
    $series_name = $_POST['series_name'] ?? null;
    $media_type = $_POST['media_type'] ?? null;
    $next_in_series = $_POST['next_in_series'] ?? null;

    // MySQL'in TIME / DATE / DATETIME kolonlari bos string kabul etmez,
    // sadece NULL veya gecerli bir tarih/saat. Form bos gonderirse '' gelir,
    // bunu NULL'a cevirerek INSERT hatasini engelliyoruz.
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
    $sql = "INSERT INTO animes (title, alternative_titles, status, total_episodes, aired_episodes, watched_episodes, notes, genres, image_path, watch_status, next_episode_date, anidb_link, mal_link, anime_schedule_link, episode_interval, broadcast_day, broadcast_time, broadcast_timezone, synopsis, release_date, series_name, media_type, next_in_series, mal_id, anidb_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        !empty($alternative_titles) ? implode('|', $alternative_titles) : '',
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
        $mal_id,
        $anidb_id
    ]);

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
                <input type="number" name="total_episodes" min="0" placeholder="Bilinmiyorsa boş bırakın">
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
            <label for="notes">Notlar:</label>
            <div class="input-area">
                <textarea name="notes" rows="4"></textarea>
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
                <input type="url" name="anidb_link" required placeholder="https://anidb.net/anime/12345">
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
        <input type="url" name="anime_schedule_link" placeholder="https://animeschedule.net/anime/...">
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
    </script>
</body>
</html>