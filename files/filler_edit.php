<?php
/**
 * Anime Tracker - Filler Episode Editor (0.7)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Grid editor for per-episode filler classification (KARARLAR Bolum 8).
 * One clickable cell per episode (1..N). Clicking a cell cycles it through
 * the states:
 *
 *   unmarked -> MangaCanon -> AnimeCanon -> Mixed -> Filler -> unmarked
 *
 * The traffic-light colouring (two canon types green, Mixed amber, Filler
 * red, unmarked neutral) lives in css/filler.css and is keyed off the
 * stable suffixes from filler_type_css_class(). Only EXCEPTIONS need
 * marking - an unmarked episode means "assume canon", so most cells stay
 * neutral and produce no row.
 *
 * Saving is BATCH, not per-tick: the grid state lives in the DOM, and the
 * single "Kaydet" button POSTs the whole {episode_no: type} map to
 * update_filler.php in one request (CSRF protected). The server replaces
 * the anime's filler rows atomically. This avoids 1000+ AJAX calls and the
 * half-saved-grid risk on a long series. Shift-click range selection is on
 * the near roadmap (KARARLAR Bolum 8); the first cut is single-click cycle.
 *
 * Episode count source + guard (KARARLAR Bolum 8):
 *   count = total_episodes ?? aired_episodes. If BOTH are empty the grid
 *   cannot be built, so the page shows a guard message pointing the curator
 *   to set an episode count first, instead of rendering an empty editor.
 *
 * Visibility note: animes.filler_tracking governs whether the read-only
 * summary appears on anime_details.php; it does NOT gate this editor. A
 * curator can classify episodes here whether or not the flag is on (the
 * flag only decides if the result is surfaced to readers).
 *
 * Single curator for now: in Faz 2/3 filler editing becomes "live edit +
 * catalog_edit_log + moderator revert" (AniDB hybrid, KARARLAR Bolum 8/9).
 * No role gate exists yet because the build is single-user.
 *
 * Follows the chronology.php page pattern (boilerplate, lang_init, id
 * guard, anime fetch, standard head, back button).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

lang_init($pdo);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Ana anime bilgisini cek
$stmt = $pdo->prepare("SELECT * FROM animes WHERE id = ?");
$stmt->execute([$id]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anime) {
    header('Location: index.php');
    exit;
}

// Bolum sayisi kaynagi: total_episodes ?? aired_episodes (KARARLAR Bolum 8).
// empty() semantigi: 0 da "yok" sayilir (projenin (yayinda) etiketi
// kuraliyla ayni mantik).
$episodeCount = null;
if (!empty($anime['total_episodes'])) {
    $episodeCount = (int)$anime['total_episodes'];
} elseif (!empty($anime['aired_episodes'])) {
    $episodeCount = (int)$anime['aired_episodes'];
}

// Bu anime icin mevcut filler kayitlarini cek -> episode_no => type haritasi.
// Grid'i on-doldurmak icin (sadece istisna isaretler kayitlidir, geri kalan
// hucreler notr baslar).
$existing = [];
$stmt = $pdo->prepare(
    "SELECT episode_no, type FROM filler_episodes WHERE anime_id = ? ORDER BY episode_no"
);
$stmt->execute([$id]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $existing[(int)$row['episode_no']] = $row['type'];
}

// JS tarafi icin tip meta verisi (i18n disiplini: PHP uretir, JS LANG.* /
// sabitlerden okur, JS icinde hard-coded string olmaz).
$typeOptions = filler_type_options();          // ASCII key => localized label
$typeCss = [];
foreach (array_keys($typeOptions) as $t) {
    $typeCss[$t] = filler_type_css_class($t);   // ASCII key => css suffix
}

// JS string sozlugu (chronology / list_settings JS LANG paterni).
$jsLang = [
    'saving'     => t('filler.js.saving'),
    'saved'      => t('filler.js.saved'),
    'save_error' => t('filler.js.save_error'),
    'unmarked'   => t('filler.type.unmarked'),
    'marked_n'   => t('filler.js.marked_count'),   // sprintf %d
    // Import (AnimeFillerList) JS strings.
    'importing'      => t('filler.js.importing'),
    'imported_n'     => t('filler.js.imported_count'),    // sprintf %d
    'import_skipped' => t('filler.js.import_skipped'),    // sprintf %d
    'import_review'  => t('filler.js.import_review'),
    'import_need_url'=> t('filler.js.import_need_url'),
    'import_error'   => t('filler.js.import_error'),
];
?>

<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($anime['title']); ?> - <?php echo htmlspecialchars(t('filler.title_suffix'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>
<body>
    <div class="filler-container">
        <h1 class="filler-title">
            <?php echo htmlspecialchars($anime['title']); ?>
            <small><?php echo htmlspecialchars(t('filler.subtitle'), ENT_QUOTES, 'UTF-8'); ?></small>
        </h1>

        <?php if ($episodeCount === null): ?>
            <!-- Guard: bolum sayisi yok, grid uretilemez. -->
            <div class="filler-guard">
                <i class="fas fa-exclamation-triangle"></i>
                <p><?php echo htmlspecialchars(t('filler.guard.no_count'), ENT_QUOTES, 'UTF-8'); ?></p>
                <a href="edit_anime.php?id=<?php echo (int)$anime['id']; ?>" class="back-button">
                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars(t('filler.guard.set_count'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php else: ?>

            <p class="filler-instructions"><?php echo htmlspecialchars(t('filler.instructions'), ENT_QUOTES, 'UTF-8'); ?></p>

            <!-- AnimeFillerList'ten ice aktar: URL yapistir + buton. Sonuc
                 grid'e doldurulur (kaydetmez), kullanici Kaydet'e basar. -->
            <div class="filler-import">
                <input type="url" id="fillerImportUrl" class="filler-import-url"
                       placeholder="<?php echo htmlspecialchars(t('filler.import.placeholder'), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" class="btn-secondary" id="fillerImportBtn">
                    <i class="fas fa-download"></i> <?php echo htmlspecialchars(t('filler.import.button'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <span class="filler-import-status" id="fillerImportStatus" role="status" aria-live="polite"></span>
            </div>

            <!-- Aciklama (trafik-isigi): notr + 4 tip. Renkler css/filler.css. -->
            <div class="filler-legend">
                <span class="filler-legend-item">
                    <span class="filler-swatch filler-cell"></span>
                    <?php echo htmlspecialchars(t('filler.type.unmarked'), ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <?php foreach ($typeOptions as $value => $label): ?>
                    <span class="filler-legend-item">
                        <span class="filler-swatch filler-cell filler-cell-<?php echo $typeCss[$value]; ?>"></span>
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Grid: bolum basina bir hucre. data-type bos = notr (kayit yok). -->
            <div class="filler-grid"
                 data-anime-id="<?php echo (int)$anime['id']; ?>"
                 data-csrf="<?php echo htmlspecialchars(csrf_token()); ?>">
                <?php for ($ep = 1; $ep <= $episodeCount; $ep++):
                    $curType = $existing[$ep] ?? '';
                    $cssSuffix = ($curType !== '') ? $typeCss[$curType] : '';
                ?>
                    <button type="button"
                            class="filler-cell<?php echo $cssSuffix !== '' ? ' filler-cell-' . $cssSuffix : ''; ?>"
                            data-ep="<?php echo $ep; ?>"
                            data-type="<?php echo htmlspecialchars($curType); ?>"
                            title="<?php echo $ep . ($curType !== '' ? ' - ' . htmlspecialchars($typeOptions[$curType], ENT_QUOTES, 'UTF-8') : ''); ?>">
                        <?php echo $ep; ?>
                    </button>
                <?php endfor; ?>
            </div>

            <div class="filler-actions">
                <span class="filler-marked-count" id="fillerMarkedCount"></span>
                <button type="button" class="btn-primary" id="fillerSaveBtn">
                    <i class="fas fa-save"></i> <?php echo htmlspecialchars(t('filler.save'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <span class="filler-save-status" id="fillerSaveStatus" role="status" aria-live="polite"></span>
            </div>

        <?php endif; ?>

        <div class="filler-back">
            <a href="anime_details.php?id=<?php echo (int)$anime['id']; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i> <?php echo htmlspecialchars(t('filler.back_to_details'), ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </div>

<?php if ($episodeCount !== null): ?>
<script>
// i18n: tum string'ler PHP'den gelir (KARARLAR Bolum 7 JS LANG patterni).
const LANG = <?php echo json_encode($jsLang, JSON_UNESCAPED_UNICODE); ?>;
// Hucre cycle sirasi (filler_type_options anahtar sirasi) + css ekleri +
// etiketler. Bos string ('') = isaretsiz/notr, cycle'in bes durumu.
const FILLER_TYPES  = <?php echo json_encode(array_keys($typeOptions)); ?>;
const FILLER_CSS    = <?php echo json_encode($typeCss); ?>;
const FILLER_LABELS = <?php echo json_encode($typeOptions, JSON_UNESCAPED_UNICODE); ?>;

(function () {
    const grid    = document.querySelector('.filler-grid');
    if (!grid) return;
    const animeId = grid.dataset.animeId;
    const csrf    = grid.dataset.csrf;
    const saveBtn = document.getElementById('fillerSaveBtn');
    const status  = document.getElementById('fillerSaveStatus');
    const counter = document.getElementById('fillerMarkedCount');

    // sprintf-benzeri %d ikamesi (PHP sprintf ile uyumlu, edit_anime _fmt
    // ile ayni mantik).
    function fmt(s, n) { return String(s).replace('%d', n); }

    // Bir hucreden mevcut tipe gore css sinifini soyup yeni tipe gore ekle.
    function applyType(cell, type) {
        // Once tum tip siniflarini temizle.
        Object.values(FILLER_CSS).forEach(function (suffix) {
            cell.classList.remove('filler-cell-' + suffix);
        });
        cell.dataset.type = type;
        if (type !== '') {
            cell.classList.add('filler-cell-' + FILLER_CSS[type]);
            cell.title = cell.dataset.ep + ' - ' + FILLER_LABELS[type];
        } else {
            cell.title = cell.dataset.ep;
        }
    }

    // Tikla: cycle ilerlet. Sira: '' -> tip[0] -> ... -> tip[son] -> ''.
    function cycle(cell) {
        const cur = cell.dataset.type || '';
        let next;
        if (cur === '') {
            next = FILLER_TYPES[0];
        } else {
            const i = FILLER_TYPES.indexOf(cur);
            next = (i === -1 || i === FILLER_TYPES.length - 1)
                 ? '' : FILLER_TYPES[i + 1];
        }
        applyType(cell, next);
        updateCount();
    }

    function markedCount() {
        return grid.querySelectorAll('.filler-cell[data-type]:not([data-type=""])').length;
    }

    function updateCount() {
        if (counter) counter.textContent = fmt(LANG.marked_n, markedCount());
    }

    grid.addEventListener('click', function (e) {
        const cell = e.target.closest('.filler-cell');
        if (cell && grid.contains(cell)) cycle(cell);
    });

    saveBtn.addEventListener('click', function () {
        // Grid state'ini {episode_no: type} haritasina topla (sadece
        // isaretli hucreler; bos hucreler gonderilmez -> sunucu o anime'nin
        // kayitlarini silip bu seti yazar).
        const episodes = {};
        grid.querySelectorAll('.filler-cell').forEach(function (cell) {
            const t = cell.dataset.type || '';
            if (t !== '') episodes[cell.dataset.ep] = t;
        });

        saveBtn.disabled = true;
        status.textContent = LANG.saving;
        status.className = 'filler-save-status';

        const body = new URLSearchParams();
        body.append('csrf_token', csrf);
        body.append('anime_id', animeId);
        body.append('episodes', JSON.stringify(episodes));

        fetch('update_filler.php', { method: 'POST', body: body })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                saveBtn.disabled = false;
                if (data && data.success) {
                    status.textContent = LANG.saved;
                    status.className = 'filler-save-status is-ok';
                } else {
                    status.textContent = (data && data.error) ? data.error : LANG.save_error;
                    status.className = 'filler-save-status is-err';
                }
            })
            .catch(function () {
                saveBtn.disabled = false;
                status.textContent = LANG.save_error;
                status.className = 'filler-save-status is-err';
            });
    });

    // --- AnimeFillerList ice aktarma -----------------------------------
    const importBtn    = document.getElementById('fillerImportBtn');
    const importUrl    = document.getElementById('fillerImportUrl');
    const importStatus = document.getElementById('fillerImportStatus');

    if (importBtn) {
        importBtn.addEventListener('click', function () {
            const url = (importUrl.value || '').trim();
            if (url === '') {
                importStatus.textContent = LANG.import_need_url;
                importStatus.className = 'filler-import-status is-err';
                return;
            }
            importBtn.disabled = true;
            importStatus.textContent = LANG.importing;
            importStatus.className = 'filler-import-status';

            const body = new URLSearchParams();
            body.append('csrf_token', csrf);
            body.append('anime_id', animeId);
            body.append('url', url);

            fetch('fetch_filler.php', { method: 'POST', body: body })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    importBtn.disabled = false;
                    if (!data || !data.success) {
                        importStatus.textContent = (data && data.error) ? data.error : LANG.import_error;
                        importStatus.className = 'filler-import-status is-err';
                        return;
                    }
                    // Grid'i temizle, gelen tipleri uygula. Kaydetmez -
                    // kullanici gozden gecirip Kaydet'e basar.
                    grid.querySelectorAll('.filler-cell').forEach(function (cell) {
                        applyType(cell, '');
                    });
                    const eps = data.episodes || {};
                    Object.keys(eps).forEach(function (ep) {
                        const cell = grid.querySelector('.filler-cell[data-ep="' + ep + '"]');
                        if (cell) applyType(cell, eps[ep]);
                    });
                    updateCount();

                    let msg = fmt(LANG.imported_n, data.total);
                    if (data.skipped && data.skipped > 0) {
                        msg += ' ' + fmt(LANG.import_skipped, data.skipped);
                    }
                    importStatus.textContent = msg + ' ' + LANG.import_review;
                    importStatus.className = 'filler-import-status is-ok';
                })
                .catch(function () {
                    importBtn.disabled = false;
                    importStatus.textContent = LANG.import_error;
                    importStatus.className = 'filler-import-status is-err';
                });
        });
    }

    updateCount();
})();
</script>
<?php endif; ?>
</body>
</html>
