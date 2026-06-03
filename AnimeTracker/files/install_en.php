<?php

/**
 * Anime Tracker - Schema Installer (English)
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * English twin of install.php. Setup and install run before the
 * lang_init/t() dictionary system is usable, so - following the
 * ai_notice.php / ai_notice_en.php convention - the English install
 * screen is a separate static file. setup_en.php redirects here so
 * the user stays inside the English flow.
 *
 * The file is idempotent - running it again after a successful install
 * is safe because:
 *   - schema.sql uses CREATE TABLE IF NOT EXISTS
 *   - Seed INSERTs use INSERT IGNORE
 *   - No DROP / TRUNCATE operations anywhere
 *
 * So if the user refreshes or re-opens the install page, data is never
 * lost. This is important because otherwise a careless bookmark click
 * could wipe a working install.
 *
 * After a successful install the user is shown a prominent warning
 * telling them to delete the setup files manually. In the .exe installer
 * flow these files are removed automatically during installation
 * (installer.nsi lines 169-170) so this step is only relevant for
 * manual / htdocs-copy installs.
 */

require_once __DIR__ . '/db.php';

// If we got here, db.php successfully connected. $pdo is available.
// Now load and execute the schema.

$schemaPath = __DIR__ . '/schema.sql';
$errors     = [];
$executed   = 0;

try {
    if (!file_exists($schemaPath)) {
        throw new Exception('schema.sql file not found: ' . $schemaPath);
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new Exception('schema.sql could not be read. Check the file permissions.');
    }

    // Strip line comments (-- ...). Keep block comments because schema.sql
    // uses /*!40101 ... */ syntax for MySQL version-conditional statements.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);

    // Split on semicolons. This is a simplistic split - a proper SQL parser
    // would handle semicolons inside string literals - but schema.sql is
    // hand-written and known not to contain them.
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function ($s) { return $s !== ''; }
    );

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
            $executed++;
        } catch (PDOException $e) {
            // If it's an "already exists" type error, swallow it - schema is
            // idempotent by design. Same codes migration_manager uses.
            $info = $e->errorInfo;
            $code = is_array($info) && count($info) >= 2 ? $info[1] : null;
            $idempotentCodes = [1050, 1060, 1061, 1091];
            if (!in_array($code, $idempotentCodes, true)) {
                throw $e;
            }
        }
    }

} catch (Exception $e) {
    error_log('[anime_tracker] install_en.php error: ' . $e->getMessage());
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Anime Tracker - Installation Complete</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        .install-container {
            max-width: 650px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .install-container h1 {
            margin-top: 0;
        }
        .install-container .lang-switch {
            text-align: right;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        .install-container .lang-switch a {
            color: #007bff;
            text-decoration: none;
        }
        .install-container .lang-switch a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert strong { display: block; margin-bottom: 5px; }
        .alert code {
            background: rgba(0,0,0,0.08);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .main-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .main-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="lang-switch">
            <a href="install.php"><i class="fas fa-globe"></i> Türkçe</a>
        </div>

        <h1><i class="fas fa-database"></i> Database Installation</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong><i class="fas fa-exclamation-triangle"></i> Error during installation:</strong>
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
                <p style="margin-top:10px;margin-bottom:0;">
                    Check the web server error logs for more detail. If the problem
                    persists, go back to the <a href="setup_en.php">setup wizard</a>
                    and re-check your connection details.
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Installation completed successfully</strong>
                <?php echo (int)$executed; ?> SQL statements were executed successfully.
                The database tables, default genres and configuration are ready.
            </div>

            <div class="alert alert-warning">
                <strong><i class="fas fa-shield-alt"></i> Security Warning</strong>
                Now that installation is complete, <strong>manually delete</strong>
                the following files from the server. As long as they remain
                reachable from the browser, someone could reset your database
                or change your configuration.
                <ul style="margin-top:8px;margin-bottom:0;">
                    <li><code>setup.php</code></li>
                    <li><code>setup_en.php</code></li>
                    <li><code>install.php</code></li>
                    <li><code>install_en.php</code></li>
                </ul>
            </div>

            <a href="index.php" class="main-button">
                <i class="fas fa-arrow-right"></i> Go to Home Page
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
