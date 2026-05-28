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
