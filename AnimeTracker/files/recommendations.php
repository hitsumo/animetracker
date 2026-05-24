<?php

/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sumer]

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License version 2 as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
  MA 02110-1301, USA.
 */

/**
 * Recommendation page ("Ne Izlesem?").
 *
 * Bucket metaphor:
 *   - Each sentence (tag) is a bucket.
 *   - When the user picks several sentences, each bucket dips into the
 *     pool of all anime and pulls out the matching rows.
 *   - The buckets merge into a single result. An anime that was pulled
 *     by 3 buckets ranks above one pulled by only 1 (OR + score, not
 *     AND - so the result is never empty).
 *   - Within the same score band, anime the user has not finished are
 *     surfaced first (discovery > rewatching).
 *
 * Two entry points:
 *   - Pick sentences + "Oner" -> ranked list grouped by score
 *   - "Surpriz Sec" -> a single random anime from the unwatched pool
 *     (no sentences required - quick coin flip)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// --------------------------------------------------------
// Load every available sentence so the form can render checkboxes.
// --------------------------------------------------------
$allTags = getAllTags($pdo);

// --------------------------------------------------------
// Read user input from the query string. We use GET so the result
// page is shareable / refreshable / bookmarkable. CSRF is not relevant
// here - this is a read-only screen.
// --------------------------------------------------------
$selectedTagIds = [];
if (!empty($_GET['tags']) && is_array($_GET['tags'])) {
    foreach ($_GET['tags'] as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $selectedTagIds[$tid] = true;
        }
    }
    $selectedTagIds = array_keys($selectedTagIds);
}
$mode = $_GET['mode'] ?? 'pick';   // 'pick' (sentence-driven) or 'surprise'

// --------------------------------------------------------
// Compute the result set.
// --------------------------------------------------------
$results = [];      // list of [anime, score, matched_tag_names]
$surpriseAnime = null;

if ($mode === 'surprise') {
    // Surprise mode: pick one random anime the user has not yet watched.
    // Falls back to any anime if the unwatched pool is empty.
    $stmt = $pdo->query(
        "SELECT * FROM animes
         WHERE watch_status != 'Watched'
         ORDER BY RAND()
         LIMIT 1"
    );
    $surpriseAnime = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$surpriseAnime) {
        // Everything is watched - just pick something at random
        $stmt = $pdo->query("SELECT * FROM animes ORDER BY RAND() LIMIT 1");
        $surpriseAnime = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} elseif (!empty($selectedTagIds)) {
    // Sentence mode: each selected sentence is a bucket. Collect every
    // anime touched by any of the buckets and count how many buckets
    // it appeared in - that count is the score.
    //
    // We do this in a single SQL pass so the database does the heavy
    // lifting: GROUP BY anime_id with a COUNT of distinct matching
    // tags. The IN (?, ?, ?) clause is parameterised dynamically based
    // on how many sentences were selected.
    $placeholders = implode(',', array_fill(0, count($selectedTagIds), '?'));

    $sql = "
        SELECT a.*,
               COUNT(DISTINCT at.tag_id) AS score,
               GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR '|') AS matched_tags
        FROM animes a
        INNER JOIN anime_tags at ON at.anime_id = a.id
        INNER JOIN tags t ON t.id = at.tag_id
        WHERE at.tag_id IN ($placeholders)
        GROUP BY a.id
        ORDER BY
            score DESC,
            CASE WHEN a.watch_status = 'Watched' THEN 1 ELSE 0 END ASC,
            RAND()
    ";
    // Sort priority:
    //   1) higher score first (more buckets matched = better fit)
    //   2) within the same score, unwatched anime appear above watched
    //      ones (discovery beats rewatching)
    //   3) within the same score AND watch state, randomise so refresh
    //      gives a fresh-feeling list
    $stmt = $pdo->prepare($sql);
    $stmt->execute($selectedTagIds);
    $rawResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawResults as $row) {
        $matchedNames = !empty($row['matched_tags'])
            ? explode('|', $row['matched_tags'])
            : [];
        $results[] = [
            'anime' => $row,
            'score' => (int)$row['score'],
            'matched_tag_names' => $matchedNames,
        ];
    }
}

// Helper to build a watch-status badge consistent with the rest of the
// app. Page-local because it is purely a presentation concern (inline
// style, used in 2 places here only). The status -> color map uses the
// 0.6 ASCII enum keys; label and CSS class come from the central
// functions.php helpers (single source of truth for naming).
function watch_status_badge($status) {
    $colors = [
        'Watched'     => '#28a745',
        'Watching'    => '#007bff',
        'PlanToWatch' => '#6c757d',
        'OnHold'      => '#e0a000',
    ];
    $color = $colors[$status] ?? '#6c757d';
    $label = watch_status_label($status);
    return '<span style="display: inline-block; padding: 2px 8px;
        background: ' . $color . '; color: #fff; border-radius: 10px;
        font-size: 12px;">' . htmlspecialchars($label) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Ne İzlesem? - Anime Tracker</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        /* Page-local styles - intentionally inline so the page is
           self-contained and we do not pollute style.css with rules
           used in only one place. */
        .rec-intro {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 20px;
            color: #555;
        }
        .rec-sentence-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 8px 16px;
            max-width: 900px;
            margin: 0 auto 20px;
            padding: 0 20px;
        }
        .rec-sentence-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fafafa;
            cursor: pointer;
            transition: background 0.15s;
        }
        .rec-sentence-item:hover { background: #f0f0f0; }
        .rec-sentence-item input[type=checkbox] { margin: 0; }
        .rec-actions {
            text-align: center;
            margin: 20px 0 30px;
        }
        .rec-actions button, .rec-actions a {
            margin: 0 6px;
        }
        .rec-result-group {
            max-width: 900px;
            margin: 0 auto 24px;
            padding: 0 20px;
        }
        .rec-result-group h3 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 4px;
            margin-bottom: 12px;
        }
        .rec-anime-card {
            display: flex;
            gap: 12px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            background: #fff;
            transition: box-shadow 0.15s;
        }
        .rec-anime-card.watched {
            background: #f5f5f5;
            opacity: 0.85;
        }
        .rec-anime-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .rec-anime-cover {
            width: 70px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            flex-shrink: 0;
            background: #ddd;
        }
        .rec-anime-info {
            flex: 1;
            min-width: 0;
        }
        .rec-anime-title {
            font-weight: 600;
            font-size: 1.05em;
            margin-bottom: 4px;
        }
        .rec-anime-title a {
            color: #333;
            text-decoration: none;
        }
        .rec-anime-title a:hover { color: #007bff; }
        .rec-matched-tags {
            margin-top: 6px;
            font-size: 0.9em;
            color: #555;
        }
        .rec-matched-tag-pill {
            display: inline-block;
            padding: 1px 8px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 10px;
            font-size: 0.85em;
            margin-right: 4px;
            margin-bottom: 2px;
        }
        .rec-empty {
            text-align: center;
            padding: 30px;
            color: #888;
            font-style: italic;
        }
        .rec-surprise-card {
            max-width: 500px;
            margin: 0 auto;
            text-align: center;
            padding: 20px;
            border: 2px solid #007bff;
            border-radius: 10px;
        }
        .rec-surprise-card .rec-anime-cover {
            width: 150px;
            height: 220px;
            margin: 0 auto 12px;
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-section">
        <a href="about.php" class="about-link">Hakkinda</a>
    </div>
    <div class="page-title">Ne İzlesem?</div>

    <div class="button-container">
        <a class="anime-list-button" href="index.php">Anime Izleme Listesi</a>
    </div>
    <div class="button-spacing"></div>
    <div class="section-spacing"></div>

    <?php if ($mode === 'surprise' && $surpriseAnime): ?>
        <!-- ============================================================
             Surprise mode: a single random unwatched anime
             ============================================================ -->
        <div class="rec-surprise-card">
            <h2>Bugun bunu deneyelim:</h2>
            <?php if (!empty($surpriseAnime['image_path'])): ?>
                <img class="rec-anime-cover"
                     src="<?php echo htmlspecialchars($surpriseAnime['image_path']); ?>"
                     alt="">
            <?php endif; ?>
            <div class="rec-anime-title" style="font-size: 1.3em;">
                <a href="anime_details.php?id=<?php echo (int)$surpriseAnime['id']; ?>">
                    <?php echo htmlspecialchars($surpriseAnime['title']); ?>
                </a>
            </div>
            <div style="margin: 8px 0;">
                <?php echo watch_status_badge($surpriseAnime['watch_status']); ?>
            </div>
            <p style="color: #666; margin: 12px 0;">
                <?php
                $synopsis = $surpriseAnime['synopsis'] ?? '';
                if (mb_strlen($synopsis) > 200) {
                    $synopsis = mb_substr($synopsis, 0, 200) . '...';
                }
                echo htmlspecialchars($synopsis);
                ?>
            </p>
            <div class="rec-actions">
                <a href="recommendations.php?mode=surprise" class="anime-list-button">
                    <i class="fas fa-dice"></i> Baska Bir Tane
                </a>
                <a href="recommendations.php" class="anime-list-button">
                    <i class="fas fa-list"></i> Cumlelerden Sec
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================================
             Sentence mode: form + (optional) ranked results
             ============================================================ -->
        <p class="rec-intro">
            Sana uygun olabilecek cumleleri secip <strong>Oner</strong>'e bas.
            Cok cumle secersin diye sonuc daralmaz - her cumle bir kepce gibi
            kendi eslesmesini cekiyor, en cok kepceye dusen anime ust sirada.
        </p>

        <?php if (empty($allTags)): ?>
            <div class="rec-empty">
                Henuz cumle tanimlanmamis. Once
                <a href="manage_tags.php">cumleleri yonet</a> sayfasindan
                birkac cumle ekle, sonra anime'lere atamak icin
                <a href="add_anime.php">anime ekleme</a> veya duzenleme
                ekranini kullan.
            </div>
        <?php else: ?>
            <form method="get" action="recommendations.php">
                <!-- Search box: filters the sentence list as you type.
                     Prefix match, case-insensitive but Turkish-character
                     strict (u != u, i != i, c != c). Pure JS, no
                     network round-trip. Selected checkboxes keep their
                     state even when filtered out of view. Typing in the
                     search box also auto-opens the collapsed panel so
                     the user can see what they are filtering. -->
                <div style="text-align: center; margin: 0 auto 12px; max-width: 500px;">
                    <input type="text" id="rec-search" autocomplete="off"
                           placeholder="Cumle ara (yazinca daralir)..."
                           style="width: 100%; padding: 8px 12px; font-size: 1em;
                                  border: 1px solid #ccc; border-radius: 6px;">
                </div>

                <!-- Collapse toggle. Default state:
                     - Closed (initial visit, nothing selected)
                     - Open  (after a search has been submitted - user
                       wants to see what they picked and refine)
                     PHP decides the initial state, JS handles the toggle. -->
                <?php $panelOpen = !empty($selectedTagIds); ?>
                <div style="text-align: center; margin: 0 auto 12px;">
                    <button type="button" id="rec-toggle"
                            class="anime-list-button"
                            style="display: inline-block;">
                        <i class="fas fa-chevron-<?php echo $panelOpen ? 'up' : 'down'; ?>"
                           id="rec-toggle-icon"></i>
                        <span id="rec-toggle-label">
                            <?php echo $panelOpen ? 'Cumleleri Gizle' : 'Cumleleri Goster'; ?>
                        </span>
                        <span id="rec-toggle-count"
                              style="margin-left: 6px; opacity: 0.85;
                                     <?php echo empty($selectedTagIds) ? 'display:none;' : ''; ?>">
                            (<?php echo count($selectedTagIds); ?> secili)
                        </span>
                    </button>
                </div>

                <div class="rec-sentence-list" id="rec-sentence-list"
                     style="<?php echo $panelOpen ? '' : 'display: none;'; ?>">
                    <?php foreach ($allTags as $tag): ?>
                        <?php $checked = in_array((int)$tag['id'], $selectedTagIds, true); ?>
                        <label class="rec-sentence-item"
                               data-name="<?php echo htmlspecialchars(mb_strtolower($tag['name'], 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" name="tags[]"
                                   value="<?php echo (int)$tag['id']; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($tag['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p id="rec-search-empty" style="display: none; text-align: center;
                        color: #888; font-style: italic; margin: 10px 0;">
                    Bu metinle baslayan cumle bulunamadi.
                </p>

                <div class="rec-actions">
                    <button type="submit" class="anime-list-button">
                        <i class="fas fa-search"></i> Oner
                    </button>
                    <a href="recommendations.php?mode=surprise" class="anime-list-button">
                        <i class="fas fa-dice"></i> Surpriz Sec
                    </a>
                    <?php if (!empty($selectedTagIds)): ?>
                        <a href="recommendations.php" class="anime-list-button">
                            <i class="fas fa-times"></i> Temizle
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <script>
                /* Recommendation page client-side behaviour:
                 *   - Toggle the sentence panel (default state set by PHP).
                 *   - Live filter the panel as the user types in the
                 *     search box. Prefix match, strict Turkish characters
                 *     (u != u, i != i, c != c) so 'kil' does not match
                 *     'Kilic' if the user spelled it 'Kilic'.
                 *   - Typing in the search box auto-opens the panel.
                 *   - Keep the "(N secili)" counter in sync as the user
                 *     toggles checkboxes, even before they submit. */
                (function() {
                    const search    = document.getElementById('rec-search');
                    const list      = document.getElementById('rec-sentence-list');
                    const empty     = document.getElementById('rec-search-empty');
                    const toggleBtn = document.getElementById('rec-toggle');
                    const toggleIco = document.getElementById('rec-toggle-icon');
                    const toggleLbl = document.getElementById('rec-toggle-label');
                    const toggleCnt = document.getElementById('rec-toggle-count');
                    if (!search || !list || !toggleBtn) return;

                    const items = Array.from(list.querySelectorAll('.rec-sentence-item'));

                    function isPanelOpen() {
                        return list.style.display !== 'none';
                    }

                    function setPanelOpen(open) {
                        list.style.display = open ? '' : 'none';
                        toggleIco.classList.toggle('fa-chevron-up',   open);
                        toggleIco.classList.toggle('fa-chevron-down', !open);
                        toggleLbl.textContent = open ? 'Cumleleri Gizle' : 'Cumleleri Goster';
                        // Re-apply the search filter when reopening so the
                        // user does not see hidden items popping back in.
                        if (open) applyFilter();
                        else empty.style.display = 'none';
                    }

                    function applyFilter() {
                        if (!isPanelOpen()) return;
                        const q = search.value.toLowerCase().trim();
                        let visible = 0;
                        items.forEach(item => {
                            const name = item.dataset.name || '';
                            const match = (q === '' || name.startsWith(q));
                            item.style.display = match ? '' : 'none';
                            if (match) visible++;
                        });
                        empty.style.display = (visible === 0 && q !== '') ? '' : 'none';
                    }

                    function updateCount() {
                        const n = list.querySelectorAll('input[type=checkbox]:checked').length;
                        if (n > 0) {
                            toggleCnt.textContent = '(' + n + ' secili)';
                            toggleCnt.style.display = '';
                        } else {
                            toggleCnt.style.display = 'none';
                        }
                    }

                    toggleBtn.addEventListener('click', () => setPanelOpen(!isPanelOpen()));

                    search.addEventListener('input', () => {
                        // Auto-open the panel as soon as the user starts typing
                        if (!isPanelOpen() && search.value !== '') {
                            setPanelOpen(true);
                        }
                        applyFilter();
                    });

                    // Live counter as the user toggles checkboxes
                    list.addEventListener('change', e => {
                        if (e.target && e.target.type === 'checkbox') updateCount();
                    });

                    // Initial state from PHP
                    applyFilter();
                    updateCount();
                })();
            </script>
        <?php endif; ?>

        <?php if (!empty($selectedTagIds) && empty($results)): ?>
            <div class="rec-empty">
                Sectigin cumlelerle eslesen anime bulunamadi.
                Henuz hicbir animeye bu cumleler atanmamis olabilir -
                anime duzenleme ekranindan cumle eklemeyi unutma.
            </div>
        <?php endif; ?>

        <?php if (!empty($results)):
            // Group results by score so we can show clear "X cumle eslesti"
            // headers. Within each group anime are already sorted by the SQL
            // (unwatched first, then random tiebreak).
            $byScore = [];
            foreach ($results as $r) {
                $byScore[$r['score']][] = $r;
            }
            krsort($byScore); // highest score first
            $totalSelected = count($selectedTagIds);
        ?>
            <p class="rec-intro" style="margin-top: 30px;">
                <strong><?php echo count($results); ?></strong> anime bulundu
                (<?php echo $totalSelected; ?> cumle secildi).
            </p>

            <?php foreach ($byScore as $score => $group): ?>
                <div class="rec-result-group">
                    <h3>
                        <?php echo $score; ?> / <?php echo $totalSelected; ?> cumle eslesti
                        <span style="font-weight: normal; color: #888; font-size: 0.85em;">
                            (<?php echo count($group); ?> anime)
                        </span>
                    </h3>
                    <?php foreach ($group as $r):
                        $a = $r['anime'];
                        $isWatched = ($a['watch_status'] === 'Watched');
                    ?>
                        <div class="rec-anime-card<?php echo $isWatched ? ' watched' : ''; ?>">
                            <?php if (!empty($a['image_path'])): ?>
                                <img class="rec-anime-cover"
                                     src="<?php echo htmlspecialchars($a['image_path']); ?>"
                                     alt="">
                            <?php else: ?>
                                <div class="rec-anime-cover"></div>
                            <?php endif; ?>
                            <div class="rec-anime-info">
                                <div class="rec-anime-title">
                                    <a href="anime_details.php?id=<?php echo (int)$a['id']; ?>">
                                        <?php echo htmlspecialchars($a['title']); ?>
                                    </a>
                                </div>
                                <div>
                                    <?php echo watch_status_badge($a['watch_status']); ?>
                                    <?php if (!empty($a['media_type'])): ?>
                                        <span style="color: #888; font-size: 0.9em; margin-left: 6px;">
                                            <?php echo htmlspecialchars($a['media_type']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="rec-matched-tags">
                                    <?php foreach ($r['matched_tag_names'] as $tn): ?>
                                        <span class="rec-matched-tag-pill">
                                            <?php echo htmlspecialchars($tn); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
