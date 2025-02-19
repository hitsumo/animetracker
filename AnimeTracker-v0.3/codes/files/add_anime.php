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


require_once 'functions.php';
$pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');

// Türleri veritabanından çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'];
    $watched_episodes = $_POST['watched_episodes'];
    $notes = $_POST['notes'];
    $alternative_titles = isset($_POST['alternative_titles']) ? array_filter($_POST['alternative_titles']) : [];
    $genres = !empty($_POST['genres']) ? explode(',', $_POST['genres']) : [];   
    $watch_status = $_POST['watch_status'];
    $next_episode_date = $_POST['next_episode_date'] ?? null;
    $anidb_link = $_POST['anidb_link'] ?? '';
    $mal_link = $_POST['mal_link'] ?? '';
    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $synopsis = $_POST['synopsis'] ?? '';
    $release_date = $_POST['release_date'] ?? null;

    // Resim yükleme
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);

    // Sonraki bölüm tarihini hesapla
    if ($status === 'Yayın Devam Ediyor' && !empty($broadcast_day) && !empty($broadcast_time)) {
        $next_episode_date = calculateNextEpisodeDate([
            'status' => $status,
            'broadcast_day' => $broadcast_day,
            'broadcast_time' => $broadcast_time
        ]);
    }

    // Animeyi veritabanına ekle
    $sql = "INSERT INTO animes (title, alternative_titles, status, total_episodes, watched_episodes, notes, genres, image_path, watch_status, next_episode_date, anidb_link, mal_link, episode_interval, broadcast_day, broadcast_time, synopsis, release_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        !empty($alternative_titles) ? implode('|', $alternative_titles) : '',
        $status,
        $total_episodes,
        $watched_episodes,
        $notes,
        implode(',', $genres),
        $target_file,
        $watch_status,
        $next_episode_date,
        $anidb_link,
        $mal_link,
        $episode_interval,
        $broadcast_day,
        $broadcast_time,
        $synopsis,
        $release_date
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
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
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
                <input type="number" name="total_episodes" required>
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
                    <input type="number" name="watched_episodes" value="0" min="0" required>
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
            <label for="anidb_link">AniDB Linki:</label>
            <div class="input-area">
                <input type="url" name="anidb_link">
            </div>
        </div>

        <div class="form-group">
            <label for="mal_link">MyAnimeList Linki:</label>
            <div class="input-area">
                <input type="url" name="mal_link">
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
        if (status === 'Yayın Devam Ediyor') {
            broadcastDetails.style.display = 'block';
        } else {
            broadcastDetails.style.display = 'none';
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
                document.querySelector('input[name="watched_episodes"]').value = 
                document.querySelector('input[name="total_episodes"]').value;
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