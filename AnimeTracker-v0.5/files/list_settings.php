<?php
/**
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

// Mevcut surumu settings tablosundan al. Bu deger migration_manager tarafindan
// her sayfa yuklemesinde guncel tutuluyor. "Guncelleme Kontrolu" bolumunde
// kullaniciya hangi surumde oldugu gosterilecek.
$currentVersion = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'version'");
    $currentVersion = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec - UI'da "bilinmiyor" gosterilecek
}
if (!$currentVersion) {
    $currentVersion = 'bilinmiyor';
}

// Son katalog senkronizasyonu zamani. settings tablosunda satir yoksa
// "hic senkronize edilmemis" gosterilecek. UTC olarak saklaniyor, kullaniciya
// kendi saat diliminde gosterim functions.php icinde ya da inline yapilabilir
// — simdilik sadece UTC timestamp'i basiyoruz.
$lastCatalogSync = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_catalog_sync'");
    $lastCatalogSync = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec
}

// catalog_import.php basarili olursa mesaji querystring ile geri yolluyor.
// Burada alip basarili alert'e ceviriyoruz.
if (isset($_GET['catalog_msg'])) {
    $success_message = $_GET['catalog_msg'];
}

// Listeyi Dışa Aktarma İşlemi
if (isset($_POST['export'])) {
    $stmt = $pdo->query("SELECT * FROM animes");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // JSON formatında dışa aktar
    $filename = 'anime_list_' . date('Y-m-d') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode($animes, JSON_PRETTY_PRINT);
    exit;
}

// Listeyi İçe Aktarma İşlemi
if (isset($_POST['import']) && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];
    if ($file['type'] === 'application/json') {
        $content = file_get_contents($file['tmp_name']);
        $animes = json_decode($content, true);
        
        if ($animes) {
            foreach ($animes as $anime) {
                // Var olan kayıtları güncelle veya yeni kayıt ekle
                $stmt = $pdo->prepare("INSERT INTO animes (title, alternative_titles, status, total_episodes, 
                    watched_episodes, notes, genres, image_path, watch_status, next_episode_date, 
                    anidb_link, mal_link, episode_interval, broadcast_day, broadcast_time, synopsis, release_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    alternative_titles=VALUES(alternative_titles), 
                    status=VALUES(status),
                    total_episodes=VALUES(total_episodes),
                    watched_episodes=VALUES(watched_episodes),
                    notes=VALUES(notes),
                    genres=VALUES(genres),
                    watch_status=VALUES(watch_status),
                    next_episode_date=VALUES(next_episode_date)");
                
                $stmt->execute([
                    $anime['title'],
                    $anime['alternative_titles'],
                    $anime['status'],
                    $anime['total_episodes'],
                    $anime['watched_episodes'],
                    $anime['notes'],
                    $anime['genres'],
                    $anime['image_path'],
                    $anime['watch_status'],
                    $anime['next_episode_date'],
                    $anime['anidb_link'],
                    $anime['mal_link'],
                    $anime['episode_interval'],
                    $anime['broadcast_day'],
                    $anime['broadcast_time'],
                    $anime['synopsis'],
                    $anime['release_date']
                ]);
            }
            $success_message = "Liste başarıyla içe aktarıldı!";
        }
    } else {
        $error_message = "Lütfen geçerli bir JSON dosyası yükleyin!";
    }
}

// Listeyi Temizleme İşlemi
if (isset($_POST['clear'])) {
    if (isset($_POST['confirm_clear']) && $_POST['confirm_clear'] === 'yes') {
        $pdo->exec("TRUNCATE TABLE animes");
        $success_message = "Liste başarıyla temizlendi!";
    }
}


?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Liste Ayarları - Anime Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="container">
        <div class="header-section">
            <a href="about.php" class="about-link">Hakkında</a>
        </div>
        
        <div class="page-title">Liste Ayarları</div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="settings-container">
            <!-- Dışa Aktarma Formu -->
            <div class="settings-section">
                <h3>Listeyi Dışa Aktar</h3>
                <p>Mevcut anime listenizi JSON formatında dışa aktarın.</p>
                <form method="post">
                    <button type="submit" name="export" class="settings-button">
                        <i class="fas fa-download"></i> Listeyi Dışa Aktar
                    </button>
                </form>
            </div>

            <!-- İçe Aktarma Formu -->
            <div class="settings-section">
                <h3>Listeyi İçe Aktar</h3>
                <p>Önceden dışa aktarılmış bir listeyi içe aktarın.</p>
                <form method="post" enctype="multipart/form-data">
                    <div class="file-upload">
                        <input type="file" name="import_file" id="import_file" accept=".json" required>
                        <label for="import_file" class="file-upload-label">
                            <i class="fas fa-upload"></i> Dosya Seç
                        </label>
                    </div>
                    <button type="submit" name="import" class="settings-button">
                        <i class="fas fa-upload"></i> Listeyi İçe Aktar
                    </button>
                </form>
            </div>

            <!-- Liste Temizleme Formu -->
            <div class="settings-section">
                <h3>Listeyi Temizle</h3>
                <p>DİKKAT: Bu işlem geri alınamaz!</p>
                <form method="post" onsubmit="return confirmClear()">
                    <input type="hidden" name="confirm_clear" value="yes">
                    <button type="submit" name="clear" class="settings-button danger">
                        <i class="fas fa-trash-alt"></i> Listeyi Temizle
                    </button>
                </form>
            </div>
			
			

            <div class="settings-section">
    <h3>Tür Yönetimi</h3>
    <p>Yanlış yazılan veya kullanılmayan türleri yönetin.</p>
    <a href="manage_genres.php" class="settings-button">
        <i class="fas fa-tags"></i> Türleri Yönet
    </a>
	
		</div>
		
				<!-- Katalog Senkronizasyonu -->
<div class="settings-section">
    <h3>Katalog Senkronizasyonu</h3>
    <p>Merkezi katalogdan en son anime bilgilerini cekin. Kendi izleme durumlariniz ve notlariniz korunur.</p>
    <div id="catalog-status">
        <?php if ($lastCatalogSync): ?>
            Son senkronizasyon: <strong><?php echo htmlspecialchars($lastCatalogSync, ENT_QUOTES, 'UTF-8'); ?> UTC</strong>
        <?php else: ?>
            <em>Henuz senkronize edilmedi.</em>
        <?php endif; ?>
    </div>
    <form method="post" action="catalog_import.php" onsubmit="return confirmCatalogSync()" style="margin-top: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="settings-button">
            <i class="fas fa-cloud-download-alt"></i> Katalogdan Ice Aktar
        </button>
    </form>
</div>

	<!-- list_settings.php içindeki settings-container div'ine ekleyin -->
<div class="settings-section">
    <h3>Güncelleme Kontrolü</h3>
    <p>Yeni versiyon kontrolü yapın.</p>
    <div id="update-status">Mevcut versiyon: <?php echo htmlspecialchars($currentVersion, ENT_QUOTES, 'UTF-8'); ?></div>
    <button onclick="checkUpdate()" class="settings-button">
        <i class="fas fa-sync"></i> Güncelleme Kontrolü
    </button>
    <input type="hidden" id="update-csrf-token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
</div>
        </div>


        <div class="button-container">
            <a href="index.php" class="anime-list-button">Anime Listesine Dön</a>
        </div>
    </div>

    <script>
    function confirmClear() {
        return confirm("Tüm liste silinecek. Bu işlem geri alınamaz! Devam etmek istiyor musunuz?");
		
		
		
    }

    function confirmCatalogSync() {
        return confirm(
            "Katalogdan ice aktarilacak.\n\n" +
            "Kendi izleme durumlariniz ve notlariniz KORUNUR.\n" +
            "Sadece anime bilgileri (baslik, synopsis, bolum sayisi vs.) guncellenir.\n\n" +
            "Devam etmek istiyor musunuz?"
        );
    }
	
function checkUpdate() {
    const statusDiv = document.getElementById('update-status');
    const originalText = statusDiv.innerHTML;
    statusDiv.innerHTML = '<em>Kontrol ediliyor...</em>';

    fetch('check_update.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                statusDiv.innerHTML = originalText;
                alert(data.message || 'Guncelleme kontrolu sirasinda bir hata olustu.');
                return;
            }

            if (data.needs_update) {
                // Yeni versiyon var - bilgi goster
                statusDiv.innerHTML =
                    'Mevcut versiyon: <strong>' + data.current_version + '</strong><br>' +
                    'Yeni versiyon: <strong>' + data.latest_version + '</strong>';

                // Kullaniciya onay sor. Onaylarsa runUpdate() WordPress tarzi
                // in-place update yapiyor - hicbir .exe indirmeye veya manuel
                // adima gerek yok.
                if (confirm('Yeni versiyon mevcut: ' + data.latest_version + '\n\nHemen guncellemek ister misiniz?')) {
                    runUpdate();
                }
            } else {
                // Sistem guncel
                statusDiv.innerHTML = 'Mevcut versiyon: <strong>' + data.current_version + '</strong> (güncel)';
            }
        })
        .catch(error => {
            statusDiv.innerHTML = originalText;
            alert('Güncelleme kontrolü sırasında bir hata oluştu: ' + error);
        });
}

function runUpdate() {
    const statusDiv = document.getElementById('update-status');
    const csrfToken = document.getElementById('update-csrf-token').value;

    // Kullaniciyi bilgilendirerek sureci belirginlestir. Guncelleme birkac
    // saniye surebilir (indirme + extract + kopyalama + migration).
    statusDiv.innerHTML =
        '<em>Guncelleme indiriliyor ve uygulaniyor...</em><br>' +
        '<small>Bu islem birkac saniye surebilir. Sayfayi kapatmayin.</small>';

    // POST istegi ile update.php cagriliyor. CSRF token gerekli.
    const formData = new URLSearchParams();
    formData.append('csrf_token', csrfToken);

    fetch('update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString(),
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Update basarisiz - hatayi goster, sayfayi yenileme
            statusDiv.innerHTML = '<strong style="color:#d32f2f;">Guncelleme basarisiz</strong>';
            alert('Guncelleme basarisiz: ' + (data.message || 'Bilinmeyen hata'));
            return;
        }

        if (data.already_latest) {
            // Arada biri baska bir sekmeden zaten guncellemis olabilir
            statusDiv.innerHTML = 'Mevcut versiyon: <strong>' + data.message + '</strong>';
            return;
        }

        // Basarili - yeni versiyon bilgisini goster ve sayfayi yenile
        statusDiv.innerHTML =
            '<strong style="color:#2e7d32;">Guncelleme tamamlandi!</strong><br>' +
            'Eski versiyon: ' + data.previous_version + '<br>' +
            'Yeni versiyon: <strong>' + data.new_version + '</strong><br>' +
            '<small>Sayfa yenileniyor...</small>';

        // Kisa bir gecikme ile sayfayi yenile ki kullanici basari
        // mesajini gorebilsin
        setTimeout(function() {
            window.location.reload();
        }, 2000);
    })
    .catch(error => {
        statusDiv.innerHTML = '<strong style="color:#d32f2f;">Ag hatasi</strong>';
        alert('Guncelleme sirasinda bir hata olustu: ' + error);
    });
}
    </script>

    <style>
    .settings-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    .settings-section {
        background-color: #fff;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .settings-section h3 {
        color: #333;
        margin-bottom: 10px;
        font-size: 1.2em;
    }

    .settings-section p {
        color: #666;
        margin-bottom: 15px;
    }

    .settings-button {
        background-color: #007bff;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .settings-button:hover {
        background-color: #0056b3;
    }

    .settings-button.danger {
        background-color: #dc3545;
    }

    .settings-button.danger:hover {
        background-color: #c82333;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        text-align: center;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .file-upload {
        margin-bottom: 15px;
    }
    </style>
</body>
</html>