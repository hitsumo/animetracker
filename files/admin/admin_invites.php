<?php

/**
 * Anime Tracker - Admin Invites (registration invites + registration_mode)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth) - Dilim 4. Moderator/admin page to manage
 * registration: generate single-use invite tokens, list/revoke them, and
 * (admin only) switch the instance registration_mode between 'invite' and
 * 'open'. Linked from admin.php.
 *
 * Two capabilities with DIFFERENT role bars on one page:
 *   - Generate / list / revoke invites: moderator+ (the page guard).
 *   - Change registration_mode: admin only. This is operator policy, so the
 *     toggle card is both hidden from non-admins (can('admin')) AND its POST
 *     branch re-checks require_role('admin') server-side (hide + protect).
 *
 * Security model mirrors admin.php / admin_capabilities.php: online gates by
 * role; self-host falls back to a loopback-only IP gate. No real secret lives
 * in this file. registration_mode only has meaning in multi-user mode (there
 * is no registration path in self-host); the page is still reachable on a
 * self-host install via the loopback gate, where the owner counts as admin.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

lang_init_admin($pdo);

// --- Access control ----------------------------------------------------

// Online: invite management is moderator+. Self-host: loopback-only (same
// rule as admin.php); the seeded owner counts as admin there.
if (MULTI_USER_MODE) {
    require_role($pdo, 'moderator');
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    if (!$isLocal) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_pending.localhost_only'), ENT_QUOTES, 'UTF-8'));
    }
}

// --- POST --------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_invites.csrf_invalid'), ENT_QUOTES, 'UTF-8'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        // moderator+ already enforced by the page guard.
        $email = trim($_POST['email'] ?? '');
        $emailVal = ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) ? $email : null;

        // Random single-use token. Retry on the (astronomically unlikely)
        // unique collision; give up after a few tries rather than loop forever.
        $ins = $pdo->prepare(
            "INSERT INTO invites (token, email, created_by) VALUES (?, ?, ?)"
        );
        for ($i = 0; $i < 5; $i++) {
            $token = bin2hex(random_bytes(16)); // 32 hex chars, fits varchar(64)
            try {
                $ins->execute([$token, $emailVal, (int)current_user_id()]);
                break;
            } catch (PDOException $e) {
                $code = isset($e->errorInfo[1]) ? $e->errorInfo[1] : 0;
                if ($code !== 1062) { // not a duplicate-token race -> real error
                    error_log('[anime_tracker] invite generate failed: ' . $e->getMessage());
                    break;
                }
            }
        }
    } elseif ($action === 'revoke') {
        // Only an UNUSED invite can be revoked; a consumed token stays as an
        // audit record. moderator+ (page guard).
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $del = $pdo->prepare("DELETE FROM invites WHERE id = ? AND used_by IS NULL");
            $del->execute([$id]);
        }
    } elseif ($action === 'set_mode') {
        // registration_mode is operator policy -> admin only. Hidden from
        // non-admins in the UI AND enforced here (hide + protect).
        require_role($pdo, 'admin');
        $mode = (($_POST['registration_mode'] ?? '') === 'open') ? 'open' : 'invite';
        set_setting($pdo, 'registration_mode', $mode);
    } elseif ($action === 'set_notify_email') {
        // The invite-request notification target is operator policy -> admin
        // only (hide + protect), same rule as set_mode. An empty value clears
        // it: requests are still queued, just no mail is sent.
        require_role($pdo, 'admin');
        $notify = trim($_POST['invite_notify_email'] ?? '');
        if ($notify === '' || filter_var($notify, FILTER_VALIDATE_EMAIL)) {
            set_setting($pdo, 'invite_notify_email', $notify);
        }
    } elseif ($action === 'invite_for_request') {
        // moderator+ (page guard). Generate a single-use invite carrying the
        // requester's email, then mark the request 'invited'. The new token
        // shows in the Invites list (other tab) for the operator to send.
        $reqId = (int)($_POST['id'] ?? 0);
        if ($reqId > 0) {
            $rq = $pdo->prepare(
                "SELECT email FROM invite_requests WHERE id = ? AND status = 'pending' LIMIT 1"
            );
            $rq->execute([$reqId]);
            $reqEmail = $rq->fetchColumn();
            if ($reqEmail !== false) {
                $emailVal = filter_var($reqEmail, FILTER_VALIDATE_EMAIL) ? $reqEmail : null;
                $ins = $pdo->prepare(
                    "INSERT INTO invites (token, email, created_by) VALUES (?, ?, ?)"
                );
                for ($i = 0; $i < 5; $i++) {
                    $token = bin2hex(random_bytes(16)); // 32 hex chars, fits varchar(64)
                    try {
                        $ins->execute([$token, $emailVal, (int)current_user_id()]);
                        break;
                    } catch (PDOException $e) {
                        $code = isset($e->errorInfo[1]) ? $e->errorInfo[1] : 0;
                        if ($code !== 1062) { // not a duplicate-token race -> real error
                            error_log('[anime_tracker] invite-for-request generate failed: ' . $e->getMessage());
                            break;
                        }
                    }
                }
                $upd = $pdo->prepare(
                    "UPDATE invite_requests SET status = 'invited' WHERE id = ? AND status = 'pending'"
                );
                $upd->execute([$reqId]);
            }
        }
    } elseif ($action === 'reject_request') {
        // moderator+ (page guard). Mark a pending request rejected (kept as an
        // audit record rather than deleted).
        $reqId = (int)($_POST['id'] ?? 0);
        if ($reqId > 0) {
            $upd = $pdo->prepare(
                "UPDATE invite_requests SET status = 'rejected' WHERE id = ? AND status = 'pending'"
            );
            $upd->execute([$reqId]);
        }
    }

    header('Location: admin_invites.php');
    exit;
}

// --- Read current state ------------------------------------------------

$mode = get_setting($pdo, 'registration_mode', 'invite');
$isOpen = ($mode === 'open');
$isAdmin = can($pdo, 'admin');

$invites = $pdo->query(
    "SELECT id, token, email, created_by, used_by, used_at, created_at
     FROM invites ORDER BY created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Invite requests (1.0.20). Pending first, then newest. The configured notify
// address is shown/edited in the requests tab (admin only).
$notifyEmail = (string)get_setting($pdo, 'invite_notify_email', '');

$requests = $pdo->query(
    "SELECT id, email, reason, ip, status, created_at
     FROM invite_requests
     ORDER BY (status = 'pending') DESC, created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$pendingCount = 0;
foreach ($requests as $r) {
    if ($r['status'] === 'pending') {
        $pendingCount++;
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_invites.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
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
        .cap-card label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; font-size: 0.9em; }
        .cap-card input[type="text"], .cap-card input[type="email"], .cap-card select {
            padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; min-width: 220px;
        }
        .inline-form { display: inline-flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn {
            background-color: #007bff; color: white; border: none; padding: 9px 16px;
            border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500;
        }
        .btn:hover { background-color: #0056b3; }
        .btn-danger { background-color: #dc3545; padding: 5px 10px; font-size: 12px; }
        .btn-danger:hover { background-color: #b02a37; }
        .mode-status { margin-top: 12px; padding: 8px 12px; border-radius: 4px; font-size: 0.85em; display: inline-block; }
        .status-on { background: #d4edda; color: #155724; }
        .status-off { background: #fff3cd; color: #856404; }
        table.invites { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.88em; }
        table.invites th, table.invites td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; }
        table.invites th { color: #555; font-weight: 600; }
        table.invites code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        .used { color: #999; }
        .unused-badge { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .used-badge { background: #f5f5f5; color: #777; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .back-link { display: inline-block; margin-top: 20px; color: #666; text-decoration: none; }
        .back-link:hover { color: #dc3545; }
        .it-tabs { display: flex; gap: 8px; margin: 20px 0; border-bottom: 2px solid #e0e0e0; }
        .it-tab-btn { appearance: none; background: transparent; border: none; border-bottom: 3px solid transparent; padding: 10px 18px; font-size: 1.0em; color: #555; cursor: pointer; margin-bottom: -2px; }
        .it-tab-btn:hover { color: #dc3545; }
        .it-tab-btn.active { color: #dc3545; border-bottom-color: #dc3545; font-weight: 600; }
        .it-tab-panel { display: none; }
        .it-tab-panel.active { display: block; }
        .it-badge { background: #dc3545; color: #fff; border-radius: 10px; padding: 1px 8px; font-size: 0.78em; margin-left: 6px; }
        table.it-requests { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 0.88em; }
        table.it-requests th, table.it-requests td { text-align: left; padding: 8px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.it-requests th { color: #555; font-weight: 600; }
        .it-reason { white-space: pre-wrap; max-width: 320px; word-break: break-word; }
        .st-pending { background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .st-invited { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .st-rejected { background: #f5f5f5; color: #777; padding: 2px 8px; border-radius: 10px; font-size: 0.8em; }
        .btn-ok { background-color: #28a745; padding: 5px 10px; font-size: 12px; }
        .btn-ok:hover { background-color: #1e7e34; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-header">
            <i class="fas fa-envelope-open-text" style="font-size: 2em;"></i>
            <div>
                <h1><?php echo htmlspecialchars(t('admin_invites.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="sub"><?php echo htmlspecialchars(t('admin_invites.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="admin-body">
            <p style="color: #666; margin-top: 0;">
                <?php echo htmlspecialchars(t('admin_invites.intro'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <div class="it-tabs">
                <button type="button" class="it-tab-btn active" data-tab="invites"><?php echo htmlspecialchars(t('admin_invites.tab.invites'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="it-tab-btn" data-tab="requests">
                    <?php echo htmlspecialchars(t('admin_invites.tab.requests'), ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($pendingCount > 0): ?><span class="it-badge"><?php echo (int)$pendingCount; ?></span><?php endif; ?>
                </button>
            </div>

            <!-- Tab: Invites (registration mode + generate + list) -->
            <div class="it-tab-panel active" id="it-panel-invites">

            <?php if ($isAdmin): ?>
            <!-- Registration mode (admin only) -->
            <div class="cap-card">
                <h3><i class="fas fa-door-open"></i> <?php echo htmlspecialchars(t('admin_invites.mode.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('admin_invites.mode.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="admin_invites.php" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="set_mode">
                    <label for="registration_mode" style="margin:0;"><?php echo htmlspecialchars(t('admin_invites.mode.label'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="registration_mode" name="registration_mode" onchange="this.form.submit()">
                        <option value="invite"<?php echo $isOpen ? '' : ' selected'; ?>><?php echo htmlspecialchars(t('admin_invites.mode.invite'), ENT_QUOTES, 'UTF-8'); ?></option>
                        <option value="open"<?php echo $isOpen ? ' selected' : ''; ?>><?php echo htmlspecialchars(t('admin_invites.mode.open'), ENT_QUOTES, 'UTF-8'); ?></option>
                    </select>
                    <noscript><button type="submit" class="btn"><?php echo htmlspecialchars(t('admin_invites.mode.save'), ENT_QUOTES, 'UTF-8'); ?></button></noscript>
                </form>
                <div class="mode-status <?php echo $isOpen ? 'status-off' : 'status-on'; ?>">
                    <i class="fas <?php echo $isOpen ? 'fa-door-open' : 'fa-envelope'; ?>"></i>
                    <?php echo htmlspecialchars(t('admin_invites.mode.current') . ' ' . ($isOpen ? t('admin_invites.mode.open') : t('admin_invites.mode.invite')), ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Generate an invite (moderator+) -->
            <div class="cap-card">
                <h3><i class="fas fa-plus-circle"></i> <?php echo htmlspecialchars(t('admin_invites.generate.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(t('admin_invites.generate.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                <form method="post" action="admin_invites.php" class="inline-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="generate">
                    <input type="email" name="email" placeholder="<?php echo htmlspecialchars(t('admin_invites.generate.email_label'), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars(t('admin_invites.generate.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
                </form>
            </div>

            <!-- Invite list -->
            <div class="cap-card">
                <h3><i class="fas fa-list"></i> <?php echo htmlspecialchars(t('admin_invites.list.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <?php if (empty($invites)): ?>
                    <p style="margin:0;"><?php echo htmlspecialchars(t('admin_invites.list.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
                <?php else: ?>
                    <table class="invites">
                        <thead>
                            <tr>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_token'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_created'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_used'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?php echo htmlspecialchars(t('admin_invites.list.col_action'), ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($invites as $inv): ?>
                            <?php $isUsed = ($inv['used_by'] !== null); ?>
                            <tr<?php echo $isUsed ? ' class="used"' : ''; ?>>
                                <td><code><?php echo htmlspecialchars($inv['token'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                <td><?php echo htmlspecialchars($inv['email'] !== null && $inv['email'] !== '' ? $inv['email'] : t('admin_invites.list.email_none'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($isUsed): ?>
                                        <span class="used-badge"><?php echo htmlspecialchars(t('admin_invites.list.status_used'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else: ?>
                                        <span class="unused-badge"><?php echo htmlspecialchars(t('admin_invites.list.status_unused'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars((string)$inv['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($inv['used_at'] !== null ? (string)$inv['used_at'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if (!$isUsed): ?>
                                        <form method="post" action="admin_invites.php" onsubmit="return confirm('<?php echo htmlspecialchars(t('admin_invites.list.revoke_confirm'), ENT_QUOTES, 'UTF-8'); ?>');" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="revoke">
                                            <input type="hidden" name="id" value="<?php echo (int)$inv['id']; ?>">
                                            <button type="submit" class="btn btn-danger"><?php echo htmlspecialchars(t('admin_invites.list.revoke'), ENT_QUOTES, 'UTF-8'); ?></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            </div><!-- /it-panel-invites -->

            <!-- Tab: Invite requests (notify address + pending queue) -->
            <div class="it-tab-panel" id="it-panel-requests">

                <?php if ($isAdmin): ?>
                <!-- Notification address (admin only) -->
                <div class="cap-card">
                    <h3><i class="fas fa-bell"></i> <?php echo htmlspecialchars(t('admin_invites.notify.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p><?php echo htmlspecialchars(t('admin_invites.notify.desc'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <form method="post" action="admin_invites.php" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="set_notify_email">
                        <label for="invite_notify_email" style="margin:0;"><?php echo htmlspecialchars(t('admin_invites.notify.label'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="email" id="invite_notify_email" name="invite_notify_email" value="<?php echo htmlspecialchars($notifyEmail, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars(t('admin_invites.notify.placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn"><?php echo htmlspecialchars(t('admin_invites.notify.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                    <?php if ($notifyEmail === ''): ?>
                        <div class="mode-status status-off">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars(t('admin_invites.notify.none'), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Pending / past invite requests -->
                <div class="cap-card">
                    <h3><i class="fas fa-inbox"></i> <?php echo htmlspecialchars(t('admin_invites.requests.h3'), ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php if (empty($requests)): ?>
                        <p style="margin:0;"><?php echo htmlspecialchars(t('admin_invites.requests.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else: ?>
                        <table class="it-requests">
                            <thead>
                                <tr>
                                    <th><?php echo htmlspecialchars(t('admin_invites.requests.col_email'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars(t('admin_invites.requests.col_reason'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars(t('admin_invites.requests.col_date'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars(t('admin_invites.requests.col_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                                    <th><?php echo htmlspecialchars(t('admin_invites.requests.col_action'), ENT_QUOTES, 'UTF-8'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($requests as $rq): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rq['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><div class="it-reason"><?php echo htmlspecialchars($rq['reason'], ENT_QUOTES, 'UTF-8'); ?></div></td>
                                    <td><?php echo htmlspecialchars((string)$rq['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($rq['status'] === 'pending'): ?>
                                            <span class="st-pending"><?php echo htmlspecialchars(t('admin_invites.requests.status_pending'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php elseif ($rq['status'] === 'invited'): ?>
                                            <span class="st-invited"><?php echo htmlspecialchars(t('admin_invites.requests.status_invited'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="st-rejected"><?php echo htmlspecialchars(t('admin_invites.requests.status_rejected'), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rq['status'] === 'pending'): ?>
                                            <div class="inline-form">
                                                <form method="post" action="admin_invites.php" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="invite_for_request">
                                                    <input type="hidden" name="id" value="<?php echo (int)$rq['id']; ?>">
                                                    <button type="submit" class="btn btn-ok"><i class="fas fa-ticket-alt"></i> <?php echo htmlspecialchars(t('admin_invites.requests.make_invite'), ENT_QUOTES, 'UTF-8'); ?></button>
                                                </form>
                                                <form method="post" action="admin_invites.php" onsubmit="return confirm('<?php echo htmlspecialchars(t('admin_invites.requests.reject_confirm'), ENT_QUOTES, 'UTF-8'); ?>');" style="margin:0;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="reject_request">
                                                    <input type="hidden" name="id" value="<?php echo (int)$rq['id']; ?>">
                                                    <button type="submit" class="btn btn-danger"><?php echo htmlspecialchars(t('admin_invites.requests.reject'), ENT_QUOTES, 'UTF-8'); ?></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            </div><!-- /it-panel-requests -->

            <a href="admin.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_invites.back_to_admin'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
<script>
(function () {
    var buttons = document.querySelectorAll('.it-tab-btn');
    var panels = {
        invites: document.getElementById('it-panel-invites'),
        requests: document.getElementById('it-panel-requests')
    };
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.getAttribute('data-tab');
            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            Object.keys(panels).forEach(function (key) {
                if (panels[key]) { panels[key].classList.toggle('active', key === tab); }
            });
        });
    });
})();
</script>
</body>
</html>
