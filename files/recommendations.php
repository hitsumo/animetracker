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
 *   - Each sentence (tag) is a bucket. Each emotion mark is also a
 *     bucket (added in 0.6.5).
 *   - When the user picks several buckets, each bucket dips into the
 *     pool of all anime and pulls out the matching rows.
 *   - The buckets merge into a single result. An anime that was pulled
 *     by 3 buckets ranks above one pulled by only 1 (OR + score, not
 *     AND - so the result is never empty).
 *   - Within the same score band, anime the user has not finished are
 *     surfaced first (discovery > rewatching).
 *
 * Tag and emotion buckets are run as two SEPARATE SQL queries and
 * merged in PHP. Cross-JOINing tags and emotions in a single query
 * produces a Cartesian-product COUNT inflation that is hard to undo
 * cleanly; running them apart and adding scores client-side is
 * straightforward and keeps each query simple.
 *
 * Two entry points:
 *   - Pick sentences and/or emotions + "Oner" -> ranked list grouped
 *     by combined score
 *   - "Surpriz Sec" -> a single random anime from the unwatched pool
 *     (no criteria required - quick coin flip)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Sayfa dilini baslat (i18n)
lang_init($pdo);

// English-title display preference (0.7.2). display_title() uses this for
// the surprise card and result cards below.
title_pref_init($pdo);

// --------------------------------------------------------
// Load every available sentence so the form can render checkboxes.
// Emotion options come from emotion_options() helper (functions.php
// satir 203), no DB fetch needed - the canonical list is hardcoded.
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

// 0.6.5 - emotion filter input. Validate against canonical
// emotion_options() keys to reject anything the user might inject
// via URL manipulation. The keys are ASCII identifiers
// (Huzunlendirdi, Heyecanlandirdi, ...) - see functions.php.
$selectedEmotions = [];
if (!empty($_GET['emotions']) && is_array($_GET['emotions'])) {
    $canonicalEmotions = emotion_options();
    foreach ($_GET['emotions'] as $em) {
        $em = (string)$em;
        if (array_key_exists($em, $canonicalEmotions) && !in_array($em, $selectedEmotions, true)) {
            $selectedEmotions[] = $em;
        }
    }
}

$mode = $_GET['mode'] ?? 'pick';   // 'pick' (criterion-driven) or 'surprise'

// --------------------------------------------------------
// Compute the result set.
// --------------------------------------------------------
$results = [];      // list of [anime, score, matched_tag_names, matched_emotion_names]
$surpriseAnime = null;
$uid = current_user_id();  // personal watch state is user_anime-scoped (1.0.1)

if ($mode === 'surprise') {
    // Surprise mode: pick one random anime the user has not yet watched.
    // Falls back to any anime if the unwatched pool is empty.
    // watch_status is personal (user_anime, 1.0.1): join the current user's
    // row, default un-tracked animes to PlanToWatch, and exclude only what
    // THIS user has watched.
    $stmt = $pdo->prepare(
        "SELECT a.*,
                COALESCE(ua.watch_status, 'PlanToWatch') AS watch_status,
                COALESCE(ua.watched_episodes, 0) AS watched_episodes
         FROM animes a
         LEFT JOIN user_anime ua
                ON ua.anime_id = a.id AND ua.user_id = :uid
         WHERE COALESCE(ua.watch_status, 'PlanToWatch') != 'Watched'
         ORDER BY RAND()
         LIMIT 1"
    );
    $stmt->execute([':uid' => $uid]);
    $surpriseAnime = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$surpriseAnime) {
        // Everything is watched - just pick something at random
        $stmt = $pdo->prepare(
            "SELECT a.*,
                    COALESCE(ua.watch_status, 'PlanToWatch') AS watch_status,
                    COALESCE(ua.watched_episodes, 0) AS watched_episodes
             FROM animes a
             LEFT JOIN user_anime ua
                    ON ua.anime_id = a.id AND ua.user_id = :uid
             ORDER BY RAND() LIMIT 1"
        );
        $stmt->execute([':uid' => $uid]);
        $surpriseAnime = $stmt->fetch(PDO::FETCH_ASSOC);
    }

} elseif (!empty($selectedTagIds) || !empty($selectedEmotions)) {
    // Two-pass strategy: run tag query and emotion query separately,
    // accumulate per-anime totals in $byAnimeId, then sort in PHP.
    // This keeps each SQL simple and avoids COUNT inflation that would
    // come from a single JOIN over both anime_tags and user_anime_emotion.
    $byAnimeId = []; // anime_id => entry

    // ---- Pass 1: tag (cumle) bucket ----
    if (!empty($selectedTagIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedTagIds), '?'));
        $sql = "
            SELECT a.*,
                   COUNT(DISTINCT at.tag_id) AS tag_score,
                   GROUP_CONCAT(DISTINCT CONCAT(t.name, CHAR(31), COALESCE(t.name_en, ''))
                       ORDER BY CONCAT(t.name, CHAR(31), COALESCE(t.name_en, '')) SEPARATOR '|') AS matched_tags
            FROM animes a
            INNER JOIN anime_tags at ON at.anime_id = a.id
            INNER JOIN tags t ON t.id = at.tag_id
            WHERE at.tag_id IN ($placeholders)
            GROUP BY a.id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($selectedTagIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $animeId = (int)$row['id'];
            // matched_tags packs each matched sentence as name + name_en,
            // joined by US (CHAR 31), tags separated by '|'. Rebuild row
            // arrays so tag_display_name() can pick the language (0.7.2).
            $matchedTags = [];
            if (!empty($row['matched_tags'])) {
                foreach (explode('|', $row['matched_tags']) as $packed) {
                    $parts = explode("\x1f", $packed, 2);
                    $matchedTags[] = [
                        'name'    => $parts[0],
                        'name_en' => $parts[1] ?? '',
                    ];
                }
            }
            $byAnimeId[$animeId] = [
                'anime'                 => $row,
                'tag_score'             => (int)$row['tag_score'],
                'emo_score'             => 0,
                'matched_tag_names'     => $matchedTags,
                'matched_emotion_names' => [],
            ];
        }
    }

    // ---- Pass 2: emotion bucket ----
    // Scoped to current_user_id() (1.0.x data model). The user id is bound
    // as the first positional placeholder, before the emotion list, so the
    // bind order matches the WHERE clause. Single-user mode returns 1
    // (behaviour unchanged); multi-user mode returns the session user.
    if (!empty($selectedEmotions)) {
        $eph = implode(',', array_fill(0, count($selectedEmotions), '?'));
        $sql = "
            SELECT a.*,
                   COUNT(DISTINCT uae.emotion) AS emo_score,
                   GROUP_CONCAT(DISTINCT uae.emotion ORDER BY uae.emotion SEPARATOR '|') AS matched_emos
            FROM animes a
            INNER JOIN user_anime_emotion uae ON uae.anime_id = a.id
            WHERE uae.user_id = ? AND uae.emotion IN ($eph)
            GROUP BY a.id
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([current_user_id()], $selectedEmotions));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $animeId = (int)$row['id'];
            $matchedEmos = !empty($row['matched_emos']) ? explode('|', $row['matched_emos']) : [];
            if (isset($byAnimeId[$animeId])) {
                // Already in result set from tag pass - augment with emotion data
                $byAnimeId[$animeId]['emo_score']             = (int)$row['emo_score'];
                $byAnimeId[$animeId]['matched_emotion_names'] = $matchedEmos;
            } else {
                // Emotion-only match (no tag bucket touched this anime)
                $byAnimeId[$animeId] = [
                    'anime'                 => $row,
                    'tag_score'             => 0,
                    'emo_score'             => (int)$row['emo_score'],
                    'matched_tag_names'     => [],
                    'matched_emotion_names' => $matchedEmos,
                ];
            }
        }
    }

    // Personal watch state lives in user_anime per user (1.0.1). The tag /
    // emotion passes above SELECT a.* (which still carries the vestigial
    // animes columns), so overlay the current user's watch_status /
    // watched_episodes onto each result row here. Done in PHP to avoid
    // pulling ua.* through the GROUP BY queries (and any ONLY_FULL_GROUP_BY
    // concerns on the online MySQL host).
    foreach ($byAnimeId as $aid => &$entry) {
        $ua = ua_get_state($pdo, $uid, (int)$aid);
        $entry['anime']['watch_status']     = $ua['watch_status'];
        $entry['anime']['watched_episodes'] = $ua['watched_episodes'];
    }
    unset($entry);

    // ---- Build $results with combined score + sort ----
    // Tie-break with a stable random integer attached up front so the
    // usort comparator stays deterministic during the sort pass (PHP 8
    // requires comparators to be transitive). Refreshing the page
    // generates a new random number and a new shuffle.
    foreach ($byAnimeId as $entry) {
        $entry['score']    = $entry['tag_score'] + $entry['emo_score'];
        $entry['_random']  = mt_rand();
        $results[] = $entry;
    }

    usort($results, function ($a, $b) {
        // 1. Higher combined score first
        if ($a['score'] !== $b['score']) {
            return $b['score'] - $a['score'];
        }
        // 2. Unwatched before watched (discovery beats rewatching)
        $aw = ($a['anime']['watch_status'] === 'Watched') ? 1 : 0;
        $bw = ($b['anime']['watch_status'] === 'Watched') ? 1 : 0;
        if ($aw !== $bw) {
            return $aw - $bw;
        }
        // 3. Random tie-break (precomputed for stable comparator)
        return $a['_random'] - $b['_random'];
    });
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

// 0.6.5 - check whether the user has marked any anime with any emotion.
// If not, the emotion filter section would be useless (zero results),
// so we render a hint instead of the panel. Done with a cheap COUNT
// query rather than fetching rows.
$hasAnyEmotionMarksStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM user_anime_emotion WHERE user_id = :uid"
);
$hasAnyEmotionMarksStmt->execute([':uid' => current_user_id()]);
$hasAnyEmotionMarks = (int)$hasAnyEmotionMarksStmt->fetchColumn() > 0;

// Pre-compute selection counts for the result/header templates.
$totalTagsSelected     = count($selectedTagIds);
$totalEmotionsSelected = count($selectedEmotions);
$useCombinedTemplates  = ($totalEmotionsSelected > 0);
?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars(t('recommendations.page_title'), ENT_QUOTES, 'UTF-8'); ?></title>
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

        /* 0.6.5 - emotion selection panel. Different layout from the
           sentence list: 9 fixed items, no search, badge-styled labels
           that reuse the .emotion-badge-* color classes from
           style.css (defined in 0.6.1). Checked state increases opacity
           via :has() - graceful degradation on browsers without :has()
           leaves the checkbox visible for selection feedback. */
        .rec-emotion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 12px;
            max-width: 700px;
            margin: 0 auto 16px;
            padding: 0 20px;
            justify-content: center;
        }
        .rec-emotion-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            cursor: pointer;
        }
        .rec-emotion-item input[type=checkbox] { margin: 0; }
        .rec-emotion-item:not(:has(:checked)) .emotion-badge {
            opacity: 0.55;
        }
        .rec-emotion-empty-hint {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 16px;
            padding: 10px 14px;
            background: #f8f9fa;
            border: 1px dashed #c0c4cc;
            border-radius: 6px;
            color: #666;
            font-size: 0.92em;
        }

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
        /* 0.6.5 - row showing matched emotion badges on result cards.
           Reuses .emotion-badge-* color classes; only the wrapper is
           page-local. */
        .rec-matched-emotions {
            margin-top: 6px;
            font-size: 0.9em;
            color: #555;
        }
        .rec-matched-label {
            color: #777;
            font-size: 0.82em;
            display: inline-block;
            margin-right: 4px;
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
        <a href="about.php" class="about-link"><?php echo htmlspecialchars(t('nav.about'), ENT_QUOTES, 'UTF-8'); ?></a>

        <?php echo auth_nav_links(); ?>
        <div class="lang-switcher" role="group" aria-label="<?php echo htmlspecialchars(t('lang.aria_label'), ENT_QUOTES, 'UTF-8'); ?>">
            <form method="POST" action="set_language.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="lang" value="tr">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'recommendations.php', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="lang-switch<?php echo current_lang() === 'tr' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.tr_label'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
            <form method="POST" action="set_language.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="lang" value="en">
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'recommendations.php', ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit" class="lang-switch<?php echo current_lang() === 'en' ? ' lang-switch-active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en_label'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </div>
    </div>
    <div class="page-title"><?php echo htmlspecialchars(t('recommendations.heading'), ENT_QUOTES, 'UTF-8'); ?></div>

    <div class="button-container">
        <a class="anime-list-button" href="index.php"><?php echo htmlspecialchars(t('index.list_title'), ENT_QUOTES, 'UTF-8'); ?></a>
    </div>
    <div class="button-spacing"></div>
    <div class="section-spacing"></div>

    <?php if ($mode === 'surprise' && $surpriseAnime): ?>
        <!-- ============================================================
             Surprise mode: a single random unwatched anime
             ============================================================ -->
        <div class="rec-surprise-card">
            <h2><?php echo htmlspecialchars(t('recommendations.surprise.heading'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if (!empty($surpriseAnime['image_path'])): ?>
                <img class="rec-anime-cover"
                     src="<?php echo htmlspecialchars($surpriseAnime['image_path']); ?>"
                     alt="">
            <?php endif; ?>
            <div class="rec-anime-title" style="font-size: 1.3em;">
                <a href="anime_details.php?id=<?php echo (int)$surpriseAnime['id']; ?>">
                    <?php echo htmlspecialchars(display_title($surpriseAnime)); ?>
                </a>
            </div>
            <div style="margin: 8px 0;">
                <?php echo watch_status_badge($surpriseAnime['watch_status']); ?>
            </div>
            <p style="color: #666; margin: 12px 0;">
                <?php
                // Pick the synopsis by UI language: English mode prefers
                // synopsis_en and falls back to synopsis_tr; Turkish mode
                // uses synopsis_tr. The legacy synopsis column is not read.
                if (current_lang() === 'en') {
                    $synopsis = ($surpriseAnime['synopsis_en'] ?? '') !== ''
                        ? $surpriseAnime['synopsis_en']
                        : ($surpriseAnime['synopsis_tr'] ?? '');
                } else {
                    $synopsis = $surpriseAnime['synopsis_tr'] ?? '';
                }
                if (mb_strlen($synopsis) > 200) {
                    $synopsis = mb_substr($synopsis, 0, 200) . '...';
                }
                echo htmlspecialchars($synopsis);
                ?>
            </p>
            <div class="rec-actions">
                <a href="recommendations.php?mode=surprise" class="anime-list-button">
                    <i class="fas fa-dice"></i> <?php echo htmlspecialchars(t('recommendations.surprise.try_another'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <a href="recommendations.php" class="anime-list-button">
                    <i class="fas fa-list"></i> <?php echo htmlspecialchars(t('recommendations.surprise.choose_sentences'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================================
             Criteria mode: form + (optional) ranked results
             ============================================================ -->
        <p class="rec-intro">
            <?php echo t('recommendations.intro'); ?>
        </p>

        <?php if (empty($allTags)): ?>
            <div class="rec-empty">
                <?php echo t('recommendations.no_tags_empty'); ?>
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
                           placeholder="<?php echo htmlspecialchars(t('recommendations.search.placeholder'), ENT_QUOTES, 'UTF-8'); ?>"
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
                            <?php echo htmlspecialchars($panelOpen ? t('recommendations.toggle.hide') : t('recommendations.toggle.show'), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                        <span id="rec-toggle-count"
                              style="margin-left: 6px; opacity: 0.85;
                                     <?php echo empty($selectedTagIds) ? 'display:none;' : ''; ?>">
                            <?php echo htmlspecialchars(sprintf(t('recommendations.toggle.count_selected'), count($selectedTagIds)), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </button>
                </div>

                <div class="rec-sentence-list" id="rec-sentence-list"
                     style="<?php echo $panelOpen ? '' : 'display: none;'; ?>">
                    <?php foreach ($allTags as $tag): ?>
                        <?php $checked = in_array((int)$tag['id'], $selectedTagIds, true); ?>
                        <?php $tagLabel = tag_display_name($tag); ?>
                        <label class="rec-sentence-item"
                               data-name="<?php echo htmlspecialchars(mb_strtolower($tagLabel, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="checkbox" name="tags[]"
                                   value="<?php echo (int)$tag['id']; ?>"
                                   <?php echo $checked ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars($tagLabel); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p id="rec-search-empty" style="display: none; text-align: center;
                        color: #888; font-style: italic; margin: 10px 0;">
                    <?php echo htmlspecialchars(t('recommendations.search.empty_state'), ENT_QUOTES, 'UTF-8'); ?>
                </p>

                <!-- ====================================================
                     0.6.5 - Emotion section (parallel to sentence panel).
                     No search box (only 9 fixed emotions). Same collapse
                     toggle pattern. Badge-styled labels reuse the 0.6.1
                     .emotion-badge-* color classes from style.css.
                     ==================================================== -->
                <?php if (!$hasAnyEmotionMarks): ?>
                    <div class="rec-emotion-empty-hint">
                        <i class="fas fa-info-circle"></i>
                        <?php echo htmlspecialchars(t('recommendations.emotion.empty_marks'), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php else: ?>
                    <?php $emotionPanelOpen = !empty($selectedEmotions); ?>
                    <div style="text-align: center; margin: 0 auto 12px;">
                        <button type="button" id="rec-emotion-toggle"
                                class="anime-list-button"
                                style="display: inline-block;">
                            <i class="fas fa-chevron-<?php echo $emotionPanelOpen ? 'up' : 'down'; ?>"
                               id="rec-emotion-toggle-icon"></i>
                            <span id="rec-emotion-toggle-label">
                                <?php echo htmlspecialchars($emotionPanelOpen ? t('recommendations.emotion.toggle.hide') : t('recommendations.emotion.toggle.show'), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span id="rec-emotion-toggle-count"
                                  style="margin-left: 6px; opacity: 0.85;
                                         <?php echo empty($selectedEmotions) ? 'display:none;' : ''; ?>">
                                <?php echo htmlspecialchars(sprintf(t('recommendations.emotion.toggle.count_selected'), count($selectedEmotions)), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </button>
                    </div>

                    <div class="rec-emotion-list" id="rec-emotion-list"
                         style="<?php echo $emotionPanelOpen ? '' : 'display: none;'; ?>">
                        <?php foreach (emotion_options() as $emoValue => $emoLabel):
                            $emoChecked = in_array($emoValue, $selectedEmotions, true);
                        ?>
                            <label class="rec-emotion-item">
                                <input type="checkbox" name="emotions[]"
                                       value="<?php echo htmlspecialchars($emoValue, ENT_QUOTES, 'UTF-8'); ?>"
                                       <?php echo $emoChecked ? 'checked' : ''; ?>>
                                <span class="emotion-badge emotion-badge-<?php echo emotion_css_class($emoValue); ?>">
                                    <?php echo htmlspecialchars($emoLabel); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="rec-actions">
                    <button type="submit" class="anime-list-button">
                        <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('recommendations.btn.recommend'), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <a href="recommendations.php?mode=surprise" class="anime-list-button">
                        <i class="fas fa-dice"></i> <?php echo htmlspecialchars(t('recommendations.btn.surprise'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <?php if (!empty($selectedTagIds) || !empty($selectedEmotions)): ?>
                        <a href="recommendations.php" class="anime-list-button">
                            <i class="fas fa-times"></i> <?php echo htmlspecialchars(t('recommendations.btn.clear'), ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <script>
                /* PHP-side strings exposed to JS (i18n) */
                const LANG = <?php echo json_encode([
                    'toggle_show'                     => t('recommendations.toggle.show'),
                    'toggle_hide'                     => t('recommendations.toggle.hide'),
                    'count_selected_template'         => t('recommendations.toggle.count_selected'),
                    'emotion_toggle_show'             => t('recommendations.emotion.toggle.show'),
                    'emotion_toggle_hide'             => t('recommendations.emotion.toggle.hide'),
                    'emotion_count_selected_template' => t('recommendations.emotion.toggle.count_selected'),
                ], JSON_UNESCAPED_UNICODE); ?>;

                /* Recommendation page client-side behaviour:
                 *   - Toggle the sentence panel (default state set by PHP).
                 *   - Live filter the panel as the user types in the
                 *     search box. Prefix match, strict Turkish characters
                 *     (u != u, i != i, c != c) so 'kil' does not match
                 *     'Kilic' if the user spelled it 'Kilic'.
                 *   - Typing in the search box auto-opens the panel.
                 *   - Keep the "(N secili)" counter in sync as the user
                 *     toggles checkboxes, even before they submit.
                 *   - 0.6.5 - same pattern repeated for the emotion panel
                 *     (separate IIFE, no search box - only 9 fixed items). */
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
                        toggleLbl.textContent = open ? LANG.toggle_hide : LANG.toggle_show;
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
                            toggleCnt.textContent = LANG.count_selected_template.replace('%d', n);
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

                /* 0.6.5 - Emotion panel client-side behaviour. Same toggle
                 * and counter pattern as the sentence panel; no search box
                 * (only 9 fixed emotions, search would be useless). */
                (function() {
                    const emoList   = document.getElementById('rec-emotion-list');
                    const emoBtn    = document.getElementById('rec-emotion-toggle');
                    const emoIco    = document.getElementById('rec-emotion-toggle-icon');
                    const emoLbl    = document.getElementById('rec-emotion-toggle-label');
                    const emoCnt    = document.getElementById('rec-emotion-toggle-count');
                    if (!emoList || !emoBtn) return;

                    function isEmoPanelOpen() {
                        return emoList.style.display !== 'none';
                    }

                    function setEmoPanelOpen(open) {
                        emoList.style.display = open ? '' : 'none';
                        emoIco.classList.toggle('fa-chevron-up',   open);
                        emoIco.classList.toggle('fa-chevron-down', !open);
                        emoLbl.textContent = open ? LANG.emotion_toggle_hide : LANG.emotion_toggle_show;
                    }

                    function updateEmoCount() {
                        const n = emoList.querySelectorAll('input[type=checkbox]:checked').length;
                        if (n > 0) {
                            emoCnt.textContent = LANG.emotion_count_selected_template.replace('%d', n);
                            emoCnt.style.display = '';
                        } else {
                            emoCnt.style.display = 'none';
                        }
                    }

                    emoBtn.addEventListener('click', () => setEmoPanelOpen(!isEmoPanelOpen()));

                    emoList.addEventListener('change', e => {
                        if (e.target && e.target.type === 'checkbox') updateEmoCount();
                    });

                    updateEmoCount();
                })();
            </script>
        <?php endif; ?>

        <?php if ((!empty($selectedTagIds) || !empty($selectedEmotions)) && empty($results)): ?>
            <div class="rec-empty">
                <?php if ($useCombinedTemplates): ?>
                    <?php echo t('recommendations.no_match_combined'); ?>
                <?php else: ?>
                    <?php echo t('recommendations.no_match'); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)):
            // Group results by combined score so we can show clear
            // "X criteria matched" headers. Within each group anime are
            // already sorted by usort (unwatched first, random tiebreak).
            $byScore = [];
            foreach ($results as $r) {
                $byScore[$r['score']][] = $r;
            }
            krsort($byScore); // highest score first
        ?>
            <p class="rec-intro" style="margin-top: 30px;">
                <?php if ($useCombinedTemplates): ?>
                    <?php echo sprintf(t('recommendations.result.count_combined'), count($results), $totalTagsSelected, $totalEmotionsSelected); ?>
                <?php else: ?>
                    <?php echo sprintf(t('recommendations.result.count'), count($results), $totalTagsSelected); ?>
                <?php endif; ?>
            </p>

            <?php foreach ($byScore as $score => $group): ?>
                <div class="rec-result-group">
                    <h3>
                        <?php if ($useCombinedTemplates): ?>
                            <?php echo htmlspecialchars(sprintf(t('recommendations.group.matched_combined'), $score), ENT_QUOTES, 'UTF-8'); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars(sprintf(t('recommendations.group.matched'), $score, $totalTagsSelected), ENT_QUOTES, 'UTF-8'); ?>
                        <?php endif; ?>
                        <span style="font-weight: normal; color: #888; font-size: 0.85em;">
                            <?php echo htmlspecialchars(sprintf(t('recommendations.group.count_suffix'), count($group)), ENT_QUOTES, 'UTF-8'); ?>
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
                                        <?php echo htmlspecialchars(display_title($a)); ?>
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
                                <?php if (!empty($r['matched_tag_names'])): ?>
                                    <div class="rec-matched-tags">
                                        <?php foreach ($r['matched_tag_names'] as $tn): ?>
                                            <span class="rec-matched-tag-pill">
                                                <?php echo htmlspecialchars(tag_display_name($tn)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($r['matched_emotion_names'])): ?>
                                    <!-- 0.6.5 - matched emotion badges. Reuses
                                         the 0.6.1 .emotion-badge-* color classes.
                                         emotion_label() applies diacritics for
                                         display (e.g. Huzunlendirdi -> Huzunlendirdi
                                         in the current locale). -->
                                    <div class="rec-matched-emotions">
                                        <span class="rec-matched-label">
                                            <?php echo htmlspecialchars(t('recommendations.matched.emotion_prefix'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <?php foreach ($r['matched_emotion_names'] as $em): ?>
                                            <span class="emotion-badge emotion-badge-<?php echo emotion_css_class($em); ?>">
                                                <?php echo htmlspecialchars(emotion_label($em)); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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
