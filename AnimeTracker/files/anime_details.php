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

$id = $_GET['id'];
$sql = "SELECT * FROM animes WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

$anidb_safe = safe_url($anime['anidb_link'] ?? '');
            $mal_safe   = safe_url($anime['mal_link']   ?? '');
			$schedule_safe = safe_url($anime['anime_schedule_link'] ?? '');

if (!$anime) {
    echo "Anime bulunamadı.";
    exit();
}

if (!empty($anime['next_episode_date'])) {
    updateNextEpisodeDate($pdo, $anime);
    $stmt->execute([$id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Anime tamamlanmis mi kontrol et
checkIfAnimeCompleted($pdo, $anime);

// Series relationship data
$relatedAnimes = getRelatedAnimes($pdo, $anime['series_name'] ?? null, $anime['id']);
$chronologyMarkers = getChronologyMarkers($pdo, $anime['id']);
$chronologyAlert = getActiveChronologyAlert($pdo, $anime['id'], $anime['watched_episodes']);

// Siradaki anime bilgisi (next_in_series foreign key)
$nextAnime = null;
if (!empty($anime['next_in_series'])) {
    $nextStmt = $pdo->prepare("SELECT id, title, watch_status, media_type, image_path FROM animes WHERE id = ?");
    $nextStmt->execute([(int)$anime['next_in_series']]);
    $nextAnime = $nextStmt->fetch(PDO::FETCH_ASSOC);
}

// Check if this anime is part of a next_in_series chain (either it
// points forward or another anime points to it). Used to show the
// "Seri Kronolojisi" button.
$isInSeriesChain = !empty($anime['next_in_series']);
if (!$isInSeriesChain) {
    $chainCheck = $pdo->prepare("SELECT COUNT(*) FROM animes WHERE next_in_series = ?");
    $chainCheck->execute([(int)$anime['id']]);
    $isInSeriesChain = ((int)$chainCheck->fetchColumn() > 0);
    $chainCheck->closeCursor();
}

// Ayni serideki tum animeler (marker ekleme formu dropdown'u icin)
$sameSeriesAnimes = [];
if (!empty($anime['series_name'])) {
    $ssStmt = $pdo->prepare("SELECT id, title, media_type FROM animes WHERE series_name = ? AND id != ? ORDER BY title ASC");
    $ssStmt->execute([$anime['series_name'], (int)$anime['id']]);
    $sameSeriesAnimes = $ssStmt->fetchAll(PDO::FETCH_ASSOC);
}

// 0.6.1 - Duygu Etiketleri (single-user)
// Bu anime icin kullanicinin koydugu duygu isaretlerini yukle. Single-user
// modda user_id=1 sabit; Faz 2 multi-user gecisinde $_SESSION['user_id']
// olur (KARARLAR Bolum 5 Faz 2 tasinacaklar listesi).
$emoStmt = $pdo->prepare(
    "SELECT emotion FROM user_anime_emotion
      WHERE user_id = 1 AND anime_id = ?"
);
$emoStmt->execute([(int)$anime['id']]);
$currentEmotions = $emoStmt->fetchAll(PDO::FETCH_COLUMN, 0);
$emoStmt->closeCursor();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['title']); ?> - Detaylar</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <h1>
            <div class="anime-title-container">
                <div class="anime-title page-title">
                    <?php echo htmlspecialchars($anime['title']); ?>
                </div>
            </div>
        </h1>
        
        <div class="anime-header">
            <div class="anime-cover-container">
                <img src="<?php echo htmlspecialchars($anime['image_path']); ?>" 
                    alt="<?php echo htmlspecialchars($anime['title']); ?>" 
                    class="anime-cover">
            </div>
        </div>

        <div class="anime-details-container">
            <div class="anime-details">
                <div class="detail-row">
                    <span class="detail-label">Durum:</span>
                    <span class="detail-value status"><?php echo htmlspecialchars($anime['status']); ?></span>
                </div>

               

                <div class="detail-row">
    <span class="detail-label">Toplam Bölüm:</span>
    <span class="detail-value episode"><?php
        // v0.5+: total_episodes can be NULL for ongoing anime with
        // unknown final episode count (One Piece, Detective Conan).
        if (!empty($anime['total_episodes'])) {
            echo htmlspecialchars($anime['total_episodes']);
        } else {
            echo '<em>Bilinmiyor</em>';
        }
    ?></span>
</div>

<?php if (!empty($anime['aired_episodes'])): ?>
<div class="detail-row">
    <span class="detail-label">Yayınlanan Bölüm:</span>
    <span class="detail-value episode"><?php echo htmlspecialchars($anime['aired_episodes']); ?></span>
</div>
<?php endif; ?>

<!-- Yayin tarihi -->
<div class="detail-row">
    <span class="detail-label">Yayın Tarihi:</span>
    <span class="detail-value">
        <?php 
        if (!empty($anime['release_date'])) {
            echo date('d.m.Y', strtotime($anime['release_date']));
        } else {
            echo 'Belirtilmemiş';
        }
        ?>
    </span>
</div>
<?php
// Madde E - Tek bolumlu animede yayin bitis tarihi anlamsiz (baslangic = bitis).
// Status finished AND end_date dolu AND total_episodes 1 degil ise goster.
if ($anime['status'] == 'Yayın Tamamlandı'
    && !empty($anime['end_date'])
    && (int)($anime['total_episodes'] ?? 0) !== 1):
?>
<div class="detail-row">
    <span class="detail-label">Yayın Bitiş Tarihi:</span>
    <span class="detail-value">
        <?php echo date('d.m.Y', strtotime($anime['end_date'])); ?>
    </span>
</div>
<?php endif; ?>
<?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
<div class="detail-row" style="margin-top: -8px;">
    <span class="detail-label"></span>
    <span class="detail-value" style="font-size: 11px; color: #6c757d; font-style: italic;">
        Saat bilgisi <a href="<?php echo $schedule_safe ?: 'https://animeschedule.net'; ?>" target="_blank" rel="noopener noreferrer" style="color: #6c757d; text-decoration: underline;">AnimeSchedule</a>'den alinmistir
    </span>
</div>
<?php endif; ?>

                <div class="detail-row">
                    <span class="detail-label">İzlenen Bölüm:</span>
                    <span class="detail-value episode"><?php echo htmlspecialchars($anime['watched_episodes']); ?></span>
                </div>

                <?php if (!empty($anime['synopsis'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Konu:</span>
                    <span class="detail-value synopsis"><?php echo nl2br(htmlspecialchars($anime['synopsis'])); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($anime['user_synopsis'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Kişisel Konu:</span>
                    <span class="detail-value synopsis"><?php echo nl2br(htmlspecialchars($anime['user_synopsis'])); ?></span>
                </div>
                <?php endif; ?>

            

                <div class="detail-row">
                    <span class="detail-label">Türler:</span>
                    <div class="detail-value genres">
                        <?php
                        // Genres come from the anime_genres join table.
                        // Helper returns rows with id and name; trim is no
                        // longer needed because the names are stored
                        // canonically in the genres table.
                        $genre_rows = getAnimeGenres($pdo, $anime['id']);
                        foreach ($genre_rows as $genre_row): ?>
                            <span class="genre-tag"><?php echo htmlspecialchars($genre_row['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">İzleme Durumu:</span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo watch_status_css_class($anime['watch_status']); ?>">
                            <?php echo htmlspecialchars(watch_status_label($anime['watch_status'])); ?>
                        </span>
                    </span>
                </div>

                <!-- 0.6.1 - Duygu Etiketleri (single-user). Kullanici bu
                     animeye en fazla 3 duygu isareti koyabilir. Tikla =
                     toggle (varsa kaldir, yoksa ekle); 3'e ulasinca diger
                     pasif butonlar disabled olur. Sunucu tarafi update_emotion.php
                     ayni siniri zorlar (UI bypass edilirse sunucu reddeder).
                     KARARLAR Bolum 8 v1 spec. -->
                <div class="detail-row emotion-row">
                    <span class="detail-label">Duygu:</span>
                    <div class="detail-value">
                        <div class="emotion-toolbar"
                             data-anime-id="<?php echo (int)$anime['id']; ?>"
                             data-csrf="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <?php foreach (emotion_options() as $value => $label):
                                $isActive = in_array($value, $currentEmotions, true);
                                $atMax    = (count($currentEmotions) >= 3 && !$isActive);
                            ?>
                                <button type="button"
                                        class="emotion-btn emotion-btn-<?php echo emotion_css_class($value); ?><?php echo $isActive ? ' is-active' : ''; ?>"
                                        data-emotion="<?php echo htmlspecialchars($value); ?>"
                                        <?php echo $atMax ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </button>
                            <?php endforeach; ?>
                            <span class="emotion-toolbar-meta">
                                <span class="emotion-count"><?php echo count($currentEmotions); ?></span>/3
                            </span>
                        </div>
                    </div>
                </div>

                <?php if ($anime['status'] == 'Yayın Devam Ediyor'): ?>
                <div class="broadcast-info">
                    <div class="detail-row">
                        <span class="detail-label">Yayın Günü:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($anime['broadcast_day'] ?? 'Belirtilmemiş'); ?></span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Yayın Saati:</span>
                        <span class="detail-value broadcast-time"><?php echo htmlspecialchars(!empty($anime['broadcast_time']) ? substr($anime['broadcast_time'], 0, 5) : 'Belirtilmemiş'); ?></span>
                    </div>

     <div class="detail-row">
    <span class="detail-label">Sonraki Bölüm:</span>
    <span class="detail-value next-episode">
        <?php echo getTimeUntilNextEpisode($anime['next_episode_date'], $anime['watched_episodes'], $anime['total_episodes'] ?? 0, $anime['aired_episodes'] ?? 0); ?>
    </span>
</div>

<?php if (!empty($chronologyMarkers)): ?>
<div class="detail-row">
    <span class="detail-label"></span>
    <span class="detail-value">
        <a href="chronology.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button">
            <i class="fas fa-stream"></i> Kronoloji
        </a>
    </span>
</div>
<?php endif; ?>

                    <?php if (!empty($anime['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Notlar:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($anime['notes'])); ?></span>
                </div>
                <?php endif; ?>


                </div>
                <?php endif; ?>

                <?php // Yayin Tamamlandi durumunda broadcast-info yok, kronoloji
                      // butonunu burada goster (devam eden animede zaten broadcast-info
                      // icinde gosteriliyor)
                ?>
                <?php if ($anime['status'] != 'Yayın Devam Ediyor' && !empty($chronologyMarkers)): ?>
                <div class="detail-row" style="margin-top: 10px;">
                    <a href="chronology.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button">
                        <i class="fas fa-stream"></i> Kronoloji
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <?php
            // Pre-compute safe URLs once. safe_url() returns empty string
            // for dangerous schemes (javascript:, data:, etc.) and the result
            // is already htmlspecialchars-encoded for attribute context.
            
            ?>
            <?php if ($anidb_safe || $mal_safe || true): ?>
            <div class="external-links">
                <h3>Anime Siteleri</h3>
                <?php if ($anidb_safe): ?>
                <a href="<?php echo $anidb_safe; ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="site-link anidb-link">
                    <i class="fas fa-database"></i> AniDB
                </a>
                <?php endif; ?>
                
                <?php if ($mal_safe): ?>
                <a href="<?php echo $mal_safe; ?>" 
                   target="_blank" 
                   rel="noopener noreferrer"
                   class="site-link mal-link">
                    <i class="fas fa-list"></i> MyAnimeList
                </a>
				<a href="<?php echo $schedule_safe ?: 'https://animeschedule.net'; ?>"
   target="_blank" rel="noopener noreferrer"
   class="site-link schedule-link">
    <i class="fas fa-calendar-alt"></i> AnimeSchedule
</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Kronoloji Uyarisi
                  // Kullanicinin izleme ilerlemesi bir kronoloji marker'ina
                  // denk geliyorsa, "bu bolumden sonra sunu izle" uyarisi goster.
                  // ============================================================
            ?>
            <?php if ($chronologyAlert): ?>
            <div class="chronology-alert">
                <div class="alert-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="alert-content">
                    <strong><?php echo (int)$chronologyAlert['after_episode']; ?>. bölümden sonra izlenmeli:</strong>
                    <a href="anime_details.php?id=<?php echo (int)$chronologyAlert['related_id']; ?>" class="alert-anime-link">
                        <?php echo htmlspecialchars($chronologyAlert['related_title']); ?>
                        <?php if (!empty($chronologyAlert['related_media_type'])): ?>
                            (<?php echo htmlspecialchars($chronologyAlert['related_media_type']); ?>)
                        <?php endif; ?>
                    </a>
                    <span class="alert-watch-status ws-<?php echo watch_status_css_class($chronologyAlert['related_watch_status']); ?>">
                        <?php echo htmlspecialchars(watch_status_label($chronologyAlert['related_watch_status'])); ?>
                    </span>
                    <?php if (!empty($chronologyAlert['note'])): ?>
                        <small class="alert-note"><?php echo htmlspecialchars($chronologyAlert['note']); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Siradaki Anime (next_in_series)
                  // Bu animeyi tamamen bitirdikten sonra izlenecek anime.
                  // Sadece next_in_series FK dolu ise gosterilir.
                  // ============================================================
            ?>
            <?php if ($nextAnime): ?>
            <div class="next-anime-panel">
                <h3><i class="fas fa-arrow-right"></i> Sıradaki</h3>
                <div class="next-anime-info">
                    <a href="anime_details.php?id=<?php echo (int)$nextAnime['id']; ?>" class="next-anime-link">
                        <?php echo htmlspecialchars($nextAnime['title']); ?>
                        <?php if (!empty($nextAnime['media_type'])): ?>
                            (<?php echo htmlspecialchars($nextAnime['media_type']); ?>)
                        <?php endif; ?>
                    </a>
                    <span class="next-anime-status ws-<?php echo watch_status_css_class($nextAnime['watch_status']); ?>">
                        <?php echo htmlspecialchars(watch_status_label($nextAnime['watch_status'])); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isInSeriesChain): ?>
            <div style="margin: 15px 0;">
                <a href="series_timeline.php?id=<?php echo (int)$anime['id']; ?>" class="chronology-button" style="background: #8e44ad;">
                    <i class="fas fa-list-ol"></i> Seri Kronolojisi
                </a>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Baglantili Animeler
                  // Ayni series_name'i paylasan tum animeler, media_type'a gore
                  // gruplanmis. Sadece series_name dolu ise gosterilir.
                  // ============================================================
            ?>
            <?php if (!empty($relatedAnimes)): ?>
            <div class="related-animes-section">
                <h3><i class="fas fa-link"></i> Bağlantılı Animeler
                    <small>(<?php echo htmlspecialchars($anime['series_name']); ?>)</small>
                </h3>
                <div class="related-animes-list">
                    <?php
                    // media_type'a gore grupla
                    $grouped = [];
                    foreach ($relatedAnimes as $ra) {
                        $type = $ra['media_type'] ?? 'Diger';
                        $grouped[$type][] = $ra;
                    }
                    ?>
                    <?php foreach ($grouped as $type => $animes): ?>
                        <div class="related-group">
                            <h4><?php echo htmlspecialchars($type); ?></h4>
                            <?php foreach ($animes as $ra): ?>
                                <div class="related-anime-item">
                                    <a href="anime_details.php?id=<?php echo (int)$ra['id']; ?>" class="related-anime-link">
                                        <?php echo htmlspecialchars($ra['title']); ?>
                                    </a>
                                    <span class="related-anime-progress">
                                        <?php echo (int)$ra['watched_episodes']; ?>/<?php echo $ra['total_episodes'] ? (int)$ra['total_episodes'] : '?'; ?>
                                    </span>
                                    <span class="related-anime-status ws-<?php echo watch_status_css_class($ra['watch_status']); ?>">
                                        <?php echo htmlspecialchars(watch_status_label($ra['watch_status'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php // ============================================================
                  // SECTION: Kronoloji Markerlari
                  // Bu animenin bolum-seviyesinde kronoloji notlari.
                  // Ornegin "23. bolumden sonra Film 1'i izle".
                  // Marker ekleme formu da burada (ayni seri icerisinden secer).
                  // ============================================================
            ?>
            <?php if (!empty($chronologyMarkers) || !empty($sameSeriesAnimes)): ?>
            <div class="chronology-section">
                <h3><i class="fas fa-clock"></i> Kronoloji Notları</h3>

                <?php if (!empty($chronologyMarkers)): ?>
                <div class="marker-list">
                    <?php foreach ($chronologyMarkers as $cm): ?>
                        <div class="marker-item">
                            <span class="marker-episode"><?php echo (int)$cm['after_episode']; ?>. bölümden sonra</span>
                            <span class="marker-arrow">→</span>
                            <a href="anime_details.php?id=<?php echo (int)$cm['related_anime_id']; ?>" class="marker-anime-link">
                                <?php echo htmlspecialchars($cm['related_title']); ?>
                                <?php if (!empty($cm['related_media_type'])): ?>
                                    (<?php echo htmlspecialchars($cm['related_media_type']); ?>)
                                <?php endif; ?>
                            </a>
                            <span class="marker-watch-status ws-<?php echo watch_status_css_class($cm['related_watch_status']); ?>">
                                <?php echo htmlspecialchars(watch_status_label($cm['related_watch_status'])); ?>
                            </span>
                            <?php if (!empty($cm['note'])): ?>
                                <small class="marker-note">(<?php echo htmlspecialchars($cm['note']); ?>)</small>
                            <?php endif; ?>
                            <form method="POST" action="delete_chronology_marker.php" class="marker-delete-form"
                                  onsubmit="return confirm('Bu kronoloji notunu silmek istediginize emin misiniz?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="marker_id" value="<?php echo (int)$cm['id']; ?>">
                                <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                                <button type="submit" class="marker-delete-btn" title="Sil"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($sameSeriesAnimes)): ?>
                <div class="marker-add-form">
                    <h4>Yeni Kronoloji Notu Ekle</h4>
                    <form method="POST" action="add_chronology_marker.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="anime_id" value="<?php echo (int)$anime['id']; ?>">
                        <div class="marker-form-row">
                            <label>Bölümden sonra:</label>
                            <input type="number" name="after_episode" min="1" max="<?php echo $anime['total_episodes'] ? (int)$anime['total_episodes'] : 9999; ?>" required placeholder="Orn: 23">
                        </div>
                        <div class="marker-form-row">
                            <label>İzlenecek anime:</label>
                            <select name="related_anime_id" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($sameSeriesAnimes as $ssa): ?>
                                    <option value="<?php echo (int)$ssa['id']; ?>">
                                        <?php echo htmlspecialchars($ssa['title']); ?>
                                        <?php if (!empty($ssa['media_type'])): ?>(<?php echo htmlspecialchars($ssa['media_type']); ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="marker-form-row">
                            <label>Not (opsiyonel):</label>
                            <input type="text" name="note" placeholder="Orn: Kanonik kronoloji">
                        </div>
                        <button type="submit" class="marker-add-btn"><i class="fas fa-plus"></i> Ekle</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="edit_anime.php?id=<?php echo $anime['id']; ?>" class="edit-button">
                    <i class="fas fa-edit"></i> Düzenle
                </a>
                <a href="index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
        </div>
    </div>

    <!-- 0.6.1 - Duygu Etiketleri toggle scripti
         Her butona tiklama: POST update_emotion.php ile toggle. Sunucu
         otoriter; cevaptaki current_emotions listesini DOM'a yansitir.
         Cap kontrolu (3'te diger pasif butonlari disabled yap) sunucu
         cevabindaki at_max flag'i ile yapilir, lokalde sayma yok. -->
    <script>
    (function() {
        var toolbar = document.querySelector('.emotion-toolbar');
        if (!toolbar) return;

        var animeId = toolbar.dataset.animeId;
        var csrf    = toolbar.dataset.csrf;
        var meta    = toolbar.querySelector('.emotion-count');
        var buttons = toolbar.querySelectorAll('.emotion-btn');

        function syncFromServer(currentEmotions, atMax) {
            // Aktif/disabled durumlarini sunucudaki gercege gore yeniden
            // kur. currentEmotions: ASCII emotion degerlerini icerir.
            var active = {};
            for (var i = 0; i < currentEmotions.length; i++) {
                active[currentEmotions[i]] = true;
            }
            buttons.forEach(function(btn) {
                var emo = btn.dataset.emotion;
                var isOn = !!active[emo];
                btn.classList.toggle('is-active', isOn);
                // 3'e ulasildiysa pasif butonlari disable et; aktif olanlar
                // her zaman tiklanabilir (toggle off serbest).
                btn.disabled = (atMax && !isOn);
            });
            meta.textContent = currentEmotions.length;
        }

        toolbar.addEventListener('click', function(ev) {
            var btn = ev.target.closest('.emotion-btn');
            if (!btn || btn.disabled) return;

            var emotion = btn.dataset.emotion;
            // Geri donus gelene kadar tum butonlari kilitle - cift tikla
            // race'i onler.
            buttons.forEach(function(b) { b.disabled = true; });

            var form = new FormData();
            form.append('csrf_token', csrf);
            form.append('anime_id', animeId);
            form.append('emotion', emotion);

            fetch('update_emotion.php', {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    syncFromServer(data.current_emotions || [], !!data.at_max);
                } else {
                    // Sunucu reddetti - butonlari onceki haline geri dondur
                    // ve mesaji goster. Sayfayi tekrar render etmek yerine
                    // mevcut DOM'dan aktif listesini cikarip kullaniyoruz.
                    var fallback = [];
                    buttons.forEach(function(b) {
                        if (b.classList.contains('is-active')) {
                            fallback.push(b.dataset.emotion);
                        }
                    });
                    syncFromServer(fallback, fallback.length >= 3);
                    alert(data.error || 'Islem basarisiz oldu.');
                }
            })
            .catch(function(err) {
                // Ag hatasi - butonlari onceki aktif/disabled durumuna
                // dondur. Hata aciklayici degil cunku JSON donmedi.
                var fallback = [];
                buttons.forEach(function(b) {
                    if (b.classList.contains('is-active')) {
                        fallback.push(b.dataset.emotion);
                    }
                });
                syncFromServer(fallback, fallback.length >= 3);
                alert('Baglanti hatasi. Lutfen tekrar deneyin.');
            });
        });
    })();
    </script>
</body>
</html>