<?php

/**
 * Anime Tracker - Logout
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth). Ends the session.
 *
 * Logout is a state-changing action, so it is done with a POST + CSRF
 * token (a GET logout link could be triggered by a third-party page). A
 * plain GET renders a small confirm button that submits the POST.
 *
 * Single-user mode has no session to end, so it redirects home.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// Not signed in -> send to the login page.
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_verify($_POST['csrf_token'] ?? '')) {
        auth_logout();
        header('Location: login.php');
        exit;
    }
    // CSRF mismatch falls through to re-render the confirm screen.
}

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('auth.logout.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
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
            text-align: center;
        }
        .auth-container h1 {
            margin-top: 0;
            color: #333;
            font-size: 1.5em;
        }
        .auth-container p { color: #666; margin-bottom: 25px; }
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
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars(t('auth.logout.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars(t('auth.logout.confirm'), ENT_QUOTES, 'UTF-8'); ?></p>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="submit-button">
                <i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars(t('auth.logout.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>
    </div>
</body>
</html>
