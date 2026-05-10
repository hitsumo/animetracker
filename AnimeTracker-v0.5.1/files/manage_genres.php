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

// Tür silme işlemi
if (isset($_POST['delete_genre'])) {
    // CSRF kontrolu - form'dan gelen token oturumdaki ile eslesmiyorsa reddet.
    // hash_equals timing-safe karsilastirma yapar (bkz. functions.php csrf_verify).
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.');
    }

    // Cast to int defensively. Even with prepared statements, int cast
    // documents the intent and prevents accidental string passing if
    // the form ever changes. The ON DELETE CASCADE on anime_genres
    // will automatically remove every link row that references this
    // genre - no manual cleanup needed.
    $genre_id = (int)$_POST['genre_id'];
    $stmt = $pdo->prepare("DELETE FROM genres WHERE id = ?");
    $stmt->execute([$genre_id]);
    header("Location: manage_genres.php");
    exit();
}

// Master genre list. Fetched via the helper for consistency with the
// other modules; same shape as the inline query (id, name).
$genres = getAllGenres($pdo);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Tür Yönetimi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="page-title">Tür Yönetimi</div>
        
        <table>
            <tr>
                <th>Tür Adı</th>
                <th>İşlem</th>
            </tr>
            <?php foreach ($genres as $genre): ?>
            <tr>
                <td><?php echo htmlspecialchars($genre['name']); ?></td>
                <td>
                    <form method="post" style="display: inline;" onsubmit="return confirm('Bu türü silmek istediğinize emin misiniz? Bu türü kullanan animelerden de otomatik olarak kaldırılacaktır.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="genre_id" value="<?php echo (int)$genre['id']; ?>">
                        <button type="submit" name="delete_genre" class="delete-button">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="button-container">
            <a href="index.php" class="anime-list-button">Anime Listesine Dön</a>
        </div>
    </div>
</body>
</html>