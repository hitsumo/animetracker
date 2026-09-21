<?php

/**
 * Anime Tracker - Emotion Helpers (emotion key -> label / options / CSS class)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
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

/**
 * 1.1.45 - Toplu duygu dagilimi: bir animeye TUM uyelerin koydugu
 * isaretlerin sayimi. Detay sayfasindaki "N kisi isaretledi: ..." satiri
 * ve update_emotion.php'nin cevabi buradan beslenir.
 *
 * Yalniz cevrimici (MULTI_USER_MODE) modda anlamli: tek kullanicili
 * kurulumda dagilim = kullanicinin kendi isaretleri, ayni satirin
 * ustundeki dugmeler zaten onu gosterir. Cagiran taraf modu kontrol
 * eder; bu fonksiyon veriyi sayar, karar vermez.
 *
 * Sayimlar anonimdir - kim ne isaretledi buradan cikmaz; yalniz
 * isaret basina toplam ve kac farkli kisinin isaretledigi. Siralama:
 * en cok isaretlenen once, esitlikte emotion_options() sirasi (kararli
 * cikti; iki kisi ayni sayfayi ayni sirada gorur).
 *
 * @param PDO $pdo
 * @param int $animeId
 * @return array{voters:int, marks:int, items:array<int,array{emotion:string,count:int}>}
 */
function emotion_distribution(PDO $pdo, $animeId)
{
    $out = ['voters' => 0, 'marks' => 0, 'items' => []];
    $animeId = (int)$animeId;
    if ($animeId <= 0) {
        return $out;
    }

    try {
        // idx_anime her iki sorguyu da tasir.
        $stmt = $pdo->prepare(
            "SELECT emotion, COUNT(*) AS cnt
               FROM user_anime_emotion
              WHERE anime_id = ?
           GROUP BY emotion"
        );
        $stmt->execute([$animeId]);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // emotion => cnt

        $stmt = $pdo->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM user_anime_emotion WHERE anime_id = ?"
        );
        $stmt->execute([$animeId]);
        $out['voters'] = (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('[anime_tracker] emotion_distribution: ' . $e->getMessage());
        return $out;
    }

    // Kanonik sirayla gez: DB'de kalmis olabilecek tanimsiz bir deger
    // dagilima girmez (emotion_label onu ham gosterirdi).
    $order = 0;
    foreach (array_keys(emotion_options()) as $emotion) {
        if (empty($rows[$emotion])) {
            continue;
        }
        $out['items'][] = [
            'emotion' => $emotion,
            'count'   => (int)$rows[$emotion],
            '_order'  => $order++,
        ];
        $out['marks'] += (int)$rows[$emotion];
    }
    usort($out['items'], function ($a, $b) {
        if ($a['count'] !== $b['count']) {
            return $b['count'] - $a['count'];
        }
        return $a['_order'] - $b['_order'];
    });
    foreach ($out['items'] as &$item) {
        unset($item['_order']);
    }
    unset($item);

    return $out;
}

/**
 * 1.1.45 - emotion_distribution() ciktisini detay sayfasindaki satirin
 * IC HTML'ine cevirir. Tek cizim noktasi: sayfa ilk yuklenirken PHP,
 * bir isaret degisince update_emotion.php'nin cevabi ('distribution_html')
 * ayni fonksiyonu kullanir; JS yalnizca innerHTML degistirir, kendi
 * kopyasini cizmez (etiket cevirisi ve renk siniflari tek yerde kalir).
 *
 * Hic isaret yoksa bos dize doner; cagiran taraf kapsayiciyi bos birakir
 * (gizli). Iki uyeli bir kurulumda sayfalarin cogu 0'dir; "henuz kimse
 * isaretlemedi" satiri her sayfada gurultu olurdu.
 *
 * Cipler 0.6.1'in .emotion-badge-* siniflari (salt-okunur; o gunden beri
 * "detay sayfasi ozeti" icin ayrilmis, ilk kullanimi bu).
 *
 * @param array $dist emotion_distribution() ciktisi
 * @return string HTML (guvenli; tum metinler kacirilmis)
 */
function emotion_distribution_html(array $dist)
{
    if (empty($dist['items']) || (int)$dist['voters'] <= 0) {
        return '';
    }
    $voters = (int)$dist['voters'];
    $lead   = sprintf(t($voters === 1 ? 'anime_details.emotion.dist_one' : 'anime_details.emotion.dist_many'), $voters);

    $html = '<span class="emotion-dist-lead">' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . '</span> ';
    foreach ($dist['items'] as $item) {
        $html .= '<span class="emotion-badge emotion-badge-' . emotion_css_class($item['emotion']) . '">'
               . htmlspecialchars(emotion_label($item['emotion']), ENT_QUOTES, 'UTF-8')
               . ' <b>' . (int)$item['count'] . '</b></span>';
    }
    return $html;
}
