<?php

/**
 * Anime Tracker - Schema Installer
 * https://www.sicakcikolata.com
 * Copyright (C) 2025 Okan Sumer
 * Licensed under GNU General Public License v2
 *
 * Second step of the WordPress-style setup flow. Runs after setup.php
 * has written config.php. Loads schema.sql and executes each statement
 * against the freshly created database.
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
 * telling them to delete setup.php and install.php manually. In the
 * .exe installer flow these files are removed automatically during
 * installation (installer.nsi lines 169-170) so this step is only
 * relevant for manual / htdocs-copy installs.
 */

require_once __DIR__ . '/db.php';

// If we got here, db.php successfully connected. $pdo is available.
// Now load and execute the schema.

$schemaPath = __DIR__ . '/schema.sql';
$errors     = [];
$executed   = 0;

try {
    if (!file_exists($schemaPath)) {
        throw new Exception('schema.sql dosyasi bulunamadi: ' . $schemaPath);
    }

    $sql = file_get_contents($schemaPath);
    if ($sql === false) {
        throw new Exception('schema.sql dosyasi okunamadi. Dosya izinlerini kontrol edin.');
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
    error_log('[anime_tracker] install.php error: ' . $e->getMessage());
    $errors[] = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anime Tracker - Kurulum Tamamlandi</title>
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
        <h1><i class="fas fa-database"></i> Veritabani Kurulumu</h1>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong><i class="fas fa-exclamation-triangle"></i> Kurulum sirasinda hata:</strong>
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
                <p style="margin-top:10px;margin-bottom:0;">
                    Detayli bilgi icin web sunucusunun hata loglarini kontrol edin.
                    Sorun devam ederse <a href="setup.php">kurulum sihirbazina</a>
                    dondurup baglanti bilgilerinizi tekrar kontrol edin.
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Kurulum basariyla tamamlandi</strong>
                <?php echo (int)$executed; ?> SQL ifadesi basariyla calistirildi.
                Veritabani tablolari, varsayilan turler ve yapilandirma hazir.
            </div>

            <div class="alert alert-warning">
                <strong><i class="fas fa-shield-alt"></i> Guvenlik Uyarisi</strong>
                Kurulum tamamlandigina gore asagidaki iki dosyayi sunucu uzerinden
                <strong>manuel olarak silin</strong>. Bu dosyalar tarayicidan
                erisilebilir kaldigi surece biri veritabaninizi sifirlayabilir
                veya yapilandirmanizi degistirebilir.
                <ul style="margin-top:8px;margin-bottom:0;">
                    <li><code>setup.php</code></li>
                    <li><code>install.php</code></li>
                </ul>
            </div>

            <a href="index.php" class="main-button">
                <i class="fas fa-arrow-right"></i> Ana Sayfaya Git
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
