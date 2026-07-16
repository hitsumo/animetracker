<?php

/**
 * Anime Tracker - Auth Helpers (password hashing, session login/logout,
 * role checks, capability gating)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * ---
 *
 * Faz 2 / Milestone 2 (auth) - slice 1 skeleton.
 *
 * This file only DEFINES the auth API. In this slice nothing calls these
 * functions yet (wiring endpoints + UI happens in a later slice), so adding
 * the file is purely additive and does not change behaviour.
 *
 * Two-mode contract (mirrors current_user_id() in db.php):
 *   - MULTI_USER_MODE = false (self-host): there is no login. The single
 *     seeded owner (id 1) is treated as an always-present admin. Every gate
 *     below is a no-op that passes, so self-host behaviour is unchanged.
 *   - MULTI_USER_MODE = true (online): the session user drives identity and
 *     authorization.
 *
 * Loaded via the functions.php loader. Assumes db.php has already run
 * (session started, MULTI_USER_MODE defined, current_user_id() available).
 */

/**
 * Numeric rank for a role, used for "at least this role" comparisons.
 * Higher rank = more authority. Unknown/empty role ranks 0 (below 'user').
 */
function auth_role_rank($role)
{
    $ranks = [
        'user'      => 1,
        'trusted'   => 2,
        'moderator' => 3,
        'admin'     => 4,
    ];
    return isset($ranks[$role]) ? $ranks[$role] : 0;
}

/**
 * Hash a plaintext password with the current default algorithm
 * (bcrypt/argon2 depending on the PHP build). Used at registration and
 * password change.
 */
function auth_hash_password($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

/**
 * Verify a plaintext password against a stored hash.
 *
 * Returns false for an empty/NULL hash: the self-host owner row carries a
 * NULL password_hash and must never authenticate via login.
 */
function auth_verify_password($plain, $hash)
{
    if (!is_string($hash) || $hash === '') {
        return false;
    }
    return password_verify($plain, $hash);
}

/**
 * Attempt to log a user in by username + password.
 *
 * Only meaningful in multi-user mode; self-host has no login screen. On
 * success: regenerate the session id (fixation defense) and store the user
 * id in the session. Returns true on success, false otherwise (caller shows
 * a generic "invalid credentials" message - never reveal which half failed).
 */
function auth_login($pdo, $username, $password)
{
    $stmt = $pdo->prepare(
        "SELECT id, password_hash, status FROM users WHERE username = ? LIMIT 1"
    );
    $stmt->execute([(string)$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['status'] !== 'active') {
        return false;
    }
    if (!auth_verify_password($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    return true;
}

/**
 * Log the current user out: drop the session user id and issue a fresh
 * session id. Self-host never calls this (no login), but it is safe there.
 */
function auth_logout()
{
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

/**
 * Return the current user's DB row (id, username, email, role, status) or
 * null when there is no resolvable user (anonymous in multi-user mode).
 *
 * Cached per request, keyed by user id, so repeated calls hit the DB once.
 * In self-host this resolves the seeded owner (id 1).
 */
function current_user($pdo)
{
    static $cache = [];

    $uid = current_user_id();
    if ($uid === null) {
        return null;
    }
    if (array_key_exists($uid, $cache)) {
        return $cache[$uid];
    }

    $stmt = $pdo->prepare(
        "SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1"
    );
    $stmt->execute([(int)$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $cache[$uid] = $row ?: null;
    return $cache[$uid];
}

/**
 * Whether there is a "logged-in" identity.
 *   - self-host: always true (the owner is always present).
 *   - online: true only when a session user id is set.
 */
function is_logged_in()
{
    if (!MULTI_USER_MODE) {
        return true;
    }
    return isset($_SESSION['user_id']);
}

/**
 * The current user's role string.
 *   - self-host: 'admin' (owner has full authority), no DB hit.
 *   - online: the role from the users row, or null when anonymous.
 */
function current_user_role($pdo)
{
    if (!MULTI_USER_MODE) {
        return 'admin';
    }
    $u = current_user($pdo);
    return $u ? $u['role'] : null;
}

/**
 * Internal: reject an unauthorized request and stop.
 *
 * $asJson distinguishes a page (redirect) from an endpoint/AJAX call (status
 * code + JSON body). $reason is 'login' (not authenticated) or 'forbidden'
 * (authenticated but lacks the role).
 */
function auth_deny($asJson, $reason)
{
    if ($asJson) {
        http_response_code($reason === 'login' ? 401 : 403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $reason]);
    } elseif ($reason === 'login') {
        header('Location: login.php');
    } else {
        http_response_code(403);
        header('Location: index.php');
    }
    exit;
}

/**
 * Require an authenticated user.
 *   - self-host: no-op (always passes).
 *   - online: passes only when logged in; otherwise denies via auth_deny().
 *
 * Pass $asJson = true from write/AJAX endpoints so the denial is a status
 * code + JSON body instead of a redirect.
 */
function require_login($asJson = false)
{
    if (!MULTI_USER_MODE) {
        return;
    }
    if (isset($_SESSION['user_id'])) {
        return;
    }
    auth_deny($asJson, 'login');
}

/**
 * Require at least the given role (see auth_role_rank for ordering).
 *   - self-host: no-op (owner counts as admin).
 *   - online: must be logged in AND rank >= $minRole, else auth_deny().
 */
function require_role($pdo, $minRole, $asJson = false)
{
    if (!MULTI_USER_MODE) {
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        auth_deny($asJson, 'login');
    }
    if (auth_role_rank(current_user_role($pdo)) < auth_role_rank($minRole)) {
        auth_deny($asJson, 'forbidden');
    }
}

/**
 * Capability check for UI gating (and a second server-side line of defense).
 * Returns a bool; the SAME function backs both "should I render this button"
 * and "is this request allowed", so the two never drift apart.
 *
 * Self-host returns true for everything (owner). The action names below are
 * provisional (FAZ2_AUTH_TASARIMI section 1 matrix is finalized in the
 * wiring slice); unknown actions deny by default.
 */
function can($pdo, $action)
{
    if (!MULTI_USER_MODE) {
        return true;
    }

    $role = current_user_role($pdo); // null = anonymous

    switch ($action) {
        case 'browse':    // read catalog / detail
        case 'suggest':   // submit a correction note
            return true;  // anonymous allowed
        case 'personal':  // own list, watch state, emotion, prefs
        case 'add_anime': // add a new anime (goes to the approval queue)
            return $role !== null; // any logged-in user
        case 'moderate':  // approve suggestions/additions, manage tags/genres
            return auth_role_rank($role) >= auth_role_rank('moderator');
        case 'admin':     // user management, admin panel
            return $role === 'admin';
        default:
            return false;
    }
}

/**
 * Render the auth navigation links (login / account / logout) as a string of
 * <a> tags styled to match the existing header nav (.about-link).
 *
 * Returns an empty string in self-host mode, so the header looks exactly as
 * it always has. In multi-user mode it shows "Account" + "Sign out" to a
 * logged-in user, or "Sign in" to an anonymous visitor.
 *
 * Pages echo the return value just before the language switcher in their
 * header, so the links appear in a consistent spot across the site.
 */
function auth_nav_links()
{
    if (!MULTI_USER_MODE) {
        return '';
    }

    $link = function ($href, $key) {
        return '<a href="' . $href . '" class="about-link">'
            . htmlspecialchars(t($key), ENT_QUOTES, 'UTF-8')
            . '</a>';
    };

    if (is_logged_in()) {
        return $link('account.php', 'nav.account') . $link('logout.php', 'nav.logout');
    }
    return $link('login.php', 'nav.login') . $link('register.php', 'nav.register');
}

/**
 * Invite-request slot state (1.1.12). The operator can cap how many invite
 * requests may sit in the queue at once via settings.invite_request_limit;
 * once the number of PENDING requests reaches that cap the public request
 * form (request_invite.php) closes and no new request is accepted. A limit of
 * 0 (or unset) means "no cap" - the form is always open, which is also how the
 * operator removes a previously set limit.
 *
 * Counting PENDING (not all-time) requests makes the cap self-healing: as the
 * operator invites or rejects queued requests those slots reopen automatically.
 *
 * Returns ['limit' => int, 'pending' => int, 'open' => bool]. 'pending' is only
 * queried when a positive limit is set (no needless COUNT when uncapped). On a
 * DB error we fail OPEN rather than lock legitimate visitors out.
 */
function invite_request_limit_state($pdo)
{
    $limit = (int)get_setting($pdo, 'invite_request_limit', '0');
    if ($limit <= 0) {
        return ['limit' => 0, 'pending' => 0, 'open' => true];
    }
    try {
        $pending = (int)$pdo->query(
            "SELECT COUNT(*) FROM invite_requests WHERE status = 'pending'"
        )->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] invite_request_limit_state count failed: ' . $e->getMessage());
        return ['limit' => $limit, 'pending' => 0, 'open' => true];
    }
    return ['limit' => $limit, 'pending' => $pending, 'open' => ($pending < $limit)];
}

/**
 * Store a public invite request after anti-spam checks (1.0.20). Mirrors the
 * suggest.php protection model: a per-IP rate limit backed by idx_ip_created.
 * CSRF + honeypot are handled by the calling page (request_invite.php), the
 * same split as suggest.php.
 *
 * Returns 'ok' on a stored request, 'rate' if the per-IP hourly limit is hit,
 * or 'err' on a validation problem. Invite requests are a multi-user feature
 * only; the caller gates MULTI_USER_MODE and registration_mode.
 */
function invite_request_submit($pdo, $email, $reason, $ip)
{
    $email  = trim((string)$email);
    $reason = trim((string)$reason);

    // Slot cap (1.1.12): if the pending queue is full, the request form is
    // closed. request_invite.php also hides the form on GET; this is the
    // authoritative server-side guard against a direct POST. Checked before
    // validation so a full queue always answers 'full' (not 'err').
    if (!invite_request_limit_state($pdo)['open']) {
        return 'full';
    }

    // The email must be syntactically valid and the reason non-empty.
    if ($email === '' || $reason === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'err';
    }
    if (mb_strlen($reason) > 2000) {
        $reason = mb_substr($reason, 0, 2000);
    }

    // Per-IP rate limit: at most 5 requests per IP in the trailing hour,
    // backed by idx_ip_created. Skipped if the IP is somehow unavailable.
    if ($ip !== '') {
        $rl = $pdo->prepare(
            "SELECT COUNT(*) FROM invite_requests
             WHERE ip = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)"
        );
        $rl->execute([$ip]);
        if ((int)$rl->fetchColumn() >= 5) {
            return 'rate';
        }
    }

    $ins = $pdo->prepare(
        "INSERT INTO invite_requests (email, reason, ip) VALUES (?, ?, ?)"
    );
    $ins->execute([$email, $reason, ($ip !== '' ? $ip : null)]);
    return 'ok';
}

/**
 * Best-effort notification mail to the operator-configured address
 * (settings.invite_notify_email). The stored request is the source of truth:
 * if no address is set, the address is invalid, or mail() fails, the request
 * still sits in the admin queue. Returns true only when mail() accepted the
 * message; failures are logged (no emoji) and never block the request.
 *
 * From is derived from the request host so SPF/DKIM align on a self-hosted
 * server; Reply-To is the requester so the operator can answer directly. The
 * subject is plain ASCII per language, so no MIME word-encoding is needed; the
 * body is UTF-8 (declared in the Content-Type header).
 */
function invite_request_notify($pdo, $email, $reason)
{
    $to = trim((string)get_setting($pdo, 'invite_notify_email', ''));
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false; // no destination configured -> queue only
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $host = preg_replace('/:\d+$/', '', $host);  // strip a :port suffix
    $host = preg_replace('/^www\./', '', $host); // strip a leading www.
    $from = 'no-reply@' . $host;

    $subject = t('invite_request.mail.subject');
    $body = t('invite_request.mail.line_email') . ' ' . $email . "\n\n"
          . t('invite_request.mail.line_reason') . "\n" . $reason . "\n";

    $headers = 'From: ' . $from . "\r\n"
             . 'Reply-To: ' . $email . "\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n";

    $ok = @mail($to, $subject, $body, $headers);
    if (!$ok) {
        error_log('[anime_tracker] invite request notify mail failed for ' . $to);
    }
    return $ok;
}
