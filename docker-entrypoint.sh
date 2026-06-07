#!/bin/bash
# Anime Tracker - Docker Entrypoint
# config.php yoksa ortam degiskenlerinden otomatik olusturur.
# Schema yukleme MariaDB'nin docker-entrypoint-initdb.d mekanizmasi ile yapilir.

set -e

# MULTI_USER_MODE env degerini PHP bool literaline cevir.
# false = tek kullanici / self-host (giris yok) - VARSAYILAN.
# true  = online / cok kullanici (giris zorunlu). setup.php'deki kurulum
# secimin Docker karsiligi; Docker setup sihirbazini atladigi icin mod
# burada env ile belirlenir.
if [ "${MULTI_USER_MODE:-false}" = "true" ]; then
    MU_LITERAL=true
else
    MU_LITERAL=false
fi

# config.php yoksa olustur (ilk calistirmada)
if [ ! -f /var/www/html/config.php ]; then
    echo "[anime_tracker] config.php bulunamadi, olusturuluyor..."

    cat > /var/www/html/config.php <<PHPEOF
<?php
define('DB_HOST', '${DB_HOST:-db}');
define('DB_NAME', '${DB_NAME:-anime_tracker}');
define('DB_USER', '${DB_USER:-root}');
define('DB_PASS', '${DB_PASS:-root}');

// Multi-user mode. false = tek kullanici / self-host (giris yok);
// true = online / cok kullanici (giris zorunlu). MULTI_USER_MODE env'inden.
define('MULTI_USER_MODE', ${MU_LITERAL});
PHPEOF

    echo "[anime_tracker] config.php olusturuldu (MULTI_USER_MODE=${MU_LITERAL})."
fi

# uploads klasoru izinleri
mkdir -p /var/www/html/uploads
chown -R www-data:www-data /var/www/html/uploads

# temp klasoru izinleri (update.php icin)
mkdir -p /var/www/html/temp
chown -R www-data:www-data /var/www/html/temp

# migration klasoru izinleri
if [ -d /var/www/html/migration ]; then
    chown -R www-data:www-data /var/www/html/migration
fi

# --- Ilk admin bootstrap (yalniz cok kullanici modu) ---
# Online modda ilk yonetici normalde install.php sihirbazindan olusur; Docker
# o sihirbazi atladigi icin ayni isi burada yapariz. ADMIN_USER + ADMIN_PASS
# verilmisse ve henuz sifreli bir admin yoksa, seed edilen owner (id=1)
# yoneticiye terfi edilir. install.php ile ayni mantik: password_hash
# (PASSWORD_DEFAULT), role='admin', status='active'. Idempotent - admin zaten
# varsa dokunmaz. Loglarda emoji yoktur.
if [ "${MU_LITERAL}" = "true" ]; then
    cat > /tmp/at-bootstrap-admin.php <<'PHPEOF'
<?php
$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'anime_tracker';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS');
$au   = getenv('ADMIN_USER');
$ap   = getenv('ADMIN_PASS');

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        ($pass === false ? '' : $pass),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    fwrite(STDERR, "[anime_tracker] admin bootstrap: DB baglantisi kurulamadi, atlaniyor.\n");
    exit(0);
}

try {
    $adminReady = ((int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'admin' AND password_hash IS NOT NULL"
    )->fetchColumn()) > 0;
} catch (Exception $e) {
    fwrite(STDERR, "[anime_tracker] admin bootstrap: users tablosu okunamadi, atlaniyor.\n");
    exit(0);
}

if ($adminReady) {
    echo "[anime_tracker] admin zaten mevcut, bootstrap atlaniyor.\n";
    exit(0);
}

if ($au === false || $au === '' || $ap === false || $ap === '') {
    fwrite(STDERR, "[anime_tracker] MULTI_USER_MODE acik ama ADMIN_USER/ADMIN_PASS verilmemis; admin bootstrap atlaniyor.\n");
    exit(0);
}

$ulen = function_exists('mb_strlen') ? mb_strlen($au) : strlen($au);
if ($ulen > 32) {
    fwrite(STDERR, "[anime_tracker] ADMIN_USER en fazla 32 karakter olabilir; atlaniyor.\n");
    exit(0);
}
if (strlen($ap) < 8) {
    fwrite(STDERR, "[anime_tracker] ADMIN_PASS en az 8 karakter olmali; atlaniyor.\n");
    exit(0);
}

try {
    $hash = password_hash($ap, PASSWORD_DEFAULT);
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
    echo "[anime_tracker] ilk admin olusturuldu/terfi edildi: {$au}\n";
} catch (Exception $e) {
    fwrite(STDERR, "[anime_tracker] admin bootstrap: yonetici olusturulamadi (kullanici adi zaten kullaniliyor olabilir).\n");
    exit(0);
}
PHPEOF
    php /tmp/at-bootstrap-admin.php || true
    rm -f /tmp/at-bootstrap-admin.php
fi

# Orijinal PHP Apache entrypoint'ine devam et
exec docker-php-entrypoint "$@"
