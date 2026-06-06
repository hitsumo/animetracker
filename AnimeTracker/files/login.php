<?php

/**
 * Anime Tracker - Login
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth). The sign-in page for multi-user mode.
 *
 * In single-user mode there is no login, so this page redirects to the
 * home page. In multi-user mode it shows a username/password form, verifies
 * the credentials via auth_login(), and starts the session on success.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

// No login in single-user / self-host mode.
if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// Already signed in -> nothing to do here.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = t('auth.login.error');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = t('auth.login.empty');
        } elseif (auth_login($pdo, $username, $password)) {
            header('Location: index.php');
            exit;
        } else {
            // Generic message: never reveal whether the username or the
            // password was the wrong half.
            $error = t('auth.login.error');
        }
    }
}

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('auth.login.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
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
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(t('auth.login.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($error !== ''): ?>
            <div class="errors"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="form-row">
                <label for="username"><?php echo htmlspecialchars(t('auth.login.username'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" autofocus required>
            </div>
            <div class="form-row">
                <label for="password"><?php echo htmlspecialchars(t('auth.login.password'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="submit-button">
                <i class="fas fa-sign-in-alt"></i> <?php echo htmlspecialchars(t('auth.login.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>
    </div>
</body>
</html>
