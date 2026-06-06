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


require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Initialize i18n (session-wide language; switcher lives on index /
// list_settings, this secondary page just inherits current_lang()).
lang_init($pdo);

// Managing genres edits the shared taxonomy, so a moderator+ is required
// (online only; no-op in self-host).
require_role($pdo, 'moderator');

// Genre English-name save handler (0.7.2). Sets genres.name_en for a
// single row. Empty input clears it back to NULL so the display falls
// back to the Turkish name. The TR name stays authoritative and is not
// editable here (it is the catalog/sync key).
if (isset($_POST['save_genre_en'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('manage_genres.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
    }
    $genre_id = (int)$_POST['genre_id'];
    $name_en  = trim($_POST['name_en'] ?? '');
    if (mb_strlen($name_en) > 50) {
        $name_en = mb_substr($name_en, 0, 50);
    }
    $stmt = $pdo->prepare("UPDATE genres SET name_en = ? WHERE id = ?");
    $stmt->execute([$name_en !== '' ? $name_en : null, $genre_id]);
    header("Location: manage_genres.php");
    exit();
}

// Genre delete handler
if (isset($_POST['delete_genre'])) {
    // CSRF check - reject if the form token does not match the session.
    // hash_equals does a timing-safe comparison (see functions.php csrf_verify).
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('manage_genres.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
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
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('manage_genres.title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="page-title"><?php echo htmlspecialchars(t('manage_genres.title'), ENT_QUOTES, 'UTF-8'); ?></div>
        
        <table>
            <tr>
                <th><?php echo htmlspecialchars(t('manage_genres.th.name'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(t('manage_genres.th.name_en'), ENT_QUOTES, 'UTF-8'); ?></th>
                <th><?php echo htmlspecialchars(t('manage_genres.th.action'), ENT_QUOTES, 'UTF-8'); ?></th>
            </tr>
            <?php foreach ($genres as $genre): ?>
            <tr>
                <td><?php echo htmlspecialchars($genre['name']); ?></td>
                <td>
                    <form method="post" style="display: inline;" class="inline-en-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="genre_id" value="<?php echo (int)$genre['id']; ?>">
                        <input type="text" name="name_en" maxlength="50"
                               value="<?php echo htmlspecialchars($genre['name_en'] ?? ''); ?>"
                               placeholder="<?php echo htmlspecialchars(t('manage_genres.ph.name_en'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" name="save_genre_en" class="add-button">
                            <i class="fas fa-save"></i> <?php echo htmlspecialchars(t('manage_genres.btn.save_en'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                </td>
                <td>
                    <form method="post" style="display: inline;" onsubmit="return confirm('<?php echo htmlspecialchars(t('manage_genres.confirm_delete'), ENT_QUOTES, 'UTF-8'); ?>');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <input type="hidden" name="genre_id" value="<?php echo (int)$genre['id']; ?>">
                        <button type="submit" name="delete_genre" class="delete-button">
                            <i class="fas fa-trash"></i> <?php echo htmlspecialchars(t('manage_genres.btn.delete'), ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="button-container">
            <a href="index.php" class="anime-list-button"><?php echo htmlspecialchars(t('manage_genres.back_to_list'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
    </div>
</body>
</html>
