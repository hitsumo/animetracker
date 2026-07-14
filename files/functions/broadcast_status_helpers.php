<?php

/**
 * Anime Tracker - Broadcast-status Helpers (animes.status enum -> UI label / options)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Introduced in 1.1.10 together with the three new broadcast-status
 * values ('Yayın Başlamadı', 'Seçim Yapılmadı', 'Yayın İptal Edildi').
 * Before 1.1.10 the two-value status was translated by an inlined
 * if/elseif at every render surface; with five values that duplication
 * became error-prone, so - mirroring the watch_status_label family -
 * this file is now the single source of truth.
 *
 * Unlike watch_status (ASCII enum), animes.status stores the Turkish
 * display string directly in the DB. The label helper therefore maps
 * the raw enum value onto the existing index.broadcast.* i18n keys so
 * the English UI still gets a translated label.
 *
 * Loaded via the functions.php loader.
 */

/**
 * Map a broadcast-status enum value to a user-facing, localized label.
 *
 * Reuses the index.broadcast.* keys already defined in lang/tr.php and
 * lang/en.php. Falls back to the raw enum value if it is unknown
 * (defensive - a stray value never produces an empty cell). Uses the
 * active UI language via t(); no $lang override because t() has none.
 *
 * @param string|null $status One of the five animes.status enum values.
 * @return string             Localized label, or $status itself if unmapped.
 */
function broadcast_status_label($status) {
    static $keys = [
        'Yayın Tamamlandı'   => 'index.broadcast.finished',
        'Yayın Devam Ediyor' => 'index.broadcast.ongoing',
        'Yayın Başlamadı'    => 'index.broadcast.not_started',
        'Seçim Yapılmadı'    => 'index.broadcast.unselected',
        'Yayın İptal Edildi' => 'index.broadcast.cancelled',
    ];
    if (!isset($keys[$status])) {
        return (string)$status;
    }
    return t($keys[$status]);
}

/**
 * Return the broadcast-status options for a <select>, in display order.
 *
 * Order follows the broadcast lifecycle, with the "not selected" default
 * first so a fresh add-form defaults to it (1.1.10 decision - unknown
 * status folds to 'Seçim Yapılmadı' instead of the historical
 * default-to-finished):
 *   Seçim Yapılmadı -> Yayın Başlamadı -> Yayın Devam Ediyor
 *   -> Yayın Tamamlandı -> Yayın İptal Edildi
 *
 * Use as:
 *   foreach (broadcast_status_options() as $value => $label) { ... }
 *
 * @return array Associative array: enum value => localized label.
 */
function broadcast_status_options() {
    $order = [
        'Seçim Yapılmadı',
        'Yayın Başlamadı',
        'Yayın Devam Ediyor',
        'Yayın Tamamlandı',
        'Yayın İptal Edildi',
    ];
    $options = [];
    foreach ($order as $status) {
        $options[$status] = broadcast_status_label($status);
    }
    return $options;
}
