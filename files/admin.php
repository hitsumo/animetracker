<?php

/**
 * Anime Tracker - Admin Dashboard (unlinked entry point)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Admin dashboard - server sync and other admin operations.
 * Deliberately NOT linked from any other page; reached only by typing
 * http://localhost/anime_tracker/admin.php directly.
 *
 * Security model (IMPORTANT - read carefully):
 *   Distribution channels are NOT the same:
 *     - GitHub repo: this file IS published here, by design (GPL source;
 *       the catalog owner needs admin.php). So it is NOT secret.
 *     - .exe installer: this file is NOT bundled. End users who install
 *       via the .exe never receive any admin_*.php (installer files/
 *       folder excludes them).
 *     - Docker image: PRESENT by design - _dockerignore does not list
 *       admin_*.php. Docker is a server/self-host channel like the
 *       GitHub repo (not the consumer .exe), so shipping admin here is
 *       intentional, not an inconsistency. It stays harmless: the
 *       localhost gate below blocks remote access and no real secret
 *       ships (admin_secret.php / real admin_sync.php are repo- and
 *       installer-excluded).
 *   Because the file is public on GitHub, do NOT rely on file secrecy
 *   and do NOT put any real secret (server URL, token, password) here.
 *   The actual protection is:
 *     1. Localhost-only IP check below - non-loopback requests get 403.
 *     2. Not being linked from anywhere (obscurity - weak on its own).
 *   Real secrets live in admin_secret.php and the real admin_sync.php,
 *   which are kept OUT of BOTH the repo and the installer (only
 *   *_example.php are published) and listed in .gitignore.
 *
 * To add new admin tools in the future: just add a new card in the
 * tools grid below that links to a new admin_*.php file. Keep admin
 * functionality in separate files - this dashboard stays thin.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init_admin($pdo);

// --- Access control ----------------------------------------------------

// Online: gate the admin area by role (must be a signed-in admin). Self-host:
// no login exists, so hard-gate to loopback addresses only (refuses remote
// access entirely), which is the original self-host protection.
if (MULTI_USER_MODE) {
    require_role($pdo, 'admin');
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    if (!$isLocal) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_pending.localhost_only'), ENT_QUOTES, 'UTF-8'));
    }
}

// --- Tool availability check -------------------------------------------

// Each admin tool has two requirements:
//   - The tool PHP file must exist
//   - Its dependencies (e.g. shared secret file) must be in place
// If anything is missing, we show a setup hint instead of a dead link.
$syncAvailable = file_exists(__DIR__ . '/admin_sync.php');
$secretAvailable = file_exists(__DIR__ . '/admin_secret.php');
$syncReady = $syncAvailable && $secretAvailable;

$pendingAvailable = file_exists(__DIR__ . '/admin_pending.php');

// Count pending (source='local') animes so the card header shows the
// number without forcing the admin to open the page. If the DB cannot
// be reached we quietly skip this - the dashboard still renders.
$pendingCount = null;
if ($pendingAvailable) {
    try {
        require_once __DIR__ . '/db.php';
        $row = $pdo->query("SELECT COUNT(*) FROM animes WHERE source = 'local'")
                   ->fetchColumn();
        $pendingCount = (int)$row;
    } catch (Exception $e) {
        // Ignore - card will render without the count
        $pendingCount = null;
    }
}


$catalogRequestsAvailable = file_exists(__DIR__ . '/admin_catalog_requests.php');
$catalogRequestsCount = null;
if (MULTI_USER_MODE && $catalogRequestsAvailable) {
    try {
        require_once __DIR__ . '/db.php';
        $catalogRequestsCount = (int)$pdo->query(
            "SELECT COUNT(*) FROM catalog_requests WHERE suggestion_status = 'pending'"
        )->fetchColumn();
    } catch (Exception $e) {
        $catalogRequestsCount = null;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .admin-dashboard {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
        }
        .admin-header {
            background: #dc3545;
            color: white;
            padding: 25px 30px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 1.5em;
            font-weight: 500;
        }
        .admin-header .sub {
            opacity: 0.9;
            font-size: 0.9em;
            margin-top: 4px;
        }
        .admin-body {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .tool-card {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 20px;
            transition: all 0.2s;
        }
        .tool-card:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.1);
        }
        .tool-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 1.1em;
        }
        .tool-card p {
            color: #666;
            font-size: 0.9em;
            margin: 0 0 15px 0;
            line-height: 1.5;
        }
        .tool-link {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.2s;
        }
        .tool-link:hover {
            background: #c82333;
        }
        .tool-link.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        .tool-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .status-ok {
            background: #d4edda;
            color: #155724;
        }
        .status-missing {
            background: #fff3cd;
            color: #856404;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-header">
            <i class="fas fa-user-shield" style="font-size: 2em;"></i>
            <div>
                <h1><?php echo htmlspecialchars(t('admin.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="sub"><?php echo htmlspecialchars(t('admin.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="admin-body">
            <p style="color: #666; margin-top: 0;">
                <?php echo t('admin.intro'); ?>
            </p>

            <div class="tools-grid">

                <!-- Sunucu Sync -->
                <div class="tool-card">
                    <h3><i class="fas fa-cloud-upload-alt"></i> <?php echo htmlspecialchars(t('admin.tool.sync.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo t('admin.tool.sync.desc'); ?>
                    </p>

                    <?php if (!$syncReady): ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars(t('admin.tool.sync.missing_files'), ENT_QUOTES, 'UTF-8'); ?>
                            <ul style="margin: 5px 0 0 0; padding-left: 20px;">
                                <?php if (!$syncAvailable): ?>
                                    <li><code>admin_sync.php</code></li>
                                <?php endif; ?>
                                <?php if (!$secretAvailable): ?>
                                    <li><code>admin_secret.php</code></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php elseif ($pendingCount !== null && $pendingCount > 0): ?>
                        <span class="tool-link disabled">
                            <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars(t('admin.tool.sync.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo sprintf(t('admin.tool.sync.pending_warning'), (int)$pendingCount); ?>
                        </div>
                    <?php else: ?>
                        <a href="admin_sync.php" class="tool-link">
                            <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars(t('admin.tool.sync.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <div class="tool-status status-ok">
                            <i class="fas fa-check"></i> <?php echo htmlspecialchars(t('admin.tool.sync.status_ok'), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bekleyen Animeler (source='local' promotion) -->
                <div class="tool-card">
                    <h3>
                        <i class="fas fa-inbox"></i> <?php echo htmlspecialchars(t('admin.tool.pending.h3'), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($pendingCount !== null && $pendingCount > 0): ?>
                            <span style="background: #ffc107; color: #212529;
                                         padding: 2px 10px; border-radius: 12px;
                                         font-size: 0.75em; margin-left: 6px;">
                                <?php echo $pendingCount; ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p>
                        <?php echo t('admin.tool.pending.desc'); ?>
                    </p>

                    <?php if ($pendingAvailable): ?>
                        <a href="admin_pending.php" class="tool-link">
                            <i class="fas fa-tasks"></i>
                            <?php if ($pendingCount !== null && $pendingCount > 0): ?>
                                <?php echo htmlspecialchars(sprintf(t('admin.tool.pending.link.count'), (int)$pendingCount), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars(t('admin.tool.pending.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </a>
                        <?php if ($pendingCount === 0): ?>
                            <div class="tool-status status-ok">
                                <i class="fas fa-check"></i> <?php echo htmlspecialchars(t('admin.tool.pending.status_ok'), ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars(t('admin.tool.pending.missing_file'), ENT_QUOTES, 'UTF-8'); ?> <code>admin_pending.php</code>
                        </div>
                    <?php endif; ?>
                </div>
<?php if (MULTI_USER_MODE): ?>
                <!-- Katalog Onerileri (catalog_requests: ice aktarimdan eslesmeyenler) -->
                <div class="tool-card">
                    <h3>
                        <i class="fas fa-lightbulb"></i> <?php echo htmlspecialchars(t('admin.tool.catalog_requests.h3'), ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($catalogRequestsCount !== null && $catalogRequestsCount > 0): ?>
                            <span style="background: #28a745; color: #fff; padding: 2px 10px;
                                         border-radius: 12px; font-size: 0.75em; margin-left: 6px;">
                                <?php echo $catalogRequestsCount; ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <p><?php echo t('admin.tool.catalog_requests.desc'); ?></p>
                    <?php if ($catalogRequestsAvailable): ?>
                        <a href="admin_catalog_requests.php" class="tool-link">
                            <i class="fas fa-clipboard-list"></i>
                            <?php if ($catalogRequestsCount !== null && $catalogRequestsCount > 0): ?>
                                <?php echo htmlspecialchars(sprintf(t('admin.tool.catalog_requests.link.count'), (int)$catalogRequestsCount), ENT_QUOTES, 'UTF-8'); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars(t('admin.tool.catalog_requests.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars(t('admin.tool.catalog_requests.missing_file'), ENT_QUOTES, 'UTF-8'); ?> <code>admin_catalog_requests.php</code>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <!-- Yonetici Yetenekleri (override toggles) -->
                <div class="tool-card">
                    <h3><i class="fas fa-sliders-h"></i> <?php echo htmlspecialchars(t('admin.tool.capabilities.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo t('admin.tool.capabilities.desc'); ?>
                    </p>
                    <?php if (file_exists(__DIR__ . '/admin_capabilities.php')): ?>
                        <a href="admin_capabilities.php" class="tool-link">
                            <i class="fas fa-cog"></i> <?php echo htmlspecialchars(t('admin.tool.capabilities.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <code>admin_capabilities.php</code>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Kayit ve Davetler (invites + registration mode) -->
                <div class="tool-card">
                    <h3><i class="fas fa-envelope-open-text"></i> <?php echo htmlspecialchars(t('admin.tool.invites.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo t('admin.tool.invites.desc'); ?>
                    </p>
                    <?php if (file_exists(__DIR__ . '/admin_invites.php')): ?>
                        <a href="admin_invites.php" class="tool-link">
                            <i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars(t('admin.tool.invites.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <code>admin_invites.php</code>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Kullanici Yonetimi (rol + durum) -->
                <div class="tool-card">
                    <h3><i class="fas fa-users-cog"></i> <?php echo htmlspecialchars(t('admin.tool.users.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo t('admin.tool.users.desc'); ?>
                    </p>
                    <?php if (file_exists(__DIR__ . '/admin_users.php')): ?>
                        <a href="admin_users.php" class="tool-link">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars(t('admin.tool.users.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <code>admin_users.php</code>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Duzeltme Onerileri (moderation queue) -->
                <div class="tool-card">
                    <h3><i class="fas fa-flag"></i> <?php echo htmlspecialchars(t('admin.tool.suggestions.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>
                        <?php echo t('admin.tool.suggestions.desc'); ?>
                    </p>
                    <?php if (file_exists(__DIR__ . '/admin_suggestions.php')): ?>
                        <a href="admin_suggestions.php" class="tool-link">
                            <i class="fas fa-clipboard-check"></i> <?php echo htmlspecialchars(t('admin.tool.suggestions.link.open'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php else: ?>
                        <span class="tool-link disabled"><?php echo htmlspecialchars(t('admin.tool.sync.link.disabled'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <div class="tool-status status-missing">
                            <i class="fas fa-exclamation-triangle"></i>
                            <code>admin_suggestions.php</code>
                        </div>
                    <?php endif; ?>
                </div>

                <!--
                  Future tools go here. Example skeleton:

                  <div class="tool-card">
                      <h3><i class="fas fa-database"></i> DB Yedek Al</h3>
                      <p>Local DB'yi SQL dosyasi olarak yedekler.</p>
                      <a href="admin_backup.php" class="tool-link">Yedek al</a>
                  </div>
                -->

            </div>

            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin.back_to_home'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</body>
</html>
