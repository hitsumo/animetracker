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

// --- First administrator (multi-user mode only) -------------------------
//
// In single-user / self-host mode there is no login, so this whole block is
// skipped and install behaves exactly as before. In multi-user mode the
// installer creates the first admin (WordPress method): until a usable admin
// (an admin row that has a password) exists, show a username + password form
// and promote the seeded owner row (id 1) into the real administrator.
//
// No CSRF token here, consistent with the rest of setup/install: these files
// run once during installation and are deleted afterwards.
$firstAdminNeeded = false;
$adminReady       = false;
$adminErrors      = [];

if (empty($errors) && MULTI_USER_MODE) {
    try {
        $adminReady = ((int)$pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND password_hash IS NOT NULL"
        )->fetchColumn()) > 0;
    } catch (PDOException $e) {
        $adminReady = false;
    }

    if (!$adminReady) {
        $firstAdminNeeded = true;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_username'])) {
            $au = trim($_POST['admin_username'] ?? '');
            $ap = $_POST['admin_password'] ?? '';
            $ac = $_POST['admin_password_confirm'] ?? '';

            if ($au === '' || $ap === '' || $ac === '') {
                $adminErrors[] = 'Username and password fields are required.';
            } elseif (mb_strlen($au) > 32) {
                $adminErrors[] = 'Username can be at most 32 characters.';
            } elseif (strlen($ap) < 8) {
                $adminErrors[] = 'Password must be at least 8 characters.';
            } elseif ($ap !== $ac) {
                $adminErrors[] = 'Passwords do not match.';
            } else {
                try {
                    $hash = password_hash($ap, PASSWORD_DEFAULT);
                    // Promote the seeded owner (id 1) if it exists, else insert.
                    $ownerExists = ((int)$pdo->query(
                        "SELECT COUNT(*) FROM users WHERE id = 1"
                    )->fetchColumn()) > 0;
                    if ($ownerExists) {
                        $stmt = $pdo->prepare(
                            "UPDATE users SET username = ?, password_hash = ?, role = 'admin', status = 'active' WHERE id = 1"
                        );
                    } else {
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, 'admin', 'active')"
                        );
                    }
                    $stmt->execute([$au, $hash]);
                    $firstAdminNeeded = false;
                    $adminReady       = true;
                } catch (PDOException $e) {
                    error_log('[anime_tracker] install_en first-admin: ' . $e->getMessage());
                    $adminErrors[]    = 'Could not create administrator. The username may already be in use.';
                    $firstAdminNeeded = true;
                }
            }
        }
    }
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
        .form-row { margin-bottom: 15px; }
        .form-row label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        .form-row input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .submit-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 500;
        }
        .submit-button:hover { background-color: #0056b3; }
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
        <?php elseif ($firstAdminNeeded): ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Database is ready</strong>
                <?php echo (int)$executed; ?> SQL statements were executed. You chose a
                multi-user installation; now create the first administrator account.
            </div>

            <?php if (!empty($adminErrors)): ?>
                <div class="alert alert-error">
                    <strong><i class="fas fa-exclamation-triangle"></i> Could not create administrator:</strong>
                    <?php foreach ($adminErrors as $ae): ?>
                        <div><?php echo htmlspecialchars($ae, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 style="font-size:1.1em;margin-bottom:15px;"><i class="fas fa-user-shield"></i> Administrator Account</h2>
            <form method="post">
                <div class="form-row">
                    <label for="admin_username">Username</label>
                    <input type="text" id="admin_username" name="admin_username" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="32" required>
                </div>
                <div class="form-row">
                    <label for="admin_password">Password</label>
                    <input type="password" id="admin_password" name="admin_password" required>
                    <small style="color:#888;font-size:12px;">At least 8 characters.</small>
                </div>
                <div class="form-row">
                    <label for="admin_password_confirm">Password (confirm)</label>
                    <input type="password" id="admin_password_confirm" name="admin_password_confirm" required>
                </div>
                <button type="submit" class="submit-button">
                    <i class="fas fa-user-plus"></i> Create Administrator
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-success">
                <strong><i class="fas fa-check-circle"></i> Installation completed successfully</strong>
                <?php echo (int)$executed; ?> SQL statements were executed successfully.
                The database tables, default genres and configuration are ready.
                <?php if (MULTI_USER_MODE): ?>
                    The administrator account has been created.
                <?php endif; ?>
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

            <a href="<?php echo MULTI_USER_MODE ? 'login.php' : 'index.php'; ?>" class="main-button">
                <i class="fas fa-arrow-right"></i> <?php echo MULTI_USER_MODE ? 'Go to Sign In' : 'Go to Home Page'; ?>
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
