<?php

/**
 * Anime Tracker - Admin Sync (Client Side) — EXAMPLE FILE
 *
 * This is a template. To set up admin sync on your local machine:
 *   1. Copy this file:  cp admin/admin_sync_example.php admin/admin_sync.php
 *   2. Set ADMIN_PUSH_URL below to your server's admin_push.php URL
 *   3. Create admin/admin_secret.php with your HMAC secret (see below)
 *   4. Make sure admin_push.php is deployed on your server
 *
 * This file is git-ignored — your real admin_sync.php should never
 * be committed because it contains your server URL.
 *
 * ---
 *
 * Local admin tool to push the catalog from your workstation to the
 * server. Run this from localhost only - it's gated both by IP check
 * and by the HMAC secret. Regular users never see or need this file.
 *
 * Typical workflow:
 *   1. Add a new anime locally via add_anime.php
 *   2. Edit or add chronology markers
 *   3. Open this page -> click "Push to Server" -> done
 *
 * What it does:
 *   - Reads all source='catalog' animes and all chronology_markers
 *     from your local DB.
 *   - POSTs them as JSON to the server's admin_push.php endpoint,
 *     with an HMAC-SHA256 signature.
 *   - Shows the server's response (inserted/updated/markers).
 *
 * Personal data is stripped before sending: watched_episodes,
 * watch_status, notes, next_episode_date are your private state.
 * Only catalog fields go up.
 *
 * Required files:
 *   - admin/admin_secret.php  (HMAC secret, single define line; same folder)
 *   - config.php              (DB credentials at project root, created by setup.php)
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

// --- Configuration -------------------------------------------------------

// CHANGE THIS: Your server's admin_push.php URL.
// Example: 'https://www.yourdomain.com/admin_push.php' or subdomain
const ADMIN_PUSH_URL = 'https://www.yourdomain.com/admin_push.php';

// Local-only: refuse requests that don't come from the loopback interface.
// This is an admin tool - nobody remote should be able to reach it.
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
if (!$isLocal) {
    http_response_code(403);
    die('Bu sayfa sadece localhost uzerinden erisilebilir.');
}

// Load the shared HMAC secret from a SEPARATE file that is NEVER committed
// to git and NEVER packaged into the .exe installer.
//
// File location:   {project_root}/admin/admin_secret.php
// File contents:   <?php define('ADMIN_PUSH_SECRET', '<64 hex chars>');
//
// Generate a secret:
//   Linux/Mac:   openssl rand -hex 32
//   Windows:     C:\xampp\php\php.exe -r "echo bin2hex(random_bytes(32));"
//
// The SAME secret must be set in the server's admin_push_config.php
// as ADMIN_SECRET.
$secretFile = __DIR__ . '/admin_secret.php';
if (file_exists($secretFile)) {
    require_once $secretFile;
}

$secretConfigured = defined('ADMIN_PUSH_SECRET') && strlen(ADMIN_PUSH_SECRET) >= 32;

// Count animes still sitting at source='local'. They would be missed
// by this push because admin_sync only sends source='catalog' rows.
$pendingLocalCount = 0;
try {
    $pendingLocalCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM animes WHERE source = 'local'"
    )->fetchColumn();
} catch (Exception $e) {
    // Non-fatal - warning just will not appear
}

// --- Handle POST (actual push) ------------------------------------------

$result = null;
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['do_push'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'CSRF tokeni gecersiz.';
    } elseif (!$secretConfigured) {
        $error = 'ADMIN_PUSH_SECRET admin_secret.php icinde tanimli degil.';
    } else {
        try {
            // Gather all catalog animes.
            // Genres no longer live on this row - they are in the
            // anime_genres join table and get attached as a CSV string
            // below to keep wire-format compatibility with the server
            // (which still expects a comma-separated 'genres' string).
            $animeRows = $pdo->query("
                SELECT id, title, alternative_titles, title_english, status,
                       total_episodes, aired_episodes,
                       synopsis_tr, synopsis_en, translation_status, release_date, end_date,
                       anidb_link, mal_link, anime_schedule_link,
                       episode_interval, broadcast_day, broadcast_time, broadcast_timezone,
                       series_name, media_type, country, is_adult,
                       mal_id, anidb_id, catalog_uuid,
                       image_path
                FROM animes
                WHERE source = 'catalog'
                ORDER BY id
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Gather all chronology markers.
            //
            // Karar 1B: this SELECT intentionally has NO
            // "WHERE source = 'catalog'" filter. The admin curates the
            // universal chronology, so a push must carry BOTH the admin's
            // own markers (source='user') and previously-synced ones
            // (source='catalog') up to the catalog. The source='user'
            // label exists only to protect local markers from being
            // wiped by a catalog_import that runs before a push - it is
            // NOT a draft/unpublished state. admin_push.php stores every
            // pushed marker as source='catalog' (publishing to the
            // catalog makes it catalog-managed); the next local import
            // then reconverges the local labels via ON DUPLICATE KEY
            // UPDATE.
            //
            // DO NOT add a source filter here. Filtering to 'catalog'
            // would silently stop the admin's own markers from ever
            // reaching the catalog and re-break the 14 Nisan 2026
            // marker-loss fix from the other direction.
            $markers = $pdo->query("
                SELECT anime_id, after_episode, story_after_episode, related_anime_id, note
                FROM chronology_markers
                ORDER BY anime_id, after_episode
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Gather every tag (recommendation system sentences).
            //
            // 0.7.7: tags also carry an optional English name (name_en).
            // The wire keys tags by their (Turkish) name, so name_en is
            // shipped as a separate translation map (tag_name_en:
            // { turkish_name: english_name }) rather than turning the
            // per-anime tag list into objects. Old servers ignore the
            // extra key; the import side fills name_en from it. Only
            // non-empty values are sent. Mirror of catalog.php.
            $tagRows = $pdo->query("
                SELECT id, name, name_en, is_adult FROM tags ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);

            // Build a lookup so we can attach a flat list of sentence
            // texts to each anime in the payload.
            $tagById = [];
            $tagNameEn = [];
            $tagIsAdult = [];
            foreach ($tagRows as $t) {
                $tagById[(int)$t['id']] = $t['name'];
                if (isset($t['name_en']) && $t['name_en'] !== '') {
                    $tagNameEn[$t['name']] = $t['name_en'];
                }
                // 1.1.3: adult-flag map, keyed by name like tag_name_en.
                // Only adult (1) entries travel; absence means not-adult.
                if (!empty($t['is_adult'])) {
                    $tagIsAdult[$t['name']] = 1;
                }
            }

            $linkRows = $pdo->query("
                SELECT anime_id, tag_id FROM anime_tags
            ")->fetchAll(PDO::FETCH_ASSOC);

            $tagsByAnime = [];
            foreach ($linkRows as $row) {
                $aid = (int)$row['anime_id'];
                $tid = (int)$row['tag_id'];
                if (isset($tagById[$tid])) {
                    $tagsByAnime[$aid][] = $tagById[$tid];
                }
            }

            // Attach the sentence list to each anime row.
            foreach ($animeRows as &$a) {
                $aid = (int)$a['id'];
                $a['tags'] = $tagsByAnime[$aid] ?? [];
            }
            unset($a);

            // Genres (canonical taxonomy).
            //
            // Build a single in-memory map of anime_id -> [name, name, ...]
            // by JOINing anime_genres with genres once, then attach each
            // anime's genres as a comma-separated string. CSV format is
            // the wire-format the server still expects (Decision 2 - A
            // in PROJE_DURUMU.md: server side untouched). When the server
            // moves to an array format in a later release this can be
            // replaced with attaching the array directly.
            $genreLinkRows = $pdo->query("
                SELECT ag.anime_id, g.name
                FROM anime_genres ag
                INNER JOIN genres g ON g.id = ag.genre_id
                ORDER BY ag.anime_id, g.name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $genresByAnime = [];
            foreach ($genreLinkRows as $row) {
                $aid = (int)$row['anime_id'];
                $genresByAnime[$aid][] = $row['name'];
            }

            foreach ($animeRows as &$a) {
                $aid = (int)$a['id'];
                $names = $genresByAnime[$aid] ?? [];
                $a['genres'] = implode(',', $names);
            }
            unset($a);

            // 0.7.7: genre English-name translation map, built from the
            // master genres table (all genres, not just linked ones) so
            // it stays symmetric with the global tag library. Only
            // non-empty values. Mirror of catalog.php.
            $genreNameEn = [];
            $genreIsAdult = [];
            $genreNameEnRows = $pdo->query("
                SELECT name, name_en, is_adult FROM genres ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($genreNameEnRows as $row) {
                if (isset($row['name_en']) && $row['name_en'] !== '') {
                    $genreNameEn[$row['name']] = $row['name_en'];
                }
                // 1.1.3: adult-flag map, mirror of genre_name_en. Only
                // adult (1) entries travel; absence means not-adult.
                if (!empty($row['is_adult'])) {
                    $genreIsAdult[$row['name']] = 1;
                }
            }

            // Push via the shared batched helper (catalog_push.php). It does the
            // catalog gather + chunked, chronology-safe send in one place, so the
            // manual push uses the EXACT same wire as the automatic push: large
            // catalogs are split into anime chunks and the chronology is sent in
            // a final id_map batch, staying under the receiver's per-request caps.
            //
            // NOTE: the gather block above is now superseded by the helper (which
            // re-reads the catalog itself) and could be removed; it is left here
            // only so this template still documents exactly which columns travel.
            require_once __DIR__ . '/catalog_push.php';
            $r = catalog_push_to_server($pdo);
            if (empty($r['ok'])) {
                throw new Exception($r['message'] ?? 'Push basarisiz.');
            }
            $result = [
                'inserted'     => (int)($r['inserted'] ?? 0),
                'updated'      => (int)($r['updated']  ?? 0),
                'markers'      => (int)($r['markers']  ?? 0),
                'anime_count'  => (int)($r['anime_count']  ?? 0),
                'marker_count' => (int)($r['marker_count'] ?? 0),
                'batches'      => (int)($r['batches'] ?? 1),
            ];

        } catch (Exception $e) {
            $error = $e->getMessage();
            error_log('[admin_sync] ' . $error);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Admin Sync - Anime Tracker</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        .admin-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-container h1 { margin-top: 0; }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin: 15px 0;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .stat-box .num {
            font-size: 2em;
            font-weight: 600;
            color: #28a745;
        }
        .stat-box .label {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .push-button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }
        .push-button:hover {
            background: #c82333;
        }
        .push-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1><i class="fas fa-cloud-upload-alt"></i> Admin Sync</h1>

        <p style="color:#666;">
            Local katalogunuzu sunucuya gonderir. Sadece sizin (admin) tarafindan kullanilir.
            Kisisel izleme veriniz (watched, status, notes) GONDERILMEZ - sadece katalog
            bilgileri (basliklar, synopsis, linkler, kronoloji) aktarilir.
        </p>

        <?php if ($pendingLocalCount > 0): ?>
            <div class="warning-box">
                <strong><i class="fas fa-inbox"></i> Bekleyen <?php echo $pendingLocalCount; ?> anime var</strong><br>
                Local DB'de <?php echo $pendingLocalCount; ?> anime hala
                <code>source='local'</code> durumunda - bu push onlari
                <strong>sunucuya gondermez</strong>.
                Once <a href="admin_pending.php">Bekleyen Animeler</a>
                sayfasindan kataloga al, sonra buradan push yap.
            </div>
        <?php endif; ?>

        <?php if (!$secretConfigured): ?>
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Kurulum gerekli</strong><br>
                Proje icindeki <code>admin/</code> klasorune <code>admin_secret.php</code>
                dosyasi olusturun (kesinlikle
                GitHub'a commit etmeyin, <code>.gitignore</code>'da tanimli):
                <pre style="background:#f8f9fa;padding:10px;border-radius:4px;margin-top:10px;">
&lt;?php
define('ADMIN_PUSH_SECRET', '&lt;uzun_rastgele_anahtar&gt;');</pre>
                Ayni anahtar sunucunun <code>private/admin_push_config.php</code>
                dosyasindaki <code>ADMIN_SECRET</code> ile birebir ayni olmali.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-box">
                <strong><i class="fas fa-times-circle"></i> Hata</strong><br>
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <?php if ($result): ?>
            <div class="success-box">
                <strong><i class="fas fa-check-circle"></i> Sunucu guncellendi</strong>
                <div class="stat-grid">
                    <div class="stat-box">
                        <div class="num"><?php echo $result['inserted']; ?></div>
                        <div class="label">yeni anime eklendi</div>
                    </div>
                    <div class="stat-box">
                        <div class="num"><?php echo $result['updated']; ?></div>
                        <div class="label">mevcut anime guncellendi</div>
                    </div>
                    <div class="stat-box">
                        <div class="num"><?php echo $result['markers']; ?></div>
                        <div class="label">kronoloji notu</div>
                    </div>
                </div>
                <p style="margin-bottom:0;">
                    Gonderilen: <?php echo $result['anime_count']; ?> anime,
                    <?php echo $result['marker_count']; ?> kronoloji notu.
                </p>
                <?php if ($result['inserted'] > 0): ?>
                    <p style="margin-top:10px;">
                        <strong>Hatirlatma:</strong> Yeni eklenen animeler icin poster gorsellerini
                        sunucunun <code>uploads/</code> klasorune FTP ile yuklemeyi unutmayin.
                        Yoksa ilk kullanici sync'te poster indirmeye calisirken 404 alir.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" onsubmit="return confirm('Local katalog sunucuya gonderilecek. Devam?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="do_push" value="1">
            <button type="submit" class="push-button" <?php echo $secretConfigured ? '' : 'disabled'; ?>>
                <i class="fas fa-paper-plane"></i> Sunucuya Gonder
            </button>
        </form>

        <p style="margin-top:30px;">
            <a href="../list_settings.php">Liste Ayarlarina don</a>
        </p>
    </div>
</body>
</html>
