<?php

/**
 * Anime Tracker - Security Helpers (image upload, CSRF token/verify, URL sanitize)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

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
    $uploadDir = dirname(__DIR__) . '/uploads';
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
