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

// Karar 1B: kataloga gonderilmemis (source='user') kronoloji isareti
// sayisi. Sifirdan buyukse "Katalogdan Ice Aktar" oncesi yumusak uyari
// gosterilir (buton pasif YAPILMAZ - import artik source='user'
// satirlari silmiyor, bkz catalog_import.php). Defansif: source kolonu
// henuz yoksa (0.5.3 oncesi DB, migration calismadan once) sorgu hata
// verir, sayiyi 0 kabul edip uyariyi gostermeyiz.
$unpushedUserMarkers = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM chronology_markers WHERE source = 'user'");
    $unpushedUserMarkers = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    // source kolonu yok veya sorgu basarisiz - 0 kabul et, uyari gosterme
}

// Bolum sayisi (aired_episodes) son senkronizasyon zamani. Madde C ile
// eklendi. Otomatik gunluk run icin baslangic kontrolu olarak da kullanilir.
// settings tablosundaki last_aired_sync satiri hic yoksa NULL doner.
$lastAiredSync = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_aired_sync'");
    $lastAiredSync = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Sessizce gec
}

// catalog_import.php basarili olursa mesaji querystring ile geri yolluyor.
// Burada alip basarili alert'e ceviriyoruz.
if (isset($_GET['catalog_msg'])) {
    $success_message = $_GET['catalog_msg'];
}

// Bolum sayisi senkronizasyonu sonuc mesaji (manuel buton sonrasi).
if (isset($_GET['aired_msg'])) {
    $success_message = $_GET['aired_msg'];
}

// Listeyi Dışa Aktarma İşlemi
// Asagidaki tum POST islemleri (export, import, clear) icin ortak CSRF kontrolu.
// Tek noktada yaparak ileride eklenecek POST handler'larin da otomatik
// korunmasini sagliyoruz. Mevcut catalog_import.php endpoint'i kendi CSRF
// kontrolunu kendisi yapiyor (ayri sayfa), o etkilenmez.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.');
    }
}

// Madde C — Manuel "Bolum Sayisi Senkronizasyonu" butonu.
// Form'dan POST geldiginde tum ongoing animeler icin AnimeSchedule
// timetable'i sorgulanir, aired_episodes guncellenir. Sonuc mesajini
// query string ile geri donderiyoruz (klasik PRG patern: form yenileme
// sirasinda istek tekrarlanmasin).
if (isset($_POST['sync_aired'])) {
    $stats = syncAllOngoingAiredEpisodes($pdo, 3);

    if (isset($stats['global_error'])) {
        $msg = 'Senkronizasyon iptal edildi: ' . $stats['global_error'];
        if ($stats['global_error'] === 'no_key') {
            $msg = 'AnimeSchedule API anahtari config.php icinde tanimli degil.';
        } elseif ($stats['global_error'] === 'http_429') {
            $msg = 'API istek limiti asildi. Birkac dakika sonra tekrar deneyin.';
        } elseif ($stats['global_error'] === 'http_401') {
            $msg = 'API anahtari gecersiz. config.php yi kontrol edin.';
        }
        header('Location: list_settings.php?aired_msg=' . urlencode($msg));
    } else {
        $msg = $stats['updated'] . ' anime guncellendi, '
             . $stats['unchanged'] . ' degismedi, '
             . $stats['not_in_table'] . ' takvimde bulunamadi'
             . ($stats['no_slug']  > 0 ? ', ' . $stats['no_slug']  . ' AnimeSchedule URL si yok' : '')
             . ($stats['errors']   > 0 ? ', ' . $stats['errors']   . ' hata' : '')
             . '.';
        header('Location: list_settings.php?aired_msg=' . urlencode($msg));
    }
    exit;
}

// Madde C — Otomatik gunluk silent sync.
// last_aired_sync timestamp'i bugune ait degilse (veya hic yoksa)
// arka planda tum ongoing animeleri senkronize et. UI'da hicbir
// gosterim olmaz (silent), kullanici sadece guncel rakamlari gorur.
//
// Karar: tetikleme list_settings.php basinda. Anasayfa (index.php)
// daha sik aciliyor ama oranin yavaslamasini istemiyoruz - liste
// ayarlari "bilincli aciliyor", kullanici 5-15 saniye beklemeyi
// kabullenir.
//
// Hata durumunda last_aired_sync GUNCELLEMEZ (helper icinde global
// hatalarda timestamp atilmaz), bu sayede bir sonraki sayfa yuklemede
// tekrar denenir. Tek tek anime hatalari ise normal kabul edilir,
// timestamp guncellenir.
//
// Karsilastirma 'Y-m-d' duzeyinde - aym gun icindeki ikinci aciliste
// tekrar calistirilmaz. UTC kullanilir cunku tum sistem UTC odakli.
$todayUtc = gmdate('Y-m-d');
$lastSyncDate = $lastAiredSync ? substr((string)$lastAiredSync, 0, 10) : null;
if ($lastSyncDate !== $todayUtc && !isset($_POST['sync_aired'])) {
    // Sessizce arka planda. Kullaniciya duyurmak istemiyoruz cunku
    // otomatik bir sey - sayfa biraz yavas yuklenir, o kadar.
    syncAllOngoingAiredEpisodes($pdo, 3);
    // Tazelenmis timestamp'i UI gosterimi icin yeniden oku
    try {
        $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'last_aired_sync'");
        $lastAiredSync = $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Sessizce gec
    }
}

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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
    <?php if ($unpushedUserMarkers > 0): ?>
    <div style="margin-top: 10px; padding: 10px; border-left: 4px solid #e6a700; background: #fff8e1; color: #5a4500; font-size: 0.92em;">
        Katalog ile senkronize olmayan <strong><?php echo (int)$unpushedUserMarkers; ?></strong> kronoloji
        isareti var. Ice aktarma bunlari <strong>silmez</strong> &mdash; kendi ekledikleriniz korunur,
        katalogdan gelenler otomatik eslestirilir. Evrensel kronolojinin eksiksiz kalmasi icin
        bunlari admin push ile kataloga gondermeniz onerilir.
    </div>
    <?php endif; ?>
    <form method="post" action="catalog_import.php" onsubmit="return confirmCatalogSync()" style="margin-top: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <button type="submit" class="settings-button">
            <i class="fas fa-cloud-download-alt"></i> Katalogdan Ice Aktar
        </button>
    </form>
</div>

	<!-- Bolum Sayisi Senkronizasyonu (Madde C) -->
<div class="settings-section">
    <h3>Bolum Sayisi Senkronizasyonu</h3>
    <p>Yayini devam eden animelerin "yayinlanan bolum sayisi" bilgisi AnimeSchedule den otomatik olarak guncellenir. Bu sayfa her acildiginda gunde bir kez arka planda calisir; manuel calistirmak icin asagidaki butonu kullanabilirsiniz.</p>
    <div id="aired-status">
        <?php if ($lastAiredSync): ?>
            Son senkronizasyon: <strong><?php echo htmlspecialchars($lastAiredSync, ENT_QUOTES, 'UTF-8'); ?> UTC</strong>
        <?php else: ?>
            <em>Henuz senkronize edilmedi.</em>
        <?php endif; ?>
    </div>
    <form method="post" action="list_settings.php" style="margin-top: 10px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="sync_aired" value="1">
        <button type="submit" class="settings-button">
            <i class="fas fa-sync"></i> Simdi Senkronize Et
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
        var unpushedMarkers = <?php echo (int)$unpushedUserMarkers; ?>;
        var msg =
            "Katalogdan ice aktarilacak.\n\n" +
            "Kendi izleme durumlariniz ve notlariniz KORUNUR.\n" +
            "Sadece anime bilgileri (baslik, synopsis, bolum sayisi vs.) guncellenir.\n\n";
        if (unpushedMarkers > 0) {
            msg +=
                "NOT: Katalog ile senkronize olmayan " + unpushedMarkers +
                " kronoloji isareti var.\n" +
                "Ice aktarma bunlari SILMEZ. Kendi ekledikleriniz korunur,\n" +
                "katalogdan gelenler otomatik eslestirilir.\n\n";
        }
        msg += "Devam etmek istiyor musunuz?";
        return confirm(msg);
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