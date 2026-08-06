<?php

/**
 * Anime Tracker - Anime Link Search Endpoint (synopsis shortcode picker)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.26. Backs the "anime baglantisi ekle" picker next to
 * every synopsis textarea in add_anime.php / edit_anime.php.
 *
 * WHY THIS EXISTS: 1.1.19 gave the synopsis an inline link shortcode
 * ([[anime:<mal_id>|etiket]], see functions/synopsis_helpers.php) but no
 * writing aid at all - the curator had to type the code by hand AND know
 * the target's MAL number by heart. That was recorded as the first
 * follow-up job (proje_durumu_70, "Kalan / notlar"). This endpoint is the
 * data half of that job: type a title, get back the mal_id.
 *
 * WHY AN ENDPOINT AND NOT AN INLINE LIST: edit_anime.php already ships the
 * whole anime list once, for the next_in_series <select>. That list carries
 * id + title + media_type and is bounded by "same series or everything" -
 * shipping a SECOND full copy (this time with mal_id, alternative titles
 * and dates) on every form load would grow the page with the catalog, and
 * the catalog is the part of this project that is meant to grow. A search
 * endpoint stays flat: it transfers at most SEARCH_LIMIT rows per keystroke
 * burst, whatever the catalog size.
 *
 * Request:
 *   GET anime_link_search.php?q=<query>
 *
 * Response (success):
 *   {
 *     "success": true,
 *     "results": [
 *       { "mal_id": 52991, "title": "Sousou no Frieren",
 *         "year": "2023", "media_type": "TV" },
 *       ...
 *     ]
 *   }
 *
 * Response (error): {"success": false, "error": "<code>"} with a 4xx status.
 *
 * Only rows that CAN be linked are returned: the shortcode addresses its
 * target by mal_id, so a row without one is not an answer to this question
 * (known limit, KARARLAR_4 sec.69). The viewer's +18 preference is honored
 * exactly as on every listing surface (1.1.2) - the picker must not become
 * a side channel that reveals masked titles.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Rows returned per query. Deliberately small: this is a "pick the one you
// meant" list, not a browse surface. A curator who cannot see the target in
// the first dozen hits types more letters, which is cheaper than paging.
const ANIME_LINK_SEARCH_LIMIT = 12;

// Shortest query we answer. One or two letters match nearly everything and
// would cost a full-table LIKE scan for a list nobody can use.
const ANIME_LINK_SEARCH_MIN = 2;

function als_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    als_respond(['success' => false, 'error' => 'method']);
}

// Gate = the WEAKER of the two hosting pages. edit_anime.php is moderator+,
// but add_anime.php is open to any logged-in member (it writes source='local'
// into the approval queue), and the picker sits on both. Gating at moderator
// would leave a normal member staring at a button that always answers 403.
//
// Nothing is opened up by that choice: every row returned here is one the
// member can already reach through the catalog listing and its search box,
// and the +18 preference is applied below exactly as it is there. Anonymous
// visitors are still refused - this is a bulk "what titles exist" view and
// there is no page that needs it before login.
require_login(true);

// The two per-user preferences that decide WHAT the viewer may see and HOW a
// title is spelled. Both must run before the query/render below, exactly as
// on the listing pages.
lang_init($pdo);
title_pref_init($pdo);
adult_pref_init($pdo);

// --- Input ---------------------------------------------------------------

$q = trim((string)($_GET['q'] ?? ''));

if (mb_strlen($q) < ANIME_LINK_SEARCH_MIN) {
    // Not an error: the field is simply still too short to search on.
    als_respond(['success' => true, 'results' => []]);
}

// LIKE wildcards inside the user's text are escaped so that a title
// containing % or _ is searched for literally.
$like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';

// --- Query ---------------------------------------------------------------

// mal_id IS NOT NULL: see the header - a row without one cannot be the
// target of the shortcode, so offering it would produce a code that silently
// resolves to nothing. alternative_titles is searched too, because the
// curator will often type the name they SEE (Baslik Dili preference, 1.1.21)
// rather than the romaji title stored in `title`.
$sql = "SELECT id, mal_id, title, alternative_titles, media_type, release_date
        FROM animes
        WHERE mal_id IS NOT NULL AND mal_id <> 0
          AND (title LIKE :q1 OR alternative_titles LIKE :q2)"
     . adult_filter_where('animes') . "
        ORDER BY (title LIKE :q3) DESC, title ASC
        LIMIT " . (int)ANIME_LINK_SEARCH_LIMIT;

$stmt = $pdo->prepare($sql);
// :q3 repeats :q1 on purpose - it lifts rows whose MAIN title matches above
// rows that only matched on an alternative title, so "Frieren" puts the
// Frieren row first even when a dozen others carry it as an alt title.
$stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);

$results = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $results[] = [
        'mal_id'     => (int)$row['mal_id'],
        // display_title() honors the Baslik Dili preference, so the label
        // the curator picks is the one they were reading. It becomes the
        // shortcode's label text on the client side.
        'title'      => display_title($row),
        'year'       => (!empty($row['release_date']) && strlen($row['release_date']) >= 4)
                        ? substr($row['release_date'], 0, 4)
                        : '',
        'media_type' => (string)($row['media_type'] ?? ''),
    ];
}

als_respond(['success' => true, 'results' => $results]);
