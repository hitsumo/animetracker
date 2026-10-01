<?php
/**
 * Anime Tracker - Update Anime Relation
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.2.1. POST endpoint that changes the TYPE and/or the
 * DIRECTION of one existing row in anime_relations. Called from the
 * pencil button next to each relation in the "Iliskiler" panel on
 * edit_anime.php.
 *
 * Until 1.2.1 a relation could only be deleted and entered again. The
 * common mistake that made this a chore is a reversed direction: the
 * form asks "the anime you picked is THIS one's ___", so entering a link
 * from the wrong page stores the opposite sentence ("the 1974 TV series
 * is the summary of the 1993 OVA"). Fixing it meant deleting the row,
 * picking the other anime again from a long list and choosing the
 * mirrored type.
 *
 * The OTHER END never changes here: this endpoint rewrites what the pair
 * says, not which pair it is. Pointing a relation at a different anime is
 * still delete + add.
 *
 * Required POST fields:
 *   csrf_token      - CSRF protection token
 *   relation_id     - The anime_relations.id to change
 *   anime_id        - The anime being edited (one end of the row; the
 *                     choice is phrased from its side)
 *   relation_choice - A key of anime_relation_choices(), read exactly as
 *                     add_anime_relation.php reads it
 *
 * On success: back to edit_anime.php?id={anime_id}#relations
 * On a rejected change: the same address plus relation_error=<code>.
 *
 * NOT pushed to the central catalog, like every relation (see
 * add_anime_relation.php).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Gate: POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Gate: CSRF
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die(htmlspecialchars(t('add_anime.csrf.invalid'), ENT_QUOTES, 'UTF-8'));
}

// Same gate as adding and deleting: relations are shared series structure.
require_role($pdo, 'moderator');

$relation_id = (int)($_POST['relation_id'] ?? 0);
$anime_id    = (int)($_POST['anime_id'] ?? 0);
$choice      = anime_relation_parse_choice($_POST['relation_choice'] ?? '');

/**
 * Send the curator back to the panel, with an error code when the change
 * was refused. The code is a fixed keyword, never user text.
 */
function relation_update_redirect($anime_id, $error = null) {
    $url = 'edit_anime.php?id=' . (int)$anime_id;
    if ($error !== null) {
        $url .= '&relation_error=' . rawurlencode($error);
    }
    header('Location: ' . $url . '#relations');
    exit;
}

if ($anime_id <= 0) {
    header('Location: index.php');
    exit;
}
if ($relation_id <= 0 || $choice === null) {
    relation_update_redirect($anime_id, 'input');
}

// The row must exist AND this anime must be one of its ends: the choice is
// phrased from this anime's side, so from any other page it would mean
// something else. A stale page (the row was deleted meanwhile) lands here
// too.
$findStmt = $pdo->prepare("SELECT from_anime_id, to_anime_id, relation_type FROM anime_relations WHERE id = ?");
$findStmt->execute([$relation_id]);
$row = $findStmt->fetch(PDO::FETCH_ASSOC);
$findStmt->closeCursor();
if (!$row) {
    relation_update_redirect($anime_id, 'missing');
}
$rowFrom = (int)$row['from_anime_id'];
$rowTo   = (int)$row['to_anime_id'];
if ($rowFrom !== $anime_id && $rowTo !== $anime_id) {
    relation_update_redirect($anime_id, 'missing');
}
$other_id = ($rowFrom === $anime_id) ? $rowTo : $rowFrom;

// Same rule as adding: a symmetric type is stored smaller id first, an
// asymmetric one keeps the direction the choice states.
list($from, $to) = anime_relation_endpoints($anime_id, $other_id, $choice['type'], $choice['inverse']);

// Nothing changed (the curator opened the editor and saved as is): no
// write, no IndexNow ping.
if ($from === $rowFrom && $to === $rowTo && $choice['type'] === $row['relation_type']) {
    relation_update_redirect($anime_id);
}

try {
    // One relation per pair (relation_helpers.php) means no OTHER row can
    // hold this pair, so the UNIQUE key can only trip on a race - handled
    // below like a double submit in add_anime_relation.php.
    $upd = $pdo->prepare("
        UPDATE anime_relations
           SET from_anime_id = ?, to_anime_id = ?, relation_type = ?
         WHERE id = ?
    ");
    $upd->execute([$from, $to, $choice['type'], $relation_id]);
} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        relation_update_redirect($anime_id, 'exists');
    }
    error_log('[anime_tracker] update_anime_relation failed: ' . $e->getMessage());
    relation_update_redirect($anime_id, 'failed');
}

// Both detail pages now read differently - queue both, as add/delete do.
indexnow_queue_anime($pdo, $anime_id);
indexnow_queue_anime($pdo, $other_id);

relation_update_redirect($anime_id);
