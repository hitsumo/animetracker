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

// Türleri çek
$genre_stmt = $pdo->query("SELECT name FROM genres ORDER BY name ASC");
$genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $status = $_POST['status'];
    $total_episodes = $_POST['total_episodes'];
    $watched_episodes = $_POST['watched_episodes'];
    $notes = $_POST['notes'];
    $genres = $_POST['genres'];
    $watch_status = $_POST['watch_status'];
    $next_episode_date = $_POST['next_episode_date'] ?? null;
    $anidb_link = $_POST['anidb_link'] ?? '';
    $mal_link = $_POST['mal_link'] ?? '';
    $episode_interval = $_POST['episode_interval'] ?? 7;
    $broadcast_day = $_POST['broadcast_day'] ?? '';
    $broadcast_time = $_POST['broadcast_time'] ?? '';
    $synopsis = $_POST['synopsis'] ?? '';
    $alternative_titles = isset($_POST['alternative_titles']) ? array_filter($_POST['alternative_titles']) : [];

    $image_path = $_POST['existing_image'];
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    $sql = "UPDATE animes SET 
        title = ?, 
        alternative_titles = ?,
        status = ?, 
        total_episodes = ?, 
        watched_episodes = ?, 
        notes = ?, 
        genres = ?, 
        image_path = ?,
        watch_status = ?,
        next_episode_date = ?,
        anidb_link = ?,
        mal_link = ?,
        episode_interval = ?,
        broadcast_day = ?,
        broadcast_time = ?,
        synopsis = ?
        WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        implode('|', $alternative_titles),
        $status, 
        $total_episodes, 
        $watched_episodes, 
        $notes, 
        $genres, 
        $image_path,
        $watch_status,
        $next_episode_date,
        $anidb_link,
        $mal_link,
        $episode_interval,
        $broadcast_day,
        $broadcast_time,
        $synopsis,
        $id
    ]);

    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$sql = "SELECT * FROM animes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

// Alternatif isimleri diziye çevir
$alternative_titles = !empty($anime['alternative_titles']) ? explode('|', $anime['alternative_titles']) : [];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anime Düzenle</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
</head>
<body>
    <div class="container">
    <div class="page-title anime-edit-title">
        Anime Düzenleme
    </div>

        <div class="button-container">
            <a class="anime-list-button" href="index.php">Anime İzleme Listesi</a>
        </div>
        <div class="button-spacing"></div>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $anime['id']; ?>">
            <input type="hidden" name="existing_image" value="<?php echo $anime['image_path']; ?>">

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
                    <textarea name="synopsis" rows="6" placeholder="Animenin konusunu yazın"><?php echo htmlspecialchars($anime['synopsis']); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="total_episodes">Toplam Bölüm Sayısı:</label>
                <div class="input-area">
                    <input type="number" name="total_episodes" value="<?php echo $anime['total_episodes']; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="status">Yayın Durumu:</label>
                <div class="input-area">
                    <select name="status" onchange="toggleBroadcastDetails()" required>
                        <option value="">Seçiniz</option>
                        <option value="Yayın Tamamlandı" <?php echo $anime['status'] == 'Yayın Tamamlandı' ? 'selected' : ''; ?>>Yayın Tamamlandı</option>
                        <option value="Yayın Devam Ediyor" <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'selected' : ''; ?>>Yayın Devam Ediyor</option>
                    </select>
                </div>
            </div>

            <div id="broadcast-details" style="display: <?php echo $anime['status'] == 'Yayın Devam Ediyor' ? 'block' : 'none'; ?>">
                <div class="form-group">
                    <label for="episode_interval">Bölümler Arası Süre (Gün):</label>
                    <div class="input-area">
                        <input type="number" name="episode_interval" value="<?php echo $anime['episode_interval']; ?>" min="1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_day">Yayın Günü:</label>
                    <div class="input-area">
                        <select name="broadcast_day">
                            <?php
                            $days = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];
                            foreach ($days as $day) {
                                echo "<option value=\"$day\"" . ($anime['broadcast_day'] == $day ? ' selected' : '') . ">$day</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="broadcast_time">Yayın Saati:</label>
                    <div class="input-area">
                        <input type="time" name="broadcast_time" value="<?php echo $anime['broadcast_time']; ?>">
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
                        <input type="number" name="watched_episodes" value="<?php echo $anime['watched_episodes']; ?>" min="0" required>
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
                        <!-- Seçili türler burada gösterilecek -->
                    </div>
                    <input type="hidden" name="genres" id="genres-input" value="<?php echo htmlspecialchars($anime['genres']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notlar:</label>
                <div class="input-area">
                    <textarea name="notes" rows="4"><?php echo htmlspecialchars($anime['notes']); ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label for="anidb_link">AniDB Linki:</label>
                <div class="input-area">
                    <input type="url" name="anidb_link" value="<?php echo htmlspecialchars($anime['anidb_link']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="mal_link">MyAnimeList Linki:</label>
                <div class="input-area">
                    <input type="url" name="mal_link" value="<?php echo htmlspecialchars($anime['mal_link']); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="image">Resim:</label>
                <div class="input-area">
                    <div class="file-upload">
                        <input type="file" name="image" id="image" accept="image/*" onchange="updateFileName(this)">
                        <label for="image" class="file-upload-label">
                            <i class="fas fa-upload"></i> Yeni Resim Seç
                        </label>
                        <span class="file-name-display" id="file-name">Mevcut resim kullanılıyor</span>
                    </div>
                    <?php if (!empty($anime['image_path'])): ?>
                        <div class="current-image">
                            <img src="<?php echo htmlspecialchars($anime['image_path']); ?>" 
                                 alt="Mevcut resim" 
                                 style="max-width: 200px; margin-top: 10px;">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="button-group">
                <input type="submit" value="Kaydet" class="submit-button">
                <a href="index.php" class="cancel-button">Vazgeç</a>
            </div>
        </form>
    </div>

    <script>
        // Mevcut türleri yükle
        let selectedGenres = '<?php echo $anime['genres']; ?>'.split(',').map(genre => genre.trim());
        updateGenreTags();

        function updateFileName(input) {
            const fileName = input.files[0]?.name;
            document.getElementById('file-name').textContent = fileName || 'Mevcut resim kullanılıyor';
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
            broadcastDetails.style.display = status === 'Yayın Devam Ediyor' ? 'block' : 'none';
        }

        function toggleWatchedEpisodes() {
            const watchStatus = document.querySelector('select[name="watch_status"]').value;
            const watchedEpisodesDiv = document.getElementById('watched-episodes-section');
            const watchedEpisodesInput = document.querySelector('input[name="watched_episodes"]');
            const totalEpisodes = document.querySelector('input[name="total_episodes"]').value;

            if (watchStatus === 'İzleniyor') {
                watchedEpisodesDiv.style.display = 'block';
            } else {
                watchedEpisodesDiv.style.display = 'none';
                if (watchStatus === 'İzlendi') {
                    watchedEpisodesInput.value = totalEpisodes;
                } else if (watchStatus === 'İzlenme Planlandı') {
                    watchedEpisodesInput.value = '0';
                }
            }
        }

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