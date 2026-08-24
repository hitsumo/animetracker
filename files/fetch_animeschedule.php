<?php

/**
 * Anime Tracker - AnimeSchedule API Fetch Endpoint
 * https://www.sicakcikolata.com
 * Copyright (C) 2025-2026 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * AJAX endpoint called by add_anime.php and edit_anime.php when the
 * user clicks "Otomatik Doldur" next to the AnimeSchedule URL field.
 *
 * Request:
 *   POST fetch_animeschedule.php
 *     csrf_token=<token>
 *     url=<full AnimeSchedule URL>
 *     mal_link=<the form's MAL URL>   // optional, 1.1.27 - end date lookup
 *
 * Response (success):
 *   {
 *     "success": true,
 *     "fields": {
 *       "broadcast_day":      "Persembe",
 *       "broadcast_time":     "23:30",
 *       "broadcast_timezone": "Asia/Tokyo",
 *       "status":             "Yayin Devam Ediyor",
 *       "total_episodes":     12,     // only for finished anime
 *       "release_date":       "2019-04-08", // 1.1.27
 *       "end_date":           "2019-06-24"  // 1.1.27, finished + multi-episode
 *     }
 *   }
 *
 * 1.1.31 - A DATE MAY COME BACK PARTIAL. When AniList knows only the year (or
 * only year + month) the payload carries the PRECISION plus the pieces instead
 * of a full date, using the same field names the form posts:
 *   "release_date_precision": "year",  "release_date_year": "1979"
 *   "release_date_precision": "month", "release_date_year": "1979",
 *                                      "release_date_month": "11"
 * Both shapes never appear together for the same field. A client that does not
 * know these keys simply reports them as "field not found" and fills nothing -
 * the old keys keep their old meaning.
 *
 * 1.1.27 - TWO SOURCES BEHIND ONE BUTTON. Everything above comes from
 * AnimeSchedule except the two DATES, which come from AniList: the AnimeSchedule
 * anime object carries no end-date field at all, and its `premier` is an instant
 * rather than a calendar date (late-night broadcasts make those differ by a day).
 * The AniList call is a best-effort extra - if it fails the AnimeSchedule fields
 * are still returned. See the block below and anilist_fetch_dates() for the full
 * reasoning.
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

// --- Yayin ve bitis tarihi: AniList (1.1.27) ------------------------------
//
// AnimeSchedule'in anime nesnesinde BITIS TARIHI ALANI YOKTUR - tarih
// alanlari premier / subPremier / dubPremier, delayed* cifti ve
// jpnTime/subTime/dubTime'dir; hicbiri finali isaretlemez. Yayin tarihi icin
// `premier` var ama o bir ZAMAN ANIDIR; takvim gunune cevirmek gece yarisi
// sonrasi yayinlarda bir gun kaymaya aciktir ("Cuma 25:25" = Cumartesi 01:25).
// Iki tarih de bu yuzden AniList'ten, TEK sorguda alinir. Ayrintili gerekce
// (ve "premier + (bolum-1)x7 ile hesaplama" secenegin neden reddedildigi)
// anilist_fetch_dates()'in docblock'unda.
//
// ESLESME KIMLIGI NEREDEN GELIR (oncelik sirasi):
//   1. AnimeSchedule cevabinin KENDI `websites` nesnesi (AniList id, sonra MAL
//      id). Bu en saglam kaynaktir: kullanicinin forma hangi baglantiyi
//      yapistirdigina bagli degildir. Kurator yalnizca AniDB baglantisi
//      girdiyse ya da "Otomatik Doldur"a MAL kutusunu doldurmadan bastiysa
//      tarihler eskiden hic gelmiyordu - kimlik artik zaten cektigimiz
//      cevaptan okunuyor.
//   2. Formdaki MAL baglantisi (eski yol, yedek olarak duruyor): `websites`
//      bos gelirse ya da tanimadigimiz bir bicimdeyse devreye girer.
// Ikisinden de kimlik cikmazsa istek hic atilmaz.
//
// Iki tarihin kendi kurallari var:
//   - release_date HER durumda anlamlidir (devam eden, baslamamis, bitmis)
//     ve formda her zaman gorunur; bilindigi her yerde tasinir.
//   - end_date yalnizca durum "Yayın Tamamlandı" ISE ve TEK BOLUMLUK yapim
//     DEGILSE tasinir. Tek bolumde (film/ozel/OVA) bitis tarihi yayin
//     tarihinin kendisidir - AniList de start == end doner - ve form bu alani
//     zaten gizler (isSingleEpisode / toggleEndDateBySingleEpisode). §79
//     ilkesi: formun gostermedigi alani doldurmayi onerme.
//
// Bu EK bir istektir: basarisiz olursa (ag hatasi, eslesmeyen kayit, yarim
// tarih) sessizce atlanir ve AnimeSchedule'dan gelenler yine dondurulur.
// Not: AniList istemcisinin kendi zaman asimi vardir, yani en kotu durumda
// dugme AnimeSchedule + AniList beklemesi kadar surer.
$extIds     = animeScheduleExternalIds($apiResult);
$aniListId  = $extIds['anilist'];
$malId      = $extIds['mal'];
if ($malId === null) {
    // Yedek: formdaki MAL baglantisi.
    $malId = parseMalId(trim($_POST['mal_link'] ?? ''));
}

// 1.1.31 - KISMI tarih de tasinir. AniList eski yapimlarin cogunda yalnizca
// yili (bazen yil + ayi) tutar; onceden bu kayitlar tamamen dusuyordu ve alan
// bos kaliyordu. Artik bilinen parca forma girer:
//   'full'  -> release_date alanina tam tarih
//   'month' -> hassasiyet "Ay ve yil" + ay ve yil kutulari
//   'year'  -> hassasiyet "Yalniz yil" + yil kutusu
// Hassasiyet anahtari deger anahtarlarindan ONCE yazilir: betik alanlari
// geldikleri sirada isler ve hassasiyet kutusuna yazar yazmaz dogru girdiyi
// gorunur kilar (toggleDatePrecision).
$asPartialDateFields = function (array &$fields, $base, $date, $precision) {
    if ($date === null) {
        return;
    }
    if ($precision === 'full') {
        $fields[$base] = $date;
        return;
    }
    $fields[$base . '_precision'] = $precision;
    $fields[$base . '_year']      = substr($date, 0, 4);
    if ($precision === 'month') {
        $fields[$base . '_month'] = substr($date, 5, 2);
    }
};

if ($aniListId !== null || $malId !== null) {
    $alDates = anilist_fetch_dates($malId, $aniListId);

    $asPartialDateFields($fields, 'release_date', $alDates['start_date'], $alDates['start_precision']);

    if (($fields['status'] ?? null) === 'Yayın Tamamlandı'
        && (int)($fields['total_episodes'] ?? 0) !== 1) {
        $asPartialDateFields($fields, 'end_date', $alDates['end_date'], $alDates['end_precision']);
    }
}

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
