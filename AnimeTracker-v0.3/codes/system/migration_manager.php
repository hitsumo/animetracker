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
 
 
class MigrationManager {
    private $pdo;
    private $baseDir;
    private $currentVersion;
    private $targetVersion;
    
    public function __construct() {
        $this->pdo = new PDO('mysql:host=localhost;dbname=anime_tracker', 'root', '');
        $this->baseDir = "C:/xampp/htdocs/anime_tracker/";
        $this->currentVersion = $this->getCurrentVersion();
        $this->targetVersion = $this->getTargetVersion();
    }
    
    private function getCurrentVersion() {
        try {
            $stmt = $this->pdo->query("SELECT value FROM settings WHERE name = 'version'");
            return $stmt->fetchColumn() ?: '0.1';
        } catch (PDOException $e) {
            // Settings tablosu yoksa oluştur
            $this->createSettingsTable();
            return '0.1';
        }
    }
    
    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL UNIQUE,
            value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        
        // Versiyon kaydı ekle
        $stmt = $this->pdo->prepare("INSERT INTO settings (name, value) VALUES ('version', '0.1')");
        $stmt->execute();
    }
    
    private function getTargetVersion() {
        $versions = glob($this->baseDir . 'migrations/*', GLOB_ONLYDIR);
        return basename(end($versions));
    }
    
    public function needsUpdate() {
        return version_compare($this->currentVersion, $this->targetVersion, '<');
    }
    
    public function update() {
        if (!$this->needsUpdate()) {
            return false;
        }
        
        $versions = $this->getUpdatePath();
        
        foreach ($versions as $version) {
            try {
                $this->applyMigration($version);
            } catch (Exception $e) {
                $this->logError($e->getMessage());
                return false;
            }
        }
        
        return true;
    }
    
    private function applyMigration($version) {
        try {
            $this->pdo->beginTransaction();
            
            $migrationPath = $this->baseDir . "migrations/$version/";
            $migrationConfig = json_decode(file_get_contents($migrationPath . "migration.json"), true);
            
            // Veritabanı güncellemeleri
            if (isset($migrationConfig['database'])) {
                $queries = explode(';', file_get_contents($migrationPath . $migrationConfig['database']['file']));
                foreach ($queries as $query) {
                    if (trim($query)) {
                        try {
                            $this->pdo->exec($query);
                        } catch (PDOException $e) {
                            if ($e->getCode() != '42S21') { // Duplicate column hatası değilse
                                throw $e;
                            }
                        }
                    }
                }
            }
            
            // Dosya güncellemeleri
            if (isset($migrationConfig['files'])) {
                // Yeni dosyaları ekle
                foreach ($migrationConfig['files']['add'] as $file) {
                    if (!file_exists($this->baseDir . $file)) {
                        copy($migrationPath . "files/$file", $this->baseDir . $file);
                    }
                }
                
                // Mevcut dosyaları güncelle
                foreach ($migrationConfig['files']['update'] as $file) {
                    if (file_exists($this->baseDir . $file)) {
                        copy($this->baseDir . $file . '.backup-' . date('Y-m-d-His'),
                             $this->baseDir . $file);
                    }
                    copy($migrationPath . "files/$file", $this->baseDir . $file);
                }
                
                // Eski dosyaları kaldır
                foreach ($migrationConfig['files']['remove'] as $file) {
                    if (file_exists($this->baseDir . $file)) {
                        unlink($this->baseDir . $file);
                    }
                }
            }
            
            // Versiyon güncelle
            $stmt = $this->pdo->prepare("UPDATE settings SET value = ? WHERE name = 'version'");
            $stmt->execute([$version]);
            
            $this->pdo->commit();
            $this->logUpdate("Versiyon $version başarıyla uygulandı.");
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logError("Versiyon $version güncellemesi başarısız: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function getUpdatePath() {
        $allVersions = array_map('basename', glob($this->baseDir . 'migrations/*', GLOB_ONLYDIR));
        $updates = [];
        
        foreach ($allVersions as $version) {
            if (version_compare($this->currentVersion, $version, '<') && 
                version_compare($version, $this->targetVersion, '<=')) {
                $updates[] = $version;
            }
        }
        
        sort($updates);
        return $updates;
    }
    
    private function logUpdate($message) {
        $logFile = $this->baseDir . 'logs/update.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    private function logError($message) {
        $logFile = $this->baseDir . 'logs/error.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] ERROR: $message\n", FILE_APPEND);
    }
}