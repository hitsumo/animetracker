<?php

/**
 * Anime Tracker - Admin Suggestions (correction moderation queue)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Faz 2 / Milestone 2 (auth) - Dilim 5. Moderator/admin queue for the
 * free-text correction suggestions submitted via suggest.php. A moderator
 * marks each one accepted or rejected (and can reopen a decided one back to
 * pending). Applying an accepted suggestion to the catalog is MANUAL - there
 * is an "edit anime" link per row; there is no field-level auto-apply (the
 * suggestion is a free-text note, not structured data).
 *
 * Access: moderator+ (suggestion moderation, role matrix FAZ sec.5). Security
 * model mirrors admin.php: online gates by role, self-host falls back to a
 * loopback-only IP gate.
 *
 * The IP column is shown here because this is a moderator-only abuse-handling
 * screen (the migration note "not shown to end users" refers to the public
 * site, not the moderation queue).
 *
 * Suggestions only exist in multi-user mode; in self-host the queue is simply
 * empty (the owner edits the catalog directly).
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

lang_init_admin($pdo);

// --- Access control (moderator+) ---------------------------------------

if (MULTI_USER_MODE) {
    require_role($pdo, 'moderator');
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    if (!$isLocal) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_pending.localhost_only'), ENT_QUOTES, 'UTF-8'));
    }
}

$VALID_STATUS = ['pending', 'accepted', 'rejected'];

// --- POST: set a suggestion's status -----------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_suggestions.csrf_invalid'), ENT_QUOTES, 'UTF-8'));
    }

    if (($_POST['action'] ?? '') === 'set_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $filter = $_POST['filter'] ?? 'pending';
        if ($id > 0 && in_array($status, $VALID_STATUS, true)) {
            $upd = $pdo->prepare("UPDATE suggestions SET status = ? WHERE id = ?");
            $upd->execute([$status, $id]);
        }
        // Preserve the current tab on redirect.
        $f = in_array($filter, ['pending', 'accepted', 'rejected', 'all'], true) ? $filter : 'pending';
        header('Location: admin_suggestions.php?filter=' . $f);
        exit;
    }

    header('Location: admin_suggestions.php');
    exit;
}

// --- Read state --------------------------------------------------------

$filter = $_GET['filter'] ?? 'pending';
if (!in_array($filter, ['pending', 'accepted', 'rejected', 'all'], true)) {
    $filter = 'pending';
}

// Counts per status for the tab badges.
$counts = ['pending' => 0, 'accepted' => 0, 'rejected' => 0, 'all' => 0];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM suggestions GROUP BY status") as $row) {
    if (isset($counts[$row['status']])) {
        $counts[$row['status']] = (int)$row['c'];
    }
    $counts['all'] += (int)$row['c'];
}

$sql =
    "SELECT s.id, s.anime_id, s.note, s.submitter_user_id, s.ip, s.status, s.created_at,
            a.title AS anime_title, u.username AS submitter_username
     FROM suggestions s
     JOIN animes a ON a.id = s.anime_id
     LEFT JOIN users u ON u.id = s.submitter_user_id";
$params = [];
if ($filter !== 'all') {
    $sql .= " WHERE s.status = ?";
    $params[] = $filter;
}
$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tabs = ['pending', 'accepted', 'rejected', 'all'];

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_suggestions.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        .admin-dashboard { max-width: 1040px; margin: 40px auto; padding: 30px; }
        .admin-header {
            background: #dc3545; color: white; padding: 25px 30px;
            border-radius: 8px 8px 0 0; display: flex; align-items: center; gap: 15px;
        }
        .admin-header h1 { margin: 0; font-size: 1.5em; font-weight: 500; }
        .admin-header .sub { opacity: 0.9; font-size: 0.9em; margin-top: 4px; }
        .admin-body {
            background: white; padding: 30px; border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .tabs { display: flex; gap: 8px; margin: 18px 0; flex-wrap: wrap; }
        .tab { padding: 7px 14px; border-radius: 6px; text-decoration: none; color: #555; background: #f0f0f0; font-size: 0.9em; }
        .tab.active { background: #dc3545; color: #fff; }
        .tab .badge { background: rgba(0,0,0,0.12); border-radius: 10px; padding: 1px 7px; margin-left: 5px; font-size: 0.85em; }
        table.sg { width: 100%; border-collapse: collapse; font-size: 0.88em; }
        table.sg th, table.sg td { text-align: left; padding: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.sg th { color: #555; font-weight: 600; }
        .note-cell { max-width: 360px; white-space: pre-wrap; word-break: break-word; }
        .meta { color: #999; font-size: 0.85em; }
        .badge { padding: 2px 9px; border-radius: 10px; font-size: 0.8em; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-accepted { background: #d4edda; color: #155724; }
        .badge-rejected { background: #f8d7da; color: #721c24; }
        .btn { color: #fff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; margin: 2px 0; }
        .btn-accept { background: #28a745; }
        .btn-reject { background: #dc3545; }
        .btn-reopen { background: #6c757d; }
        .btn-edit { background: #007bff; text-decoration: none; display: inline-block; }
        .row-actions form { display: inline; }
        .back-link { display: inline-block; margin-top: 22px; color: #666; text-decoration: none; }
        .back-link:hover { color: #dc3545; }
    </style>
</head>
<body>
    <div class="admin-dashboard">
        <div class="admin-header">
            <i class="fas fa-flag" style="font-size: 2em;"></i>
            <div>
                <h1><?php echo htmlspecialchars(t('admin_suggestions.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="sub"><?php echo htmlspecialchars(t('admin_suggestions.subtitle'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="admin-body">
            <p style="color: #666; margin-top: 0;">
                <?php echo htmlspecialchars(t('admin_suggestions.intro'), ENT_QUOTES, 'UTF-8'); ?>
            </p>

            <div class="tabs">
                <?php foreach ($tabs as $tb): ?>
                    <a href="admin_suggestions.php?filter=<?php echo $tb; ?>" class="tab<?php echo ($filter === $tb) ? ' active' : ''; ?>">
                        <?php echo htmlspecialchars(t('admin_suggestions.filter.' . $tb), ENT_QUOTES, 'UTF-8'); ?>
                        <span class="badge"><?php echo (int)$counts[$tb]; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($rows)): ?>
                <p><?php echo htmlspecialchars(t('admin_suggestions.empty'), ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
            <table class="sg">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_anime'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_note'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_submitter'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_ip'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_created'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_suggestions.col_action'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="../anime_details.php?id=<?php echo (int)$r['anime_id']; ?>">
                                <?php echo htmlspecialchars($r['anime_title'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </td>
                        <td class="note-cell"><?php echo nl2br(htmlspecialchars($r['note'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td>
                            <?php
                            echo htmlspecialchars(
                                $r['submitter_username'] !== null
                                    ? $r['submitter_username']
                                    : t('admin_suggestions.submitter_anon'),
                                ENT_QUOTES, 'UTF-8'
                            );
                            ?>
                        </td>
                        <td class="meta"><?php echo htmlspecialchars($r['ip'] !== null ? $r['ip'] : '-', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="meta"><?php echo htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(t('admin_suggestions.status.' . $r['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td class="row-actions">
                            <?php
                                $actions = [];
                                if ($r['status'] !== 'accepted') { $actions[] = ['accepted', 'btn-accept', 'admin_suggestions.action.accept']; }
                                if ($r['status'] !== 'rejected') { $actions[] = ['rejected', 'btn-reject', 'admin_suggestions.action.reject']; }
                                if ($r['status'] !== 'pending')  { $actions[] = ['pending',  'btn-reopen', 'admin_suggestions.action.reopen']; }
                            ?>
                            <?php foreach ($actions as $act): ?>
                                <form method="post" action="admin_suggestions.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="set_status">
                                    <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $act[0]; ?>">
                                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter, ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="btn <?php echo $act[1]; ?>"><?php echo htmlspecialchars(t($act[2]), ENT_QUOTES, 'UTF-8'); ?></button>
                                </form>
                            <?php endforeach; ?>
                            <a href="../edit_anime.php?id=<?php echo (int)$r['anime_id']; ?>" class="btn btn-edit"><i class="fas fa-edit"></i> <?php echo htmlspecialchars(t('admin_suggestions.action.edit_anime'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <a href="admin.php" class="back-link">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_suggestions.back_to_admin'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>
</body>
</html>
