<?php

/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]

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

/**
 * Tag management page (sentence/keyword library used by the
 * recommendation system).
 *
 * Tags are created automatically from add_anime / edit_anime when the
 * user types a new label into the tag input. This page exists so the
 * admin can:
 *   - see every tag in the library,
 *   - rename a typo (e.g. "okkul" -> "okul"),
 *   - delete a tag that should never have existed (cascade removes
 *     anime_tags links automatically thanks to the FK).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$message = null;
$messageType = null; // 'success' or 'error'

// --------------------------------------------------------
// POST handlers - all CSRF-protected
// --------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        http_response_code(400);
        die('Gecersiz CSRF tokeni. Sayfayi yenileyip tekrar deneyin.');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '') {
                throw new Exception('Cumle bos olamaz.');
            }
            // findOrCreateTag handles trim, length cap, and the race
            // where two requests insert the same name at once.
            findOrCreateTag($pdo, $name);
            $message = 'Cumle eklendi (veya zaten mevcuttu): ' . $name;
            $messageType = 'success';

        } elseif ($action === 'rename') {
            $tag_id = (int)($_POST['tag_id'] ?? 0);
            $new_name = trim((string)($_POST['new_name'] ?? ''));
            if ($tag_id <= 0 || $new_name === '') {
                throw new Exception('Eksik bilgi: cumle ID veya yeni metin bos.');
            }
            if (mb_strlen($new_name) > 150) {
                $new_name = mb_substr($new_name, 0, 150);
            }
            $stmt = $pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
            $stmt->execute([$new_name, $tag_id]);
            $message = 'Cumle guncellendi.';
            $messageType = 'success';

        } elseif ($action === 'delete') {
            $tag_id = (int)($_POST['tag_id'] ?? 0);
            if ($tag_id <= 0) {
                throw new Exception('Gecersiz cumle ID.');
            }
            // anime_tags rows are removed automatically by FK CASCADE
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$tag_id]);
            $message = 'Cumle silindi.';
            $messageType = 'success';

        } else {
            throw new Exception('Bilinmeyen islem.');
        }
    } catch (PDOException $e) {
        // 23000 = duplicate key (rename collided with an existing tag)
        if ($e->getCode() === '23000') {
            $message = 'Bu cumle zaten var.';
        } else {
            error_log('[anime_tracker] manage_tags PDO error: ' . $e->getMessage());
            $message = 'Veritabani hatasi olustu.';
        }
        $messageType = 'error';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// --------------------------------------------------------
// Fetch the current tag list, plus a per-tag usage count so the admin
// can see which tags are actually attached to anime before deleting.
// --------------------------------------------------------
$stmt = $pdo->query(
    "SELECT t.id, t.name, COUNT(at.anime_id) AS usage_count
     FROM tags t
     LEFT JOIN anime_tags at ON at.tag_id = t.id
     GROUP BY t.id, t.name
     ORDER BY t.name ASC"
);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Cumle Yonetimi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<div class="container">
    <div class="page-title">Cumle Yonetimi</div>

    <p style="text-align: center; max-width: 600px; margin: 0 auto 20px;">
        Cumleler oneri sisteminde kullanicilara gosterilir. Anime ekleme/duzenleme
        ekraninda yeni cumle yazinca otomatik olusur. Buradan yazim hatalarini
        duzeltebilir veya gereksiz cumleleri silebilirsin. Cumleyi tam olarak
        kullanicinin gorecegi sekilde yaz (orn: "Okulda gecsin", "Spor temasi olsun").
    </p>

    <?php if ($message): ?>
        <div class="<?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>"
             style="text-align: center; margin: 0 auto 20px; max-width: 600px;
                    padding: 10px; border-radius: 5px;
                    background-color: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>;
                    color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Add new tag form -->
    <form method="post" style="text-align: center; margin: 0 auto 30px; max-width: 600px;">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="add">
        <input type="text" name="name" maxlength="150" required
               placeholder="Yeni cumle (orn: Okulda gecsin, Spor temasi olsun)"
               style="padding: 8px; width: 60%; max-width: 350px;">
        <button type="submit" class="anime-list-button" style="display: inline-block;">
            <i class="fas fa-plus"></i> Ekle
        </button>
    </form>

    <table>
        <tr>
            <th>Cumle</th>
            <th>Kullanim</th>
            <th>Yeniden Yaz</th>
            <th>Sil</th>
        </tr>
        <?php if (empty($tags)): ?>
            <tr>
                <td colspan="4" style="text-align: center; padding: 20px;">
                    Henuz cumle yok. Yukaridaki formdan ilk cumleni ekleyebilirsin.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($tags as $tag): ?>
            <tr>
                <td><?php echo htmlspecialchars($tag['name']); ?></td>
                <td style="text-align: center;"><?php echo (int)$tag['usage_count']; ?> anime</td>
                <td>
                    <form method="post" style="display: inline-flex; gap: 5px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="action" value="rename">
                        <input type="hidden" name="tag_id" value="<?php echo (int)$tag['id']; ?>">
                        <input type="text" name="new_name" maxlength="150" required
                               value="<?php echo htmlspecialchars($tag['name']); ?>"
                               style="padding: 4px; width: 150px;">
                        <button type="submit" class="edit-button" style="padding: 4px 10px;">
                            <i class="fas fa-save"></i>
                        </button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display: inline;"
                          onsubmit="return confirm('<?php echo htmlspecialchars($tag['name']); ?> cumlesini silmek istediginize emin misiniz? <?php echo (int)$tag['usage_count']; ?> animeden kaldirilacak.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="tag_id" value="<?php echo (int)$tag['id']; ?>">
                        <button type="submit" class="delete-button">
                            <i class="fas fa-trash"></i> Sil
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="button-container">
        <a href="index.php" class="anime-list-button">Anime Listesine Don</a>
    </div>
</div>
</body>
</html>
