<?php

/**
  [Anime Tracker/Anime izleme takip listesi.
    https://www.sicakcikolata.com]
  Copyright (C) 2025 [Okan Sümer]
 
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
 * Map an internal watch_status ENUM value to a user-facing label.
 *
 * Since 0.6, the DB enum stores ASCII values ('Watched', 'Watching',
 * 'PlanToWatch', 'OnHold'). The user-facing UI text remains Turkish.
 * This helper is the single source of truth for the translation.
 *
 * Adding a new language: extend the $map with a new lang key.
 * Adding a new status:   add an entry under each lang.
 *
 * Falls back to the raw status if the value is unknown (defensive -
 * a stray enum value never produces an empty cell).
 *
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold'.
 * @param string $lang   'tr' (default) or 'en'.
 * @return string        Localized label, or $status itself if unmapped.
 */
function watch_status_label($status, $lang = null) {
    // Default to the active UI language. Passing $lang explicitly still
    // overrides this - useful for tests, admin scripts, or any spot that
    // needs a specific language regardless of the user's UI choice.
    if ($lang === null) {
        $lang = current_lang();
    }
    static $map = [
        'tr' => [
            'Watched'     => 'İzlendi',
            'Watching'    => 'İzleniyor',
            'PlanToWatch' => 'İzlenme Planlandı',
            'OnHold'      => 'İzleme Ertelendi',
        ],
        'en' => [
            'Watched'     => 'Watched',
            'Watching'    => 'Watching',
            'PlanToWatch' => 'Plan to Watch',
            'OnHold'      => 'On Hold',
        ],
    ];
    return $map[$lang][$status] ?? $status;
}

/**
 * Return the watch_status options for a dropdown, in display order.
 *
 * Order: Watched, Watching, PlanToWatch, OnHold.
 *   - First three preserve the existing UI order from 0.5.x (filter
 *     dropdown in index.php was Watched / Watching / PlanToWatch).
 *   - OnHold is appended at the end as the newest, least-used value -
 *     keeps existing user muscle memory intact.
 *
 * Use as:
 *   foreach (watch_status_options() as $value => $label) {
 *       echo "<option value=\"{$value}\">{$label}</option>";
 *   }
 *
 * @param string $lang 'tr' (default) or 'en'.
 * @return array       Associative array: ASCII value => localized label.
 */
function watch_status_options($lang = null) {
    if ($lang === null) {
        $lang = current_lang();
    }
    $order = ['Watched', 'Watching', 'PlanToWatch', 'OnHold'];
    $options = [];
    foreach ($order as $status) {
        $options[$status] = watch_status_label($status, $lang);
    }
    return $options;
}

/**
 * Map an internal watch_status ENUM value to a stable CSS class suffix.
 *
 * Pre-0.6, classes were built ad-hoc from the TR enum value via
 * strtolower(str_replace(' ', '-', $status)), which produced names with
 * the Turkish "ı" character (e.g. ws-izlenme-planlandı). The 0.6 ASCII
 * migration moved DB values to English, which would now produce names
 * like ws-watched / ws-plantowatch - English in the markup and a clash
 * with the KARARLAR Bolum 1 convention "UI Turkish, internals English".
 *
 * Resolution: a stable, language-neutral suffix per enum value. CSS in
 * style.css (0.6 adim 8) targets these exact names. The suffix is also
 * ASCII-clean and case-insensitive friendly.
 *
 *   Watched     -> watched
 *   Watching    -> watching
 *   PlanToWatch -> plantowatch
 *   OnHold      -> onhold
 *
 * Unknown values fall back to 'unknown' so a stray DB value never
 * produces an empty class attribute.
 *
 * @param string $status One of 'Watched', 'Watching', 'PlanToWatch', 'OnHold'.
 * @return string        CSS suffix (no prefix). Caller adds its own prefix.
 */
function watch_status_css_class($status) {
    static $map = [
        'Watched'     => 'watched',
        'Watching'    => 'watching',
        'PlanToWatch' => 'plantowatch',
        'OnHold'      => 'onhold',
    ];
    return $map[$status] ?? 'unknown';
}

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

// =====================================================================
// SECTION: Generic application settings (key-value store)
// ---------------------------------------------------------------------
// The settings table is a generic name -> value store (see schema.sql).
// Until 0.6.2 each caller wrote its own SELECT / INSERT against it,
// which meant the INSERT ... ON DUPLICATE KEY UPDATE pattern was
// repeated in several places. These two helpers collect that into one
// spot so future callers (including the i18n layer below) do not have
// to know the SQL shape.
//
// Existing direct-SQL callers (check_update.php, list_settings.php,
// update.php, the last_aired_sync writer in this file) are intentionally
// left alone in this release - changing them is a separate cleanup pass
// and the helpers + the legacy code coexist without conflict because
// they both speak the same INSERT ... ON DUPLICATE pattern.
// =====================================================================

/**
 * Read a value from the settings key-value table.
 *
 * Returns the stored string for $name, or $default if the row does not
 * exist. Treats DB errors as "row absent" - a transient read failure
 * should never crash a page, and the caller is expected to supply a
 * safe default (e.g. 'tr' for display_language).
 *
 * @param PDO         $pdo
 * @param string      $name     Settings key (e.g. 'version', 'display_language').
 * @param string|null $default  Returned when the row is absent.
 * @return string|null
 */
function get_setting($pdo, $name, $default = null) {
    try {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return $default;
        }
        return $row['value'];
    } catch (PDOException $e) {
        error_log('[anime_tracker] get_setting(' . $name . '): ' . $e->getMessage());
        return $default;
    }
}

/**
 * Write a value to the settings key-value table.
 *
 * Uses INSERT ... ON DUPLICATE KEY UPDATE so the row is created on
 * first use and overwritten on subsequent writes. Returns true on
 * success, false on a DB error (logged via error_log).
 *
 * Callers that need to react to a write failure should check the
 * return value; callers that just want "best effort" persistence can
 * ignore it.
 *
 * @param PDO    $pdo
 * @param string $name
 * @param string $value
 * @return bool
 */
function set_setting($pdo, $name, $value) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (name, value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$name, $value]);
        return true;
    } catch (PDOException $e) {
        error_log('[anime_tracker] set_setting(' . $name . '): ' . $e->getMessage());
        return false;
    }
}

// =====================================================================
// SECTION: UI translations (i18n)
// ---------------------------------------------------------------------
// Three-function family, parallel to the watch_status_* / emotion_*
// helpers: one function loads state, one reports state, one renders.
//
//   lang_init($pdo)     Reads display_language from settings, loads the
//                       matching dictionary into a static cache. Called
//                       once at the top of each page that uses t().
//   current_lang()      Returns 'tr' or 'en'. Falls back to 'tr' if
//                       lang_init() was not called yet.
//   t($key)             Returns the translated string. Falls back to
//                       Turkish if the English entry is missing, then
//                       to $key itself if even Turkish is missing -
//                       a defensive choice so a missing key shows up
//                       as a visible token instead of a blank cell.
//
// Adding a new language: drop a lang/<code>.php file with the same
// keys, append the code to $allowed in lang_init(), done. The CSS
// classes used elsewhere in the project are already ASCII suffixes
// (watched / huzunlendirdi / ...) so they are language-independent.
// =====================================================================

/**
 * Internal cache shared by lang_init / current_lang / t.
 *
 * Pulled out into its own accessor so all three helpers see the same
 * state without each one re-implementing the static. Callers should
 * not invoke this directly.
 *
 * @param array|null $write  When non-null, replaces the cached state.
 * @return array{lang: string, dict: array, fallback: array}
 */
function _lang_cache($write = null) {
    static $cache = [
        'lang'     => 'tr',
        'dict'     => null,
        'fallback' => null,
    ];
    if ($write !== null) {
        $cache = $write;
    }
    return $cache;
}

/**
 * Initialize the i18n layer for the current request.
 *
 * Reads display_language from the settings table, loads the matching
 * dictionary from lang/<code>.php, and also pre-loads lang/tr.php as
 * a fallback so the per-key fallback in t() does not need to touch
 * the filesystem on every miss.
 *
 * Should be called once at the top of each page that uses t(), right
 * after the db.php / functions.php requires. Subsequent calls in the
 * same request are no-ops.
 *
 * @param PDO $pdo
 * @return void
 */
function lang_init($pdo) {
    $cache = _lang_cache();
    if ($cache['dict'] !== null) {
        return; // already initialised this request
    }

    $allowed = ['tr', 'en'];
    $lang = get_setting($pdo, 'display_language', 'tr');
    if (!in_array($lang, $allowed, true)) {
        $lang = 'tr';
    }

    $dict     = _lang_load($lang);
    $fallback = ($lang === 'tr') ? $dict : _lang_load('tr');

    _lang_cache([
        'lang'     => $lang,
        'dict'     => $dict,
        'fallback' => $fallback,
    ]);
}

/**
 * Load a translation dictionary from disk.
 *
 * Returns an empty array if the file is missing or does not return
 * an array - the t() helper will then either fall back to Turkish or
 * to the raw key, so a missing dictionary degrades gracefully rather
 * than crashing the page.
 *
 * @param string $lang
 * @return array
 */
function _lang_load($lang) {
    $path = __DIR__ . '/lang/' . $lang . '.php';
    if (!is_file($path)) {
        error_log('[anime_tracker] lang file missing: ' . $path);
        return [];
    }
    $data = include $path;
    return is_array($data) ? $data : [];
}

/**
 * Return the active language code for the current request.
 *
 * Returns 'tr' if lang_init() has not been called yet - this keeps
 * pages that have not been translated yet rendering in their
 * original Turkish wording instead of throwing.
 *
 * @return string  'tr' or 'en'.
 */
function current_lang() {
    $cache = _lang_cache();
    return $cache['lang'];
}

/**
 * Translate a UI string key into the active language.
 *
 * Lookup order:
 *   1. Active language dictionary (loaded by lang_init).
 *   2. Turkish dictionary (fallback - English may have gaps while
 *      translation is in progress).
 *   3. The key itself, returned unchanged - a visible token tells
 *      the developer which entry is missing without leaving the
 *      user with a blank screen.
 *
 * @param string $key  Dot-namespaced key, e.g. 'nav.statistics'.
 * @return string
 */
function t($key) {
    $cache = _lang_cache();

    if ($cache['dict'] !== null && isset($cache['dict'][$key])) {
        return $cache['dict'][$key];
    }
    if ($cache['fallback'] !== null && isset($cache['fallback'][$key])) {
        return $cache['fallback'][$key];
    }
    return $key;
}

/**
 * Extract the numeric MyAnimeList ID from a MAL URL.
 *
 * Accepts:
 *   https://myanimelist.net/anime/12345
 *   https://myanimelist.net/anime/12345/Some_Slug
 *   http://myanimelist.net/anime/12345
 *
 * Returns null for empty input or URLs that don't match.
 *
 * Used by add_anime.php and edit_anime.php to populate the mal_id
 * column automatically (so the catalog sync can match local rows
 * against the server catalog by MAL ID without the user having to
 * type the ID by hand).
 */
function parseMalId($url) {
    if (empty($url) || !is_string($url)) {
        return null;
    }
    if (preg_match('#myanimelist\.net/anime/(\d+)#i', $url, $m)) {
        return (int)$m[1];
    }
    return null;
}

/**
 * Extract the numeric AniDB ID from an AniDB URL.
 *
 * Accepts three URL formats that have appeared on AniDB over the years:
 *   1. Modern:     https://anidb.net/anime/12345
 *   2. Short:      https://anidb.net/a12345
 *   3. Legacy CGI: https://anidb.net/perl-bin/animedb.pl?show=anime&aid=12345
 *                  (older entries in existing databases still use this)
 *
 * Returns null for empty input or URLs that don't match.
 */
function parseAnidbId($url) {
    if (empty($url) || !is_string($url)) {
        return null;
    }
    // Legacy CGI form uses aid= parameter
    if (preg_match('#aid=(\d+)#i', $url, $m)) {
        return (int)$m[1];
    }
    // Short form: /a12345
    if (preg_match('#anidb\.net/a(\d+)#i', $url, $m)) {
        return (int)$m[1];
    }
    // Modern form: /anime/12345
    if (preg_match('#anidb\.net/anime/(\d+)#i', $url, $m)) {
        return (int)$m[1];
    }
    return null;
}

function calculateNextEpisodeDate($anime) {
    if ($anime['status'] != 'Yayın Devam Ediyor' || empty($anime['broadcast_day']) || empty($anime['broadcast_time'])) {
        return null;
    }

    // Animenin yayin saat dilimi. Eski kayitlar icin varsayilan: Asia/Tokyo
    $broadcastTzName = !empty($anime['broadcast_timezone']) ? $anime['broadcast_timezone'] : 'Asia/Tokyo';
    try {
        $broadcastTz = new DateTimeZone($broadcastTzName);
    } catch (Exception $e) {
        $broadcastTz = new DateTimeZone('Asia/Tokyo');
    }

    // "Simdi"yi animenin yayin saat diliminde al ki gun/saat karsilastirmalari
    // dogru yayin bolgesinde yapilsin
    $now = new DateTime('now', $broadcastTz);
    $broadcastTime = new DateTime($anime['broadcast_time'], $broadcastTz);
    $days = [
        'Pazartesi' => 1,
        'Salı' => 2,
        'Çarşamba' => 3,
        'Perşembe' => 4,
        'Cuma' => 5,
        'Cumartesi' => 6,
        'Pazar' => 7
    ];

    // Defensive lookup: if broadcast_day contains an unexpected value
    // (typo, legacy data, trailing whitespace, etc.) we return null
    // instead of raising an "Undefined index" warning. The caller
    // (updateNextEpisodeDate) already handles the null return.
    $broadcastDayNum = $days[$anime['broadcast_day']] ?? null;
    if ($broadcastDayNum === null) {
        error_log('[anime_tracker] Unknown broadcast_day: ' . var_export($anime['broadcast_day'], true));
        return null;
    }

    $currentDayNum = $now->format('N');

    $nextDate = clone $now;
    $nextDate->setTime($broadcastTime->format('H'), $broadcastTime->format('i'), 0);

    if ($currentDayNum < $broadcastDayNum) {
        $daysToAdd = $broadcastDayNum - $currentDayNum;
    } elseif ($currentDayNum == $broadcastDayNum) {
        if ($now < $nextDate) {
            $daysToAdd = 0;
        } else {
            $daysToAdd = 7;
        }
    } else {
        $daysToAdd = 7 - ($currentDayNum - $broadcastDayNum);
    }

    $nextDate->modify("+{$daysToAdd} days");
    // Sonucu UTC'ye cevirip oyle sakla. Boylece DB'de timezone-bagimsiz
    // tek bir referans nokta tutulmus olur ve gosterimde istenilen saat
    // dilimine cevrilebilir.
    $nextDate->setTimezone(new DateTimeZone('UTC'));
    return $nextDate->format('Y-m-d H:i:s');
}

function updateNextEpisodeDate($pdo, &$anime) {
    if (empty($anime['next_episode_date'])) {
        return;
    }

    $now = new DateTime('now', new DateTimeZone('UTC'));
    $nextEpisodeDate = new DateTime($anime['next_episode_date'], new DateTimeZone('UTC'));

    if ($now <= $nextEpisodeDate) {
        return;
    }

    $newNextEpisodeDate = calculateNextEpisodeDate($anime);
    if (!$newNextEpisodeDate) {
        return;
    }

    // Only update the next broadcast date. aired_episodes is managed
    // manually by the user because automatic counting cannot handle
    // real-world irregularities (broadcast breaks, holidays, specials).
    $sql = "UPDATE animes SET next_episode_date = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$newNextEpisodeDate, $anime['id']]);
    $anime['next_episode_date'] = $newNextEpisodeDate;
}

function getTimeUntilNextEpisode($next_episode_date, $watched_episodes = 0, $total_episodes = 0, $aired_episodes = 0, $lang = null) {
    // The $lang parameter mirrors the watch_status_label / emotion_label
    // pattern: explicit override wins, otherwise the active UI language
    // (set by lang_init) is used. Hard-coded strings live in a static
    // $map so a future third language only needs one new entry per key.
    if ($lang === null) {
        $lang = current_lang();
    }
    static $map = [
        'tr' => [
            'completed'    => 'İzleme tamamlandı',
            'catch_up'     => '%d bölüm izlenebilir (%d. bölümden devam)',
            'unset'        => 'Belirtilmemiş',
            'new_episode'  => 'Yeni bölüm yayınlandı',
            'time_until'   => '%d. bölüme kalan süre:',
            'unit_day'     => '%d gün',
            'unit_hour'    => '%d saat',
            'unit_minute'  => '%d dakika',
        ],
        'en' => [
            'completed'    => 'Watched all episodes',
            'catch_up'     => '%d episodes available (continue from ep. %d)',
            'unset'        => 'Not set',
            'new_episode'  => 'New episode aired',
            'time_until'   => 'Time until ep. %d:',
            'unit_day'     => '%d d',
            'unit_hour'    => '%d h',
            'unit_minute'  => '%d m',
        ],
    ];
    $L = $map[$lang] ?? $map['tr']; // fallback to TR if unknown lang

    // User has watched every episode that has a final count.
    // NOTE: This is about the WATCH status, not the broadcast status.
    // For ongoing anime the caller passes total_episodes = 0 (or NULL
    // from DB, which becomes 0 here), so this branch is skipped and we
    // fall through to calculate the time to the next broadcast.
    if ($total_episodes > 0 && $watched_episodes >= $total_episodes) {
        return $L['completed'];
    }

    // Sonraki izlenecek bolum numarasi
    $next_episode_number = $watched_episodes + 1;

    // Eger aired_episodes bilgisi varsa ve kullanici henuz yayinlanmis
    // bolumlere yetismediyse, geri sayim gostermenin anlami yok.
    // Ornek: Detective Conan 1185 bolum yayinlandi, kullanici 430'da.
    // 431. bolum zaten mevcut - beklemesine gerek yok.
    if ($aired_episodes > 0 && $next_episode_number <= $aired_episodes) {
        $remaining = $aired_episodes - $watched_episodes;
        return sprintf($L['catch_up'], $remaining, $next_episode_number);
    }

    if (empty($next_episode_date)) {
        return $L['unset'];
    }

    // DB stores next_episode_date in UTC. Read it explicitly as UTC so
    // the countdown is correct regardless of PHP's default timezone.
    $next_dt = new DateTime($next_episode_date, new DateTimeZone('UTC'));
    $next_episode_timestamp = $next_dt->getTimestamp();
    $current_timestamp = time();

    // Zaman gecmisse (yeni bolum yayinlandi)
    if ($next_episode_timestamp < $current_timestamp) {
        return $L['new_episode'];
    }

    // Kalan sureyi hesapla - bu sadece kullanici yayinlanan bolumlere
    // yetismis ve bir sonraki bolumun yayinini bekliyorsa anlamli.
    $seconds_remaining = $next_episode_timestamp - $current_timestamp;
    $days = floor($seconds_remaining / 86400);
    $hours = floor(($seconds_remaining % 86400) / 3600);
    $minutes = floor(($seconds_remaining % 3600) / 60);

    // Zamanli gosterim. The cell shows this inside <pre>, so newlines
    // render literally. Format matches the pre-i18n version for visual
    // continuity (number on top line, units below).
    $parts = [];
    if ($days > 0) {
        $parts[] = sprintf($L['unit_day'], $days);
    }
    if ($hours > 0) {
        $parts[] = sprintf($L['unit_hour'], $hours);
    }
    if ($minutes > 0) {
        $parts[] = sprintf($L['unit_minute'], $minutes);
    }
    $time_string = implode(' ', $parts);

    return sprintf($L['time_until'], $next_episode_number) . "\n" . $time_string;
}

/**
 * Auto-mark a finished anime as watched when the user has caught up.
 *
 * This function ONLY touches watch_status (the user's viewing progress).
 * It never touches status (the Japan broadcast status). The two concepts
 * are kept strictly separate from v0.5 onwards.
 *
 * The function only triggers for anime where:
 *   - status is 'Yayın Tamamlandı' (broadcast finished in Japan)
 *   - total_episodes is set (not NULL, not 0)
 *   - watched_episodes >= total_episodes
 *   - watch_status is not already 'Watched'
 *
 * This means ongoing anime (One Piece, Detective Conan) are never
 * touched automatically - the user tracks aired_episodes manually.
 * This prevents the old bug where catching up on an ongoing series
 * would incorrectly mark it as watched on every page load.
 */
function checkIfAnimeCompleted($pdo, $anime) {
    if ($anime['status'] !== 'Yayın Tamamlandı') {
        return $anime;
    }

    if (empty($anime['total_episodes']) || $anime['total_episodes'] <= 0) {
        return $anime;
    }

    if ($anime['watched_episodes'] < $anime['total_episodes']) {
        return $anime;
    }

    if ($anime['watch_status'] === 'Watched') {
        return $anime;
    }

    // All conditions met - mark as watched.
    $stmt = $pdo->prepare("UPDATE animes SET watch_status = 'Watched' WHERE id = :id");
    $stmt->execute(['id' => $anime['id']]);
    $anime['watch_status'] = 'Watched';

    return $anime;
}

/**
 * Securely handle an uploaded anime cover image.
 *
 * Performs the following checks before saving:
 *   1. Upload error code is OK (catches "no file", "too large", etc.)
 *   2. File size is within the 5 MB limit
 *   3. The temp file is actually an uploaded file (defense against path tricks)
 *   4. Real MIME type (read from file content, not user-supplied) is in
 *      the allowed image list. This is the only reliable way to detect
 *      a renamed .php file pretending to be a .jpg.
 *   5. Filename is generated server-side from random bytes, so the user
 *      cannot inject path components and two uploads with the same
 *      original name do not overwrite each other.
 *
 * On the first call after install, creates the uploads/ directory and
 * an .htaccess file inside it that disables PHP execution. This is a
 * defense-in-depth measure in case Apache configuration is overridden.
 *
 * Returns the relative path stored in the animes.image_path column
 * (e.g. "uploads/a1b2c3d4e5f6.jpg") or null if no file was uploaded.
 *
 * Throws Exception with a Turkish user-facing message on validation
 * failure. Callers should catch and display the message.
 */
function handleImageUpload($file)
{
    // 1. No file at all - this is a valid case (user just edited fields).
    if (!isset($file) || !is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // 2. Upload error from PHP itself (size, partial, missing tmp dir, etc.)
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'Resim sunucu sinirini asti.',
            UPLOAD_ERR_FORM_SIZE  => 'Resim form sinirini asti.',
            UPLOAD_ERR_PARTIAL    => 'Resim kismen yuklendi, tekrar deneyin.',
            UPLOAD_ERR_NO_TMP_DIR => 'Sunucuda gecici klasor bulunamadi.',
            UPLOAD_ERR_CANT_WRITE => 'Diske yazilamadi.',
            UPLOAD_ERR_EXTENSION  => 'Bir PHP eklentisi yuklemeyi durdurdu.',
        ];
        $message = $errorMessages[$file['error']] ?? 'Bilinmeyen yukleme hatasi.';
        throw new Exception('Resim yuklenemedi: ' . $message);
    }

    // 3. Size limit (5 MB). PHP also enforces upload_max_filesize from
    // php.ini, but we set our own limit so the message is friendly.
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Resim cok buyuk. En fazla 5 MB olabilir.');
    }

    // 4. Defense in depth: confirm the temp file really came from an upload.
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Gecersiz dosya yuklemesi.');
    }

    // 5. Read the real MIME type from the file content. Never trust the
    // user-supplied $file['type'] - it can be anything.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowedMimes[$mimeType])) {
        throw new Exception('Sadece JPG, PNG, WEBP veya GIF resim yukleyebilirsiniz.');
    }

    $extension = $allowedMimes[$mimeType];

    // 6. Make sure uploads/ exists and has the .htaccess guard.
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Yukleme klasoru olusturulamadi.');
        }
    }

    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        $htaccess = "# Disable PHP execution in this directory\n";
        $htaccess .= "php_flag engine off\n";
        $htaccess .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|phar)$\">\n";
        $htaccess .= "    Require all denied\n";
        $htaccess .= "</FilesMatch>\n";
        @file_put_contents($htaccessPath, $htaccess);
    }

    // 7. Generate a unique server-side filename. random_bytes is
    // cryptographically secure and rules out collisions in practice.
    $uniqueName = bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $uploadDir . '/' . $uniqueName;

    // 8. Move the file into place.
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Dosya kaydedilemedi.');
    }

    // Return the web-accessible path that goes into the DB.
    return 'uploads/' . $uniqueName;
}

/**
 * Return the current session's CSRF token, creating one on first call.
 *
 * The token is 64 hex characters (32 random bytes) generated from
 * random_bytes, which is cryptographically secure.
 *
 * Every form that performs a state-changing action (insert, update,
 * delete) must include this token as a hidden input, and the receiving
 * handler must call csrf_verify() on the posted value.
 *
 * Assumes session_start() has already been called (db.php does this).
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Timing-safe comparison of a posted token against the session token.
 *
 * Uses hash_equals() to avoid leaking information via timing attacks.
 * Returns false if either value is missing or does not match.
 */
function csrf_verify($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Return a URL safe to use inside an href="" attribute, or an empty
 * string if the URL is unsafe or empty.
 *
 * This protects against javascript: and data: URL schemes that would
 * execute code when clicked. htmlspecialchars alone does NOT protect
 * against these - it escapes HTML metacharacters but leaves the scheme
 * intact, so <a href="javascript:alert(1)"> is still dangerous after
 * htmlspecialchars.
 *
 * Only http:// and https:// URLs are accepted. Anything else (javascript:,
 * data:, vbscript:, file:, ftp:, missing scheme, malformed URL) returns
 * an empty string.
 *
 * The return value is already HTML-escaped for attribute context, so
 * callers should NOT wrap the result in htmlspecialchars again:
 *     <a href="<?= safe_url($url) ?>">...</a>
 *
 * Returns empty string so the caller can safely compare with empty()
 * to decide whether to render the link at all.
 */
function safe_url($url) {
    if (empty($url)) {
        return '';
    }
    $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }
    return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
}

// =====================================================================
// Series relationship helpers (v0.5 mid-cycle addition)
// =====================================================================

/**
 * Return all animes that share the same series_name, excluding the
 * given anime itself. Results are grouped by media_type (TV first,
 * then Film, then OVA/Special/ONA) and within each group sorted by
 * release_date ascending.
 *
 * Returns an empty array if $series_name is empty/null or no related
 * animes exist.
 */
function getRelatedAnimes($pdo, $series_name, $exclude_id) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT id, title, media_type, watch_status, watched_episodes,
               total_episodes, release_date, image_path
        FROM animes
        WHERE series_name = ? AND id != ?
        ORDER BY
            FIELD(media_type, 'TV', 'Film', 'OVA', 'Special', 'ONA'),
            release_date ASC,
            id ASC
    ");
    $stmt->execute([$series_name, (int)$exclude_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return all chronology markers for a given anime, with full details
 * of the related anime (title, watch_status, etc.) via JOIN.
 *
 * Results are sorted by after_episode ascending so the UI can display
 * them in episode order.
 */
function getChronologyMarkers($pdo, $anime_id) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title, a.watch_status AS related_watch_status,
               a.media_type AS related_media_type
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
        ORDER BY cm.after_episode ASC
    ");
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check whether the user's current watch progress triggers a
 * chronology marker alert. Returns the marker row (with related
 * anime details) if the NEXT episode to watch (watched + 1) matches
 * a marker's after_episode. Returns null if no alert is needed.
 *
 * Example: anime has a marker with after_episode=23. If the user has
 * watched 23 episodes, the next one would be 24, but the marker says
 * "watch the related anime first". So we compare watched_episodes
 * against after_episode: if watched >= after_episode AND the related
 * anime is not yet watched, show the alert.
 *
 * We only alert for markers where the related anime's watch_status
 * is NOT 'Izlendi' — if the user already watched the film, no need
 * to remind them.
 */
function getActiveChronologyAlert($pdo, $anime_id, $watched_episodes) {
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title, a.watch_status AS related_watch_status,
               a.media_type AS related_media_type, a.id AS related_id
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        WHERE cm.anime_id = ?
          AND cm.after_episode <= ?
          AND a.watch_status != 'Watched'
        ORDER BY cm.after_episode ASC
        LIMIT 1
    ");
    $stmt->execute([(int)$anime_id, (int)$watched_episodes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Return all distinct series_name values from the animes table,
 * sorted alphabetically. Used to populate the datalist/auto-complete
 * in the add/edit forms so the user does not have to type series
 * names from memory (and risk typos).
 */
function getAllSeriesNames($pdo) {
    $stmt = $pdo->query("
        SELECT DISTINCT series_name
        FROM animes
        WHERE series_name IS NOT NULL AND series_name != ''
        ORDER BY series_name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Validate that setting next_in_series does not create a direct
 * circular reference (A -> B -> A). Does NOT check transitive
 * cycles (A -> B -> C -> A) — that would require a recursive
 * walk and is overkill for a single-user app.
 *
 * Returns true if the link is safe, false if it would create a
 * direct loop.
 */
function validateNextInSeries($pdo, $anime_id, $target_id) {
    if (empty($target_id) || $target_id == $anime_id) {
        // Pointing to yourself is always invalid
        return $target_id != $anime_id;
    }
    // Check if the target already points back to us
    $stmt = $pdo->prepare("SELECT next_in_series FROM animes WHERE id = ?");
    $stmt->execute([(int)$target_id]);
    $targetNext = $stmt->fetchColumn();
    if ($targetNext !== false && (int)$targetNext === (int)$anime_id) {
        return false; // direct circular: A -> B -> A
    }
    return true;
}

/**
 * Genre helpers (canonical taxonomy).
 *
 * Genres are the canonical classification of an anime (Action, Drama,
 * Comedy, ...) sourced from MAL/AniDB and shared with the catalog.
 * They live in the `genres` table and are linked to animes through
 * the `anime_genres` join table. This is the parallel of the tag
 * helpers below; the two systems never mix.
 *
 * Before the v0.5 in-place patch (genres_relational_upgrade.sql),
 * genres were stored as a comma-separated TEXT column on animes.
 * That column has been dropped; all reads and writes go through
 * the helpers in this section.
 */

/**
 * Return every genre in alphabetical order.
 * Used by the dropdown in add_anime / edit_anime and by the filter
 * dropdown in index.php.
 */
function getAllGenres($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return the genres currently attached to a single anime.
 * Each row has both `id` and `name` so callers can render badges and
 * still know the IDs to keep the form's selected state in sync.
 */
function getAnimeGenres($pdo, $anime_id) {
    $stmt = $pdo->prepare(
        "SELECT g.id, g.name
         FROM genres g
         INNER JOIN anime_genres ag ON ag.genre_id = g.id
         WHERE ag.anime_id = ?
         ORDER BY g.name ASC"
    );
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Replace the genre set attached to an anime with the given list of
 * genre IDs.
 *
 * Implementation notes (mirrors setAnimeTags):
 * - Wrapped in a transaction so a half-applied update can never leave
 *   the link table in a partial state.
 * - DELETE + INSERT is simpler than computing the diff and produces
 *   the same end state. The link table is small (a handful of rows
 *   per anime) so the cost is negligible.
 * - Duplicate IDs in the input are silently ignored (the PRIMARY KEY
 *   on the link table would otherwise raise 23000).
 * - Zero/negative IDs are dropped so a stray empty value from the form
 *   does not become a bogus row.
 */
function setAnimeGenres($pdo, $anime_id, $genre_ids) {
    $anime_id = (int)$anime_id;

    // Normalize: cast to int, drop zero/negative, deduplicate
    $clean = [];
    foreach ((array)$genre_ids as $gid) {
        $gid = (int)$gid;
        if ($gid > 0) {
            $clean[$gid] = true;
        }
    }
    $clean = array_keys($clean);

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM anime_genres WHERE anime_id = ?");
        $del->execute([$anime_id]);

        if (!empty($clean)) {
            $ins = $pdo->prepare(
                "INSERT INTO anime_genres (anime_id, genre_id) VALUES (?, ?)"
            );
            foreach ($clean as $gid) {
                $ins->execute([$anime_id, $gid]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Look up a genre by name (case-insensitive). If it does not exist,
 * create it. Returns the genre ID either way.
 *
 * This is what powers two flows:
 *   1. add_anime / edit_anime form submissions, where the user picks
 *      from the dropdown by name (the form posts the names, the
 *      handler resolves them to IDs here).
 *   2. catalog_import sync, where the catalog payload may contain a
 *      genre name that does not exist in the local master list yet
 *      (e.g. a typo correction or a newly-introduced genre on the
 *      server). Auto-creating keeps sync robust; the new genre then
 *      shows up in manage_genres.php like any other.
 *
 * Whitespace is trimmed and the name is capped at 50 characters to
 * match the schema. Empty names return 0 so the caller can skip them.
 */
function findOrCreateGenre($pdo, $name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 0;
    }
    if (mb_strlen($name) > 50) {
        $name = mb_substr($name, 0, 50);
    }

    // Case-insensitive lookup using the default utf8mb4_general_ci
    // collation - "Aksiyon" and "aksiyon" resolve to the same row.
    $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    // Race-safe insert: if another request created the same genre a
    // moment ago, the UNIQUE constraint on genres.name will fire and
    // we re-query to get the ID created by the other request.
    try {
        $ins = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
        $ins->execute([$name]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $stmt = $pdo->prepare("SELECT id FROM genres WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int)$id;
            }
        }
        throw $e;
    }
}

/**
 * Convenience wrapper: take a list of genre names (e.g. from a form
 * post or a catalog payload) and replace the anime's genres with the
 * resolved set. Names that cannot be resolved or created are skipped.
 *
 * Used by:
 *   - add_anime / edit_anime: form posts the comma-separated names
 *     from the hidden input, handler explodes and calls this.
 *   - catalog_import: payload contains the CSV genres string, handler
 *     explodes and calls this.
 */
function setAnimeGenresByNames($pdo, $anime_id, $names) {
    $ids = [];
    foreach ((array)$names as $name) {
        $gid = findOrCreateGenre($pdo, $name);
        if ($gid > 0) {
            $ids[] = $gid;
        }
    }
    setAnimeGenres($pdo, $anime_id, $ids);
}

/**
 * Serialize an anime's genres as a comma-separated name string.
 * Used by admin_sync*.php to emit the legacy CSV format the catalog
 * server still expects. Once the server side moves to a JSON array
 * format this helper can be removed.
 */
function getAnimeGenresAsCsv($pdo, $anime_id) {
    $rows = getAnimeGenres($pdo, $anime_id);
    $names = array_map(function ($r) { return $r['name']; }, $rows);
    return implode(',', $names);
}


/**
 * Tag helpers (recommendation system).
 *
 * Tags are descriptive labels (e.g. "Okul", "Spor", "Buyu") used by
 * the recommendation system as buckets. They live in their own table
 * (`tags`) and are linked to animes via `anime_tags`. This is a parallel
 * classification system to `genres` and the two never mix.
 */

/**
 * Return every tag in alphabetical order.
 * Used by the auto-complete dropdown in add_anime / edit_anime and by
 * the sentence list in recommendations.php.
 */
function getAllTags($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM tags ORDER BY name ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return the tags currently attached to a single anime.
 * Each row has both `id` and `name` so callers can render badges and
 * still know the IDs to keep the form's selected state in sync.
 */
function getAnimeTags($pdo, $anime_id) {
    $stmt = $pdo->prepare(
        "SELECT t.id, t.name
         FROM tags t
         INNER JOIN anime_tags at ON at.tag_id = t.id
         WHERE at.anime_id = ?
         ORDER BY t.name ASC"
    );
    $stmt->execute([(int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Replace the tag set attached to an anime with the given list of tag IDs.
 *
 * Implementation notes:
 * - Wrapped in a transaction so a half-applied update can never leave
 *   the link table in a partial state.
 * - DELETE + INSERT is simpler than computing the diff and produces
 *   the same end state. The link table is small (a few rows per anime)
 *   so the cost is negligible.
 * - Duplicate IDs in the input are silently ignored (the PRIMARY KEY
 *   on the link table would otherwise raise 23000).
 */
function setAnimeTags($pdo, $anime_id, $tag_ids) {
    $anime_id = (int)$anime_id;
    // Normalize: cast to int, drop zero/negative, deduplicate
    $clean = [];
    foreach ((array)$tag_ids as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $clean[$tid] = true;
        }
    }
    $clean = array_keys($clean);

    $pdo->beginTransaction();
    try {
        $del = $pdo->prepare("DELETE FROM anime_tags WHERE anime_id = ?");
        $del->execute([$anime_id]);

        if (!empty($clean)) {
            $ins = $pdo->prepare(
                "INSERT INTO anime_tags (anime_id, tag_id) VALUES (?, ?)"
            );
            foreach ($clean as $tid) {
                $ins->execute([$anime_id, $tid]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Look up a tag by name (case-insensitive). If it does not exist,
 * create it. Returns the tag ID either way.
 *
 * This is what powers the "create on the fly" behaviour of the
 * add_anime / edit_anime tag input: when the user types a new tag and
 * confirms it, the form posts the raw name and the server resolves it
 * to an ID here.
 *
 * Whitespace is trimmed and the name is capped at 50 characters to
 * match the schema. Empty names return 0 so the caller can skip them.
 */
function findOrCreateTag($pdo, $name) {
    $name = trim((string)$name);
    if ($name === '') {
        return 0;
    }
    if (mb_strlen($name) > 150) {
        $name = mb_substr($name, 0, 150);
    }

    // Case-insensitive lookup using the default utf8mb4_general_ci
    // collation - "Okul" and "okul" resolve to the same row.
    $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int)$id;
    }

    // Race-safe insert: if another request created the same tag a
    // moment ago, the UNIQUE constraint on tags.name will fire and we
    // re-query to get the ID created by the other request.
    try {
        $ins = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
        $ins->execute([$name]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $id = $stmt->fetchColumn();
            if ($id !== false) {
                return (int)$id;
            }
        }
        throw $e;
    }
}

// ============================================================================
// AnimeSchedule API Helpers
// ============================================================================
//
// These three functions handle the "Otomatik Doldur" button on add_anime.php
// and edit_anime.php. They are pure helpers - no DB access, no session, no
// HTTP response writing. The AJAX endpoint (fetch_animeschedule.php) calls
// them and wraps the result in JSON.
//
// Flow:
//   1. parseAnimeScheduleSlug() extracts the slug from a user-pasted URL
//   2. fetchAnimeScheduleData() makes the HTTP request to the API
//   3. mapAnimeScheduleToFormFields() turns the API JSON into our form values
//
// API key is loaded from config.php as ANIMESCHEDULE_API_KEY. If the constant
// is not defined the feature is disabled (the AJAX endpoint reports this back
// to the browser).

/**
 * Extract the slug from an AnimeSchedule URL.
 *
 * Accepts every reasonable variant we have seen users paste:
 *   - https://animeschedule.net/anime/akane-banashi
 *   - https://animeschedule.net/anime/akane-banashi/
 *   - http://animeschedule.net/anime/akane-banashi
 *   - animeschedule.net/anime/akane-banashi      (no scheme)
 *   - https://animeschedule.net/anime/akane-banashi?foo=bar
 *
 * Returns the slug string, or null if the URL is not an AnimeSchedule
 * anime URL. The slug is what we pass to the /api/v3/anime/{slug}
 * endpoint - lowercase letters, digits and dashes per the AnimeSchedule
 * URL convention.
 */
function parseAnimeScheduleSlug($url) {
    if (empty($url) || !is_string($url)) {
        return null;
    }
    // The slug character class is intentionally lenient (a-z, 0-9, dash,
    // underscore) so we don't reject valid slugs we have not seen yet.
    // The trailing group stops at /, ?, # or end of string.
    if (preg_match('#animeschedule\.net/anime/([a-z0-9_-]+)#i', $url, $m)) {
        return strtolower($m[1]);
    }
    return null;
}

/**
 * Fetch the JSON body for /api/v3/anime/{slug}.
 *
 * Returns an associative array on success. On any kind of failure
 * returns an array with an 'error' key so the caller can report a
 * Turkish message back to the user. We intentionally do not throw -
 * the AJAX endpoint converts these errors into JSON responses and
 * exceptions would force ugly HTTP 500 pages.
 *
 * Possible 'error' values:
 *   'no_key'      - ANIMESCHEDULE_API_KEY not defined in config.php
 *   'curl'        - network/cURL failure (timeout, DNS, no internet)
 *   'http_404'    - slug does not exist on AnimeSchedule
 *   'http_401'    - API token invalid or expired
 *   'http_403'    - API token lacks permission for this endpoint
 *   'http_429'    - rate limit hit
 *   'http_other'  - any other unexpected HTTP status
 *   'bad_json'    - response body was not valid JSON
 *
 * The 'http_code' key carries the raw HTTP status when applicable so
 * the caller can include it in the error message for debugging.
 */
function fetchAnimeScheduleData($slug) {
    if (!defined('ANIMESCHEDULE_API_KEY') || ANIMESCHEDULE_API_KEY === '') {
        return ['error' => 'no_key'];
    }
    if (empty($slug) || !preg_match('/^[a-z0-9_-]+$/i', $slug)) {
        return ['error' => 'bad_slug'];
    }

    $url = 'https://animeschedule.net/api/v3/anime/' . rawurlencode($slug);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . ANIMESCHEDULE_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        // Cert verification on - we never want to talk to a MITM
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log('[anime_tracker] AnimeSchedule cURL error: ' . $curlErr);
        return ['error' => 'curl', 'detail' => $curlErr];
    }

    if ($httpCode === 404) {
        return ['error' => 'http_404', 'http_code' => 404];
    }
    if ($httpCode === 401) {
        return ['error' => 'http_401', 'http_code' => 401];
    }
    if ($httpCode === 403) {
        return ['error' => 'http_403', 'http_code' => 403];
    }
    if ($httpCode === 429) {
        return ['error' => 'http_429', 'http_code' => 429];
    }
    if ($httpCode !== 200) {
        return ['error' => 'http_other', 'http_code' => $httpCode];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'bad_json'];
    }

    return $data;
}

/**
 * Convert an AnimeSchedule API response into a flat array of form
 * field values our add/edit forms can consume.
 *
 * Returned keys (only ones we actually use - synopsis, names, links
 * are intentionally NOT mapped per the user's request):
 *
 *   broadcast_day        - 'Pazartesi'..'Pazar', or null
 *   broadcast_time       - 'HH:MM' (Asia/Tokyo local time), or null
 *   broadcast_timezone   - always 'Asia/Tokyo' if either day or time is set
 *   status               - 'Yayın Devam Ediyor' / 'Yayın Tamamlandı', or null
 *   total_episodes       - int (only when API has 'episodes' AND status finished)
 *   aired_episodes       - intentionally NOT set (the basic /anime endpoint
 *                          does not give a reliable aired count for ongoing
 *                          shows, see /timetables endpoint for future work)
 *
 * Any key whose value cannot be derived is OMITTED from the result -
 * the caller can then iterate the array and only fill empty form
 * fields, leaving anything else untouched.
 *
 * IMPORTANT: This function uses TWO different API fields for broadcast
 * info, because the API splits the data:
 *
 *   broadcast_day  comes from `premier` (the first episode's air date,
 *                  whose weekday equals the show's weekly broadcast day)
 *   broadcast_time comes from `jpnTime` (per the AnimeSchedule docs:
 *                  "only the hour and minute are relevant" - the date
 *                  part of jpnTime is unreliable, often points to an
 *                  announcement timestamp from months earlier)
 *
 * Why we cannot use jpnTime's weekday: real-world testing showed that
 * for Marriagetoxin (Spring 2026, broadcasts Tuesdays) jpnTime returned
 * a Friday in October 2025 - that timestamp matches the show's "Tuesday
 * Night Block" announcement, not its actual broadcast day. Using the
 * weekday from jpnTime gave wrong results for both Marriagetoxin and
 * Akane-banashi (the latter broadcasts Saturdays but jpnTime pointed
 * to a Thursday).
 *
 * The premier field, in contrast, is the actual first-episode air date.
 * Its weekday matches the show's weekly broadcast slot reliably.
 *
 * Both timestamps are converted from UTC to Asia/Tokyo before extracting
 * weekday/time, because that is the broadcast region the API normalises
 * to and the local form values must match what the user sees on
 * AnimeSchedule.net.
 */
function mapAnimeScheduleToFormFields($apiData) {
    $out = [];

    // --- Helper: parse an API datetime field, return DateTime in Tokyo
    // or null if the value is missing / null-marker / unparseable.
    $parseTokyo = function($value) {
        if (empty($value) || !is_string($value)) return null;
        // API uses "0001-01-01T00:00:00Z" as null marker
        if (strpos($value, '0001-01-01') === 0) return null;
        try {
            $dt = new DateTime($value, new DateTimeZone('UTC'));
            // Sanity check: anything before 1971 is the null marker or junk
            if ((int)$dt->format('Y') < 1971) return null;
            $dt->setTimezone(new DateTimeZone('Asia/Tokyo'));
            return $dt;
        } catch (Exception $e) {
            return null;
        }
    };

    $dayMap = [
        'Monday'    => 'Pazartesi',
        'Tuesday'   => 'Salı',
        'Wednesday' => 'Çarşamba',
        'Thursday'  => 'Perşembe',
        'Friday'    => 'Cuma',
        'Saturday'  => 'Cumartesi',
        'Sunday'    => 'Pazar',
    ];

    // --- Broadcast day from `premier` ---------------------------------
    // The first-episode air date's weekday is the weekly broadcast day.
    $premierTokyo = $parseTokyo($apiData['premier'] ?? null);
    if ($premierTokyo !== null) {
        $dayEn = $premierTokyo->format('l');
        if (isset($dayMap[$dayEn])) {
            $out['broadcast_day'] = $dayMap[$dayEn];
        }
    }

    // --- Broadcast time from `jpnTime` --------------------------------
    // Only HH:MM is meaningful (per API docs). The date part of jpnTime
    // is unreliable - see the function docblock for the explanation.
    $jpnTokyo = $parseTokyo($apiData['jpnTime'] ?? null);
    if ($jpnTokyo !== null) {
        $out['broadcast_time'] = $jpnTokyo->format('H:i');
    }

    // --- Timezone -----------------------------------------------------
    // Set Asia/Tokyo only if at least one of day/time was filled, so we
    // do not stamp a timezone onto rows where both date fields were null.
    if (isset($out['broadcast_day']) || isset($out['broadcast_time'])) {
        $out['broadcast_timezone'] = 'Asia/Tokyo';
    }

    // --- Status -------------------------------------------------------
    // API values seen: "Finished", "Ongoing". "Upcoming" exists per the
    // docs but the form has no equivalent (we only support two states).
    if (!empty($apiData['status']) && is_string($apiData['status'])) {
        if ($apiData['status'] === 'Finished') {
            $out['status'] = 'Yayın Tamamlandı';
        } elseif ($apiData['status'] === 'Ongoing') {
            $out['status'] = 'Yayın Devam Ediyor';
        }
        // "Upcoming" or unknown values: leave status unset.
    }

    // --- Episode count ------------------------------------------------
    // The API only returns 'episodes' for shows where the final count
    // is known (typically status=Finished). For ongoing shows the field
    // is omitted entirely (per API docs: "if the value is null, the
    // entire field will be omitted"). We map it to total_episodes only
    // when status is Finished - otherwise it would mislead the user into
    // thinking an ongoing show has a confirmed final count.
    if (
        isset($apiData['episodes']) &&
        is_int($apiData['episodes']) &&
        $apiData['episodes'] > 0 &&
        ($out['status'] ?? null) === 'Yayın Tamamlandı'
    ) {
        $out['total_episodes'] = $apiData['episodes'];
    }

    return $out;
}

// ============================================================================
// AnimeSchedule Timetable Helpers (aired_episodes auto-sync)
// ============================================================================
//
// These helpers power the "Senkronize Et" button on edit_anime.php and the
// once-a-day silent sync on list_settings.php. They query the
// /timetables/sub endpoint to learn how many episodes have aired so far
// for an ongoing show, since /anime/{slug} does not give us that count
// reliably.
//
// CRITICAL FIELD NAME NOTE — the API returns CAMELCASE keys ('episodeNumber',
// 'episodeDate', 'route', 'title') even though some external SDK docs show
// PascalCase. Our first cut used PascalCase and produced silent zero
// matches across every anime. Always use camelCase here.
//
// CRITICAL FILTER NOTE — the documented 'mal-ids' query parameter is
// silently IGNORED by the public endpoint. Sending mal-ids=63376 returns
// the full week's list (76+ entries), not the requested anime. Real-world
// test confirmed this on 2026-04-28. We work around it by pulling the
// week unfiltered and matching on the 'route' slug client side. Bonus:
// this turns N anime lookups into 1 request per week.
//
// MATCHING — we match by the 'route' field, which is the URL slug visible
// in animeschedule.net/anime/<slug>. Our DB stores the full URL in
// animes.anime_schedule_link; we run parseAnimeScheduleSlug() on it to
// get the slug. Animes without an anime_schedule_link cannot be matched
// (the helper returns 'no_slug' for them).

/**
 * Build a list of {week, year} pairs walking backwards from today.
 *
 * Uses ISO 8601 week numbering (PHP 'W' for week 1-53, 'o' for the year
 * the ISO week belongs to - which can differ from 'Y' at year boundaries).
 *
 * Example output for $weeks = 3:
 *   [
 *     ['week' => 18, 'year' => 2026],   // current week
 *     ['week' => 17, 'year' => 2026],   // last week
 *     ['week' => 16, 'year' => 2026],   // two weeks ago
 *   ]
 */
function buildIsoWeekWindow($weeks = 3) {
    $out = [];
    $cursor = new DateTime('now', new DateTimeZone('UTC'));
    for ($i = 0; $i < $weeks; $i++) {
        $out[] = [
            'week' => (int)$cursor->format('W'),
            'year' => (int)$cursor->format('o'),
        ];
        $cursor->modify('-1 week');
    }
    return $out;
}

/**
 * Fetch the full sub timetable for a given ISO week. No filters - we
 * always pull the complete week's anime list and match client side.
 *
 * Returns either:
 *   - On success: array of TimetableShow objects (raw API response,
 *     ~50-100 entries per week, all camelCase keys)
 *   - On failure: ['error' => 'code', 'http_code' => N (optional)]
 *
 * Possible error codes:
 *   'no_key'     - ANIMESCHEDULE_API_KEY missing in config.php
 *   'bad_input'  - week/year out of sane range
 *   'curl'       - network error
 *   'http_401'   - bad token
 *   'http_403'   - token lacks permission
 *   'http_429'   - rate limit
 *   'http_other' - any other unexpected status
 *   'bad_json'   - response not parseable as JSON array
 */
function fetchAnimeScheduleTimetable($week, $year) {
    if (!defined('ANIMESCHEDULE_API_KEY') || ANIMESCHEDULE_API_KEY === '') {
        return ['error' => 'no_key'];
    }

    $week = (int)$week;
    $year = (int)$year;
    if ($week < 1 || $week > 53 || $year < 2000 || $year > 2100) {
        return ['error' => 'bad_input'];
    }

    $url = 'https://animeschedule.net/api/v3/timetables/sub'
         . '?week=' . $week . '&year=' . $year;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . ANIMESCHEDULE_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $body     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log('[anime_tracker] timetable cURL: ' . $curlErr);
        return ['error' => 'curl', 'detail' => $curlErr];
    }

    if ($httpCode === 401) return ['error' => 'http_401', 'http_code' => 401];
    if ($httpCode === 403) return ['error' => 'http_403', 'http_code' => 403];
    if ($httpCode === 429) return ['error' => 'http_429', 'http_code' => 429];
    if ($httpCode !== 200) return ['error' => 'http_other', 'http_code' => $httpCode];

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['error' => 'bad_json'];
    }

    return $data;
}

/**
 * Decide whether a timetable row represents an episode that has actually
 * aired by now, or one that is scheduled for the future.
 *
 * Why this matters: a query for the current ISO week returns ALL rows
 * scheduled in that week, including episodes that are still days away.
 * Without this filter we would happily report Episode 5 as aired on
 * Tuesday when the actual broadcast slot is the upcoming Saturday.
 *
 * Returns true if the episode has aired (or if we cannot tell - safer
 * to drop into the previous week than to report an episode that does
 * not exist yet, but we log unknowns so we notice if the API ever
 * stops sending episodeDate).
 */
function isTimetableRowAired($row) {
    if (!isset($row['episodeDate']) || empty($row['episodeDate'])) {
        // No date field at all - log once and treat as "do not trust"
        // (return false so we walk back to the previous week)
        error_log('[anime_tracker] timetable row missing episodeDate, slug: '
            . ($row['route'] ?? '?'));
        return false;
    }
    try {
        $epDate = new DateTime($row['episodeDate'], new DateTimeZone('UTC'));
        $now    = new DateTime('now',                new DateTimeZone('UTC'));
        return $epDate <= $now;
    } catch (Exception $e) {
        // Unparseable date - same conservative stance, do not count it
        error_log('[anime_tracker] timetable episodeDate unparseable: '
            . $row['episodeDate'] . ' (' . $e->getMessage() . ')');
        return false;
    }
}

/**
 * Find a timetable row by its 'route' slug (case-insensitive).
 *
 * Returns the matched row (associative array) or null. The match is
 * exact on the slug - the API is consistent about route formatting
 * (lowercase, dashes) so a strict comparison is fine.
 *
 * Only rows whose episodeDate is in the past (or the present) are
 * considered. Future-dated rows are skipped here so the caller does
 * not accidentally credit an episode that is still days away.
 *
 * If multiple rows match (rare - a show that aired multiple episodes
 * the same week), returns the row with the highest episodeNumber so
 * we report the latest aired count.
 */
function findTimetableRowBySlug($timetable, $slug) {
    if (!is_array($timetable) || empty($timetable) || empty($slug)) {
        return null;
    }
    $needle = strtolower($slug);
    $best   = null;
    foreach ($timetable as $row) {
        if (!isset($row['route'])) continue;
        if (strtolower($row['route']) !== $needle) continue;
        if (!isTimetableRowAired($row)) continue;

        $epNum = isset($row['episodeNumber']) ? (int)$row['episodeNumber'] : 0;
        if ($best === null) {
            $best = $row;
            continue;
        }
        $bestEp = isset($best['episodeNumber']) ? (int)$best['episodeNumber'] : 0;
        if ($epNum > $bestEp) {
            $best = $row;
        }
    }
    return $best;
}

/**
 * Sync aired_episodes for a single anime.
 *
 * Walks the ISO week window backwards (today, last week, the week
 * before, ...). The first week where the timetable contains a row
 * matching this anime's AnimeSchedule slug wins; the episodeNumber
 * from that row is written to animes.aired_episodes (overwriting -
 * that is the whole point of this feature).
 *
 * Returns one of:
 *   ['success' => true, 'aired_episodes' => N, 'week_offset' => K,
 *    'old_value' => OLD_OR_NULL, 'changed' => bool]
 *   ['error' => 'code']
 *
 * Possible error codes:
 *   'not_found'        - $animeId does not exist in DB
 *   'no_mal_id'        - anime has no mal_id (edge case - we still
 *                        require it for the catalog system identity)
 *   'not_ongoing'      - status is not 'Yayın Devam Ediyor'
 *   'no_slug'          - anime_schedule_link is empty / unparseable;
 *                        without a slug we cannot match into the
 *                        timetable response
 *   'not_in_timetable' - slug absent from every week we tried
 *   plus any error code returned by fetchAnimeScheduleTimetable()
 */
function syncSingleAiredEpisodes($pdo, $animeId, $maxWeeksBack = 3) {
    $animeId = (int)$animeId;
    if ($animeId <= 0) return ['error' => 'not_found'];

    $stmt = $pdo->prepare("
        SELECT id, mal_id, status, aired_episodes, anime_schedule_link
          FROM animes
         WHERE id = ?
         LIMIT 1
    ");
    $stmt->execute([$animeId]);
    $anime = $stmt->fetch();
    if (!$anime) return ['error' => 'not_found'];

    if (empty($anime['mal_id']))                   return ['error' => 'no_mal_id'];
    if ($anime['status'] !== 'Yayın Devam Ediyor') return ['error' => 'not_ongoing'];

    $slug = parseAnimeScheduleSlug($anime['anime_schedule_link'] ?? '');
    if ($slug === null)                            return ['error' => 'no_slug'];

    $weeks = buildIsoWeekWindow($maxWeeksBack);

    foreach ($weeks as $offset => $w) {
        $result = fetchAnimeScheduleTimetable($w['week'], $w['year']);

        // API/network error: bail immediately, do not silently keep
        // walking backwards through more failed requests
        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        $row = findTimetableRowBySlug($result, $slug);
        if ($row !== null && isset($row['episodeNumber'])) {
            $epNum = (int)$row['episodeNumber'];
            if ($epNum > 0) {
                $oldValue = isset($anime['aired_episodes']) ? (int)$anime['aired_episodes'] : null;
                $changed  = ($oldValue !== $epNum);

                if ($changed) {
                    $upd = $pdo->prepare("UPDATE animes SET aired_episodes = ? WHERE id = ?");
                    $upd->execute([$epNum, $animeId]);
                }

                return [
                    'success'        => true,
                    'aired_episodes' => $epNum,
                    'week_offset'    => $offset,
                    'old_value'      => $oldValue,
                    'changed'        => $changed,
                ];
            }
        }
    }

    return ['error' => 'not_in_timetable'];
}

/**
 * Bulk sync aired_episodes for every ongoing anime that has both a
 * MAL id and an AnimeSchedule slug.
 *
 * This is the once-a-day silent sync triggered from list_settings.php.
 * One API request per week serves the entire batch (vs one request per
 * anime in the naive design), because the timetable comes back unfiltered
 * and we match locally on slug.
 *
 * Side effect: settings.last_aired_sync is updated to the current UTC
 * timestamp on a successful run. If we hit a global API failure
 * (no_key, http_401, http_429) we DO NOT update the timestamp, so the
 * next page load will retry. Per-anime soft results (not_in_table,
 * no_slug) do not block the timestamp update.
 *
 * Returns:
 *   [
 *     'updated'       => N,     // animes whose aired_episodes changed
 *     'unchanged'     => N,     // animes confirmed at same value
 *     'not_in_table'  => N,     // slug absent from every week we tried
 *     'no_slug'       => N,     // animes lacking anime_schedule_link
 *     'errors'        => N,     // unexpected per-anime failures
 *     'global_error'  => 'code' // present only if a global API error stopped the run
 *   ]
 */
function syncAllOngoingAiredEpisodes($pdo, $maxWeeksBack = 3) {
    $stats = [
        'updated'      => 0,
        'unchanged'    => 0,
        'not_in_table' => 0,
        'no_slug'      => 0,
        'errors'       => 0,
    ];

    // Pull every ongoing anime that has the identity bits we need. We
    // require mal_id (project-wide convention for ongoing animes) and
    // we will additionally check anime_schedule_link per row.
    $stmt = $pdo->query("
        SELECT id, mal_id, aired_episodes, anime_schedule_link
          FROM animes
         WHERE status = 'Yayın Devam Ediyor'
           AND mal_id IS NOT NULL
    ");
    $animes = $stmt->fetchAll();

    if (empty($animes)) {
        // Nothing to do. Still mark a successful run so we do not
        // hammer the API on every page load looking for animes that do
        // not exist.
        markLastAiredSync($pdo);
        return $stats;
    }

    // Build slug => [anime row] map. Animes without a parseable slug go
    // straight into the no_slug bucket - no API can save them.
    $slugMap = [];
    foreach ($animes as $a) {
        $slug = parseAnimeScheduleSlug($a['anime_schedule_link'] ?? '');
        if ($slug === null) {
            $stats['no_slug']++;
            continue;
        }
        // Same slug pointing at multiple animes is theoretically
        // possible if the user has duplicate entries, but extremely
        // unlikely. Last-write wins is fine.
        $slugMap[$slug] = $a;
    }

    if (empty($slugMap)) {
        markLastAiredSync($pdo);
        return $stats;
    }

    // Update statement reused inside the loop
    $upd = $pdo->prepare("UPDATE animes SET aired_episodes = ? WHERE id = ?");

    // Walk weeks backwards. Once a slug is matched in some week we drop
    // it from the remaining set so older weeks do not overwrite newer
    // numbers (we found episode 5 last week; do not let "two weeks ago"
    // pull us back to episode 4).
    $remaining = $slugMap;
    $weeks = buildIsoWeekWindow($maxWeeksBack);

    foreach ($weeks as $w) {
        if (empty($remaining)) break;

        $result = fetchAnimeScheduleTimetable($w['week'], $w['year']);

        if (isset($result['error'])) {
            // Global errors abort the whole run; per-week errors of
            // these kinds will only repeat if we keep going
            if (in_array($result['error'], ['no_key', 'http_401', 'http_429'], true)) {
                $stats['global_error'] = $result['error'];
                error_log('[anime_tracker] aired sync aborted: ' . $result['error']);
                return $stats;
            }
            // Other errors (curl glitch, http_other): log and continue
            // to the next week - maybe the next request succeeds
            error_log('[anime_tracker] aired sync week '
                . $w['week'] . '/' . $w['year'] . ': ' . $result['error']);
            $stats['errors']++;
            continue;
        }

        // Walk this week's rows ONCE and try to resolve every remaining
        // slug we still care about. The timetable has ~80 rows, our
        // remaining list has at most 20 - this is fast.
        //
        // We skip future-dated rows here too (same reason as
        // findTimetableRowBySlug): the week's timetable contains
        // episodes that have not aired yet, and counting them would
        // overshoot by one.
        foreach ($result as $row) {
            if (empty($remaining)) break;
            if (!isset($row['route'])) continue;
            $rowSlug = strtolower($row['route']);
            if (!isset($remaining[$rowSlug])) continue;
            if (!isTimetableRowAired($row)) continue;

            $epNum = isset($row['episodeNumber']) ? (int)$row['episodeNumber'] : 0;
            if ($epNum <= 0) continue;

            $anime    = $remaining[$rowSlug];
            $oldValue = isset($anime['aired_episodes']) ? (int)$anime['aired_episodes'] : null;

            if ($oldValue !== $epNum) {
                try {
                    $upd->execute([$epNum, (int)$anime['id']]);
                    $stats['updated']++;
                } catch (PDOException $e) {
                    $stats['errors']++;
                    error_log('[anime_tracker] aired sync UPDATE anime#'
                        . $anime['id'] . ': ' . $e->getMessage());
                }
            } else {
                $stats['unchanged']++;
            }

            unset($remaining[$rowSlug]);
        }
    }

    // Anything still in the remaining set was not in any of the weeks
    $stats['not_in_table'] += count($remaining);

    markLastAiredSync($pdo);

    return $stats;
}

/**
 * Helper used by syncAllOngoingAiredEpisodes to record a successful
 * run timestamp. Pulled out so error paths can decide whether to call
 * it without duplicating the SQL.
 */
function markLastAiredSync($pdo) {
    try {
        $upd = $pdo->prepare("
            INSERT INTO settings (name, value) VALUES ('last_aired_sync', ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $upd->execute([gmdate('Y-m-d H:i:s')]);
    } catch (PDOException $e) {
        error_log('[anime_tracker] last_aired_sync write: ' . $e->getMessage());
    }
}
?>