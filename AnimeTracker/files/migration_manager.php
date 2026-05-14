<?php

/**
 * Anime Tracker - Migration Manager
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Database schema migration system. Handles upgrades from one
 * schema version to the next via SQL files.
 *
 * Usage (typically called from db.php):
 *   require_once __DIR__ . '/migration_manager.php';
 *   MigrationManager::run($pdo);
 *
 * Migration packages live in migration/{version}/upgrade.sql
 * The "version" folder name is the version this migration upgrades TO.
 * Migrations are applied in version order (version_compare).
 *
 * Example: to release 0.5, create migration/0.5/upgrade.sql with the
 * ALTER TABLE / new INSERT statements needed to bring a 0.4 database
 * up to 0.5. The settings.version row will be updated to '0.5' after
 * the SQL runs successfully.
 *
 * The current schema version is stored in the settings table under
 * the key 'version'. A fresh install creates this row with the current
 * code version (see schema.sql).
 *
 * File replacement (PHP, CSS, etc.) is handled by the installer or
 * manual file copy, NOT by this class. Migration packages are
 * SQL-only.
 */

class MigrationManager
{
    private $pdo;
    private $migrationDir;

    public function __construct(PDO $pdo, $migrationDir = null)
    {
        $this->pdo = $pdo;
        $this->migrationDir = $migrationDir ?: __DIR__ . '/migration';
    }

    /**
     * Convenience entry point: create an instance and run pending migrations.
     * Returns the number of migrations applied (0 if up to date).
     */
    public static function run(PDO $pdo)
    {
        $mm = new self($pdo);
        return $mm->applyPending();
    }

    /**
     * Apply all migrations whose version is greater than the current one.
     */
    public function applyPending()
    {
        $current = $this->getCurrentVersion();

        if ($current === null) {
            // No version recorded - either the install path has not run yet
            // or the settings table is missing. Either way, do nothing here.
            // The install path (schema.sql) is responsible for seeding settings.
            error_log('[anime_tracker] No schema version found, skipping migrations');
            return 0;
        }

        $pending = $this->getPendingMigrations($current);

        foreach ($pending as $version) {
            $this->applyMigration($version);
        }

        return count($pending);
    }

    /**
     * Read the current schema version from the settings table.
     * Returns null if the settings table or version row is missing.
     */
    private function getCurrentVersion()
    {
        try {
            $stmt = $this->pdo->query("SELECT value FROM settings WHERE name = 'version'");
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Find migration folders whose version is greater than the current one.
     * Returns a sorted array of version strings, oldest first.
     */
    private function getPendingMigrations($current)
    {
        if (!is_dir($this->migrationDir)) {
            return [];
        }

        $folders = glob($this->migrationDir . '/*', GLOB_ONLYDIR);
        if (!$folders) {
            return [];
        }

        $versions = [];
        foreach ($folders as $folder) {
            $version = basename($folder);
            if (version_compare($version, $current, '>')) {
                $versions[] = $version;
            }
        }

        usort($versions, 'version_compare');
        return $versions;
    }

    /**
     * Apply a single migration: read SQL, execute statements, update version.
     */
    private function applyMigration($version)
    {
        $sqlFile = $this->migrationDir . '/' . $version . '/upgrade.sql';

        if (!file_exists($sqlFile)) {
            throw new Exception("Migration SQL file not found: $sqlFile");
        }

        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new Exception("Could not read migration SQL file: $sqlFile");
        }

        // Strip line comments and split on semicolons.
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($s) { return $s !== ''; }
        );

        // Execute each statement, ignoring "already exists" type errors so
        // partial migrations can be safely re-run.
        // NOTE: MySQL/MariaDB DDL statements auto-commit, so a transaction
        // here would not actually roll back ALTER TABLE on failure. We rely
        // on idempotent error handling and clear logging instead.
        foreach ($statements as $statement) {
            try {
                $this->pdo->exec($statement);
            } catch (PDOException $e) {
                if (!$this->isIdempotentError($e)) {
                    throw new Exception(
                        "Migration $version failed on statement: " .
                        substr($statement, 0, 100) . '... : ' . $e->getMessage()
                    );
                }
            }
        }

        // Update the recorded schema version.
        $stmt = $this->pdo->prepare(
            "INSERT INTO settings (name, value) VALUES ('version', ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value)"
        );
        $stmt->execute([$version]);

        error_log("[anime_tracker] Migration $version applied successfully");
    }

    /**
     * Detect MySQL/MariaDB error codes that indicate a structure already
     * exists or does not exist. These are safe to ignore when re-running
     * a partially-applied migration.
     *
     * Codes:
     *   1050 - Table already exists
     *   1060 - Duplicate column name
     *   1061 - Duplicate key (index already exists)
     *   1091 - Cannot drop, structure does not exist
     */
    private function isIdempotentError(PDOException $e)
    {
        $info = $e->errorInfo;
        if (!is_array($info) || count($info) < 2) {
            return false;
        }

        $driverCode = $info[1];
        $idempotentCodes = [1050, 1060, 1061, 1091];

        return in_array($driverCode, $idempotentCodes, true);
    }
}
