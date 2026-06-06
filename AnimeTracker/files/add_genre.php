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

   
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

// CSRF dogrulama. Token AJAX caller (add_anime.php / edit_anime.php) tarafindan
// formdaki gizli input'tan okunup body'de gonderilir. db.php session_start
// cagirdigi icin $_SESSION zaten hazir.
if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token gecersiz. Sayfayi yenileyip tekrar deneyin.']);
    exit;
}

// Adding a genre edits the shared taxonomy, so a moderator+ is required
// (online only; no-op in self-host). JSON denial for the AJAX caller.
require_role($pdo, 'moderator', true);

try {
    if (isset($_POST['genre'])) {
        $genre = trim($_POST['genre']);
        
        // Tur zaten var mi kontrol et
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM genres WHERE name = ?");
        $check_stmt->execute([$genre]);
        
        if ($check_stmt->fetchColumn() == 0) {
            // Yeni turu ekle
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
