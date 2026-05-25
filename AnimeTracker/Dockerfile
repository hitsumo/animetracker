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
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "max_execution_time = 60" >> /usr/local/etc/php/conf.d/anime-tracker.ini \
    && echo "date.timezone = Europe/Istanbul" >> /usr/local/etc/php/conf.d/anime-tracker.ini

# Calisma dizini
WORKDIR /var/www/html

# Uygulama dosyalarini kopyala
COPY files/ /var/www/html/

# Docker entrypoint: config.php otomatik olustur + izinler
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
