FROM php:8.2-apache

# Gerekli PHP eklentileri:
#   pdo_mysql  - veritabani baglantisi (PDO)
#   zip        - update.php icin ZipArchive
#   fileinfo   - resim yukleme MIME tespiti (finfo)
RUN docker-php-ext-install pdo_mysql zip \
    && docker-php-ext-enable fileinfo

# Apache mod_rewrite etkinlestir (uploads/.htaccess icin gerekli)
RUN a2enmod rewrite

# Apache'nin .htaccess dosyalarini islemesine izin ver
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# uploads klasoru olustur ve izinlerini ayarla
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads

# PHP ayarlari: dosya yukleme limitleri ve zaman dilimi
# Uygulama tarafi UTC kullaniyor (functions.php her anime icin kendi
# broadcast_timezone'unu tasiyor) - burada date.timezone sadece PHP'nin
# genel default'u, UTC kabul edilebilir. Europe/Istanbul da yazilabilir
# ama UTC uygulama logic'iyle uyumludur.
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "date.timezone = UTC" >> /usr/local/etc/php/conf.d/anime-tracker.ini

# Calisma dizini
WORKDIR /var/www/html

# Uygulama dosyalarini kopyala
COPY files/ /var/www/html/

# Docker entrypoint: config.php otomatik olustur + izinler
# dos2unix gerekli cunku dosya Windows'ta duzenlendiyse CRLF tasiyor olabilir,
# bash CRLF'yi anlamaz ve "bad interpreter" hatasi verir.
COPY docker-entrypoint.sh /usr/local/bin/
RUN apt-get update && apt-get install -y dos2unix \
    && dos2unix /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh \
    && apt-get remove -y dos2unix \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
