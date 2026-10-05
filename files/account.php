<?php

/**
 * Anime Tracker - Account
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth). The signed-in user's account page: shows the
 * profile (username / email / role, read-only for now) and lets the user
 * change their password.
 *
 * 1.2.5 - "Delete my account": the member confirms with their password AND
 * by typing their username (there is no undo). The work is done by
 * account_delete() (functions/account_helpers.php), the same routine the
 * admin's Users page uses, after account_delete_check() (id 1 and the last
 * active admin cannot be deleted). On success the session ends and the
 * browser lands on login.php?deleted=1.
 *
 * Single-user mode has no account concept (the owner has no password), so
 * it redirects home. In multi-user mode it requires a logged-in user.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// Multi-user: must be signed in (redirects to login.php otherwise).
require_login();

$user    = current_user($pdo);
$message = '';
$error   = '';
$delError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_account') {
    // 1.2.5 - delete my account.
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $delError = t('auth.account.delete.err_failed');
    } else {
        $hashStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $hashStmt->execute([(int)current_user_id()]);
        $storedHash = $hashStmt->fetchColumn();
        $why = account_delete_check($pdo, (int)current_user_id());

        if (!auth_verify_password($_POST['delete_password'] ?? '', $storedHash)) {
            $delError = t('auth.account.err_current');
        } elseif (trim((string)($_POST['confirm_username'] ?? '')) !== (string)($user['username'] ?? '')) {
            $delError = t('auth.account.delete.err_confirm');
        } elseif ($why === 'owner') {
            $delError = t('auth.account.delete.err_owner');
        } elseif ($why === 'last_admin') {
            $delError = t('auth.account.delete.err_last_admin');
        } elseif ($why !== '' || !account_delete($pdo, (int)current_user_id())) {
            $delError = t('auth.account.delete.err_failed');
        } else {
            auth_logout();
            header('Location: login.php?deleted=1');
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = t('auth.account.err_empty');
    } else {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $error = t('auth.account.err_empty');
        } else {
            // current_user() does not select password_hash, so read it here.
            $hashStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
            $hashStmt->execute([(int)current_user_id()]);
            $storedHash = $hashStmt->fetchColumn();

            if (!auth_verify_password($current, $storedHash)) {
                $error = t('auth.account.err_current');
            } elseif (strlen($new) < 8) {
                $error = t('auth.account.err_short');
            } elseif ($new !== $confirm) {
                $error = t('auth.account.err_mismatch');
            } else {
                $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $upd->execute([auth_hash_password($new), (int)current_user_id()]);
                $message = t('auth.account.success');
            }
        }
    }
}

// 1.2.5: the delete form is not offered at all where it could never succeed.
$delBlocked = account_delete_check($pdo, (int)current_user_id());

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('auth.account.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php echo asset_styles(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .auth-container {
            max-width: 520px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-container h1 { margin-top: 0; color: #333; font-size: 1.5em; }
        .auth-container h2 {
            font-size: 1.1em;
            color: #333;
            margin-top: 25px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .profile-row { margin-bottom: 10px; color: #444; }
        .profile-row .label { font-weight: 500; display: inline-block; min-width: 120px; color: #333; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-row input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .submit-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
        }
        .submit-button:hover { background-color: #0056b3; }
        .errors {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .danger-h2 { color: #b02a37 !important; }
        .danger-text { color: #555; font-size: 0.92em; line-height: 1.55; }
        .danger-button { background-color: #dc3545; }
        .danger-button:hover { background-color: #b02a37; }
        .back-link { display: inline-block; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><i class="fas fa-user-cog"></i> <?php echo htmlspecialchars(t('auth.account.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($message !== ''): ?>
            <div class="success"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="errors"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="profile-row">
            <span class="label"><?php echo htmlspecialchars(t('auth.account.username_label'), ENT_QUOTES, 'UTF-8'); ?>:</span>
            <?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="profile-row">
            <span class="label"><?php echo htmlspecialchars(t('auth.account.email_label'), ENT_QUOTES, 'UTF-8'); ?>:</span>
            <?php
                $email = $user['email'] ?? '';
                echo $email !== ''
                    ? htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
                    : htmlspecialchars(t('auth.account.email_empty'), ENT_QUOTES, 'UTF-8');
            ?>
        </div>
        <div class="profile-row">
            <span class="label"><?php echo htmlspecialchars(t('auth.account.role_label'), ENT_QUOTES, 'UTF-8'); ?>:</span>
            <?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>

        <h2><?php echo htmlspecialchars(t('auth.account.change_password'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <label for="current_password"><?php echo htmlspecialchars(t('auth.account.current_password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-row">
                <label for="new_password"><?php echo htmlspecialchars(t('auth.account.new_password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            <div class="form-row">
                <label for="new_password_confirm"><?php echo htmlspecialchars(t('auth.account.new_password_confirm'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="new_password_confirm" name="new_password_confirm" required>
            </div>
            <button type="submit" class="submit-button">
                <i class="fas fa-key"></i> <?php echo htmlspecialchars(t('auth.account.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>

        <h2 id="delete" class="danger-h2"><?php echo htmlspecialchars(t('auth.account.delete.h2'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <?php if ($delError !== ''): ?>
            <div class="errors"><?php echo htmlspecialchars($delError, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($delBlocked === 'owner' || $delBlocked === 'last_admin'): ?>
        <p class="danger-text"><?php echo htmlspecialchars(t($delBlocked === 'owner' ? 'auth.account.delete.err_owner' : 'auth.account.delete.err_last_admin'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php else: ?>
        <p class="danger-text"><?php echo t('auth.account.delete.text'); ?></p>
        <form method="post" action="account.php#delete">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="delete_account">
            <div class="form-row">
                <label for="delete_password"><?php echo htmlspecialchars(t('auth.account.delete.password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="delete_password" name="delete_password" required autocomplete="current-password">
            </div>
            <div class="form-row">
                <label for="confirm_username"><?php echo htmlspecialchars(t('auth.account.delete.confirm_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="confirm_username" name="confirm_username" required autocomplete="off"
                       placeholder="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <button type="submit" class="submit-button danger-button">
                <i class="fas fa-trash-alt"></i> <?php echo htmlspecialchars(t('auth.account.delete.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>
        <?php endif; ?>

        <a href="index.php" class="back-link"><?php echo t('help.back_to_home'); ?></a>
    </div>
</body>
</html>
