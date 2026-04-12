<?php

/**
 * Anime Tracker - Self Update Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * WordPress-style in-place update endpoint. Downloads a ZIP from the
 * project website, extracts it into a staging folder, copies the files
 * over the existing installation, then runs any pending database
 * migrations.
 *
 * Invoked via POST from list_settings.php (via AJAX). Requires a valid
 * CSRF token.
 *
 * Protected files (never overwritten, always preserved):
 *   - config.php         (user's DB credentials)
 *   - uploads/           (user-uploaded anime cover images)
 *   - temp/              (this endpoint's own staging directory)
 *
 * Process:
 *   1. CSRF check
 *   2. Fetch remote version.txt to know which version to download
 *   3. Compare with current version - bail out if already up to date
 *   4. Download ZIP into temp/update.zip
 *   5. Extract into temp/extracted/
 *   6. Walk the extracted tree, copying each file into place
 *      (respecting the protected list)
 *   7. Run MigrationManager::run($pdo) for any DB schema changes
 *   8. Clean up temp/
 *   9. Return JSON success
 *
 * On any error the endpoint returns JSON with error=true and a Turkish
 * user-facing message. Partial updates can be resumed by simply running
 * the endpoint again - file copies are idempotent and the migration
 * manager handles partial DB upgrades via its idempotent error code
 * whitelist (1050, 1060, 1061, 1091).
 *
 * SECURITY NOTE: This endpoint can overwrite ANY file in the install
 * directory except the protected list. It is gated by:
 *   - CSRF token (prevents cross-site request forgery)
 *   - POST only (no accidental triggering via GET)
 *   - Fixed remote URL (cannot be parameterized by the client)
 * A compromised sicakcikolata.com would be catastrophic. In a future
 * release consider adding GPG/RSA signature verification on the ZIP.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// --- Helper: respond and exit --------------------------------------------

function update_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function update_fail($message, $logDetail = null) {
    if ($logDetail !== null) {
        error_log('[anime_tracker] update failed: ' . $message . ' | ' . $logDetail);
    } else {
        error_log('[anime_tracker] update failed: ' . $message);
    }
    update_respond([
        'success' => false,
        'error'   => true,
        'message' => $message,
    ]);
}

// --- Gate: POST + CSRF ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    update_fail('Sadece POST istekleri kabul edilir.');
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    update_fail('CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.');
}

// --- Config ---------------------------------------------------------------

// The remote endpoint that tells us the latest version.
$versionUrl = 'https://www.sicakcikolata.com/anime_tracker/version.txt';

// URL template for the downloadable ZIP. {VERSION} is substituted below.
$zipUrlTemplate = 'https://www.sicakcikolata.com/anime_tracker/updates/{VERSION}/anime-tracker-{VERSION}.zip';

// Files and directories that must NEVER be overwritten by an update.
// Paths are relative to the install root.
$protected = [
    'config.php',
    'uploads',
    'temp',
];

// Staging directory (created and cleaned up by this script).
$tempDir    = __DIR__ . '/temp';
$zipPath    = $tempDir . '/update.zip';
$extractDir = $tempDir . '/extracted';

// Upper bound on ZIP size to avoid accidentally downloading huge files.
// A normal update is ~50 KB; 20 MB is a huge safety margin.
$maxZipBytes = 20 * 1024 * 1024;

// --- Step 1: Pre-flight checks -------------------------------------------

if (!class_exists('ZipArchive')) {
    update_fail('Sunucu ZipArchive desteklemiyor. PHP zip eklentisi gerekli.');
}

if (!is_writable(__DIR__)) {
    update_fail('Kurulum klasoru yazilabilir degil. Izinleri kontrol edin.');
}

// --- Step 2: Fetch remote version ----------------------------------------

$remoteVersion = @file_get_contents($versionUrl);
if ($remoteVersion === false) {
    update_fail('Surum bilgisi alinamadi. Internet baglantinizi kontrol edin.');
}
$remoteVersion = trim($remoteVersion);

if ($remoteVersion === '' || !preg_match('/^[0-9]+(\.[0-9]+)*$/', $remoteVersion)) {
    update_fail('Uzak surum bilgisi gecersiz format.', 'received: ' . var_export($remoteVersion, true));
}

// --- Step 3: Read current version and compare ----------------------------

$currentVersion = null;
try {
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'version'");
    $currentVersion = $stmt->fetchColumn();
} catch (PDOException $e) {
    update_fail('Mevcut surum okunamadi.', $e->getMessage());
}

if (!$currentVersion) {
    update_fail('Mevcut surum bulunamadi. Kurulum bozuk olabilir.');
}

if (version_compare($currentVersion, $remoteVersion, '>=')) {
    update_respond([
        'success'        => true,
        'already_latest' => true,
        'message'        => 'Sistem zaten guncel (surum ' . $currentVersion . ').',
    ]);
}

// --- Step 4: Prepare staging directory -----------------------------------

// Remove any leftover temp directory from a previous failed run.
if (is_dir($tempDir)) {
    if (!delete_recursive($tempDir)) {
        update_fail('Eski gecici dosyalar temizlenemedi.');
    }
}

if (!mkdir($tempDir, 0755, true)) {
    update_fail('Gecici klasor olusturulamadi.');
}

// --- Step 5: Download the update ZIP -------------------------------------

$zipUrl = str_replace('{VERSION}', rawurlencode($remoteVersion), $zipUrlTemplate);

$zipData = @file_get_contents($zipUrl);
if ($zipData === false) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi indirilemedi.', 'url: ' . $zipUrl);
}

if (strlen($zipData) === 0) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi bos geldi.');
}

if (strlen($zipData) > $maxZipBytes) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi beklenenden buyuk, guvenlik icin durduruldu.');
}

if (file_put_contents($zipPath, $zipData) === false) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi diske yazilamadi.');
}

// --- Step 6: Extract the ZIP ---------------------------------------------

$zip = new ZipArchive();
$openResult = $zip->open($zipPath);
if ($openResult !== true) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi acilamadi (bozuk veya gecersiz ZIP).', 'code: ' . $openResult);
}

if (!$zip->extractTo($extractDir)) {
    $zip->close();
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi cikartilamadi.');
}
$zip->close();

// --- Step 7: Copy files into place ---------------------------------------

// Determine where in the extracted tree the actual file root lives.
// A zip created from `files/` contents directly will have index.php at
// the top level. A zip that preserves a top-level "files/" folder will
// have files/index.php one level deeper. Support both layouts.
$rootCandidate = $extractDir;
if (!file_exists($extractDir . '/index.php') && file_exists($extractDir . '/files/index.php')) {
    $rootCandidate = $extractDir . '/files';
}

// Sanity check: the extracted payload must at least contain index.php.
// If not, something is wrong with the ZIP layout - bail out before we
// start copying random files over the install.
if (!file_exists($rootCandidate . '/index.php')) {
    delete_recursive($tempDir);
    update_fail('Guncelleme paketi beklenen yapiya sahip degil (index.php bulunamadi).');
}

$copiedCount = 0;
$copyError   = null;
try {
    $copiedCount = copy_tree($rootCandidate, __DIR__, $protected);
} catch (Exception $e) {
    $copyError = $e->getMessage();
}

// Clean up staging even if the copy failed - the app is already in a
// half-updated state at this point, nothing to gain by keeping temp.
delete_recursive($tempDir);

if ($copyError !== null) {
    update_fail('Dosyalar kopyalanirken hata olustu: ' . $copyError);
}

if ($copiedCount === 0) {
    update_fail('Hicbir dosya kopyalanmadi. Paket bos olabilir.');
}

// --- Step 8: Run database migrations -------------------------------------

try {
    MigrationManager::run($pdo);
} catch (Exception $e) {
    update_fail('Dosyalar guncellendi ancak veritabani migrasyonu basarisiz oldu. Sunucu loglarini kontrol edin.', $e->getMessage());
}

// --- Step 9: Success ------------------------------------------------------

error_log('[anime_tracker] update succeeded: ' . $currentVersion . ' -> ' . $remoteVersion);

update_respond([
    'success'         => true,
    'previous_version' => $currentVersion,
    'new_version'     => $remoteVersion,
    'files_copied'    => $copiedCount,
    'message'         => 'Guncelleme tamamlandi. Surum ' . $remoteVersion,
]);

// --- Helper functions ----------------------------------------------------

/**
 * Recursively delete a directory and everything inside it.
 * Returns true on success, false on failure.
 * Safe to call on a path that does not exist.
 */
function delete_recursive($path) {
    if (!file_exists($path)) {
        return true;
    }
    if (is_file($path) || is_link($path)) {
        return @unlink($path);
    }
    if (is_dir($path)) {
        $entries = @scandir($path);
        if ($entries === false) {
            return false;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (!delete_recursive($path . DIRECTORY_SEPARATOR . $entry)) {
                return false;
            }
        }
        return @rmdir($path);
    }
    return false;
}

/**
 * Recursively copy every file from $source into $destination, creating
 * directories as needed. Paths that match an entry in $protected (relative
 * to the install root) are skipped entirely.
 *
 * Returns the number of files actually copied.
 * Throws Exception on any copy or mkdir failure.
 */
function copy_tree($source, $destination, array $protected) {
    $count = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        // Compute the path relative to the staging root. This is the key
        // we compare against the protected list, so the list works the
        // same way regardless of where the staging folder lives.
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $relative = str_replace('\\', '/', $relative);

        // Check protected list. A protected entry matches either an
        // exact filename (config.php) or any path underneath a
        // protected directory (uploads/foo.jpg matches "uploads").
        foreach ($protected as $skip) {
            if ($relative === $skip || strpos($relative, $skip . '/') === 0) {
                continue 2; // continue the outer foreach, skip this item
            }
        }

        $target = $destination . DIRECTORY_SEPARATOR . $relative;

        if ($item->isDir()) {
            if (!is_dir($target)) {
                if (!@mkdir($target, 0755, true)) {
                    throw new Exception('Klasor olusturulamadi: ' . $relative);
                }
            }
        } else {
            // Make sure the parent directory exists before copying.
            $parent = dirname($target);
            if (!is_dir($parent)) {
                if (!@mkdir($parent, 0755, true)) {
                    throw new Exception('Ust klasor olusturulamadi: ' . $relative);
                }
            }
            if (!@copy($item->getPathname(), $target)) {
                throw new Exception('Dosya kopyalanamadi: ' . $relative);
            }
            $count++;
        }
    }

    return $count;
}
