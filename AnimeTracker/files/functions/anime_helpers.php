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
