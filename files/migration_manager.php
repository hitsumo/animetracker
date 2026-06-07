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

    // ===================================================================
    // File operations (files.ops) - the file-system counterpart to
    // migrations. The installer / self-update / docker / git only ADD or
    // overwrite files; none of them removes a file that a newer version
    // moved or deleted. Each version that relocates or removes a file ships
    // migration/{version}/files.ops describing those changes.
    //
    // This runs from db.php on every request (like applyPending) but is
    // tracked by a SEPARATE settings row, 'files_ops_version', independent
    // of the schema 'version'. That independence is what lets it survive
    // the very transition that introduces it: a pre-files.ops install has no
    // 'files_ops_version' row, so the first time this new code runs it
    // treats everything as pending and catches up - no matter how the update
    // arrived. files.ops only ever names OLD-location paths, so on a fresh
    // install (which only has the new layout) every op is a harmless no-op.
    // ===================================================================

    /**
     * Convenience entry point: apply pending file operations.
     * Never throws - failures are logged and returned in the result array
     * ['moved'=>int, 'deleted'=>int, 'skipped'=>int, 'errors'=>array].
     */
    public static function runFileOps(PDO $pdo)
    {
        $mm = new self($pdo);
        return $mm->applyPendingFileOps();
    }

    public function applyPendingFileOps()
    {
        $result = ['moved' => 0, 'deleted' => 0, 'skipped' => 0, 'errors' => []];

        // The schema version is the ceiling - files.ops folders never exceed
        // the installed code version. If it is missing, settings is not
        // seeded yet; do nothing (same guard as applyPending).
        $schemaVersion = $this->getCurrentVersion();
        if ($schemaVersion === null) {
            return $result;
        }

        // A missing files_ops_version means "never applied" -> start from 0,
        // so every available files.ops up to the schema version runs once.
        $current = $this->getFileOpsVersion();
        $from = ($current === null) ? '0' : $current;

        foreach ($this->getPendingFileOps($from, $schemaVersion) as $version) {
            $this->applyFileOpsFile($version, $result);
        }

        // Catch the stamp up to the schema version (written once, then stable).
        if ($current !== $schemaVersion) {
            $this->setFileOpsVersion($schemaVersion);
        }

        return $result;
    }

    private function getFileOpsVersion()
    {
        try {
            $stmt = $this->pdo->query("SELECT value FROM settings WHERE name = 'files_ops_version'");
            $value = $stmt->fetchColumn();
            return $value !== false ? $value : null;
        } catch (PDOException $e) {
            return null;
        }
    }

    private function setFileOpsVersion($version)
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO settings (name, value) VALUES ('files_ops_version', ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value)"
            );
            $stmt->execute([$version]);
        } catch (PDOException $e) {
            error_log('[anime_tracker] files.ops version stamp failed: ' . $e->getMessage());
        }
    }

    /**
     * Version folders that have a files.ops, are newer than $from, and not
     * newer than $ceiling (the schema version). Sorted oldest first.
     */
    private function getPendingFileOps($from, $ceiling)
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
            if (!is_file($folder . '/files.ops')) {
                continue;
            }
            $version = basename($folder);
            if (version_compare($version, $from, '>')
                && version_compare($version, $ceiling, '<=')) {
                $versions[] = $version;
            }
        }
        usort($versions, 'version_compare');
        return $versions;
    }

    /**
     * Parse and apply one migration/{version}/files.ops manifest.
     * Mutates $result counters. Idempotent and best-effort.
     *
     * Syntax (one op per line; '#' and blank lines ignored):
     *   move <old-relative-path> <new-relative-path>
     *   delete <relative-path>
     */
    private function applyFileOpsFile($version, array &$result)
    {
        $opsFile = $this->migrationDir . '/' . $version . '/files.ops';
        $lines = @file($opsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        $installRoot = dirname($this->migrationDir);
        $protected = ['config.php', 'config_example.php', 'uploads', 'temp'];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = preg_split('/\s+/', $line);
            $op = strtolower($parts[0]);

            if ($op === 'delete' && isset($parts[1])) {
                $rel = $parts[1];
                $abs = self::fileOpResolve($installRoot, $rel);
                if ($abs === null) {
                    $result['errors'][] = 'invalid path (delete): ' . $rel;
                    error_log('[anime_tracker] files.ops invalid path (delete): ' . $rel);
                    continue;
                }
                if (self::fileOpIsProtected($rel, $protected)) {
                    $result['skipped']++;
                    error_log('[anime_tracker] files.ops skip protected delete: ' . $rel);
                    continue;
                }
                if (!file_exists($abs) && !is_link($abs)) {
                    $result['skipped']++; // already gone - idempotent
                    continue;
                }
                if (self::recursiveDelete($abs)) {
                    $result['deleted']++;
                    error_log('[anime_tracker] files.ops deleted: ' . $rel);
                } else {
                    $result['errors'][] = 'delete failed: ' . $rel;
                    error_log('[anime_tracker] files.ops delete failed: ' . $rel);
                }

            } elseif ($op === 'move' && isset($parts[1], $parts[2])) {
                $relOld = $parts[1];
                $relNew = $parts[2];
                $absOld = self::fileOpResolve($installRoot, $relOld);
                $absNew = self::fileOpResolve($installRoot, $relNew);
                if ($absOld === null || $absNew === null) {
                    $result['errors'][] = 'invalid path (move): ' . $line;
                    error_log('[anime_tracker] files.ops invalid path (move): ' . $line);
                    continue;
                }
                if (self::fileOpIsProtected($relOld, $protected)) {
                    $result['skipped']++;
                    error_log('[anime_tracker] files.ops skip protected move: ' . $relOld);
                    continue;
                }
                if (!file_exists($absOld) && !is_link($absOld)) {
                    $result['skipped']++; // nothing to move - idempotent
                    continue;
                }
                $parent = dirname($absNew);
                if (!is_dir($parent) && !@mkdir($parent, 0755, true)) {
                    $result['errors'][] = 'mkdir failed: ' . $relNew;
                    error_log('[anime_tracker] files.ops mkdir failed: ' . $relNew);
                    continue;
                }
                if (file_exists($absNew)) {
                    // Destination already present - never overwrite a
                    // user-installed file; just drop the stale source.
                    if (self::recursiveDelete($absOld)) {
                        $result['deleted']++;
                        error_log('[anime_tracker] files.ops dest exists, removed old: ' . $relOld);
                    } else {
                        $result['errors'][] = 'old-copy delete failed: ' . $relOld;
                        error_log('[anime_tracker] files.ops old-copy delete failed: ' . $relOld);
                    }
                } else {
                    if (@rename($absOld, $absNew)) {
                        $result['moved']++;
                        error_log('[anime_tracker] files.ops moved: ' . $relOld . ' -> ' . $relNew);
                    } elseif (@copy($absOld, $absNew) && @unlink($absOld)) {
                        $result['moved']++;
                        error_log('[anime_tracker] files.ops moved (copy+unlink): ' . $relOld . ' -> ' . $relNew);
                    } else {
                        $result['errors'][] = 'move failed: ' . $line;
                        error_log('[anime_tracker] files.ops move failed: ' . $line);
                    }
                }

            } else {
                $result['errors'][] = 'invalid op: ' . $line;
                error_log('[anime_tracker] files.ops invalid op: ' . $line);
            }
        }
    }

    /**
     * Resolve a files.ops relative path to an absolute path inside the
     * install root. Returns null if unsafe: empty, absolute, or escaping
     * the root via a ".." segment.
     */
    private static function fileOpResolve($installRoot, $relative)
    {
        $relative = str_replace('\\', '/', trim($relative));
        if ($relative === '' || $relative[0] === '/' || preg_match('#^[A-Za-z]:#', $relative)) {
            return null;
        }
        foreach (explode('/', $relative) as $seg) {
            if ($seg === '..') {
                return null;
            }
        }
        return $installRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }

    private static function fileOpIsProtected($relative, array $protected)
    {
        $relative = str_replace('\\', '/', trim($relative));
        foreach ($protected as $skip) {
            if ($relative === $skip || strpos($relative, $skip . '/') === 0) {
                return true;
            }
        }
        return false;
    }

    private static function recursiveDelete($path)
    {
        if (!file_exists($path) && !is_link($path)) {
            return true;
        }
        if (is_file($path) || is_link($path)) {
            return @unlink($path);
        }
        if (is_dir($path)) {
            $entries = @scandir($path);
            if ($entries === false) {
                return false;
            }
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                if (!self::recursiveDelete($path . DIRECTORY_SEPARATOR . $entry)) {
                    return false;
                }
            }
            return @rmdir($path);
        }
        return false;
    }
}
