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

   
function calculateNextEpisodeDate($anime) {
    if ($anime['status'] != 'Yayın Devam Ediyor' || empty($anime['broadcast_day']) || empty($anime['broadcast_time'])) {
        return null;
    }

    // Animenin yayin saat dilimi. Eski kayitlar icin varsayilan: Asia/Tokyo
    $broadcastTzName = !empty($anime['broadcast_timezone']) ? $anime['broadcast_timezone'] : 'Asia/Tokyo';
    try {
        $broadcastTz = new DateTimeZone($broadcastTzName);
    } catch (Exception $e) {
        $broadcastTz = new DateTimeZone('Asia/Tokyo');
    }

    // "Simdi"yi animenin yayin saat diliminde al ki gun/saat karsilastirmalari
    // dogru yayin bolgesinde yapilsin
    $now = new DateTime('now', $broadcastTz);
    $broadcastTime = new DateTime($anime['broadcast_time'], $broadcastTz);
    $days = [
        'Pazartesi' => 1,
        'Salı' => 2,
        'Çarşamba' => 3,
        'Perşembe' => 4,
        'Cuma' => 5,
        'Cumartesi' => 6,
        'Pazar' => 7
    ];

    // Defensive lookup: if broadcast_day contains an unexpected value
    // (typo, legacy data, trailing whitespace, etc.) we return null
    // instead of raising an "Undefined index" warning. The caller
    // (updateNextEpisodeDate) already handles the null return.
    $broadcastDayNum = $days[$anime['broadcast_day']] ?? null;
    if ($broadcastDayNum === null) {
        error_log('[anime_tracker] Unknown broadcast_day: ' . var_export($anime['broadcast_day'], true));
        return null;
    }

    $currentDayNum = $now->format('N');

    $nextDate = clone $now;
    $nextDate->setTime($broadcastTime->format('H'), $broadcastTime->format('i'), 0);

    if ($currentDayNum < $broadcastDayNum) {
        $daysToAdd = $broadcastDayNum - $currentDayNum;
    } elseif ($currentDayNum == $broadcastDayNum) {
        if ($now < $nextDate) {
            $daysToAdd = 0;
        } else {
            $daysToAdd = 7;
        }
    } else {
        $daysToAdd = 7 - ($currentDayNum - $broadcastDayNum);
    }

    $nextDate->modify("+{$daysToAdd} days");
    // Sonucu UTC'ye cevirip oyle sakla. Boylece DB'de timezone-bagimsiz
    // tek bir referans nokta tutulmus olur ve gosterimde istenilen saat
    // dilimine cevrilebilir.
    $nextDate->setTimezone(new DateTimeZone('UTC'));
    return $nextDate->format('Y-m-d H:i:s');
}

function updateNextEpisodeDate($pdo, $anime) {
    $now = new DateTime();
    $nextEpisodeDate = new DateTime($anime['next_episode_date']);

    if ($now > $nextEpisodeDate) {
        $newNextEpisodeDate = calculateNextEpisodeDate($anime);
        if ($newNextEpisodeDate) {
            $sql = "UPDATE animes SET next_episode_date = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newNextEpisodeDate, $anime['id']]);
        }
    }
}

function getTimeUntilNextEpisode($next_episode_date, $watched_episodes = 0, $total_episodes = 0, $aired_episodes = 0) {
    // User has watched every episode that has a final count.
    // NOTE: This is about the WATCH status, not the broadcast status.
    // For ongoing anime the caller passes total_episodes = 0 (or NULL
    // from DB, which becomes 0 here), so this branch is skipped and we
    // fall through to calculate the time to the next broadcast.
    if ($total_episodes > 0 && $watched_episodes >= $total_episodes) {
        return 'Izleme tamamlandi';
    }

    // Sonraki izlenecek bolum numarasi
    $next_episode_number = $watched_episodes + 1;

    // Eger aired_episodes bilgisi varsa ve kullanici henuz yayinlanmis
    // bolumlere yetismediyse, geri sayim gostermenin anlami yok.
    // Ornek: Detective Conan 1185 bolum yayinlandi, kullanici 430'da.
    // 431. bolum zaten mevcut — beklemesine gerek yok.
    if ($aired_episodes > 0 && $next_episode_number <= $aired_episodes) {
        $remaining = $aired_episodes - $watched_episodes;
        return $remaining . ' bolum izlenebilir (' . $next_episode_number . '. bolumden devam)';
    }

    if (empty($next_episode_date)) {
        return 'Belirtilmemis';
    }

    $next_episode_timestamp = strtotime($next_episode_date);
    $current_timestamp = time();
    
    // Zaman gecmisse (yeni bolum yayinlandi)
    if ($next_episode_timestamp < $current_timestamp) {
        return 'Yeni bolum yayinlandi';
    }
    
    // Kalan sureyi hesapla — bu sadece kullanici yayinlanan bolumlere
    // yetismis ve bir sonraki bolumun yayinini bekliyorsa anlamli.
    $seconds_remaining = $next_episode_timestamp - $current_timestamp;
    $days = floor($seconds_remaining / 86400);
    $hours = floor(($seconds_remaining % 86400) / 3600);
    $minutes = floor(($seconds_remaining % 3600) / 60);
    
    // Zamanli gosterim
    $time_string = "";
    if ($days > 0) {
        $time_string .= "$days gun\n ";
    }
    if ($hours > 0) {
        $time_string .= "$hours saat ";
    }
    if ($minutes > 0) {
        $time_string .= "$minutes dakika";
    }
    
    return $next_episode_number . ". bolume\n kalan sure:\n" . $time_string;
}

/**
 * Auto-mark a finished anime as watched when the user has caught up.
 *
 * This function ONLY touches watch_status (the user's viewing progress).
 * It never touches status (the Japan broadcast status). The two concepts
 * are kept strictly separate from v0.5 onwards.
 *
 * The function only triggers for anime where:
 *   - status is 'Yayın Tamamlandı' (broadcast finished in Japan)
 *   - total_episodes is set (not NULL, not 0)
 *   - watched_episodes >= total_episodes
 *   - watch_status is not already 'İzlendi'
 *
 * This means ongoing anime (One Piece, Detective Conan) are never
 * touched automatically - the user tracks aired_episodes manually and
 * decides when to mark them as watched. This prevents the old bug where
 * catching up on an ongoing series would incorrectly mark it as watched
 * on every page load.
 */
function checkIfAnimeCompleted($pdo, $anime) {
    if ($anime['status'] !== 'Yayın Tamamlandı') {
        return $anime;
    }

    if (empty($anime['total_episodes']) || $anime['total_episodes'] <= 0) {
        return $anime;
    }

    if ($anime['watched_episodes'] < $anime['total_episodes']) {
        return $anime;
    }

    if ($anime['watch_status'] === 'İzlendi') {
        return $anime;
    }

    // All conditions met - mark as watched.
    $stmt = $pdo->prepare("UPDATE animes SET watch_status = 'İzlendi' WHERE id = :id");
    $stmt->execute(['id' => $anime['id']]);
    $anime['watch_status'] = 'İzlendi';

    return $anime;
}

/**
 * Securely handle an uploaded anime cover image.
 *
 * Performs the following checks before saving:
 *   1. Upload error code is OK (catches "no file", "too large", etc.)
 *   2. File size is within the 5 MB limit
 *   3. The temp file is actually an uploaded file (defense against path tricks)
 *   4. Real MIME type (read from file content, not user-supplied) is in
 *      the allowed image list. This is the only reliable way to detect
 *      a renamed .php file pretending to be a .jpg.
 *   5. Filename is generated server-side from random bytes, so the user
 *      cannot inject path components and two uploads with the same
 *      original name do not overwrite each other.
 *
 * On the first call after install, creates the uploads/ directory and
 * an .htaccess file inside it that disables PHP execution. This is a
 * defense-in-depth measure in case Apache configuration is overridden.
 *
 * Returns the relative path stored in the animes.image_path column
 * (e.g. "uploads/a1b2c3d4e5f6.jpg") or null if no file was uploaded.
 *
 * Throws Exception with a Turkish user-facing message on validation
 * failure. Callers should catch and display the message.
 */
function handleImageUpload($file)
{
    // 1. No file at all - this is a valid case (user just edited fields).
    if (!isset($file) || !is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // 2. Upload error from PHP itself (size, partial, missing tmp dir, etc.)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'Resim sunucu sinirini asti.',
            UPLOAD_ERR_FORM_SIZE  => 'Resim form sinirini asti.',
            UPLOAD_ERR_PARTIAL    => 'Resim kismen yuklendi, tekrar deneyin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda gecici klasor bulunamadi.',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazilamadi.',
            UPLOAD_ERR_EXTENSION  => 'Bir PHP eklentisi yuklemeyi durdurdu.',
        ];
        $message = $errorMessages[$file['error']] ?? 'Bilinmeyen yukleme hatasi.';
        throw new Exception('Resim yuklenemedi: ' . $message);
    }

    // 3. Size limit (5 MB). PHP also enforces upload_max_filesize from
    // php.ini, but we set our own limit so the message is friendly.
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Resim cok buyuk. En fazla 5 MB olabilir.');
    }

    // 4. Defense in depth: confirm the temp file really came from an upload.
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Gecersiz dosya yuklemesi.');
    }

    // 5. Read the real MIME type from the file content. Never trust the
    // user-supplied $file['type'] - it can be anything.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowedMimes[$mimeType])) {
        throw new Exception('Sadece JPG, PNG, WEBP veya GIF resim yukleyebilirsiniz.');
    }

    $extension = $allowedMimes[$mimeType];

    // 6. Make sure uploads/ exists and has the .htaccess guard.
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Yukleme klasoru olusturulamadi.');
        }
    }

    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccess = "# Disable PHP execution in this directory\n";
        $htaccess .= "php_flag engine off\n";
        $htaccess .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar)$\">\n";
        $htaccess .= "    Require all denied\n";
        $htaccess .= "</FilesMatch>\n";
        @file_put_contents($htaccessPath, $htaccess);
    }

    // 7. Generate a unique server-side filename. random_bytes is
    // cryptographically secure and rules out collisions in practice.
    $uniqueName = bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $uniqueName;

    // 8. Move the file into place.
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Dosya kaydedilemedi.');
    }

    // Return the web-accessible path that goes into the DB.
    return 'uploads/' . $uniqueName;
}

/**
 * Return the current session's CSRF token, creating one on first call.
 *
 * The token is 64 hex characters (32 random bytes) generated from
 * random_bytes, which is cryptographically secure.
 *
 * Every form that performs a state-changing action (insert, update,
 * delete) must include this token as a hidden input, and the receiving
 * handler must call csrf_verify() on the posted value.
 *
 * Assumes session_start() has already been called (db.php does this).
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Timing-safe comparison of a posted token against the session token.
 *
 * Uses hash_equals() to avoid leaking information via timing attacks.
 * Returns false if either value is missing or does not match.
 */
function csrf_verify($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Return a URL safe to use inside an href="" attribute, or an empty
 * string if the URL is unsafe or empty.
 *
 * This protects against javascript: and data: URL schemes that would
 * execute code when clicked. htmlspecialchars alone does NOT protect
 * against these - it escapes HTML metacharacters but leaves the scheme
 * intact, so <a href="javascript:alert(1)"> is still dangerous after
 * htmlspecialchars.
 *
 * Only http:// and https:// URLs are accepted. Anything else (javascript:,
 * data:, vbscript:, file:, ftp:, missing scheme, malformed URL) returns
 * an empty string.
 *
 * The return value is already HTML-escaped for attribute context, so
 * callers should NOT wrap the result in htmlspecialchars again:
 *     <a href="<?= safe_url($url) ?>">...</a>
 *
 * Returns empty string so the caller can safely compare with empty()
 * to decide whether to render the link at all.
 */
function safe_url($url) {
    if (empty($url)) {
        return '';
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

// =====================================================================
// Series relationship helpers (v0.5 mid-cycle addition)
// =====================================================================

/**
 * Return all animes that share the same series_name, excluding the
 * given anime itself. Results are grouped by media_type (TV first,
 * then Film, then OVA/Special/ONA) and within each group sorted by
 * release_date ascending.
 *
 * Returns an empty array if $series_name is empty/null or no related
 * animes exist.
 */
function getRelatedAnimes($pdo, $series_name, $exclude_id) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT id, title, media_type, watch_status, watched_episodes,
               total_episodes, release_date, image_path
        FROM animes
        WHERE series_name = ? AND id != ?
        ORDER BY
            FIELD(media_type, 'TV', 'Film', 'OVA', 'Special', 'ONA'),
            release_date ASC,
            id ASC
    ");
    $stmt->execute([$series_name, (int)$exclude_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return all chronology markers for a given anime, with full details
 * of the related anime (title, watch_status, etc.) via JOIN.
 *
 * Results are sorted by after_episode ascending so the UI can display
 * them in episode order.
 */
function getChronologyMarkers($pdo, $anime_id) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title, a.watch_status AS related_watch_status,
               a.media_type AS related_media_type
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
        ORDER BY cm.after_episode ASC
    ");
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check whether the user's current watch progress triggers a
 * chronology marker alert. Returns the marker row (with related
 * anime details) if the NEXT episode to watch (watched + 1) matches
 * a marker's after_episode. Returns null if no alert is needed.
 *
 * Example: anime has a marker with after_episode=23. If the user has
 * watched 23 episodes, the next one would be 24, but the marker says
 * "watch the related anime first". So we compare watched_episodes
 * against after_episode: if watched >= after_episode AND the related
 * anime is not yet watched, show the alert.
 *
 * We only alert for markers where the related anime's watch_status
 * is NOT 'Izlendi' — if the user already watched the film, no need
 * to remind them.
 */
function getActiveChronologyAlert($pdo, $anime_id, $watched_episodes) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title, a.watch_status AS related_watch_status,
               a.media_type AS related_media_type, a.id AS related_id
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
          AND cm.after_episode <= ?
          AND a.watch_status != 'İzlendi'
        ORDER BY cm.after_episode ASC
        LIMIT 1
    ");
    $stmt->execute([(int)$anime_id, (int)$watched_episodes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Return all distinct series_name values from the animes table,
 * sorted alphabetically. Used to populate the datalist/auto-complete
 * in the add/edit forms so the user does not have to type series
 * names from memory (and risk typos).
 */
function getAllSeriesNames($pdo) {
    $stmt = $pdo->query("
        SELECT DISTINCT series_name
        FROM animes
        WHERE series_name IS NOT NULL AND series_name != ''
        ORDER BY series_name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Validate that setting next_in_series does not create a direct
 * circular reference (A -> B -> A). Does NOT check transitive
 * cycles (A -> B -> C -> A) — that would require a recursive
 * walk and is overkill for a single-user app.
 *
 * Returns true if the link is safe, false if it would create a
 * direct loop.
 */
function validateNextInSeries($pdo, $anime_id, $target_id) {
    if (empty($target_id) || $target_id == $anime_id) {
        // Pointing to yourself is always invalid
        return $target_id != $anime_id;
    }
    // Check if the target already points back to us
    $stmt = $pdo->prepare("SELECT next_in_series FROM animes WHERE id = ?");
    $stmt->execute([(int)$target_id]);
    $targetNext = $stmt->fetchColumn();
    if ($targetNext !== false && (int)$targetNext === (int)$anime_id) {
        return false; // direct circular: A -> B -> A
    }
    return true;
}
?>