<?php

/**
 * Anime Tracker - Suggestion submit endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth) - Dilim 5. Receives a free-text correction
 * suggestion for a catalog anime and stores it as status='pending' for the
 * moderation queue (admin_suggestions.php). Anyone may submit - anonymous or
 * signed-in (role matrix, FAZ2_AUTH_TASARIMI sec.1) - so there is no
 * require_login here; the protections are CSRF + honeypot + per-IP rate limit.
 *
 * This is a POST-only endpoint that always redirects back to the anime detail
 * page with a ?suggest=ok|err|rate flag (PRG). It produces no HTML of its own.
 *
 * Suggestions are a multi-user feature only. In self-host the owner edits the
 * catalog directly, so the whole flow is gated to MULTI_USER_MODE; in
 * self-host this endpoint just bounces to the home page.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Per-IP rate limit: at most this many suggestions per IP in the trailing
// window (the window is expressed in the SQL INTERVAL below).
const SUGGEST_RATE_MAX = 5; // per 1 hour (see query)
const SUGGEST_NOTE_MAX = 2000;

// No suggestion flow outside multi-user mode.
if (!MULTI_USER_MODE) {
    header('Location: index.php');
    exit;
}

// POST only.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$animeId = (int)($_POST['anime_id'] ?? 0);
$note    = trim($_POST['note'] ?? '');
$hp      = trim($_POST['website'] ?? ''); // honeypot - real users never see it

$back = 'anime_details.php?id=' . $animeId;

// CSRF.
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    header('Location: ' . $back . '&suggest=err');
    exit;
}

// Honeypot: a bot filled the hidden field. Pretend success and drop silently
// so the bot gets no signal that it was caught.
if ($hp !== '') {
    header('Location: ' . $back . '&suggest=ok');
    exit;
}

// Basic validation.
if ($animeId <= 0 || $note === '') {
    header('Location: ' . $back . '&suggest=err');
    exit;
}
if (mb_strlen($note) > SUGGEST_NOTE_MAX) {
    $note = mb_substr($note, 0, SUGGEST_NOTE_MAX);
}

// The anime must exist (FK would reject otherwise, but check for a clean
// redirect rather than a DB error).
$chk = $pdo->prepare("SELECT id FROM animes WHERE id = ? LIMIT 1");
$chk->execute([$animeId]);
if (!$chk->fetchColumn()) {
    header('Location: index.php');
    exit;
}

// Per-IP rate limit. Counts all of this IP's suggestions in the trailing
// hour (any status), backed by idx_ip_created. Skipped if the IP is somehow
// unavailable.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if ($ip !== '') {
    $rl = $pdo->prepare(
        "SELECT COUNT(*) FROM suggestions
         WHERE ip = ? AND created_at >= (NOW() - INTERVAL 1 HOUR)"
    );
    $rl->execute([$ip]);
    if ((int)$rl->fetchColumn() >= SUGGEST_RATE_MAX) {
        header('Location: ' . $back . '&suggest=rate');
        exit;
    }
}

// Store. submitter_user_id is the signed-in user's id, or NULL when anonymous.
$uid = is_logged_in() ? (int)current_user_id() : null;
$ins = $pdo->prepare(
    "INSERT INTO suggestions (anime_id, note, submitter_user_id, ip)
     VALUES (?, ?, ?, ?)"
);
$ins->execute([$animeId, $note, $uid, ($ip !== '' ? $ip : null)]);

header('Location: ' . $back . '&suggest=ok');
exit;
