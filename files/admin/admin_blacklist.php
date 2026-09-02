<?php
/**
 * Anime Tracker - Ice aktarma kara listesi (import blacklist)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * 1.1.35. Katalogtan SILINEN animelerin defteri; ayni zamanda o animelerin
 * bir sonraki AniList / MAL ice aktarmasiyla geri gelmesini engelleyen
 * liste. Satirlarin cogu index.php'deki silme islemi tarafindan
 * kendiliginden yazilir ('deleted'); bu sayfa onlari GORUNUR kilar, elle
 * ekleme ('manual') yapmaya ve fikir degistirildiginde LISTEDEN CIKARMAYA
 * yarar.
 *
 * Kural functions/blacklist_helpers.php'de tek yerde durur; bu sayfa
 * yalnizca onun yuzudur.
 *
 * Yetki: online -> giris yapmis moderator+ (animeyi SILEBILEN rolun ayni
 * silmenin kaydini gorup geri alabilmesi gerekir). Self-host -> loopback
 * only; orada ozellik zaten ATILDIR ve sayfa bunu acikca soyler.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';

lang_init_admin($pdo);

// --- Erisim kontrolu ---------------------------------------------------
if (MULTI_USER_MODE) {
    require_role($pdo, 'moderator');
} else {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $isLocal  = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    if (!$isLocal) {
        http_response_code(403);
        die(htmlspecialchars(t('admin_blacklist.localhost_only'), ENT_QUOTES, 'UTF-8'));
    }
}

$message     = null;
$messageType = null;

// Self-host'ta liste ATILDIR (blacklist_helpers.php basligi). Sayfa yine
// acilir - "neden bos" sorusunu bos bir tablo degil, bu bayrak cevaplar.
$active = blacklist_active();

// --- POST: listeden cikar / elle ekle ----------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        die(htmlspecialchars(t('admin_blacklist.error.csrf'), ENT_QUOTES, 'UTF-8'));
    }

    $action = $_POST['action'] ?? '';

    try {
        if (!$active) {
            throw new Exception(t('admin_blacklist.error.inactive'));
        }

        if ($action === 'remove_selected') {
            $ids = $_POST['ids'] ?? [];
            if (!is_array($ids) || empty($ids)) {
                throw new Exception(t('admin_blacklist.error.no_selection'));
            }
            $removed = blacklist_remove($pdo, $ids);
            if ($removed === 0) {
                throw new Exception(t('admin_blacklist.error.no_selection'));
            }
            $message     = sprintf(t('admin_blacklist.success.removed'), $removed);
            $messageType = 'success';

        } elseif ($action === 'add_manual') {
            $title   = trim((string)($_POST['title'] ?? ''));
            $note    = trim((string)($_POST['note']  ?? ''));
            $malRaw  = trim((string)($_POST['mal_id']   ?? ''));
            $anidbRaw= trim((string)($_POST['anidb_id'] ?? ''));
            $malId   = ($malRaw   !== '') ? (int)$malRaw   : null;
            $anidbId = ($anidbRaw !== '') ? (int)$anidbRaw : null;
            if ($malId   !== null && $malId   <= 0) { $malId   = null; }
            if ($anidbId !== null && $anidbId <= 0) { $anidbId = null; }

            if ($title === '') {
                throw new Exception(t('admin_blacklist.error.no_title'));
            }
            // ELLE eklemede en az bir kimlik ZORUNLU. Kimliksiz bir satir
            // hicbir seyi engelleyemez (eslesme yalnizca mal_id/anidb_id
            // uzerinden olur) - yani "engelle" niyetiyle yazilmis ama
            // engellemeyen sessiz bir kayit olurdu. Silme sirasinda dusen
            // kimliksiz kayit BASKA bir seydir: o bir engel degil, silme
            // kaydidir ve oyle isaretlenir.
            if ($malId === null && $anidbId === null) {
                throw new Exception(t('admin_blacklist.error.no_id'));
            }
            if (blacklist_blocks($pdo, $malId, $anidbId)) {
                $message     = t('admin_blacklist.info.already');
                $messageType = 'success';
            } elseif (blacklist_add($pdo, $malId, $anidbId, $title, 'manual', $note)) {
                $message     = sprintf(t('admin_blacklist.success.added'), $title);
                $messageType = 'success';
            } else {
                throw new Exception(t('admin_blacklist.error.add_failed'));
            }

        } else {
            throw new Exception(t('admin_blacklist.error.unknown_action'));
        }
    } catch (Exception $e) {
        $message     = $e->getMessage();
        $messageType = 'error';
    }
}

// --- Listeleme: arama + sayfalama --------------------------------------

const BLACKLIST_PER_PAGE = 50;

$q    = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));

$where  = '';
$params = [];
if ($q !== '') {
    // Baslikta ARAR; girilen deger sayiysa MAL/AniDB kimligiyle de
    // eslestirir - kuratorun elinde cogu zaman baslik degil, silerken
    // gordugu kimlik olur.
    $where  = "WHERE (b.title LIKE ?";
    $params[] = '%' . $q . '%';
    if (ctype_digit($q)) {
        $where .= " OR b.mal_id = ? OR b.anidb_id = ?";
        $params[] = (int)$q;
        $params[] = (int)$q;
    }
    $where .= ")";
}

$rows       = [];
$totalRows  = 0;
$totalPages = 1;
$tableError = false;

if ($active) {
    try {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM import_blacklist b $where");
        $cnt->execute($params);
        $totalRows  = (int)$cnt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / BLACKLIST_PER_PAGE));
        if ($page > $totalPages) { $page = $totalPages; }
        $offset = ($page - 1) * BLACKLIST_PER_PAGE;

        // in_catalog_id: kara listedeki kimlik SU ANDA katalogda duruyor mu?
        // Silinip sonra elle geri eklenmis bir anime hem katalogda hem kara
        // listede bulunabilir. Zararsizdir (satir var oldugu icin ice
        // aktarma zaten "eslesti" dalina duser, engel dalina hic gelmez) ama
        // kafa karistirir; isaret, kuratorun temizlemesi icin.
        $sql = "SELECT b.id, b.mal_id, b.anidb_id, b.title, b.reason, b.note,
                       b.created_at,
                       COALESCE(u.username, '-') AS added_by,
                       (SELECT a.id FROM animes a
                         WHERE (b.mal_id   IS NOT NULL AND a.mal_id   = b.mal_id)
                            OR (b.anidb_id IS NOT NULL AND a.anidb_id = b.anidb_id)
                         LIMIT 1) AS in_catalog_id
                  FROM import_blacklist b
                  LEFT JOIN users u ON u.id = b.created_by
                  $where
                 ORDER BY b.created_at DESC, b.id DESC
                 LIMIT " . (int)BLACKLIST_PER_PAGE . " OFFSET " . (int)$offset;
        $sel = $pdo->prepare($sql);
        $sel->execute($params);
        $rows = $sel->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Yarim yukseltme: dosyalar kopyalandi, migration kosmadi.
        // Sayfa fatal vermez, ne yapilmasi gerektigini soyler.
        error_log('[anime_tracker] blacklist page query failed: ' . $e->getMessage());
        $tableError = true;
    }
}

/** Sayfalama baglantisi - arama terimini korur. */
function blacklist_page_url($page, $q)
{
    $qs = ['page' => (int)$page];
    if ($q !== '') { $qs['q'] = $q; }
    return 'admin_blacklist.php?' . http_build_query($qs);
}
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('admin_blacklist.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
    <?php echo asset_styles('../'); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fa; }
        .admin-container { max-width: 1000px; margin: 40px auto; background: #fff;
            border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 30px; }
        .page-heading { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; }
        .page-heading h1 { margin: 0; font-size: 1.6em; }
        .msg { padding: 10px 14px; border-radius: 6px; margin-bottom: 20px; }
        .msg-success { background: #d4edda; color: #155724; }
        .msg-error   { background: #f8d7da; color: #721c24; }
        .msg-info    { background: #fff3cd; color: #856404; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; }
        .bulk-actions { display: flex; gap: 10px; margin: 20px 0; flex-wrap: wrap; align-items: center; }
        .btn-primary, .btn-secondary, .btn-danger { display: inline-block; padding: 8px 16px;
            border: none; border-radius: 6px; cursor: pointer; font-weight: 500; text-decoration: none; }
        .btn-primary { background: #28a745; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-danger { background: #dc3545; color: #fff; }
        .back-link { display: inline-block; margin-top: 20px; color: #666; text-decoration: none; }
        .back-link:hover { color: #007bff; }
        .empty { padding: 30px; text-align: center; color: #888; font-style: italic;
            background: #fafafa; border-radius: 6px; }
        .small { font-size: 0.85em; color: #666; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 0.75em; }
        .badge-deleted { background: #f8d7da; color: #721c24; }
        .badge-manual  { background: #e2e3e5; color: #383d41; }
        .badge-warn    { background: #fff3cd; color: #856404; }
        .add-box { margin-top: 30px; padding: 20px; background: #fafbfc;
            border: 1px solid #e6e8eb; border-radius: 8px; }
        .add-box h2 { margin: 0 0 6px 0; font-size: 1.1em; }
        .add-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px; margin: 14px 0; }
        .add-grid label { display: block; font-size: 0.85em; color: #555; margin-bottom: 4px; }
        .add-grid input { width: 100%; padding: 7px 9px; border: 1px solid #ccc;
            border-radius: 5px; font-family: inherit; box-sizing: border-box; }
        .search-form { display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap; }
        .search-form input[type=text] { padding: 7px 9px; border: 1px solid #ccc;
            border-radius: 5px; font-family: inherit; min-width: 220px; }
        .paging { margin-top: 16px; display: flex; gap: 10px; align-items: center; }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="page-heading">
        <i class="fas fa-ban" style="font-size: 1.6em; color: #dc3545;"></i>
        <div>
            <h1><?php echo htmlspecialchars(t('admin_blacklist.heading'), ENT_QUOTES, 'UTF-8'); ?></h1>
            <div style="color: #888; font-size: 0.9em;">
                <?php echo htmlspecialchars(t('admin_blacklist.subtitle'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="msg <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if (!$active): ?>
        <div class="msg msg-info">
            <i class="fas fa-info-circle"></i>
            <?php echo htmlspecialchars(t('admin_blacklist.selfhost_note'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php elseif ($tableError): ?>
        <div class="msg msg-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars(t('admin_blacklist.error.table_missing'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php else: ?>

        <p class="small"><?php echo htmlspecialchars(t('admin_blacklist.intro'), ENT_QUOTES, 'UTF-8'); ?></p>

        <form method="get" class="search-form">
            <input type="text" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                   placeholder="<?php echo htmlspecialchars(t('admin_blacklist.search.placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn-secondary">
                <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.search'), ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <?php if ($q !== ''): ?>
                <a href="admin_blacklist.php" class="btn-secondary">
                    <i class="fas fa-times"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.clear_search'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            <?php endif; ?>
        </form>

        <?php if (empty($rows)): ?>
            <div class="empty">
                <?php echo htmlspecialchars(
                    $q !== '' ? t('admin_blacklist.empty_search') : t('admin_blacklist.empty'),
                    ENT_QUOTES, 'UTF-8'
                ); ?>
            </div>
        <?php else: ?>
            <form method="post" id="bl-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="remove_selected">

                <div class="bulk-actions">
                    <button type="button" class="btn-secondary" onclick="toggleAll(true)">
                        <i class="fas fa-check-square"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.select_all'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="button" class="btn-secondary" onclick="toggleAll(false)">
                        <i class="far fa-square"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.clear_selection'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <button type="submit" class="btn-danger"
                            onclick="return confirm('<?php echo htmlspecialchars(t('admin_blacklist.confirm.remove'), ENT_QUOTES, 'UTF-8'); ?>');">
                        <i class="fas fa-undo"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.remove'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <span class="small"><?php echo htmlspecialchars(sprintf(t('admin_blacklist.count'), $totalRows), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"><input type="checkbox" id="select-all" onchange="toggleAll(this.checked)"></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.title'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.external_ids'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.reason'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.state'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.added_by'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(t('admin_blacklist.col.created'), ENT_QUOTES, 'UTF-8'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?php echo (int)$r['id']; ?>" class="row-check"></td>
                                <td>
                                    <?php echo htmlspecialchars($r['title']); ?>
                                    <?php if (!empty($r['note'])): ?>
                                        <div class="small"><?php echo htmlspecialchars($r['note']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php echo $r['mal_id']   ? 'MAL: '   . (int)$r['mal_id']   : '-'; ?><br>
                                    <?php echo $r['anidb_id'] ? 'AniDB: ' . (int)$r['anidb_id'] : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $r['reason'] === 'deleted' ? 'badge-deleted' : 'badge-manual'; ?>">
                                        <?php echo htmlspecialchars(
                                            t($r['reason'] === 'deleted'
                                                ? 'admin_blacklist.reason.deleted'
                                                : 'admin_blacklist.reason.manual'),
                                            ENT_QUOTES, 'UTF-8'
                                        ); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (empty($r['mal_id']) && empty($r['anidb_id'])): ?>
                                        <span class="badge badge-warn" title="<?php echo htmlspecialchars(t('admin_blacklist.state.no_key.hint'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(t('admin_blacklist.state.no_key'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php elseif (!empty($r['in_catalog_id'])): ?>
                                        <span class="badge badge-warn" title="<?php echo htmlspecialchars(t('admin_blacklist.state.in_catalog.hint'), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(t('admin_blacklist.state.in_catalog'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="small"><?php echo htmlspecialchars(t('admin_blacklist.state.blocking'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($r['added_by']); ?></td>
                                <td class="small"><?php echo htmlspecialchars((string)$r['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <?php if ($totalPages > 1): ?>
                <div class="paging">
                    <?php if ($page > 1): ?>
                        <a class="btn-secondary" href="<?php echo htmlspecialchars(blacklist_page_url($page - 1, $q), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    <span class="small"><?php echo htmlspecialchars(sprintf(t('admin_blacklist.paging'), $page, $totalPages), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a class="btn-secondary" href="<?php echo htmlspecialchars(blacklist_page_url($page + 1, $q), ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <script>
                function toggleAll(checked) {
                    document.querySelectorAll('.row-check').forEach(cb => cb.checked = checked);
                    const master = document.getElementById('select-all');
                    if (master) master.checked = checked;
                }
            </script>
        <?php endif; ?>

        <div class="add-box">
            <h2><?php echo htmlspecialchars(t('admin_blacklist.add.heading'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="small"><?php echo htmlspecialchars(t('admin_blacklist.add.hint'), ENT_QUOTES, 'UTF-8'); ?></p>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="add_manual">
                <div class="add-grid">
                    <div>
                        <label for="bl_title"><?php echo htmlspecialchars(t('admin_blacklist.add.title'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" id="bl_title" name="title" maxlength="255" required>
                    </div>
                    <div>
                        <label for="bl_mal"><?php echo htmlspecialchars(t('admin_blacklist.add.mal_id'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="number" id="bl_mal" name="mal_id" min="1" step="1">
                    </div>
                    <div>
                        <label for="bl_anidb"><?php echo htmlspecialchars(t('admin_blacklist.add.anidb_id'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="number" id="bl_anidb" name="anidb_id" min="1" step="1">
                    </div>
                    <div>
                        <label for="bl_note"><?php echo htmlspecialchars(t('admin_blacklist.add.note'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <input type="text" id="bl_note" name="note" maxlength="255">
                    </div>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-plus"></i> <?php echo htmlspecialchars(t('admin_blacklist.btn.add'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
            </form>
        </div>

    <?php endif; ?>

    <a href="admin.php" class="back-link">
        <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('admin_blacklist.back_to_dashboard'), ENT_QUOTES, 'UTF-8'); ?>
    </a>
</div>
</body>
</html>
