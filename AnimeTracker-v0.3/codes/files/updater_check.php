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
 
 

require_once 'system/migration_manager.php';

$migrationManager = new MigrationManager();

if ($migrationManager->needsUpdate()) {
    try {
        $migrationManager->update();
        echo "Güncelleme başarıyla tamamlandı.";
    } catch (Exception $e) {
        echo "Güncelleme sırasında hata oluştu: " . $e->getMessage();
    }
} else {
    echo "Sistem güncel.";
}