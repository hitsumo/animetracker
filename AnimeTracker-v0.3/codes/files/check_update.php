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

class UpdateChecker {
    private $currentVersion;
    private $latestVersion;
    private $updateUrl = "https://www.sicakcikolata.com/anime_tracker/version.txt";
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');
            $this->currentVersion = $this->getCurrentVersion();
            $this->latestVersion = $this->getLatestVersion();
        } catch (PDOException $e) {
            die(json_encode([
                'error' => true,
                'message' => 'Veritabanı bağlantı hatası: ' . $e->getMessage()
            ]));
        }
    }
    
    private function getCurrentVersion() {
        try {
            $stmt = $this->pdo->query("SELECT value FROM settings WHERE name = 'version'");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['value'];
            }
        } catch (PDOException $e) {
            $this->createSettingsTable();
        }
        
        $versionFile = __DIR__ . "/version.txt";
        if (file_exists($versionFile)) {
            $version = trim(file_get_contents($versionFile));
            $this->updateVersionInSettings($version);
            return $version;
        }
        
        return "0.3";
    }
    
    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        $sql = "INSERT INTO settings (name, value) VALUES ('version', '0.3')";
        $this->pdo->exec($sql);
    }
    
    private function updateVersionInSettings($version) {
        $stmt = $this->pdo->prepare("INSERT INTO settings (name, value) VALUES ('version', ?) 
                                    ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$version, $version]);
    }
    
    private function getLatestVersion() {
        try {
            $latestVersion = @file_get_contents($this->updateUrl);
            if ($latestVersion === false) {
                throw new Exception("Versiyon bilgisi alınamadı");
            }
            return trim($latestVersion);
        } catch (Exception $e) {
            return $this->currentVersion;
        }
    }
    
    public function needsUpdate() {
        return version_compare($this->currentVersion, $this->latestVersion, '<');
    }
    
    public function getUpdateInfo() {
        return [
            'error' => false,
            'current_version' => $this->currentVersion,
            'latest_version' => $this->latestVersion,
            'needs_update' => $this->needsUpdate(),
            'update_available' => $this->needsUpdate() ? 
                "Yeni versiyon mevcut: " . $this->latestVersion : "Sistem güncel",
            'download_url' => $this->needsUpdate() ? 
                "https://www.sicakcikolata.com/anime_tracker/updates/" . 
                $this->latestVersion . "/AnimeTrackerSetup-" . $this->latestVersion . ".exe" : null
        ];
    }
}

try {
    $updateChecker = new UpdateChecker();
    echo json_encode($updateChecker->getUpdateInfo(), 
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Güncelleme kontrolü sırasında bir hata oluştu: ' . $e->getMessage()
    ]);
}