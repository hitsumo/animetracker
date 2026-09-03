<?php

/**
 * Anime Tracker - Series + Chronology Helpers
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Split out of functions.php in 0.6.7 (code reorganization,
 * no behavior change). Loaded via the functions.php loader.
 */

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
    // watch_status / watched_episodes are personal (user_anime, 1.0.1):
    // join the current user's rows. 1.0.10: watch_status comes RAW -
    // NULL means "not selected" and the label/css helpers render it as
    // such; watched_episodes still defaults to 0. The :uid param is
    // first because it appears first in the statement (JOIN ON clause).
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.media_type,
               ua.watch_status,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               a.total_episodes, a.release_date, a.image_path
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.series_name = ? AND a.id != ?
        ORDER BY
            FIELD(a.media_type, 'TV', 'Film', 'OVA', 'Special', 'ONA'),
            a.release_date ASC,
            a.id ASC
    ");
    $stmt->execute([current_user_id(), $series_name, (int)$exclude_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Return all chronology markers for a given anime, with full details
 * of the related anime (title, watch_status, etc.) via JOIN.
 *
 * $order selects the sort axis (1.1.15):
 *   'release' (default) - by after_episode (where the related anime aired).
 *   'story'             - by COALESCE(story_after_episode, after_episode),
 *                         i.e. the recommended-watch point, falling back to
 *                         the release point when a marker has no story point.
 * $order is an internal enum (never raw user input), so the ORDER BY clause
 * is chosen from fixed strings - no injection surface.
 */
function getChronologyMarkers($pdo, $anime_id, $order = 'release') {
    $orderBy = ($order === 'story')
        ? 'COALESCE(cm.story_after_episode, cm.after_episode) ASC, cm.after_episode ASC'
        : 'cm.after_episode ASC';
    $stmt = $pdo->prepare("
        SELECT cm.id, cm.after_episode, cm.story_after_episode, cm.related_anime_id, cm.note,
               a.title AS related_title,
               a.alternative_titles AS related_alternative_titles,
               ua.watch_status AS related_watch_status,
               a.media_type AS related_media_type
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE cm.anime_id = ?
        ORDER BY $orderBy
    ");
    $stmt->execute([current_user_id(), (int)$anime_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================================
// Chronology display mode (1.1.15)
// =====================================================================
// The marker list (anime_details) and the timeline (chronology.php) can be
// shown in three modes:
//   'release' - order by the release point (after_episode).
//   'story'   - order by the story point (COALESCE(story_after_episode, ...)).
//   'both'    - render both lists, one under the other.
// The persistent default is a per-user preference set in list settings
// (key 'chrono_display_mode', default 'release'). A single cycle button
// stores an EPHEMERAL override in the session so it never overwrites the
// saved default. Precedence: session override > saved pref > 'release'.

/** Valid display modes, also the cycle order for chrono_next_mode(). */
function chrono_display_modes() {
    return ['release', 'story', 'both'];
}

/**
 * Resolve the active display mode for this request.
 * session override (cycle button) > saved user pref > 'release'.
 */
function chrono_current_mode($pdo) {
    if (isset($_SESSION['chrono_display_mode'])
        && in_array($_SESSION['chrono_display_mode'], chrono_display_modes(), true)) {
        return $_SESSION['chrono_display_mode'];
    }
    $pref = get_user_pref($pdo, current_user_id(), 'chrono_display_mode', 'release');
    return in_array($pref, chrono_display_modes(), true) ? $pref : 'release';
}

/** Next mode in the cycle release -> story -> both -> release. */
function chrono_next_mode($mode) {
    switch ($mode) {
        case 'release': return 'story';
        case 'story':   return 'both';
        default:        return 'release';
    }
}

/**
 * Localized label for a display mode (for the cycle button / settings).
 * Falls back to the raw mode key if a translation is missing.
 */
function chrono_mode_label($mode, $lang = null) {
    $key = 'chrono.mode.' . $mode;
    $label = t($key);
    return ($label === $key) ? $mode : $label;
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
               a.title AS related_title,
               a.alternative_titles AS related_alternative_titles,
               ua.watch_status AS related_watch_status,
               a.media_type AS related_media_type, a.id AS related_id
        FROM chronology_markers cm
        JOIN animes a ON a.id = cm.related_anime_id
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE cm.anime_id = ?
          AND cm.after_episode <= ?
          AND COALESCE(ua.watch_status, 'PlanToWatch') != 'Watched'
        ORDER BY cm.after_episode ASC
        LIMIT 1
    ");
    $stmt->execute([current_user_id(), (int)$anime_id, (int)$watched_episodes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Pick the display title for a related-anime row that uses the aliased
 * column names produced by the chronology queries above (related_title /
 * related_alternative_titles). Bridges those aliases to display_title() so
 * the Title Language preference applies to related anime the same way it
 * does to top-level rows. Falls back to the Romaji title when the row
 * carries no title in the chosen language. Output still needs
 * htmlspecialchars() at the call site.
 *
 * @param array $row  A row with 'related_title' and optionally 'related_alternative_titles'.
 * @return string
 */
function display_related_title($row) {
    return display_title([
        'title'              => $row['related_title'] ?? '',
        'alternative_titles' => $row['related_alternative_titles'] ?? null,
    ]);
}

/**
 * Zincir adlarini (1.1.36) datalist icin dondur.
 *
 * $series_name verilirse yalnizca O SERIDEKI adlar doner - kurator bir
 * animeyi duzenlerken kendi serisinin hatlarini gorur, katalogdaki butun
 * zincir adlari listeye dolmaz. Bos birakilirsa tum adlar doner.
 *
 * @param PDO         $pdo
 * @param string|null $series_name
 * @return string[]
 */
function getAllChainNames($pdo, $series_name = null)
{
    if ($series_name !== null && $series_name !== '') {
        $stmt = $pdo->prepare("
            SELECT DISTINCT chain_name
            FROM animes
            WHERE chain_name IS NOT NULL AND chain_name != ''
              AND series_name = ?
            ORDER BY chain_name ASC
        ");
        $stmt->execute([$series_name]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    $stmt = $pdo->query("
        SELECT DISTINCT chain_name
        FROM animes
        WHERE chain_name IS NOT NULL AND chain_name != ''
        ORDER BY chain_name ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Zincir adini karsilastirma icin normalize et (1.1.36).
 *
 * Bos dize ile NULL AYNI SEYDIR: ikisi de "adsiz" demektir. Form bos
 * gonderirse '' gelir, katalogtan gelen satirda NULL durur; ikisi ayri
 * sayilsaydi aralarindaki bag koparadi.
 *
 * @param mixed $v
 * @return string|null
 */
function chain_name_norm($v)
{
    $v = trim((string)($v === null ? '' : $v));
    return $v === '' ? null : $v;
}

/**
 * 1.1.36 - ZINCIRIN TEK KURALI.
 *
 * Iki kayit ayni zincirde mi? next_in_series bagi YALNIZCA iki ucun
 * zincir adi ayni oldugunda izlenir (ikisi de adsizsa yine ayni sayilir).
 *
 * Neden tek bir kural: 1.1.36 oncesinde zincir tamamen yuruyusten
 * turuyordu, yani "bu iki kayit ayni hatta mi" sorusunun cevabi yoktu -
 * yalnizca "birbirine bagli mi" vardi. Sailor Moon'da Crystal (AniDB'ye
 * gore alternative version) zincire baglanmisti ve zaman cizelgesi
 * "Sailor Stars'tan sonra Crystal" diyordu; spoiler kapisi da 90'lar
 * serisini Crystal'in oncülü sayiyordu. Ad bu soruyu cevaplanabilir
 * kiliyor ve cevabi TEK yerde tutuyor: bu fonksiyon.
 *
 * Adsiz veriye etkisi YOKTUR: 1.1.36 oncesi her satirin chain_name'i
 * NULL'dur, yani her karsilastirma true doner ve yuruyus 1.1.35 ile
 * birebir ayni kalir.
 *
 * @param mixed $a
 * @param mixed $b
 * @return bool
 */
function chain_same($a, $b)
{
    return chain_name_norm($a) === chain_name_norm($b);
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

// =====================================================================
// Series timeline view modes (1.1.23)
// =====================================================================
// series_timeline.php iki sekmeyle acilir:
//   'chain'   - next_in_series baglantili listesinin yuruyusu (ilk gorunum).
//   'airdate' - ayni series_name'i tasiyan HER kayit, ilk gosterim tarihine
//               gore. Elle kurulan zincire hic bagimli degildir; katalogdan
//               ice aktarilmis (baglanmamis) anime de listede yerini alir.
// Secim 1.1.15'teki chrono-mode kalibini izler: oturumdaki gecici sekme
// secimi > kayitli kisisel varsayilan (user_pref 'series_timeline_mode',
// liste ayarlarindan) > geriye uyumlu 'chain'.

/** Valid series-timeline view modes. */
function series_timeline_modes() {
    return ['chain', 'airdate'];
}

/**
 * Resolve the active series-timeline mode for this request.
 * session override (page tabs) > saved user pref > 'chain'.
 */
function series_timeline_current_mode($pdo) {
    if (isset($_SESSION['series_timeline_mode'])
        && in_array($_SESSION['series_timeline_mode'], series_timeline_modes(), true)) {
        return $_SESSION['series_timeline_mode'];
    }
    $pref = get_user_pref($pdo, current_user_id(), 'series_timeline_mode', 'chain');
    return in_array($pref, series_timeline_modes(), true) ? $pref : 'chain';
}

/**
 * Return every anime sharing the given series_name, ordered by first
 * air/release date. NULL tarihler sona duser - tarihi girilmemis bir
 * kayit serinin baslangici gibi gorunmesin. Kolon kumesi zincir
 * yuruyusununkiyle ayni sekildedir (arti end_date/is_adult), boylece
 * series_timeline.php tek kart sablonuyla iki modu da cizer.
 */
function getSeriesAnimesByAirDate($pdo, $series_name) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.media_type, a.total_episodes, a.aired_episodes,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               ua.watch_status,
               a.status, a.image_path,
               a.release_date, a.release_date_precision,
               a.end_date, a.end_date_precision,
               a.series_name, a.is_adult
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.series_name = ?
        ORDER BY (a.release_date IS NULL) ASC, a.release_date ASC, a.id ASC
    ");
    $stmt->execute([current_user_id(), $series_name]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =====================================================================
// Series chain walking + multi-chain discovery (1.1.25)
// =====================================================================
// Bir seri adi altinda TEK bir zincir olmak zorunda degil: Koukaku
// Kidoutai'de filmler bir zincir, SAC dizileri bambaska bir zincirdir.
// series_timeline.php 1.1.24'e kadar yalnizca istenen animenin icinde
// bulundugu zinciri cizerdi - digerlerinin VARLIGI bile gorunmezdi.
// Asagidaki yardimcilar seri adi grubunu tarayip kac ayri zincir
// oldugunu bulur; sayfa da bunlari "Diger Zincir 1..N" sekmesi yapar.
//
// Zincir yuruyusu (geri/ileri) 1.1.25'te series_timeline.php'den buraya
// tasindi: hem sayfa hem de kesif ayni yuruyusu kullansin, iki ayri
// kopya birbirinden ayrisamasin diye. Yuruyus next_in_series'i seri
// adina bakmadan izler - 1.1.24 oncesi davranisla birebir aynidir.

/**
 * Walk backwards via next_in_series until nobody points at the current
 * anime; that anime is the chain's start. Visited-set guards a cycle.
 *
 * 1.1.36: yuruyus ZINCIR ADI SINIRINDA DURUR (chain_same). Farkli adli
 * bir kayit bu animeyi isaret ediyorsa o baska bir hattir ve zincirin
 * basi burasidir. Adsiz veride her karsilastirma true dondugu icin
 * davranis 1.1.35 ile birebir aynidir.
 */
function seriesChainStartId($pdo, $anime_id) {
    $current = (int)$anime_id;
    $visited = [];

    // Yuruyusun tasidigi ad: BASLANGIC kaydinin adi. Her adimda yeniden
    // okunmaz - yoksa adi degisen bir halka zinciri sessizce baska bir
    // hatta kaydirirdi.
    $nameStmt = $pdo->prepare("SELECT chain_name FROM animes WHERE id = ?");
    $nameStmt->execute([$current]);
    $chainName = $nameStmt->fetchColumn();
    $nameStmt->closeCursor();
    if ($chainName === false) {
        return $current; // satir yok; cagiran taraf zaten bos zincir gorur
    }

    while (true) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare(
            "SELECT id, chain_name FROM animes WHERE next_in_series = ? ORDER BY id ASC"
        );
        $stmt->execute([$current]);
        $prev = null;
        // Birden cok kayit ayni animeyi isaret edebilir (kolon tekil DEGIL,
        // yalnizca hedefi tekil). Ayni adli ILK onculu al - id sirasi
        // seriesUnwatchedPredecessors ile ayni secimi yapar.
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (chain_same($row['chain_name'], $chainName)) {
                $prev = (int)$row['id'];
                break;
            }
        }
        $stmt->closeCursor();
        if (!$prev) break;
        $current = $prev;
    }
    return $current;
}

/**
 * Walk the chain forward from its start, returning only the ids. Used by
 * getSeriesChains() where the full display row would be wasted work.
 *
 * 1.1.36: yuruyus ZINCIR ADI SINIRINDA DURUR - baslangicin adindan farkli
 * adli bir halkaya gecilmez.
 */
function seriesChainIds($pdo, $start_id) {
    $ids = [];
    $current = (int)$start_id;
    $visited = [];
    $chainName = null;
    $first = true;

    while ($current) {
        if (isset($visited[$current])) break; // circular guard
        $visited[$current] = true;
        $stmt = $pdo->prepare("SELECT id, next_in_series, chain_name FROM animes WHERE id = ?");
        $stmt->execute([$current]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if (!$row) break;
        if ($first) {
            $chainName = $row['chain_name'];
            $first = false;
        } elseif (!chain_same($row['chain_name'], $chainName)) {
            break; // baska bir hatta gecis - zincir burada biter
        }
        $ids[] = (int)$row['id'];
        $current = $row['next_in_series'] ? (int)$row['next_in_series'] : null;
    }
    return $ids;
}

/**
 * 1.1.36 - ADLI bir zincirin, yuruyusun ULASAMADIGI uyelerini ekle.
 *
 * Ad UYELIGI belirler, next_in_series SIRAYI belirler. Bu ikisi ayri
 * oldugu icin bir uye adi tasiyip da hicbir baga sahip olmayabilir: ya
 * hentestir ya da aradaki bir bag girilmeyi unutulmustur. Boyle bir uyeyi
 * listeden dusurmek, adin YALAN soylemesi demek olurdu ("bu hatta 5 kayit
 * var" deyip 3 gostermek).
 *
 * Ulasilamayanlar YAYIN TARIHINE gore sona eklenir - uydurma bir sira
 * degil, elde olan tek nesnel olcut. Zincirin bagli kismi once gelir,
 * yani kuratorun kurdugu sira her zaman ustundedir.
 *
 * Adsiz zincirlerde (chain_name NULL) hicbir sey yapmaz: orada uyelik
 * zaten yuruyusun kendisidir.
 *
 * @param PDO    $pdo
 * @param int[]  $ids          Yuruyusun urettigi id dizisi (sirali).
 * @param string $series_name
 * @param mixed  $chain_name
 * @return int[]
 */
function seriesChainAppendUnlinked($pdo, array $ids, $series_name, $chain_name)
{
    $name = chain_name_norm($chain_name);
    if ($name === null || empty($series_name)) {
        return $ids;
    }
    $stmt = $pdo->prepare("
        SELECT id
        FROM animes
        WHERE series_name = ? AND chain_name = ?
        ORDER BY (release_date IS NULL) ASC, release_date ASC, id ASC
    ");
    $stmt->execute([$series_name, $name]);
    $members = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $stmt->closeCursor();

    $have = array_flip($ids);
    foreach ($members as $mid) {
        $mid = (int)$mid;
        if (!isset($have[$mid])) {
            $ids[] = $mid;
            $have[$mid] = true;
        }
    }
    return $ids;
}

/**
 * 1.1.36 - verilen id'ler icin gosterim satirlarini VERILEN SIRADA dondur.
 *
 * getSeriesChainRows() 1.1.35'te her halka icin ayri bir sorgu atiyordu;
 * ad geldikten sonra sira artik yalniz yuruyusten cikmadigi (ulasilmayan
 * uyeler sona ekleniyor) icin "once id listesini kur, sonra tek sorguyla
 * cek" kalibina gecildi. Kolon kumesi getSeriesAnimesByAirDate() ile ayni
 * kalir - series_timeline.php iki modu da tek kart sablonuyla cizer.
 *
 * @param PDO   $pdo
 * @param int[] $ids
 * @return array
 */
function seriesRowsByIds($pdo, array $ids)
{
    if (empty($ids)) {
        return [];
    }
    $clean = [];
    foreach ($ids as $i) {
        $i = (int)$i;
        if ($i > 0) { $clean[] = $i; }
    }
    if (empty($clean)) {
        return [];
    }
    $ph = implode(',', array_fill(0, count($clean), '?'));
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.media_type, a.total_episodes, a.aired_episodes,
               COALESCE(ua.watched_episodes, 0) AS watched_episodes,
               ua.watch_status,
               a.status, a.image_path,
               a.release_date, a.release_date_precision,
               a.end_date, a.end_date_precision,
               a.is_adult, a.next_in_series, a.series_name, a.chain_name
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.id IN ($ph)
    ");
    $stmt->execute(array_merge([current_user_id()], $clean));
    $byId = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $byId[(int)$r['id']] = $r;
    }
    $stmt->closeCursor();

    // Istenen SIRAYI koru - IN(...) sirayi garanti etmez.
    $out = [];
    foreach ($clean as $i) {
        if (isset($byId[$i])) { $out[] = $byId[$i]; }
    }
    return $out;
}

/**
 * Walk the chain forward from its start, collecting the full display rows
 * series_timeline.php draws. Column set matches getSeriesAnimesByAirDate()
 * so the page renders both views with one card template.
 *
 * 1.1.36: uc adim oldu - (1) ad sinirinda duran ileri yuruyus,
 * (2) adli zincirin ulasilmayan uyelerinin tarihe gore sona eklenmesi,
 * (3) tek sorguyla gosterim satirlarinin cekilmesi.
 */
function getSeriesChainRows($pdo, $start_id) {
    $start_id = (int)$start_id;
    if ($start_id <= 0) {
        return [];
    }

    $ids = seriesChainIds($pdo, $start_id);
    if (empty($ids)) {
        return [];
    }

    // Baslangicin serisi ve adi - ulasilmayan uyeler bu ikisiyle bulunur.
    $meta = $pdo->prepare("SELECT series_name, chain_name FROM animes WHERE id = ?");
    $meta->execute([$start_id]);
    $head = $meta->fetch(PDO::FETCH_ASSOC);
    $meta->closeCursor();
    if ($head) {
        $ids = seriesChainAppendUnlinked($pdo, $ids, $head['series_name'], $head['chain_name']);
    }

    return seriesRowsByIds($pdo, $ids);
}

/**
 * Discover every distinct next_in_series chain inside a series_name group.
 *
 * Grup uyeleri ilk gosterim tarihine gore taranir, her uye icin zincirin
 * basi bulunur ve zincir bir kez yuruyulur; ayni zincirin diger uyeleri
 * atlanir. Sonuc bu yuzden hep ayni sirada gelir: "Diger Zincir 1" en
 * eski tarihli zincirdir.
 *
 * $min_length varsayilan olarak 2'dir: hicbir yere baglanmamis TEK bir
 * kayit zincir sayilmaz, yoksa seri adini paylasan her bagimsiz film ayri
 * bir sekme uretirdi. O kayitlar zaten "Yayin Tarihi" sekmesinde durur.
 *
 * Donen her oge: ['start_id' => int, 'ids' => int[], 'count' => int].
 */
function getSeriesChains($pdo, $series_name, $min_length = 2) {
    if (empty($series_name)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT id, chain_name
        FROM animes
        WHERE series_name = ?
        ORDER BY (release_date IS NULL) ASC, release_date ASC, id ASC
    ");
    $stmt->execute([$series_name]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    $seen = [];
    $chains = [];
    foreach ($members as $member) {
        $memberId = (int)$member['id'];
        if (isset($seen[$memberId])) {
            continue;
        }
        $name    = chain_name_norm($member['chain_name']);
        $startId = seriesChainStartId($pdo, $memberId);
        $ids     = seriesChainIds($pdo, $startId);

        // 1.1.36: adli zincirde uyelik ADDAN gelir, yuruyusten degil -
        // hic baglanmamis uyeler de bu hatta aittir.
        $ids = seriesChainAppendUnlinked($pdo, $ids, $series_name, $name);

        foreach ($ids as $cid) {
            $seen[$cid] = true;
        }

        // 1.1.36: $min_length yalnizca ADSIZ zincirlere uygulanir. Adsiz tek
        // bir kayit "zincir" degildir (yoksa seri adini paylasan her bagimsiz
        // film ayri bir sekme uretirdi, 1.1.25 karari). ADI OLAN tek kayit ise
        // bilincli bir beyandir: "bu kayit kendi hattidir" - Space Adventure
        // Cobra'nin 1982 filmi tam olarak budur, TV dizisinin devami degil
        // AniDB'ye gore alternative version'udur ve zincire baglanmamalidir.
        if ($name === null && count($ids) < $min_length) {
            continue;
        }

        $chains[] = [
            'start_id' => $startId,
            'ids'      => $ids,
            'count'    => count($ids),
            'name'     => $name,
        ];
    }
    return $chains;
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

// =====================================================================
// Konu spoiler kapisi (1.1.33)
// =====================================================================
// Bir serinin ikinci ve sonraki halkalarinin KONUSU, kendinden onceki
// halkalarin sonunu ele verir: "X oldukten sonra kalan ekip..." diye
// baslayan bir ozet, o animeyi henuz izlememis kisiye sifir bilgi verir
// ama bir onceki sezonu bastan sona spoiler'lar.
//
// Kural: zincirde (next_in_series) bu animeden ONCE gelen kayitlardan
// biri bile izlenmemisse konu dogrudan basilmaz, "okumak istiyorum"
// dugmesinin arkasina alinir. Onceki halkalarin HEPSI izlendiyse ortada
// dugme de yoktur - sayfa 1.1.32'deki gibi gorunur.
//
// UC KARAR:
//
//   (1) ZINCIRDEKI TUM ONCEKILER sorulur, yalnizca bir onceki halka
//       degil. S3'un sayfasinda S2 izlenmis ama S1 atlanmissa konu yine
//       de S1'i ele verebilir; "yalniz en yakin halkaya bak" kurali o
//       kisiyi korumazdi.
//
//   (2) ANONIM ZIYARETCIDE DE CALISIR. Anonim kullanicinin kisisel
//       izleme verisi yoktur, yani hicbir sey izlenmis sayilmaz ve devam
//       halkalarinin konusu kapinin arkasinda acilir. Katalogu ilk kez
//       gezen kisi tam da korunmasi gereken kisidir; maliyeti tek tik.
//
//   (3) ANIMEYE BASLAMIS KULLANICIYA KAPI KURULMAZ. Izlemekte oldugun
//       (ya da bitirdigin, erteledigin, biraktigin) bir animenin
//       konusunda senin icin spoiler yoktur - onceki sezonu atlamis
//       olsan bile onu zaten bu animeyi izlerken ogrendin.
//
// Kapi kisi bazli tercihle kapatilabilir: user_pref 'spoiler_guard'
// (varsayilan acik), Liste Ayarlari > "Spoiler korumasi".

/**
 * Kapi acik mi? user_pref 'spoiler_guard', varsayilan ACIK.
 *
 * Yalnizca '0' degeri kapatir; anahtar hic yoksa (yeni kurulum, anonim
 * ziyaretci, tercihe hic dokunmamis kullanici) ozellik aciktir.
 *
 * Sonuc istek basina onbelleklenir - ayni sayfada birden cok yerden
 * cagrilabilir ve tercih bir istek icinde degismez.
 */
function spoiler_guard_enabled($pdo) {
    static $enabled = null;
    if ($enabled === null) {
        $enabled = (get_user_pref($pdo, current_user_id(), 'spoiler_guard', '1') !== '0');
    }
    return $enabled;
}

/**
 * Zincirde bu animeden once gelen ve HENUZ IZLENMEMIS kayitlar.
 *
 * next_in_series geriye dogru yurunur (bu animeyi isaret eden kayit, onu
 * isaret eden kayit, ...). Yalnizca watch_status'u 'Watched' OLMAYAN
 * halkalar doner; en yakini listenin basindadir.
 *
 * "Izleniyor" izlenmis SAYILMAZ: yarim birakilmis bir sezonun sonunu ele
 * veren ozet de spoiler'dir.
 *
 * seriesChainStartId() ile ayni yuruyus ve ayni dongu korumasi (visited
 * kumesi); fark, bu yuruyusun her adimda kisisel izleme durumunu da
 * okumasi ve zincirin basini degil izlenmemis halkalari toplamasidir.
 * Ayni kaydi iki kayit birden isaret ediyorsa (veri bunu engellemez) en
 * kucuk id secilir - seriesChainStartId() de oyle yapar.
 *
 * @param PDO $pdo
 * @param int $anime_id  Zincirde durdugumuz kayit.
 * @param int $limit     Toplanacak izlenmemis halka tavani (guvenlik agi).
 * @return array         Satirlar: id, title, alternative_titles, is_adult, watch_status.
 */
function seriesUnwatchedPredecessors($pdo, $anime_id, $limit = 25) {
    $unwatched = [];
    $current   = (int)$anime_id;
    $visited   = [$current => true];

    // 1.1.36: KAPI DA ZINCIR ADI SINIRINDA DURUR.
    //
    // Bu, adin varlik sebeplerinden biridir. Sailor Moon'da Crystal 90'lar
    // zincirine bagliydi ve kapi, Crystal'i acan kisiye 90'lar serisinin
    // sekiz kaydini "izlenmemis oncul" diye sayiyordu. Oysa Crystal ayni
    // hikayenin YENIDEN ANLATIMIDIR (AniDB: alternative version), oncesi
    // degil. Iki hat ayri adlandirildiginda kapi artik sinirda durur.
    //
    // TARIHE DUSULMEZ: getSeriesChainRows() adli bir zincirin baglanmamis
    // uyelerini yayin tarihine gore listeye ekler, ama KAPI bunu YAPMAZ.
    // Listede bir kaydi gostermek zararsizdir; "sunu once izlemelisin"
    // demek ise bir iddiadir ve yalnizca kuratorun ELLE kurdugu baga
    // dayanmalidir. Girilmemis bir bagi tarihten uydurmak, spoiler
    // uyarisini tahmine cevirirdi.
    $nameStmt = $pdo->prepare("SELECT chain_name FROM animes WHERE id = ?");
    $nameStmt->execute([$current]);
    $chainName = $nameStmt->fetchColumn();
    $nameStmt->closeCursor();
    if ($chainName === false) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.alternative_titles, a.is_adult, a.chain_name,
               ua.watch_status
        FROM animes a
        LEFT JOIN user_anime ua
               ON ua.anime_id = a.id AND ua.user_id = ?
        WHERE a.next_in_series = ?
        ORDER BY a.id ASC
    ");

    while (count($unwatched) < $limit) {
        $stmt->execute([current_user_id(), $current]);
        // Ayni adli ILK onculu al (id sirasi) - seriesChainStartId() ile
        // ayni secim. Kolon tekil olmadigi icin birden cok kayit ayni
        // animeyi isaret edebilir.
        $prev = null;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (chain_same($row['chain_name'], $chainName)) {
                $prev = $row;
                break;
            }
        }
        $stmt->closeCursor();
        if (!$prev) {
            break;                        // zincirin basina gelindi
        }
        $prevId = (int)$prev['id'];
        if (isset($visited[$prevId])) {
            break;                        // circular guard
        }
        $visited[$prevId] = true;
        if (($prev['watch_status'] ?? null) !== 'Watched') {
            $unwatched[] = $prev;
        }
        $current = $prevId;
    }

    return $unwatched;
}

/**
 * Bu anime icin konu spoiler kapisi gerekiyor mu?
 *
 * @param PDO   $pdo
 * @param array $anime  En az id; varsa watch_status ve watched_episodes
 *                      da okunur (ikisi de KISISEL alandir - cagiran
 *                      taraf user_anime ile birlestirilmis satiri verir).
 * @return array|null   Kapi gerekmiyorsa null. Gerekiyorsa:
 *                        ['count' => izlenmemis halka sayisi,
 *                         'title' => en yakin izlenmemis halkanin adi]
 */
function spoiler_gate($pdo, array $anime) {
    if (empty($anime['id']) || !spoiler_guard_enabled($pdo)) {
        return null;
    }

    // Karar (3): animeye baslamis kullaniciya kapi kurulmaz. Bolum sayaci
    // 0'in ustundeyse ya da durum "planlandi / secilmemis" disinda bir
    // seyse kisi bu animenin icindedir.
    $status = $anime['watch_status'] ?? null;
    if ((int)($anime['watched_episodes'] ?? 0) > 0
        || ($status !== null && $status !== '' && $status !== 'PlanToWatch')) {
        return null;
    }

    $unwatched = seriesUnwatchedPredecessors($pdo, (int)$anime['id']);
    if (!$unwatched) {
        return null;
    }

    // 1.1.2 - en yakin halka +18 damgaliysa ve izleyici yetiskin icerigi
    // acmamissa basligi notr yer tutucuyla maskelenir: kapinin uyari
    // metni, gizlenmesi gereken bir adi sizdiran yer olmamali.
    $nearest = adult_mask_related($unwatched[0], 'is_adult', 'title', 'alternative_titles');

    return [
        'count' => count($unwatched),
        'title' => display_title($nearest),
    ];
}

/**
 * Kapinin ACILIS markup'i (spoiler_gate_close() ile birlikte kullanilir).
 *
 * Neden acilis/kapanis cifti, tek bir spoiler_wrap($gate, $html) degil:
 * kapinin sardigi icerik cagiran sayfanin KENDI sablonudur (konu metni +
 * ceviri notu, ya da 200 karakterlik tanitim). Onu bir dizeye toplamak
 * o markup'i yeniden yazmayi gerektirirdi; bu cift, var olan markup'a
 * hic dokunmadan etrafina gecirilir.
 *
 * JAVASCRIPT YOKTUR. <details>/<summary> tarayicinin kendi acilir
 * bolumudur: JS kapaliyken de acilir, klavyeyle gezilebilir ve icerik
 * DOM'da durdugu icin arama motoru sayfanin konusunu yine gorur. Kapi
 * bir spoiler perdesidir, erisim denetimi DEGIL - gizlenen sey zaten
 * herkese acik bir katalog ozetidir.
 *
 * @param array|null $gate  spoiler_gate() sonucu; null ise bos dize.
 * @return string
 */
function spoiler_gate_open($gate) {
    if (!$gate) {
        return '';
    }
    // Baslik once kacislanir, sonra ceviri kalibina yerlestirilir: kalip
    // dil dosyasindan gelir (guvenilir), degisken kisim katalog verisidir.
    $title  = htmlspecialchars($gate['title'], ENT_QUOTES, 'UTF-8');
    $notice = ((int)$gate['count'] > 1)
        ? sprintf(t('spoiler.notice_more_fmt'), $title, (int)$gate['count'] - 1)
        : sprintf(t('spoiler.notice_fmt'), $title);

    return '<details class="spoiler-guard">'
         . '<summary class="spoiler-guard-toggle">'
         . '<span class="spoiler-guard-note">' . $notice . '</span>'
         . '<span class="spoiler-guard-action spoiler-guard-show">'
         . htmlspecialchars(t('spoiler.reveal'), ENT_QUOTES, 'UTF-8') . '</span>'
         . '<span class="spoiler-guard-action spoiler-guard-hide">'
         . htmlspecialchars(t('spoiler.hide'), ENT_QUOTES, 'UTF-8') . '</span>'
         . '</summary>'
         . '<div class="spoiler-guard-body">';
}

/** Kapinin kapanis markup'i - bkz. spoiler_gate_open(). */
function spoiler_gate_close($gate) {
    return $gate ? '</div></details>' : '';
}
