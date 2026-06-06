<?php

/**
 * Anime Tracker - AnimeFillerList Import Endpoint (0.7)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by filler_edit.php when the user pastes an
 * animefillerlist.com show URL and clicks "Import". Fetches the show
 * page server-side (browsers cannot due to CORS), parses the per-episode
 * filler/canon classification and returns it as an {episode_no: type}
 * map. It does NOT write to the DB - the map is loaded into the grid for
 * the user to review, then saved through the existing update_filler.php
 * Save button. This keeps the curator in the loop and reuses one save
 * path.
 *
 * Episode numbers beyond the anime's known episode count are dropped
 * (the grid has no cell for them); the count of dropped episodes is
 * reported so the user can raise the episode count if needed.
 *
 * Request:
 *   POST fetch_filler.php
 *     csrf_token=<token>
 *     anime_id=<int>
 *     url=<animefillerlist show url or bare slug>
 *
 * Response (success):
 *   {
 *     "success":  true,
 *     "episodes": { "6": "Filler", "112": "MangaCanon", ... },
 *     "total":    568,          // episodes returned (within grid range)
 *     "skipped":  0,            // episodes dropped (beyond episode count)
 *     "count":    1061,         // anime's episode count used as the cap
 *     "counts":   { "Filler": 469, "MangaCanon": 591, ... }
 *   }
 *
 * Response (error):
 *   { "success": false, "error": "Turkish message", "code": "bad_url" }
 *
 * The fetch + parse live in functions/filler_helpers.php; this file is
 * the HTTP/CSRF wrapper.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function ff_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ff_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    ff_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
    ]);
}

// External metadata fetch (scrapes AnimeFillerList). Require a logged-in user
// to block anonymous abuse (online only; no-op in self-host). Read-only, so
// login is enough; no catalog write happens here.
require_login(true);

// --- Input ---------------------------------------------------------------

$animeId = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
if ($animeId <= 0) {
    ff_respond(['success' => false, 'error' => 'Gecersiz anime ID.']);
}

$rawUrl = trim($_POST['url'] ?? '');
$slug   = parseAnimeFillerListSlug($rawUrl);
if ($slug === null) {
    ff_respond([
        'success' => false,
        'error'   => 'Gecersiz AnimeFillerList adresi. Ornek: https://www.animefillerlist.com/shows/detective-conan',
        'code'    => 'bad_url',
    ]);
}

// Anime var mi + bolum sayisi (grid sinirini bulmak icin).
$stmt = $pdo->prepare('SELECT id, total_episodes, aired_episodes FROM animes WHERE id = ?');
$stmt->execute([$animeId]);
$anime = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$anime) {
    ff_respond(['success' => false, 'error' => 'Anime bulunamadi.']);
}

// --- Fetch + parse -------------------------------------------------------

$page = fetchAnimeFillerListPage($slug);
if (isset($page['error'])) {
    $msg = [
        'bad_slug'   => 'Gecersiz show adresi.',
        'curl'       => 'Sayfaya ulasilamadi (baglanti hatasi). Internet baglantinizi kontrol edin.',
        'http_404'   => 'Bu show AnimeFillerList\'te bulunamadi (404). Adresi kontrol edin.',
        'http_other' => 'AnimeFillerList beklenmeyen bir yanit dondurdu.',
    ];
    ff_respond([
        'success' => false,
        'error'   => $msg[$page['error']] ?? 'Sayfa cekilemedi.',
        'code'    => $page['error'],
    ]);
}

$parsed = parseAnimeFillerList($page['html']);
if (isset($parsed['error'])) {
    ff_respond([
        'success' => false,
        'error'   => 'Sayfa cozumlenemedi (sayfa yapisi degismis olabilir).',
        'code'    => $parsed['error'],
    ]);
}

// --- Episode-count cap ---------------------------------------------------
// Grid yalnizca kayittaki bolum sayisi kadar hucre icerir. Bu sayinin
// otesindeki bolumleri at; kac tane atildigini bildir.
$count = 0;
if (!empty($anime['total_episodes'])) {
    $count = (int)$anime['total_episodes'];
} elseif (!empty($anime['aired_episodes'])) {
    $count = (int)$anime['aired_episodes'];
}

$episodes = $parsed['episodes'];
$skipped  = 0;
if ($count > 0) {
    foreach ($episodes as $ep => $type) {
        if ($ep > $count) {
            unset($episodes[$ep]);
            $skipped++;
        }
    }
}

// counts'u da capli sete gore yeniden hesapla (kullaniciya dogru ozet).
$counts = [];
foreach ($episodes as $type) {
    $counts[$type] = ($counts[$type] ?? 0) + 1;
}

ff_respond([
    'success'  => true,
    'episodes' => (object)$episodes,   // bos olsa bile JSON object kalsin
    'total'    => count($episodes),
    'skipped'  => $skipped,
    'count'    => $count,
    'counts'   => $counts,
]);
