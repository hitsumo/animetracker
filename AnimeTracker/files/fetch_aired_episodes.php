<?php

/**
 * Anime Tracker - Aired Episodes Sync Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by edit_anime.php when the user clicks the
 * "Senkronize Et" button next to the aired_episodes input. Queries the
 * AnimeSchedule timetable for the anime's MAL id and writes the
 * EpisodeNumber it finds into animes.aired_episodes.
 *
 * The DB write is deliberate even though the user has not clicked
 * "Kaydet" yet. Reasoning: aired_episodes is a metadata field, not part
 * of the user's personal watch progress. If the user navigates away
 * without saving the rest of the form, the new aired count is still
 * the most accurate value we have. The button is only visible when the
 * anime is ongoing AND has a MAL id, so misuse is unlikely.
 *
 * Request:
 *   POST fetch_aired_episodes.php
 *     csrf_token=<token>
 *     anime_id=<int>
 *
 * Response (success):
 *   {
 *     "success": true,
 *     "aired_episodes": 5,
 *     "old_value":     4,        // null if previously unset
 *     "changed":       true,
 *     "week_offset":   0          // 0 = found in current week, 2 = 2 weeks back
 *   }
 *
 * Response (error):
 *   {
 *     "success": false,
 *     "error":   "User-facing Turkish message",
 *     "code":    "not_in_timetable" // raw code for client-side branching
 *   }
 *
 * The endpoint reuses syncSingleAiredEpisodes() in functions.php; this
 * file is just the HTTP wrapper around that helper.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function ae_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ae_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    ae_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
    ]);
}

// --- Input ---------------------------------------------------------------

$animeId = isset($_POST['anime_id']) ? (int)$_POST['anime_id'] : 0;
if ($animeId <= 0) {
    ae_respond([
        'success' => false,
        'error'   => 'Gecersiz anime ID.',
    ]);
}

// --- Sync ---------------------------------------------------------------

$result = syncSingleAiredEpisodes($pdo, $animeId, 3);

if (isset($result['error'])) {
    $code = $result['error'];
    $msg  = '';

    switch ($code) {
        case 'not_found':
            $msg = 'Anime bulunamadi.';
            break;
        case 'no_mal_id':
            $msg = 'Bu animenin MAL ID si yok. Once MyAnimeList linkini ekleyin.';
            break;
        case 'not_ongoing':
            $msg = 'Bu ozellik sadece "Yayin Devam Ediyor" durumundaki animeler icindir.';
            break;
        case 'no_slug':
            $msg = 'Once gecerli bir AnimeSchedule URL si ekleyin (Otomatik Doldur butonu yanindaki alan).';
            break;
        case 'not_in_timetable':
            $msg = 'Anime son 3 haftalik takvimde bulunamadi. Yayin yeni baslamis veya uzun bir mola olabilir.';
            break;
        case 'no_key':
            $msg = 'AnimeSchedule API anahtari config.php icinde tanimli degil.';
            break;
        case 'curl':
            $msg = 'AnimeSchedule sunucusuna ulasilamadi. Internet baglantinizi kontrol edin.';
            break;
        case 'http_401':
            $msg = 'API anahtari gecersiz. config.php icindeki ANIMESCHEDULE_API_KEY i kontrol edin.';
            break;
        case 'http_403':
            $msg = 'API anahtari bu istek icin yetersiz.';
            break;
        case 'http_429':
            $msg = 'Cok fazla istek gonderildi. Birkac saniye bekleyip tekrar deneyin.';
            break;
        case 'http_other':
            $http = $result['http_code'] ?? '?';
            $msg = 'AnimeSchedule sunucusu beklenmedik bir cevap dondurdu (HTTP ' . $http . ').';
            break;
        case 'bad_json':
            $msg = 'AnimeSchedule cevabi cozumlenemedi.';
            break;
        case 'bad_input':
            $msg = 'Hafta veya yil parametresi gecersiz.';
            break;
        default:
            $msg = 'Bilinmeyen bir hata olustu.';
            break;
    }

    ae_respond([
        'success' => false,
        'error'   => $msg,
        'code'    => $code,
    ]);
}

ae_respond([
    'success'        => true,
    'aired_episodes' => $result['aired_episodes'],
    'old_value'      => $result['old_value'],
    'changed'        => $result['changed'],
    'week_offset'    => $result['week_offset'],
]);
