<?php

/**
 * Anime Tracker - AnimeSchedule API Fetch Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by add_anime.php and edit_anime.php when the
 * user clicks "Otomatik Doldur" next to the AnimeSchedule URL field.
 *
 * Request:
 *   POST fetch_animeschedule.php
 *     csrf_token=<token>
 *     url=<full AnimeSchedule URL>
 *
 * Response (success):
 *   {
 *     "success": true,
 *     "fields": {
 *       "broadcast_day":      "Persembe",
 *       "broadcast_time":     "23:30",
 *       "broadcast_timezone": "Asia/Tokyo",
 *       "status":             "Yayin Devam Ediyor",
 *       "total_episodes":     12      // only for finished anime
 *     }
 *   }
 *
 * Response (error):
 *   {
 *     "success": false,
 *     "error":   "User-facing Turkish message",
 *     "code":    "no_key" // optional, for client-side branching
 *   }
 *
 * The "fields" object only contains keys we successfully mapped from
 * the API response. The frontend iterates this object and fills only
 * empty form fields - existing user input is never overwritten.
 *
 * Errors are user-facing Turkish strings. Detailed cURL/HTTP info goes
 * to error_log via fetchAnimeScheduleData() instead of leaking to the
 * client.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Helper: emit JSON and stop. Centralised so every exit path uses the
// same encoding flags and we never accidentally output anything else.
function as_respond($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Gates ---------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    as_respond([
        'success' => false,
        'error'   => 'Sadece POST istekleri kabul edilir.',
    ]);
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    as_respond([
        'success' => false,
        'error'   => 'CSRF tokeni gecersiz. Sayfayi yenileyip tekrar deneyin.',
    ]);
}

// External metadata fetch (consumes the API key). Require a logged-in user
// to block anonymous abuse (online only; no-op in self-host). Read-only, so
// login is enough; no catalog write happens here.
require_login(true);

// --- Input ---------------------------------------------------------------

$url = trim($_POST['url'] ?? '');
if ($url === '') {
    as_respond([
        'success' => false,
        'error'   => 'AnimeSchedule URL alani bos. Once URL girin.',
    ]);
}

$slug = parseAnimeScheduleSlug($url);
if ($slug === null) {
    as_respond([
        'success' => false,
        'error'   => 'Gecerli bir AnimeSchedule URL girin. Ornek: https://animeschedule.net/anime/solo-leveling',
    ]);
}

// --- Fetch from API ------------------------------------------------------

$apiResult = fetchAnimeScheduleData($slug);

// Error path: translate the structured error code into a Turkish
// message the user can act on. We keep the original code in the
// response so the frontend can branch (e.g. show a config-help link
// only for 'no_key').
if (isset($apiResult['error'])) {
    $code = $apiResult['error'];
    $userMessage = '';

    switch ($code) {
        case 'no_key':
            $userMessage = 'AnimeSchedule API anahtari config.php icinde tanimli degil. config.php dosyasini acip ANIMESCHEDULE_API_KEY satirini ekleyin.';
            break;
        case 'bad_slug':
            $userMessage = 'AnimeSchedule URL inden anime adi cikarilamadi.';
            break;
        case 'curl':
            $userMessage = 'AnimeSchedule sunucusuna ulasilamadi. Internet baglantinizi kontrol edin.';
            break;
        case 'http_404':
            $userMessage = 'Anime AnimeSchedule de bulunamadi. URL i kontrol edin.';
            break;
        case 'http_401':
            $userMessage = 'API anahtari gecersiz. config.php icindeki ANIMESCHEDULE_API_KEY i kontrol edin.';
            break;
        case 'http_403':
            $userMessage = 'API anahtari bu istek icin yetersiz.';
            break;
        case 'http_429':
            $userMessage = 'Cok fazla istek gonderildi. Birkac saniye bekleyip tekrar deneyin.';
            break;
        case 'http_other':
            $httpCode = $apiResult['http_code'] ?? '?';
            $userMessage = 'AnimeSchedule sunucusu beklenmedik bir cevap dondurdu (HTTP ' . $httpCode . ').';
            break;
        case 'bad_json':
            $userMessage = 'AnimeSchedule cevabi cozumlenemedi.';
            break;
        default:
            $userMessage = 'Bilinmeyen bir hata olustu.';
            break;
    }

    as_respond([
        'success' => false,
        'error'   => $userMessage,
        'code'    => $code,
    ]);
}

// --- Map and respond -----------------------------------------------------

$fields = mapAnimeScheduleToFormFields($apiResult);

if (empty($fields)) {
    // The API returned 200 but nothing we recognise. Could be a brand
    // new anime with no schedule yet, or an unexpected payload shape.
    as_respond([
        'success' => false,
        'error'   => 'AnimeSchedule cevabinda doldurulabilecek bilgi bulunamadi.',
    ]);
}

as_respond([
    'success' => true,
    'fields'  => $fields,
]);
