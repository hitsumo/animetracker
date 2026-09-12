<?php
/**
 * Anime Tracker - Add Anime Relation
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.38. POST endpoint that records ONE typed, orderless
 * relation between two animes (anime_relations). Called from the
 * "Iliskiler" panel underneath the main form on edit_anime.php.
 *
 * Required POST fields:
 *   csrf_token      - CSRF protection token
 *   anime_id        - The anime being edited
 *   other_anime_id  - The anime just picked
 *   relation_choice - A key of anime_relation_choices(); the '|inv'
 *                     suffix means the direction is inverted (see
 *                     functions/relation_helpers.php)
 *
 * On success: back to edit_anime.php?id={anime_id}#relations
 * On a rejected relation: the same address plus relation_error=<code>,
 * which the panel renders as a sentence. die() is reserved for the two
 * gates (CSRF / role) - a curator who picked an impossible pair should
 * land back on the form with their other work intact, not on a blank
 * page.
 *
 * NOT pushed to the central catalog: a relation is a pair of LOCAL row
 * ids, exactly like next_in_series and chain_name. See the migration
 * notes in migration/1.1.38/upgrade.sql.
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

// Relations are shared series structure, like chronology markers: a
// moderator+ is required (online only; no-op in self-host).
require_role($pdo, 'moderator');

$anime_id = (int)($_POST['anime_id'] ?? 0);
$other_id = (int)($_POST['other_anime_id'] ?? 0);
$choice   = anime_relation_parse_choice($_POST['relation_choice'] ?? '');

/**
 * Send the curator back to the panel, with an error code when the
 * relation was refused. The code is a fixed keyword, never user text.
 */
function relation_redirect($anime_id, $error = null) {
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
if ($other_id <= 0 || $choice === null) {
    relation_redirect($anime_id, 'input');
}
// An anime cannot be its own alternative version.
if ($anime_id === $other_id) {
    relation_redirect($anime_id, 'self');
}

// Both ends must exist. The FKs would refuse anyway; asking first turns a
// 500 into a sentence.
$existsStmt = $pdo->prepare("SELECT COUNT(*) FROM animes WHERE id IN (:a, :b)");
$existsStmt->execute([':a' => $anime_id, ':b' => $other_id]);
if ((int)$existsStmt->fetchColumn() !== 2) {
    relation_redirect($anime_id, 'missing');
}

// ONE RELATION PER PAIR (see relation_helpers.php): a second row about
// the same two animes is either a duplicate or a contradiction.
if (anime_relation_between($pdo, $anime_id, $other_id) !== false) {
    relation_redirect($anime_id, 'exists');
}

// Every type here is ORDERLESS, so a pair that next_in_series already
// chains cannot also carry one - that pair would claim to be ordered and
// unordered at once. The check calls the 1.1.36 rule (chain_same) rather
// than restating it, so a dormant link (different chain names, therefore
// never followed) blocks nothing.
if (anime_relation_chain_conflict($pdo, $anime_id, $other_id)) {
    relation_redirect($anime_id, 'chain');
}

list($from, $to) = anime_relation_endpoints($anime_id, $other_id, $choice['type'], $choice['inverse']);

try {
    $ins = $pdo->prepare("
        INSERT INTO anime_relations (from_anime_id, to_anime_id, relation_type)
        VALUES (?, ?, ?)
    ");
    $ins->execute([$from, $to, $choice['type']]);
} catch (PDOException $e) {
    // 23000 = integrity constraint violation. With the pair check above
    // this is only reachable through a double submit, which is not an
    // error worth a stack trace: the relation the curator wanted exists.
    if ($e->getCode() == '23000') {
        relation_redirect($anime_id, 'exists');
    }
    error_log('[anime_tracker] add_anime_relation failed: ' . $e->getMessage());
    relation_redirect($anime_id, 'failed');
}

// 1.1.32: both detail pages now show a section they did not have a moment
// ago. Queueing BOTH ends is the point - a relation changes two pages.
// The queue helper re-reads eligibility, so a page that is still a thin
// stub (1.1.37) is not submitted just because it gained a relation.
indexnow_queue_anime($pdo, $anime_id);
indexnow_queue_anime($pdo, $other_id);

relation_redirect($anime_id);
