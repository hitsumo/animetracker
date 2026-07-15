<?php

/**
 * Anime Tracker - Admin Users (role / status management)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth) - follow-on to Dilim 4. Admin-only page to
 * list registered users and change each user's role (user / trusted /
 * moderator / admin) and status (active / suspended). Linked from admin.php.
 *
 * Role/status management is an ADMIN capability (role x action matrix,
 * FAZ2_AUTH_TASARIMI 1/5), so unlike admin_invites.php (moderator+) this page
 * is admin-gated. Security model mirrors admin.php: online gates by role,
 * self-host falls back to a loopback-only IP gate.
 *
 * LOCKOUT SAFETY (two server-side guards, hide + protect):
 *   1. Self-guard: an admin may not change their OWN role or status here. The
 *      controls are hidden on the actor's own row AND the POST branch rejects
 *      a self-target. This alone prevents an instance from ever reaching zero
 *      admins through this screen: the actor is always an active admin, and
 *      they cannot remove themselves, so at least one admin always remains.
 *   2. Last-admin guard (defense in depth): a change that would leave zero
 *      active admins is rejected outright. Redundant given guard 1, but it
 *      keeps the invariant even if self-action is ever enabled later.
 *
 * Status: only active <-> suspended are offered as changes. 'pending'
 * (email-verification, a later milestone) and 'deleted' (the GDPR soft-delete
 * routine, also later) are system states - they are displayed if present but
 * not set from here, so this page never half-implements soft-delete.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

lang_init_admin($pdo);

// --- Access control ----------------------------------------------------

// Online: admin only. Self-host: loopback-only (admin.php rule).
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

$VALID_ROLES    = ['admin', 'moderator', 'trusted', 'user'];
$SETTABLE_STATUS = ['active', 'suspended'];

// --- POST: update a user's role/status ---------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_users.csrf_invalid'), ENT_QUOTES, 'UTF-8'));
    }

    if (($_POST['action'] ?? '') === 'update_user') {
        $id = (int)($_POST['id'] ?? 0);

        // Self-guard: never act on your own account from this screen.
        if ($id === (int)current_user_id()) {
            http_response_code(403);
            die(htmlspecialchars(t('admin_users.err_self'), ENT_QUOTES, 'UTF-8'));
        }

        $target = null;
        if ($id > 0) {
            $q = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ? LIMIT 1");
            $q->execute([$id]);
            $target = $q->fetch(PDO::FETCH_ASSOC);
        }

        if ($target) {
            // Whitelist the submitted values; fall back to the current value
            // for anything not offered (e.g. a 'pending'/'deleted' status is
            // left as-is, never set from here).
            $newRole = in_array($_POST['role'] ?? '', $VALID_ROLES, true)
                ? $_POST['role'] : $target['role'];
            $newStatus = in_array($_POST['status'] ?? '', $SETTABLE_STATUS, true)
                ? $_POST['status'] : $target['status'];

            // Last-admin guard: block a change that would demote/suspend the
            // final active admin.
            $removesAdmin = ($target['role'] === 'admin' && $target['status'] === 'active')
                && ($newRole !== 'admin' || $newStatus !== 'active');
            if ($removesAdmin) {
                $c = $pdo->prepare(
                    "SELECT COUNT(*) FROM users
                     WHERE role = 'admin' AND status = 'active' AND id <> ?"
                );
                $c->execute([$id]);
                if ((int)$c->fetchColumn() === 0) {
                    http_response_code(409);
                    die(htmlspecialchars(t('admin_users.err_last_admin'), ENT_QUOTES, 'UTF-8'));
                }
            }

            $upd = $pdo->prepare("UPDATE users SET role = ?, status = ? WHERE id = ?");
            $upd->execute([$newRole, $newStatus, $id]);
        }
    }

    // 1.1.11: reset a user's AniList import source limit by clearing their
    // recorded distinct sources - they regain the full N-source allowance.
    // Admin-gated (above); no self-guard needed (clearing is harmless) and no
    // last-admin concern (this touches no role/status).
    if (($_POST['action'] ?? '') === 'reset_anilist_sources') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare("DELETE FROM anilist_import_sources WHERE user_id = ?");
            $del->execute([$id]);
            header('Location: admin_users.php?ais_reset=' . $del->rowCount());
            exit;
        }
    }

    header('Location: admin_users.php');
    exit;
}

// --- Read current state ------------------------------------------------

$meId = (int)current_user_id();

$users = $pdo->query(
    "SELECT id, username, email, role, status, created_at
     FROM users
     ORDER BY FIELD(role, 'admin', 'moderator', 'trusted', 'user'), username"
)->fetchAll(PDO::FETCH_ASSOC);

// 1.1.11: used AniList import-source count per user (for the reset control).
$srcCounts = [];
foreach ($pdo->query(
    "SELECT user_id, COUNT(*) AS c FROM anilist_import_sources GROUP BY user_id"
) as $r) {
    $srcCounts[(int)$r['user_id']] = (int)$r['c'];
}

// Status options to render in a select for a given current status: the
// settable ones, plus the current value if it is a system state so the
// select still reflects reality.
function status_options_for($current, array $settable)
{
    $opts = $settable;
    if (!in_array($current, $opts, true)) {
        array_unshift($opts, $current);
    }
    return $opts;
}

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_users.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .admin-dashboard { max-width: 960px; margin: 40px auto; padding: 30px; }
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
        table.users { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.9em; }
        table.users th, table.users td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
        table.users th { color: #555; font-weight: 600; }
        table.users select { padding: 6px 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
        .me-row { background: #fbfbe7; }
        .me-badge { background: #007bff; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 0.78em; margin-left: 6px; }
        .self-note { color: #999; font-size: 0.85em; font-style: italic; }
        .btn {
            background-color: #007bff; color: white; border: none; padding: 6px 12px;
            border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 500;
        }
        .btn:hover { background-color: #0056b3; }
        .row-form { display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .back-link { display: inline-block; margin-top: 25px; color: #666; text-decoration: none; }
        .back-link:hover { color: #dc3545; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-header">
            <i class="fas fa-users-cog" style="font-size: 2em;"></i>
            <div>
                <h1><?php echo htmlspecialchars(t('admin_users.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="sub"><?php echo htmlspecialchars(t('admin_users.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="admin-body">
            <p style="color: #666; margin-top: 0;">
                <?php echo htmlspecialchars(t('admin_users.intro'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <?php if (isset($_GET['ais_reset'])): ?>
                <div style="background:#d4edda;color:#155724;padding:10px 14px;border-radius:4px;margin-bottom:15px;font-size:0.9em;">
                    <i class="fas fa-check"></i>
                    <?php echo htmlspecialchars(sprintf(t('admin_users.anilist_reset.done'), (int)$_GET['ais_reset']), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <table class="users">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(t('admin_users.col_username'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_users.col_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_users.col_role'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_users.col_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_users.col_created'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_users.col_action'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <?php $isMe = ((int)$u['id'] === $meId); ?>
                    <tr<?php echo $isMe ? ' class="me-row"' : ''; ?>>
                        <td>
                            <?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($isMe): ?><span class="me-badge"><?php echo htmlspecialchars(t('admin_users.you'), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars(($u['email'] !== null && $u['email'] !== '') ? $u['email'] : t('admin_users.email_none'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <?php if ($isMe): ?>
                            <td><?php echo htmlspecialchars(t('admin_users.role.' . $u['role']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(t('admin_users.status.' . $u['status']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string)$u['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="self-note"><?php echo htmlspecialchars(t('admin_users.self_locked'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <?php else: ?>
                            <td colspan="2">
                                <form method="post" action="admin_users.php" class="row-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="update_user">
                                    <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                    <select name="role">
                                        <?php foreach ($VALID_ROLES as $r): ?>
                                            <option value="<?php echo $r; ?>"<?php echo ($u['role'] === $r) ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('admin_users.role.' . $r), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="status">
                                        <?php foreach (status_options_for($u['status'], $SETTABLE_STATUS) as $s): ?>
                                            <option value="<?php echo $s; ?>"<?php echo ($u['status'] === $s) ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('admin_users.status.' . $s), ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn"><i class="fas fa-save"></i> <?php echo htmlspecialchars(t('admin_users.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                                </form>
                            </td>
                            <td><?php echo htmlspecialchars((string)$u['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <?php $sc = $srcCounts[(int)$u['id']] ?? 0; ?>
                                <?php if ($sc > 0): ?>
                                    <form method="post" action="admin_users.php" class="row-form"
                                          onsubmit="return confirm('<?php echo htmlspecialchars(t('admin_users.anilist_reset.confirm'), ENT_QUOTES, 'UTF-8'); ?>');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="reset_anilist_sources">
                                        <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                                        <span title="<?php echo htmlspecialchars(t('admin_users.anilist_reset.label'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(sprintf(t('admin_users.anilist_reset.count'), $sc), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <button type="submit" class="btn"><i class="fas fa-undo"></i> <?php echo htmlspecialchars(t('admin_users.anilist_reset.button'), ENT_QUOTES, 'UTF-8'); ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <a href="admin.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_users.back_to_admin'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
    <script src="../js/select_enhance.js" defer></script>
</body>
</html>
