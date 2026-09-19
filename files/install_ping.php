<?php
/**
 * Anime Tracker - Install counter trigger (1.1.43)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint: sends the one-time install ping to the project's counter
 * server. index.php prints a one-line fetch() to this file when
 * install_ping_due() says the ping has not succeeded yet (and was not
 * already attempted today) - so the outbound request runs here, behind
 * the page, never inside its render.
 *
 * What goes out, what does not, and how to switch it off: see the
 * docblock of functions/install_ping_helpers.php.
 *
 * Gates:
 *   - POST + CSRF, the house pattern for every state-changing endpoint
 *     (set_spoiler_pref.php etc.). The only state here is the
 *     settings.last_install_ping day stamp, but there is no reason to be
 *     the one endpoint that skips the rule.
 *   - INSTALL_PING = false in config.php -> 204, nothing sent.
 *   - Already done, or already attempted today -> 204, nothing sent (two
 *     tabs, a crawler that runs the page's JS, a manual call: all harmless).
 *
 * Responds with JSON only so the browser console shows something useful
 * when debugging; the page never reads the response.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'reason' => 'post_required']);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'reason' => 'csrf']);
    exit;
}

if (!install_ping_enabled()) {
    http_response_code(204);
    exit;
}

$result = install_ping_send($pdo);

if ($result['skipped'] === 'not_due') {
    http_response_code(204);
    exit;
}

echo json_encode([
    'ok'   => (bool)$result['sent'],
    'http' => (int)$result['http'],
], JSON_UNESCAPED_UNICODE);
