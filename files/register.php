<?php

/**
 * Anime Tracker - Register
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth) - Dilim 4. The account-registration page for
 * multi-user mode.
 *
 * In single-user mode there is no registration, so this page redirects to the
 * home page. In multi-user mode the behaviour depends on the instance setting
 * registration_mode (settings table):
 *   - 'invite' (default): the visitor must present a valid, unused invite
 *     token. The token is consumed (marked used) atomically with creating the
 *     account.
 *   - 'open': anyone may self-register; no token field is shown.
 *
 * A new account is created with role='user' and status='active'. On success
 * the user is logged in immediately (auth_login) and sent to the home page.
 * Email-verification / 'pending' status is a later milestone.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

// No registration in single-user / self-host mode.
if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// Already signed in -> nothing to register.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$mode = get_setting($pdo, 'registration_mode', 'invite');
$inviteMode = ($mode !== 'open');

// Operator announcement shown on the registration screen (1.1.12). Free text
// set in the admin panel (admin_invites.php); empty means no notice.
//
// 1.1.16: the announcement can be written per language. register_announcement
// is the Turkish (base) text; register_announcement_en is the optional English
// text. On the English interface the English text is shown IF it is set;
// otherwise it falls back to the Turkish text, so an operator who only writes
// one announcement still has it appear in both languages (unchanged 1.1.12
// behaviour for single-language operators).
$announcement = trim((string)get_setting($pdo, 'register_announcement', ''));
if (current_lang() === 'en') {
    $announcementEn = trim((string)get_setting($pdo, 'register_announcement_en', ''));
    if ($announcementEn !== '') {
        $announcement = $announcementEn;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = t('auth.register.err_generic');
    } else {
        $token    = trim($_POST['token'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        // Field validation. Stop at the first problem so the user fixes one
        // thing at a time; the server is the authority (the form also marks
        // fields required, but that is only UX).
        if ($username === '' || $password === '' || $confirm === ''
            || ($inviteMode && $token === '')) {
            $error = ($inviteMode && $token === '')
                ? t('auth.register.err_token_required')
                : t('auth.register.err_generic');
        } elseif (!preg_match('/^[A-Za-z0-9_]{3,32}$/', $username)) {
            $error = t('auth.register.err_username_invalid');
        } elseif (strlen($password) < 8) {
            $error = t('auth.register.err_password_short');
        } elseif ($password !== $confirm) {
            $error = t('auth.register.err_password_mismatch');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('auth.register.err_email_invalid');
        }

        // Invite token must exist and be unused (authoritative consume happens
        // inside the transaction below; this is the early friendly check).
        if ($error === '' && $inviteMode) {
            $chk = $pdo->prepare(
                "SELECT id FROM invites WHERE token = ? AND used_by IS NULL LIMIT 1"
            );
            $chk->execute([$token]);
            if (!$chk->fetchColumn()) {
                $error = t('auth.register.err_token_invalid');
            }
        }

        // Uniqueness pre-checks for friendly messages (the UNIQUE keys on
        // users are the real guarantee; the catch below is the backstop).
        if ($error === '') {
            $u = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            $u->execute([$username]);
            if ($u->fetchColumn()) {
                $error = t('auth.register.err_username_taken');
            }
        }
        if ($error === '' && $email !== '') {
            $e = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $e->execute([$email]);
            if ($e->fetchColumn()) {
                $error = t('auth.register.err_email_taken');
            }
        }

        if ($error === '') {
            try {
                $pdo->beginTransaction();

                $ins = $pdo->prepare(
                    "INSERT INTO users (username, email, password_hash, role, status)
                     VALUES (?, ?, ?, 'user', 'active')"
                );
                $ins->execute([
                    $username,
                    ($email !== '' ? $email : null),
                    auth_hash_password($password),
                ]);
                $newId = (int)$pdo->lastInsertId();

                if ($inviteMode) {
                    // Consume the token. The WHERE used_by IS NULL guard makes
                    // this the authoritative single-use check: if a concurrent
                    // registration already consumed it, rowCount is 0 and we
                    // roll the whole thing back.
                    $consume = $pdo->prepare(
                        "UPDATE invites SET used_by = ?, used_at = current_timestamp()
                         WHERE token = ? AND used_by IS NULL"
                    );
                    $consume->execute([$newId, $token]);
                    if ($consume->rowCount() !== 1) {
                        $pdo->rollBack();
                        $error = t('auth.register.err_token_invalid');
                    }
                }

                if ($error === '') {
                    $pdo->commit();
                }
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('[anime_tracker] register insert failed: ' . $ex->getMessage());
                // Most likely a unique-key race (username/email taken between
                // the pre-check and the insert). Keep the message generic.
                $error = t('auth.register.err_username_taken');
            }
        }

        if ($error === '') {
            // Log the new user straight in, then go home.
            auth_login($pdo, $username, $password);
            header('Location: index.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('auth.register.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .auth-container {
            max-width: 420px;
            margin: 60px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-container h1 {
            margin-top: 0;
            color: #333;
            font-size: 1.5em;
        }
        .auth-intro {
            color: #555;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-row { margin-bottom: 15px; }
        .form-row label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
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
            width: 100%;
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
        .announcement {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .announcement i { margin-right: 6px; }
        .auth-alt {
            margin-top: 18px;
            text-align: center;
            font-size: 14px;
            color: #555;
        }
        /* 1.1.16: secondary actions ("have an account? sign in" / "request an
           invite") as buttons rather than plain purple links, so they read as
           tappable controls. Colors match the "add anime" button
           (.anime-list-button): teal #17a2b8, hover #138496, white text. */
        .auth-alt a {
            display: inline-block;
            margin-top: 8px;
            padding: 9px 18px;
            background-color: #17a2b8;
            color: #fff;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        .auth-alt a:hover {
            background-color: #138496;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><i class="fas fa-user-plus"></i> <?php echo htmlspecialchars(t('auth.register.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($announcement !== ''): ?>
            <div class="announcement"><i class="fas fa-bullhorn"></i><?php echo nl2br(htmlspecialchars($announcement, ENT_QUOTES, 'UTF-8')); ?></div>
        <?php endif; ?>

        <?php if ($inviteMode): ?>
            <p class="auth-intro"><?php echo htmlspecialchars(t('auth.register.intro_invite'), ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="errors"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <?php if ($inviteMode): ?>
            <div class="form-row">
                <label for="token"><?php echo htmlspecialchars(t('auth.register.token'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($_POST['token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            <?php endif; ?>

            <div class="form-row">
                <label for="username"><?php echo htmlspecialchars(t('auth.register.username'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autofocus required>
            </div>
            <div class="form-row">
                <label for="email"><?php echo htmlspecialchars(t('auth.register.email'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="form-row">
                <label for="password"><?php echo htmlspecialchars(t('auth.register.password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-row">
                <label for="password_confirm"><?php echo htmlspecialchars(t('auth.register.password_confirm'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <button type="submit" class="submit-button">
                <i class="fas fa-user-plus"></i> <?php echo htmlspecialchars(t('auth.register.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>

        <div class="auth-alt">
            <a href="login.php"><?php echo htmlspecialchars(t('auth.register.have_account'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php if ($inviteMode): ?>
            <br><a href="request_invite.php"><?php echo htmlspecialchars(t('auth.register.request_invite'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </div>

        <?php // 1.1.16: anonim ziyaretci dil secici, kartin altinda. Uye
              // olmayanlarin user_pref satiri yoktur; bu switcher secimi
              // oturuma yazar (set_language.php). Uye/self-host icin ''
              // doner - gorunmez. ?>
        <?php echo guest_lang_switcher(); ?>
    </div>
</body>
</html>
