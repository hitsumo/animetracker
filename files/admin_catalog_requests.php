<?php
/**
 * Anime Tracker - Oneri (suggestion) moderasyonu
 *
 * Online uyelerin "Listeyi Ice Aktar" sirasinda katalogda OLMAYAN
 * animeler icin actigi "pending" onerileri listeler. Moderator/admin
 * onaylar (animes'e source='local' satir olarak gecer; sonra
 * admin_pending ile kataloga terfi + admin_sync ile push edilebilir)
 * ya da reddeder.
 *
 * Onaylanan animenin GORSELI yoktur (uyenin yerel image_path'i sunucuda
 * yok). Moderator, onayladiktan sonra edit_anime uzerinden AnimeSchedule
 * fetch ile gorseli/eksik alanlari tamamlayabilir.
 *
 * Yetki: online -> giris yapmis moderator+; self-host -> loopback-only
 * (orada oneri akisi zaten olusmaz, sayfa bos gorunur).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init_admin($pdo);

// --- Erisim kontrolu ---------------------------------------------------
if (MULTI_USER_MODE) {
    require_role($pdo, 'moderator');
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal  = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    if (!$isLocal) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_catalog_requests.localhost_only'), ENT_QUOTES, 'UTF-8'));
    }
}

$message = null;
$messageType = null;

// --- POST: onayla / reddet --------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die(htmlspecialchars(t('admin_catalog_requests.error.csrf'), ENT_QUOTES, 'UTF-8'));
    }

    $action = $_POST['action'] ?? '';
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids)) { $ids = []; }
    $clean = [];
    foreach ($ids as $id) { $id = (int)$id; if ($id > 0) { $clean[$id] = true; } }
    $clean = array_keys($clean);

    try {
        if (empty($clean)) {
            throw new Exception(t('admin_catalog_requests.error.no_selection'));
        }

        if ($action === 'approve_selected') {
            $ph = implode(',', array_fill(0, count($clean), '?'));
            $sel = $pdo->prepare(
                "SELECT * FROM catalog_requests
                  WHERE suggestion_status = 'pending' AND id IN ($ph)"
            );
            $sel->execute($clean);
            $rows = $sel->fetchAll(PDO::FETCH_ASSOC);

            $ins = $pdo->prepare("INSERT INTO animes (
                    title, alternative_titles, title_english, status,
                    total_episodes, mal_link, anidb_link, anime_schedule_link,
                    episode_interval, broadcast_day, broadcast_time,
                    broadcast_timezone, synopsis_tr, synopsis_en,
                    translation_status, release_date, end_date, series_name,
                    media_type, mal_id, anidb_id, source
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'none',
                    ?, ?, ?, ?, ?, ?, 'local'
                )");
            $mark = $pdo->prepare(
                "UPDATE catalog_requests
                    SET suggestion_status = 'approved',
                        reviewed_at = NOW(), reviewed_by = ?
                  WHERE id = ?"
            );

            $approved = 0;
            $uidR = current_user_id();
            foreach ($rows as $s) {
                try {
                    $ins->execute([
                        $s['title'], $s['alternative_titles'], $s['title_english'],
                        $s['status'] ?: 'Yayın Tamamlandı',
                        $s['total_episodes'], $s['mal_link'], $s['anidb_link'],
                        $s['anime_schedule_link'],
                        $s['episode_interval'] !== null ? (int)$s['episode_interval'] : 7,
                        $s['broadcast_day'], $s['broadcast_time'],
                        $s['broadcast_timezone'] ?: 'Asia/Tokyo',
                        $s['synopsis_tr'], $s['synopsis_en'],
                        $s['release_date'], $s['end_date'], $s['series_name'],
                        $s['media_type'], $s['mal_id'], $s['anidb_id'],
                    ]);
                } catch (PDOException $e) {
                    // animes.mal_id/anidb_id UNIQUE: bu arada katalogda
                    // olusmus olabilir. Anime zaten var demektir -> oneriyi
                    // yine de onaylanmis isaretle, satir eklemeyi atla.
                    error_log('admin_catalog_requests approve: anime exists/insert failed - ' . $e->getMessage());
                }
                $mark->execute([$uidR, (int)$s['id']]);
                $approved++;
            }
            $message = sprintf(t('admin_catalog_requests.success.approved'), $approved);
            $messageType = 'success';

        } elseif ($action === 'reject_selected') {
            $ph = implode(',', array_fill(0, count($clean), '?'));
            $upd = $pdo->prepare(
                "UPDATE catalog_requests
                    SET suggestion_status = 'rejected',
                        reviewed_at = NOW(), reviewed_by = ?
                  WHERE suggestion_status = 'pending' AND id IN ($ph)"
            );
            $upd->execute(array_merge([current_user_id()], $clean));
            $message = sprintf(t('admin_catalog_requests.success.rejected'), $upd->rowCount());
            $messageType = 'success';

        } else {
            throw new Exception(t('admin_catalog_requests.error.unknown_action'));
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// --- Bekleyen onerileri getir -----------------------------------------
$pendingStmt = $pdo->query(
    "SELECT s.id, s.title, s.status, s.mal_id, s.anidb_id, s.created_at,
            COALESCE(u.username, '-') AS suggester
       FROM catalog_requests s
       LEFT JOIN users u ON u.id = s.suggested_by
      WHERE s.suggestion_status = 'pending'
      ORDER BY s.created_at DESC, s.id DESC"
);
$pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count($pending);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_catalog_requests.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
        .admin-container { max-width: 900px; margin: 40px auto; background: #fff;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px; }
        .page-heading { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .page-heading h1 { margin: 0; font-size: 1.6em; }
        .msg { padding: 10px 14px; border-radius: 6px; margin-bottom: 20px; }
        .msg-success { background: #d4edda; color: #155724; }
        .msg-error   { background: #f8d7da; color: #721c24; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .bulk-actions { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; }
        .btn-primary, .btn-secondary, .btn-danger { display: inline-block; padding: 8px 16px;
            border: none; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; }
        .btn-primary { background: #28a745; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .back-link { display: inline-block; margin-top: 20px; color: #666; text-decoration: none; }
        .back-link:hover { color: #007bff; }
        .empty { padding: 30px; text-align: center; color: #888; font-style: italic;
            background: #fafafa; border-radius: 6px; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="page-heading">
        <i class="fas fa-lightbulb" style="font-size: 1.6em; color: #28a745;"></i>
        <div>
            <h1><?php echo htmlspecialchars(t('admin_catalog_requests.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <div style="color: #888; font-size: 0.9em;">
                <?php echo htmlspecialchars(t('admin_catalog_requests.subtitle'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="msg <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($pending)): ?>
        <div class="empty"><?php echo htmlspecialchars(t('admin_catalog_requests.empty'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php else: ?>
        <form method="post" id="sugg-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="action" value="approve_selected">

            <div class="bulk-actions">
                <button type="button" class="btn-secondary" onclick="toggleAll(true)">
                    <i class="fas fa-check-square"></i> <?php echo htmlspecialchars(t('admin_catalog_requests.btn.select_all'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <button type="button" class="btn-secondary" onclick="toggleAll(false)">
                    <i class="far fa-square"></i> <?php echo htmlspecialchars(t('admin_catalog_requests.btn.clear_selection'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <button type="submit" class="btn-primary"
                        onclick="document.querySelector('input[name=action]').value='approve_selected';">
                    <i class="fas fa-check"></i> <?php echo htmlspecialchars(t('admin_catalog_requests.btn.approve'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <button type="submit" class="btn-danger"
                        onclick="document.querySelector('input[name=action]').value='reject_selected';
                                 return confirm('<?php echo htmlspecialchars(t('admin_catalog_requests.confirm.reject'), ENT_QUOTES, 'UTF-8'); ?>');">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(t('admin_catalog_requests.btn.reject'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="select-all" onchange="toggleAll(this.checked)"></th>
                        <th><?php echo htmlspecialchars(t('admin_catalog_requests.col.title'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_catalog_requests.col.broadcast_status'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_catalog_requests.col.external_ids'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_catalog_requests.col.suggested_by'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(t('admin_catalog_requests.col.created'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $s): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?php echo (int)$s['id']; ?>" class="row-check"></td>
                            <td><?php echo htmlspecialchars($s['title']); ?></td>
                            <td><?php echo htmlspecialchars((string)$s['status']); ?></td>
                            <td style="font-size: 0.85em; color: #666;">
                                <?php echo $s['mal_id'] ? 'MAL: ' . (int)$s['mal_id'] : '-'; ?><br>
                                <?php echo $s['anidb_id'] ? 'AniDB: ' . (int)$s['anidb_id'] : '-'; ?>
                            </td>
                            <td style="font-size: 0.9em;"><?php echo htmlspecialchars($s['suggester']); ?></td>
                            <td style="font-size: 0.85em; color: #666;"><?php echo htmlspecialchars((string)$s['created_at']); ?></td>
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
        <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_catalog_requests.back_to_dashboard'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
</div>
</body>
</html>
