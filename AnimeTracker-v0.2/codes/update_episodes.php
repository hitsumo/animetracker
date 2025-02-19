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

   
require_once 'functions.php';
$pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');

// Yayın devam eden animeleri al
$sql = "SELECT * FROM animes WHERE status = 'Yayın Devam Ediyor'";
$stmt = $pdo->query($sql);
$animes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($animes as $anime) {
    $nextEpisodeDate = calculateNextEpisodeDate($anime);
    
    if ($nextEpisodeDate) {
        // Sonraki bölüm tarihini güncelle
        $sql = "UPDATE animes SET next_episode_date = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nextEpisodeDate, $anime['id']]);
    }
}
?>