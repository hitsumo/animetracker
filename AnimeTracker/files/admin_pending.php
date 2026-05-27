<?php

/**
 * Anime Tracker - Admin: Pending Catalog Promotion
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Lists all local animes with source='local' and lets the admin
 * promote them to source='catalog' so the next admin_sync push
 * sends them to the server.
 *
 * Rationale:
 *   - add_anime.php inserts with the schema default source='local'.
 *     That is the correct default for Faz 2 (when normal users add
 *     their own private anime that must NEVER leave their machine).
 *   - The admin, however, typically wants everything they add to be
 *     part of the public catalog. Rather than hardcode source='catalog'
 *     in add_anime.php (which would break Faz 2), we keep the safe
 *     default and offer this explicit "promote" step for the admin.
 *   - Promotion is deliberate: the admin sees the list, checks what
 *     they want to publish, confirms. Accidental publication is hard.
 *
 * Security: localhost-only (same gate as admin.php). No secret file
 * needed - this tool does not touch the network, only the local DB.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init_admin($pdo);

// --- Access control ----------------------------------------------------

$clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
if (!$isLocal) {
    http_response_code(403);
    die(htmlspecialchars(t('admin_pending.localhost_only'), ENT_QUOTES, 'UTF-8'));
}

$message = null;
$messageType = null; // 'success' or 'error'

// --- POST: promote selected animes to catalog --------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die(htmlspecialchars(t('admin_pending.error.csrf'), ENT_QUOTES, 'UTF-8'));
    }

    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'promote_selected') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids)) $ids = [];
            $clean = [];
            foreach ($ids as $id) {
                $id = (int)$id;
                if ($id > 0) $clean[$id] = true;
            }
            $clean = array_keys($clean);

            if (empty($clean)) {
                throw new Exception(t('admin_pending.error.no_selection'));
            }

            // Only flip rows that are currently 'local' - never accidentally
            // re-flag something that is already catalog or any other state.
            $placeholders = implode(',', array_fill(0, count($clean), '?'));
            $sql = "UPDATE animes SET source = 'catalog'
                    WHERE source = 'local' AND id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($clean);
            $affected = $stmt->rowCount();

            $message = sprintf(t('admin_pending.success.promoted_some'), $affected);
            $messageType = 'success';

        } elseif ($action === 'promote_all') {
            $stmt = $pdo->query("UPDATE animes SET source = 'catalog' WHERE source = 'local'");
            $affected = $stmt->rowCount();
            $message = sprintf(t('admin_pending.success.promoted_all'), $affected);
            $messageType = 'success';

        } elseif ($action === 'demote') {
            // Opposite direction - remove an anime from the catalog back
            // to local-only. Useful if the admin changes their mind
            // before the next sync.
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception(t('admin_pending.error.invalid_id'));
            }
            $stmt = $pdo->prepare("UPDATE animes SET source = 'local'
                                   WHERE source = 'catalog' AND id = ?");
            $stmt->execute([$id]);
            $message = t('admin_pending.success.demoted');
            $messageType = 'success';
        } else {
            throw new Exception(t('admin_pending.error.unknown_action'));
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// --- Fetch the pending list --------------------------------------------

$pendingStmt = $pdo->query(
    "SELECT id, title, status, watch_status, created_at, mal_id, anidb_id
     FROM animes
     WHERE source = 'local'
     ORDER BY created_at DESC, id DESC"
);
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// Quick totals for the header
$totals = $pdo->query(
    "SELECT source, COUNT(*) AS c FROM animes GROUP BY source"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$catalogCount = (int)($totals['catalog'] ?? 0);
$localCount   = (int)($totals['local']   ?? 0);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_pending.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
        .admin-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        .page-heading {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }
        .page-heading h1 {
            margin: 0;
            font-size: 1.6em;
        }
        .totals {
            display: flex;
            gap: 18px;
            margin: 0 0 20px;
            font-size: 0.95em;
        }
        .totals .badge {
            padding: 6px 12px;
            border-radius: 14px;
            font-weight: 500;
        }
        .badge-catalog { background: #d4edda; color: #155724; }
        .badge-local   { background: #fff3cd; color: #856404; }
        .msg {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .msg-success { background: #d4edda; color: #155724; }
        .msg-error   { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .bulk-actions {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .btn-primary, .btn-warning, .btn-secondary, .btn-danger {
            display: inline-block;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover { color: #007bff; }
        .empty {
            padding: 30px;
            text-align: center;
            color: #888;
            font-style: italic;
            background: #fafafa;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="page-heading">
        <i class="fas fa-inbox" style="font-size: 1.6em; color: #007bff;"></i>
        <div>
            <h1><?php echo htmlspecialchars(t('admin_pending.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <div style="color: #888; font-size: 0.9em;">
                <?php echo htmlspecialchars(t('admin_pending.subtitle'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>

    <div class="totals">
        <span class="badge badge-catalog">
            <i class="fas fa-cloud"></i> <?php echo htmlspecialchars(t('admin_pending.badge.catalog'), ENT_QUOTES, 'UTF-8'); ?> <?php echo $catalogCount; ?>
        </span>
        <span class="badge badge-local">
            <i class="fas fa-laptop"></i> <?php echo htmlspecialchars(t('admin_pending.badge.local'), ENT_QUOTES, 'UTF-8'); ?> <?php echo $localCount; ?>
        </span>
    </div>

    <?php if ($message): ?>
        <div class="msg <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending)): ?>
        <div class="empty">
            <?php echo t('admin_pending.empty'); ?>
        </div>
    <?php else: ?>
        <form method="post" id="promote-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="promote_selected">

            <div class="bulk-actions">
                <button type="button" class="btn-secondary" onclick="toggleAll(true)">
                    <i class="fas fa-check-square"></i> <?php echo htmlspecialchars(t('admin_pending.btn.select_all'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <button type="button" class="btn-secondary" onclick="toggleAll(false)">
                    <i class="far fa-square"></i> <?php echo htmlspecialchars(t('admin_pending.btn.clear_selection'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-cloud-upload-alt"></i> <?php echo htmlspecialchars(t('admin_pending.btn.promote_selected'), ENT_QUOTES, 'UTF-8'); ?>
                </button>

                <button type="submit" class="btn-warning" formnovalidate
                        onclick="document.querySelector('input[name=action]').value='promote_all';
                                 return confirm('<?php echo htmlspecialchars(sprintf(t('admin_pending.confirm.promote_all'), (int)$localCount), ENT_QUOTES, 'UTF-8'); ?>');">
                    <i class="fas fa-cloud"></i> <?php echo htmlspecialchars(t('admin_pending.btn.promote_all'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">
                            <input type="checkbox" id="select-all" onchange="toggleAll(this.checked)">
                        </th>
                        <th><?php echo htmlspecialchars(t('admin_pending.col.title'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_pending.col.broadcast_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_pending.col.watch_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_pending.col.external_ids'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_pending.col.added'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $a): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="ids[]"
                                       value="<?php echo (int)$a['id']; ?>"
                                       class="row-check">
                            </td>
                            <td>
                                <a href="anime_details.php?id=<?php echo (int)$a['id']; ?>"
                                   target="_blank">
                                    <?php echo htmlspecialchars($a['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($a['status']); ?></td>
                            <td><?php echo htmlspecialchars($a['watch_status']); ?></td>
                            <td style="font-size: 0.85em; color: #666;">
                                <?php echo $a['mal_id'] ? 'MAL: ' . (int)$a['mal_id'] : '-'; ?><br>
                                <?php echo $a['anidb_id'] ? 'AniDB: ' . (int)$a['anidb_id'] : '-'; ?>
                            </td>
                            <td style="font-size: 0.85em; color: #666;">
                                <?php echo htmlspecialchars($a['created_at']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <script>
            function toggleAll(checked) {
                document.querySelectorAll('.row-check').forEach(cb => cb.checked = checked);
                const master = document.getElementById('select-all');
                if (master) master.checked = checked;
            }
        </script>
    <?php endif; ?>

    <a href="admin.php" class="back-link">
        <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_pending.back_to_dashboard'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
</div>
</body>
</html>
