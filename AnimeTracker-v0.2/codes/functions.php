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

   
function calculateNextEpisodeDate($anime) {
    if ($anime['status'] != 'Yayın Devam Ediyor' || empty($anime['broadcast_day']) || empty($anime['broadcast_time'])) {
        return null;
    }

    $now = new DateTime();
    $broadcastTime = new DateTime($anime['broadcast_time']);
    $days = [
        'Pazartesi' => 1,
        'Salı' => 2,
        'Çarşamba' => 3,
        'Perşembe' => 4,
        'Cuma' => 5,
        'Cumartesi' => 6,
        'Pazar' => 7
    ];

    $broadcastDayNum = $days[$anime['broadcast_day']];
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
    return $nextDate->format('Y-m-d H:i:s');
}

function updateNextEpisodeDate($pdo, $anime) {
    $now = new DateTime();
    $nextEpisodeDate = new DateTime($anime['next_episode_date']);

    if ($now > $nextEpisodeDate) {
        $newNextEpisodeDate = calculateNextEpisodeDate($anime);
        if ($newNextEpisodeDate) {
            $sql = "UPDATE animes SET next_episode_date = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newNextEpisodeDate, $anime['id']]);
        }
    }
}

function getTimeUntilNextEpisode($nextEpisodeDate) {
    if (empty($nextEpisodeDate)) {
        return "Belirtilmemiş";
    }

    $now = new DateTime();
    $next = new DateTime($nextEpisodeDate);

    if ($now > $next) {
        return "Bölüm yayınlandı";
    }

    $interval = $now->diff($next);
    return "Kalan süre: " . $interval->format('%a gün, %h saat, %i dakika');
}
?>