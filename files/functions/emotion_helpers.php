<?php

/**
 * Anime Tracker - Emotion Helpers (emotion key -> label / options / CSS class)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

/**
 * Map an internal emotion value to its localized UI label.
 *
 * Internal values are ASCII Turkish identifiers per the v1 spec in
 * KARARLAR Bolum 8 (Huzunlendirdi, Heyecanlandirdi, Sikti, Guldurdu,
 * Korkuttu, Dusundurdu, Sasirti, Dinlendirdi, MotiveEtti). The UI
 * label adds Turkish diacritics back ("Huzunlendirdi" -> "Hüzünlendirdi")
 * and splits CamelCase ("MotiveEtti" -> "Motive Etti"). Same idea as
 * watch_status_label: the DB stores stable ASCII keys, the UI shows
 * proper Turkish.
 *
 * Falls back to $emotion itself if unmapped, so a stray DB value never
 * produces an empty cell.
 *
 * @param string $emotion ASCII internal value.
 * @param string $lang    'tr' (default) or 'en'.
 * @return string         Localized label, or $emotion itself if unmapped.
 */
function emotion_label($emotion, $lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    static $map = [
        'tr' => [
            'Huzunlendirdi'   => 'Hüzünlendirdi',
            'Heyecanlandirdi' => 'Heyecanlandırdı',
            'Sikti'           => 'Sıktı',
            'Guldurdu'        => 'Güldürdü',
            'Korkuttu'        => 'Korkuttu',
            'Dusundurdu'      => 'Düşündürdü',
            'Sasirti'         => 'Şaşırttı',
            'Dinlendirdi'     => 'Dinlendirdi',
            'MotiveEtti'      => 'Motive Etti',
        ],
        'en' => [
            'Huzunlendirdi'   => 'Saddened',
            'Heyecanlandirdi' => 'Excited',
            'Sikti'           => 'Bored',
            'Guldurdu'        => 'Made Me Laugh',
            'Korkuttu'        => 'Scared',
            'Dusundurdu'      => 'Thought-provoking',
            'Sasirti'         => 'Surprised',
            'Dinlendirdi'     => 'Relaxing',
            'MotiveEtti'      => 'Motivating',
        ],
    ];
    return $map[$lang][$emotion] ?? $emotion;
}

/**
 * Return the emotion options for a dropdown or checkbox set, in display
 * order.
 *
 * Order matches KARARLAR Bolum 8 v1 spec (the order the items were
 * decided in, with MotiveEtti appended last as it was added in the
 * 2nd vizyon session). The list itself is the single source of truth
 * for which emotions are valid; endpoints validate user input with
 * array_key_exists() against this map, the same way watch_status
 * endpoints validate their values.
 *
 * Use as:
 *   foreach (emotion_options() as $value => $label) {
 *       echo "<label><input type=\"checkbox\" name=\"emotion[]\" value=\"{$value}\">{$label}</label>";
 *   }
 *
 * For backend validation:
 *   $valid = emotion_options();
 *   if (!array_key_exists($posted_value, $valid)) {
 *       // reject - not in canonical list
 *   }
 *
 * @param string $lang 'tr' (default) or 'en'.
 * @return array       Associative array: ASCII value => localized label.
 */
function emotion_options($lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    $order = [
        'Huzunlendirdi',
        'Heyecanlandirdi',
        'Sikti',
        'Guldurdu',
        'Korkuttu',
        'Dusundurdu',
        'Sasirti',
        'Dinlendirdi',
        'MotiveEtti',
    ];
    $options = [];
    foreach ($order as $emotion) {
        $options[$emotion] = emotion_label($emotion, $lang);
    }
    return $options;
}

/**
 * Map an internal emotion value to a stable CSS class suffix.
 *
 * Stable, language-neutral, ASCII-clean. style.css targets these exact
 * suffixes (e.g. .emotion-huzunlendirdi, .emotion-motiveetti) so the UI
 * can colour each emotion distinctly without coupling the CSS to the
 * Turkish display label.
 *
 * Same pattern as watch_status_css_class: internal value -> lowercase
 * ASCII suffix, no prefix. Caller adds its own prefix (e.g. "emotion-").
 *
 *   Huzunlendirdi   -> huzunlendirdi
 *   Heyecanlandirdi -> heyecanlandirdi
 *   Sikti           -> sikti
 *   Guldurdu        -> guldurdu
 *   Korkuttu        -> korkuttu
 *   Dusundurdu      -> dusundurdu
 *   Sasirti         -> sasirti
 *   Dinlendirdi     -> dinlendirdi
 *   MotiveEtti      -> motiveetti
 *
 * Unknown values fall back to 'unknown' so a stray DB value never
 * produces an empty class attribute.
 *
 * @param string $emotion ASCII internal value.
 * @return string         CSS suffix (no prefix).
 */
function emotion_css_class($emotion) {
    static $map = [
        'Huzunlendirdi'   => 'huzunlendirdi',
        'Heyecanlandirdi' => 'heyecanlandirdi',
        'Sikti'           => 'sikti',
        'Guldurdu'        => 'guldurdu',
        'Korkuttu'        => 'korkuttu',
        'Dusundurdu'      => 'dusundurdu',
        'Sasirti'         => 'sasirti',
        'Dinlendirdi'     => 'dinlendirdi',
        'MotiveEtti'      => 'motiveetti',
    ];
    return $map[$emotion] ?? 'unknown';
}

/**
 * Import a list of emotion marks for one (user, anime). Used by the list
 * import in list_settings.php so a user's emotional marks travel with the
 * rest of their personal data (watch state, notes) when moving a list
 * between installs.
 *
 * Rules (mirror update_emotion.php server-side):
 *   - Only canonical values (emotion_options() keys) are accepted; anything
 *     else is silently skipped.
 *   - Duplicates within the payload are collapsed.
 *   - Inserts are idempotent: INSERT IGNORE on the
 *     (user_id, anime_id, emotion) primary key, so re-importing the same
 *     file never errors or duplicates.
 *   - Hard cap of 3 marks per (user, anime). Existing marks count toward
 *     the cap and are preserved.
 *
 * @param PDO   $pdo
 * @param int   $userId
 * @param int   $animeId
 * @param array $emotions  ASCII emotion values (emotion_options() keys)
 * @return int             number of NEW marks inserted
 */
function emotion_import_set(PDO $pdo, $userId, $animeId, $emotions)
{
    if (!is_array($emotions) || empty($emotions)) {
        return 0;
    }
    $userId  = (int)$userId;
    $animeId = (int)$animeId;
    if ($userId <= 0 || $animeId <= 0) {
        return 0;
    }

    $canonical = emotion_options();

    // Existing marks count toward the cap of 3.
    $countStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM user_anime_emotion WHERE user_id = ? AND anime_id = ?"
    );
    $countStmt->execute([$userId, $animeId]);
    $total = (int)$countStmt->fetchColumn();

    $insert = $pdo->prepare(
        "INSERT IGNORE INTO user_anime_emotion (user_id, anime_id, emotion)
         VALUES (?, ?, ?)"
    );

    $added = 0;
    $seen  = [];
    foreach ($emotions as $emotion) {
        if ($total >= 3) {
            break; // cap reached
        }
        $emotion = is_string($emotion) ? trim($emotion) : '';
        if ($emotion === '' || !array_key_exists($emotion, $canonical)) {
            continue; // not a canonical emotion
        }
        if (isset($seen[$emotion])) {
            continue; // duplicate within this payload
        }
        $seen[$emotion] = true;

        $insert->execute([$userId, $animeId, $emotion]);
        if ($insert->rowCount() > 0) {
            $added++;
            $total++;
        }
    }
    return $added;
}
