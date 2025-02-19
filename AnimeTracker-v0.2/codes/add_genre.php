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

   
header('Content-Type: application/json');

try {
    $pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');
    
    if (isset($_POST['genre'])) {
        $genre = trim($_POST['genre']);
        
        // Tür zaten var mı kontrol et
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM genres WHERE name = ?");
        $check_stmt->execute([$genre]);
        
        if ($check_stmt->fetchColumn() == 0) {
            // Yeni türü ekle
            $stmt = $pdo->prepare("INSERT INTO genres (name) VALUES (?)");
            $stmt->execute([$genre]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Bu tür zaten mevcut']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Tür adı belirtilmedi']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası']);
}
?>