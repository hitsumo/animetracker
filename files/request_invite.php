<?php

/**
 * Anime Tracker - Request an Invite (public invite-request form)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.0.20. A public page where a visitor who has no invite can ask the operator
 * for one. The visitor submits an email plus a free-text reason; the request is
 * stored (invite_requests, status='pending') for review on admin_invites.php,
 * and a best-effort notification mail is sent to the operator-configured address
 * (settings.invite_notify_email). The stored row is the source of truth - if no
 * notify address is set or mail() fails, the request still sits in the queue.
 *
 * Protections mirror suggest.php: CSRF + honeypot here, per-IP rate limit in the
 * helper (invite_request_submit). No login is required - this is for people who
 * do not have an account yet.
 *
 * Invite requests are a multi-user feature only and only meaningful while
 * registration_mode is 'invite'. In self-host there is no registration; in
 * 'open' mode no invite is needed, so this page bounces away.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
lang_init($pdo);

// No invite-request flow in single-user / self-host mode.
if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// Already signed in -> no need to request an invite.
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// In open registration there is no invite to request; send to register.
$mode = get_setting($pdo, 'registration_mode', 'invite');
if ($mode === 'open') {
    header('Location: register.php');
    exit;
}

// --- POST ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hp = trim($_POST['website'] ?? ''); // honeypot - real users never see it

    // CSRF.
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        header('Location: request_invite.php?req=err');
        exit;
    }

    // Honeypot: a bot filled the hidden field. Pretend success, drop silently
    // so the bot gets no signal that it was caught.
    if ($hp !== '') {
        header('Location: request_invite.php?req=ok');
        exit;
    }

    $email  = trim($_POST['email'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

    $result = invite_request_submit($pdo, $email, $reason, $ip);
    if ($result === 'ok') {
        // Best-effort notify; never blocks the stored request.
        invite_request_notify($pdo, $email, $reason);
    }

    header('Location: request_invite.php?req=' . $result);
    exit;
}

$flag = $_GET['req'] ?? '';

// Slot cap (1.1.12): when the pending queue is full the request form is closed.
// The submit helper enforces this authoritatively; here it decides whether to
// render the form at all.
$slotOpen = invite_request_limit_state($pdo)['open'];

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('invite_request.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .auth-container {
            max-width: 460px;
            margin: 60px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-container h1 { margin-top: 0; color: #333; font-size: 1.5em; }
        .auth-intro { color: #555; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .form-row { margin-bottom: 15px; }
        .form-row label { display: block; font-weight: 500; margin-bottom: 5px; color: #333; }
        .form-row input, .form-row textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;
            font-size: 14px; box-sizing: border-box; font-family: inherit;
        }
        .form-row textarea { min-height: 110px; resize: vertical; }
        .form-hint { color: #777; font-size: 12px; margin-top: 4px; }
        /* Honeypot: kept far off-screen so real users never interact with it. */
        .hp-field { position: absolute; left: -9999px; top: -9999px; height: 0; overflow: hidden; }
        .submit-button {
            background-color: #007bff; color: white; border: none; padding: 12px 25px;
            border-radius: 4px; cursor: pointer; font-size: 15px; font-weight: 500; width: 100%;
        }
        .submit-button:hover { background-color: #0056b3; }
        .banner { padding: 12px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; }
        .banner-ok { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .banner-err { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .banner-rate { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .auth-alt { margin-top: 18px; text-align: center; font-size: 14px; color: #555; }
        /* 1.1.16: secondary action ("back to register") as a button rather than
           a plain purple link, matching register.php. Colors match the "add
           anime" button (.anime-list-button): teal #17a2b8, hover #138496. */
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
        <h1><i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars(t('invite_request.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if ($flag === 'ok'): ?>
            <div class="banner banner-ok"><?php echo htmlspecialchars(t('invite_request.ok'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($flag === 'rate'): ?>
            <div class="banner banner-rate"><?php echo htmlspecialchars(t('invite_request.rate'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($flag === 'full'): ?>
            <div class="banner banner-rate"><?php echo htmlspecialchars(t('invite_request.full'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($flag === 'err'): ?>
            <div class="banner banner-err"><?php echo htmlspecialchars(t('invite_request.err'), ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (!$slotOpen): ?>
            <!-- Queue full: form is closed. Only show the closed notice + a way back. -->
            <?php if ($flag !== 'full'): ?>
                <div class="banner banner-rate"><?php echo htmlspecialchars(t('invite_request.full'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <div class="auth-alt">
                <a href="register.php"><?php echo htmlspecialchars(t('invite_request.back_to_register'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        <?php else: ?>

        <p class="auth-intro"><?php echo htmlspecialchars(t('invite_request.intro'), ENT_QUOTES, 'UTF-8'); ?></p>

        <form method="post" action="request_invite.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

            <!-- Honeypot. Hidden from real users; bots that fill it are dropped. -->
            <div class="hp-field" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-row">
                <label for="email"><?php echo htmlspecialchars(t('invite_request.email_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" id="email" name="email" autofocus required>
            </div>
            <div class="form-row">
                <label for="reason"><?php echo htmlspecialchars(t('invite_request.reason_label'), ENT_QUOTES, 'UTF-8'); ?></label>
                <textarea id="reason" name="reason" required></textarea>
                <div class="form-hint"><?php echo htmlspecialchars(t('invite_request.reason_hint'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <button type="submit" class="submit-button">
                <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars(t('invite_request.submit'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
        </form>

        <div class="auth-alt">
            <a href="register.php"><?php echo htmlspecialchars(t('invite_request.back_to_register'), ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <?php endif; ?>

        <?php // 1.1.16: anonim ziyaretci dil secici, kartin altinda. Uye
              // olmayanlarin user_pref satiri yoktur; bu switcher secimi
              // oturuma yazar (set_language.php). Uye/self-host icin ''
              // doner - gorunmez. ?>
        <?php echo guest_lang_switcher(); ?>
    </div>
</body>
</html>
