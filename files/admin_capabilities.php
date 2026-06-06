<?php

/**
 * Anime Tracker - Admin Capabilities (override toggles)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Admin-only page for curator override switches. Linked from admin.php
 * (the hidden dashboard). NOT a client-facing feature.
 *
 * Security model (IMPORTANT - read carefully):
 *   Distribution: like admin.php, this file IS public on GitHub (GPL
 *   source) but is NOT bundled into the .exe installer (end users never
 *   receive it). (Docker: not currently excluded - see admin.php note.)
 *   The protection is NOT file secrecy - it is the localhost-only IP
 *   gate below: remote requests get 403, only loopback can reach it.
 *   So: never put a real secret (server URL, token, password) in this
 *   file or in admin.php. Real secrets live in admin_secret.php /
 *   admin_sync.php, which are kept OUT of both the repo and the
 *   installer (only *_example.php versions are published).
 *   A client who clones the GitHub repo can run this page on their OWN
 *   install, but that only edits their own local DB; the next
 *   catalog_import sync overwrites it, and they cannot push to the
 *   server (that needs admin_secret.php, which they do not have). So
 *   catalog authority is unaffected.
 *
 * First capability (0.7.2): "synopsis_edit_override". When ON, the
 * edit_anime.php Mode 2 lock (catalog synopsis TR/EN readonly when a
 * personal synopsis exists) is lifted so the curator can edit the
 * catalog synopsis directly. The Mode 1/Mode 2 logic itself is
 * unchanged - this only relaxes the readonly rendering + save guard
 * when the override is set. Stored as a runtime settings key
 * (synopsis_edit_override = '1'/'0'), same family as
 * display_title_english / display_language, so no migration is needed.
 *
 * The override lives only on this (admin) install. Client installs do
 * not ship this page and cannot flip the key, so catalog authority on
 * the client side is unaffected. Editing the catalog synopsis here is
 * correct: the curator IS the authority.
 *
 * To add a new capability later: add a settings key, a POST branch
 * below, and a card in the markup. Keep each capability self-contained.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init_admin($pdo);

// --- Access control ----------------------------------------------------

// Online: gate by role (signed-in admin). Self-host: loopback-only (same
// rule as admin.php).
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

// --- POST: toggle a capability -----------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_cap.csrf_invalid'), ENT_QUOTES, 'UTF-8'));
    }

    // synopsis_edit_override: strict '1' / '0'. Anything other than the
    // checkbox's '1' turns it off.
    if (isset($_POST['cap_synopsis_override'])) {
        $enabled = (($_POST['synopsis_edit_override'] ?? '') === '1') ? '1' : '0';
        set_setting($pdo, 'synopsis_edit_override', $enabled);
    }

    header('Location: admin_capabilities.php');
    exit;
}

// --- Read current state ------------------------------------------------

$synopsisOverride = (get_setting($pdo, 'synopsis_edit_override', '0') === '1');

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_cap.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .admin-dashboard { max-width: 900px; margin: 40px auto; padding: 30px; }
        .admin-header {
            background: #dc3545; color: white; padding: 25px 30px;
            border-radius: 8px 8px 0 0; display: flex; align-items: center; gap: 15px;
        }
        .admin-header h1 { margin: 0; font-size: 1.5em; font-weight: 500; }
        .admin-header .sub { opacity: 0.9; font-size: 0.9em; margin-top: 4px; }
        .admin-body {
            background: white; padding: 30px; border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .cap-card { border: 1px solid #e0e0e0; border-radius: 6px; padding: 20px; margin-top: 20px; }
        .cap-card h3 { margin: 0 0 10px 0; color: #333; font-size: 1.1em; }
        .cap-card p { color: #666; font-size: 0.9em; margin: 0 0 15px 0; line-height: 1.5; }
        .cap-toggle { display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95em; }
        .cap-toggle input[type="checkbox"] { width: auto; margin: 0; }
        .cap-status { margin-top: 12px; padding: 8px 12px; border-radius: 4px; font-size: 0.85em; display: inline-block; }
        .status-on { background: #d4edda; color: #155724; }
        .status-off { background: #f5f5f5; color: #666; }
        .back-link { display: inline-block; margin-top: 20px; color: #666; text-decoration: none; }
        .back-link:hover { color: #dc3545; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-header">
            <i class="fas fa-sliders-h" style="font-size: 2em;"></i>
            <div>
                <h1><?php echo htmlspecialchars(t('admin_cap.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="sub"><?php echo htmlspecialchars(t('admin_cap.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="admin-body">
            <p style="color: #666; margin-top: 0;">
                <?php echo htmlspecialchars(t('admin_cap.intro'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <!-- Capability: synopsis edit override -->
            <div class="cap-card">
                <h3><i class="fas fa-unlock-alt"></i> <?php echo htmlspecialchars(t('admin_cap.synopsis_override.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('admin_cap.synopsis_override.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="admin_capabilities.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="cap_synopsis_override" value="1">
                    <input type="hidden" name="synopsis_edit_override" value="0">
                    <label class="cap-toggle">
                        <input type="checkbox" name="synopsis_edit_override" value="1"<?php echo $synopsisOverride ? ' checked' : ''; ?> onchange="this.form.submit()">
                        <?php echo htmlspecialchars(t('admin_cap.synopsis_override.checkbox'), ENT_QUOTES, 'UTF-8'); ?>
                    </label>
                    <noscript>
                        <button type="submit" class="tool-link"><?php echo htmlspecialchars(t('admin_cap.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </noscript>
                </form>
                <div class="cap-status <?php echo $synopsisOverride ? 'status-on' : 'status-off'; ?>">
                    <i class="fas <?php echo $synopsisOverride ? 'fa-check' : 'fa-lock'; ?>"></i>
                    <?php echo htmlspecialchars($synopsisOverride ? t('admin_cap.synopsis_override.status_on') : t('admin_cap.synopsis_override.status_off'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <a href="admin.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_cap.back_to_admin'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</body>
</html>
