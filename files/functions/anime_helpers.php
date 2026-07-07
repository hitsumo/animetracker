<?php

/**
 * Anime Tracker - Anime Data Helpers (MAL/AniDB parse, next-episode date, completion)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

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
    // Safety brake: if the show's full run has already aired, do not keep
    // rolling the countdown forward week after week. Display is handled by
    // the completion logic / finished status from here.
    $totalEp = isset($anime['total_episodes']) ? (int)$anime['total_episodes'] : 0;
    $airedEp = isset($anime['aired_episodes']) ? (int)$anime['aired_episodes'] : 0;
    if ($totalEp > 0 && $airedEp >= $totalEp) {
        return;
    }

    if (empty($anime['next_episode_date'])) {
        // Senkron/import ile gelen animelerde next_episode_date YOK
        // (catalog.php onu turetilmis diye haric tutar). Senkronla GELEN
        // broadcast_day/broadcast_time'dan lokalde hesapla, yaz, don.
        $computed = calculateNextEpisodeDate($anime);
        if ($computed) {
            $stmt = $pdo->prepare("UPDATE animes SET next_episode_date = ? WHERE id = ?");
            $stmt->execute([$computed, $anime['id']]);
            $anime['next_episode_date'] = $computed;
        }
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

    // All conditions met - mark as watched. watch_status is personal
    // (user_anime, 1.0.1), so write it there for the current user instead
    // of the shared animes row.
    ua_set_state($pdo, current_user_id(), $anime['id'], ['watch_status' => 'Watched']);
    $anime['watch_status'] = 'Watched';

    return $anime;
}

/**
 * English-title display preference (0.7.2).
 *
 * The "show English titles" toggle is a per-user preference
 * stored in the user_pref table under the key display_title_english
 * ('1' = on, '0'/absent = off). It is INDEPENDENT of the UI language:
 * a user reading the Turkish interface can still choose to see English
 * titles, and vice versa. This mirrors the runtime-key family
 * (display_language, last_aired_sync, ...) so no migration is needed.
 *
 * The three helpers below follow the same load/report/render shape as
 * the i18n and watch_status helper families:
 *   title_pref_init($pdo)  - read the setting once into a static cache.
 *   show_english_titles()  - report the cached preference (false until
 *                            init is called, so a page that forgets to
 *                            init simply shows Romaji - least surprise).
 *   display_title($anime)  - render the right title for a row.
 */

/**
 * Internal cache for the English-title preference. Pulled into its own
 * accessor so init/report share one static without re-implementing it.
 *
 * @param bool|null $write  When non-null, replaces the cached value.
 * @return bool
 */
function _title_pref_cache($write = null) {
    static $enabled = false;
    if ($write !== null) {
        $enabled = (bool)$write;
    }
    return $enabled;
}

/**
 * Read display_title_english from settings into the static cache.
 *
 * Call once at the top of any page that renders anime titles, right
 * after lang_init($pdo). Subsequent calls in the same request just
 * re-read the (cheap) setting; the value is cached either way.
 *
 * @param PDO $pdo
 * @return void
 */
function title_pref_init($pdo) {
    // display_title_english is a per-user preference (user_pref, 1.0.1),
    // read for the current user (id 1 when MULTI_USER_MODE is off).
    _title_pref_cache(get_user_pref($pdo, current_user_id(), 'display_title_english', '0') === '1');
}

/**
 * Report whether English titles are currently preferred.
 *
 * Returns false if title_pref_init() has not run this request, which
 * keeps pages that have not opted in rendering Romaji titles.
 *
 * @return bool
 */
function show_english_titles() {
    return _title_pref_cache();
}

/**
 * Render the title to show for an anime row.
 *
 * Returns title_english when the preference is on AND the row has a
 * non-empty title_english; otherwise the Romaji title. The caller is
 * still responsible for htmlspecialchars() on output - this helper only
 * picks which string to show.
 *
 * @param array $anime  A row with 'title' and optionally 'title_english'.
 * @return string
 */
function display_title($anime) {
    if (
        show_english_titles()
        && isset($anime['title_english'])
        && trim((string)$anime['title_english']) !== ''
    ) {
        return $anime['title_english'];
    }
    return $anime['title'] ?? '';
}

/**
 * Adult-content visibility preference (1.1.2).
 *
 * Anime can be flagged 18+ at the catalog level (animes.is_adult). Whether
 * a viewer SEES those rows is a per-user preference stored in the user_pref
 * table under the key show_adult_content ('1' = show, '0'/absent = hide).
 * The default is HIDE: a viewer who has never opted in never sees adult
 * rows, which also protects a shared screen. This mirrors the runtime-key
 * family (display_language, display_title_english) so no migration is
 * needed for the preference itself.
 *
 * The helpers below follow the same load/report shape as the English-title
 * preference above:
 *   adult_pref_init($pdo)   - read the setting once into a static cache.
 *   show_adult_content()    - report the cached preference (false until
 *                             init is called, so a page that forgets to
 *                             init hides adult rows - least surprise, and
 *                             the safe default here).
 *   adult_filter_where()    - build the SQL fragment that hides adult rows
 *                             when the preference is off.
 */

/**
 * Internal cache for the adult-content preference. Pulled into its own
 * accessor so init/report share one static without re-implementing it.
 *
 * @param bool|null $write  When non-null, replaces the cached value.
 * @return bool
 */
function _adult_pref_cache($write = null) {
    static $enabled = false;
    if ($write !== null) {
        $enabled = (bool)$write;
    }
    return $enabled;
}

/**
 * Read show_adult_content from user_pref into the static cache.
 *
 * Call once at the top of any page that lists or counts anime, right
 * after lang_init($pdo). Absent key defaults to '0' (hide), so a brand
 * new user starts with adult content hidden.
 *
 * @param PDO $pdo
 * @return void
 */
function adult_pref_init($pdo) {
    // show_adult_content is a per-user preference (user_pref), read for the
    // current user (id 1 when MULTI_USER_MODE is off).
    _adult_pref_cache(get_user_pref($pdo, current_user_id(), 'show_adult_content', '0') === '1');
}

/**
 * Report whether adult content is currently shown.
 *
 * Returns false if adult_pref_init() has not run this request, which keeps
 * pages that have not opted in hiding adult rows (the safe default).
 *
 * @return bool
 */
function show_adult_content() {
    return _adult_pref_cache();
}

/**
 * Build the WHERE fragment that hides adult-flagged rows.
 *
 * When the viewer has opted in (show_adult_content() is true) this returns
 * an empty string, so no filtering is applied. Otherwise it returns a
 * fragment to append to an existing WHERE clause, e.g.:
 *
 *   $sql = "SELECT ... FROM animes a WHERE 1=1" . adult_filter_where('a');
 *
 * The comparison value is a literal (0) and $alias is caller-controlled
 * (never user input), so there is no bound parameter and no injection
 * surface. Keep passing a plain table alias.
 *
 * @param string $alias  Table alias for the animes table (default 'a').
 * @return string
 */
function adult_filter_where($alias = 'a') {
    if (show_adult_content()) {
        return '';
    }
    return " AND {$alias}.is_adult = 0";
}

/**
 * Mask an adult-flagged related row for neutral display (1.1.2).
 *
 * Used by ORDERED relation surfaces (chronology timeline, series chain)
 * where a +18 node must keep its structural place but not reveal its title
 * while the viewer has adult content hidden. Flat lists that can safely drop
 * a row use adult_filter_where() in SQL instead; this helper is for rows that
 * must stay in place.
 *
 * When show_adult_content() is true, or the row is not adult-flagged, the row
 * is returned unchanged. Otherwise the title is replaced with a neutral label
 * and the English title (if present) is cleared so display_title() cannot
 * reveal it either. The row's id/link is left intact: it points at the detail
 * page, which is itself gated, so following it only shows the neutral notice.
 *
 * @param array  $row       The related row (returns a copy).
 * @param string $flagKey   Key holding the is_adult flag (0/1).
 * @param string $titleKey  Key holding the (Romaji) title to mask.
 * @param string $enKey     Key holding the English title to clear.
 * @return array
 */
function adult_mask_related(array $row, $flagKey, $titleKey, $enKey) {
    if (show_adult_content() || empty($row[$flagKey])) {
        return $row;
    }
    $row[$titleKey] = t('adult.hidden_node_title');
    if (array_key_exists($enKey, $row)) {
        $row[$enKey] = null;
    }
    return $row;
}

/**
 * Filter an adult-flagged term list for display (1.1.3).
 *
 * Genres and tags (cumle) can be flagged 18+ (genres.is_adult /
 * tags.is_adult). This is the PHP-array counterpart of adult_filter_where:
 * used on DISPLAY surfaces that render a list of terms - the genre filter
 * dropdown (index), the recommendation sentence picker (recommendations)
 * and the genre badges on the detail page - to drop adult terms while the
 * viewer has adult content hidden.
 *
 * Scope note (Method A): this hides only the TERM, not the anime. An anime
 * whose own is_adult is 0 stays visible even if it carries an adult genre
 * or tag; only that term is dropped from the list. Curation surfaces
 * (add/edit, manage_genres, manage_tags) do NOT call this - a moderator
 * must see every term in order to flag it.
 *
 * When show_adult_content() is true the list is returned unchanged. Rows
 * missing the is_adult key are treated as not-adult (kept), so a caller
 * that forgot to select the column never accidentally hides everything.
 *
 * @param array $terms  Rows each optionally carrying an is_adult flag.
 * @return array        Re-indexed list with adult terms removed when off.
 */
function adult_filter_terms(array $terms) {
    if (show_adult_content()) {
        return $terms;
    }
    return array_values(array_filter($terms, function ($t) {
        return empty($t['is_adult']);
    }));
}
