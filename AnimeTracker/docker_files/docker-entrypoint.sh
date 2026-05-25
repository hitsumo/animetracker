#!/bin/bash
# Anime Tracker - Docker Entrypoint
# config.php yoksa ortam degiskenlerinden otomatik olusturur.
# Schema yukleme MariaDB'nin docker-entrypoint-initdb.d mekanizmasi ile yapilir.

set -e

# config.php yoksa olustur (ilk calistirmada)
if [ ! -f /var/www/html/config.php ]; then
    echo "[anime_tracker] config.php bulunamadi, olusturuluyor..."

    cat > /var/www/html/config.php <<PHPEOF
<?php
define('DB_HOST', '${DB_HOST:-db}');
define('DB_NAME', '${DB_NAME:-anime_tracker}');
define('DB_USER', '${DB_USER:-root}');
define('DB_PASS', '${DB_PASS:-root}');
PHPEOF

    echo "[anime_tracker] config.php olusturuldu."
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

# Orijinal PHP Apache entrypoint'ine devam et
exec docker-php-entrypoint "$@"
