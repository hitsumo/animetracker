<?php
/**
 * Anime Tracker - Delete Anime Relation
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.38. POST endpoint that removes one row from
 * anime_relations. Called from the small X button next to each relation
 * in the "Iliskiler" panel on edit_anime.php.
 *
 * Required POST fields:
 *   csrf_token  - CSRF protection token
 *   relation_id - The anime_relations.id to delete
 *   anime_id    - Used for the redirect afterwards
 *
 * On success: back to edit_anime.php?id={anime_id}#relations
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

// Same gate as adding: relations are shared series structure.
require_role($pdo, 'moderator');

$relation_id = (int)($_POST['relation_id'] ?? 0);
$anime_id    = (int)($_POST['anime_id'] ?? 0);

if ($relation_id <= 0 || $anime_id <= 0) {
    header('Location: index.php');
    exit;
}

// Read the far end BEFORE deleting: once the row is gone there is no way
// to learn which second detail page also changed, and IndexNow has to be
// told about both.
$otherId = 0;
try {
    $findStmt = $pdo->prepare("SELECT from_anime_id, to_anime_id FROM anime_relations WHERE id = ?");
    $findStmt->execute([$relation_id]);
    $row = $findStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $otherId = ((int)$row['from_anime_id'] === $anime_id)
            ? (int)$row['to_anime_id']
            : (int)$row['from_anime_id'];
    }

    // If the row is already gone (double click, stale page), the DELETE
    // simply affects 0 rows - no error, no harm.
    $del = $pdo->prepare("DELETE FROM anime_relations WHERE id = ?");
    $del->execute([$relation_id]);
} catch (PDOException $e) {
    error_log('[anime_tracker] delete_anime_relation failed: ' . $e->getMessage());
    header('Location: edit_anime.php?id=' . $anime_id . '&relation_error=failed#relations');
    exit;
}

indexnow_queue_anime($pdo, $anime_id);
if ($otherId > 0) {
    indexnow_queue_anime($pdo, $otherId);
}

header('Location: edit_anime.php?id=' . $anime_id . '#relations');
exit;
