FROM php:8.2-apache

# zip extension icin sistem kutuphanesi gerekli (PHP 8.2-apache
# image'i slim - libzip-dev varsayilan olarak yok)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Gerekli PHP eklentileri:
#   pdo_mysql  - veritabani baglantisi (PDO)
#   zip        - update.php icin ZipArchive
# Not: fileinfo (resim yukleme MIME tespiti) PHP 8.x'te built-in -
# ayrica enable etmeye gerek yok.
RUN docker-php-ext-install pdo_mysql zip

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
# --chown=www-data: update.php var olan dosyalari overwrite edebilsin.
# Windows host'tan COPY default olarak root:root + 755 mode verir,
# www-data Apache user'i mevcut PHP dosyalarinin uzerine yazamaz ve
# update.php "Dosya kopyalanamadi" hatasi firlatir. KARARLAR Bolum 2
# Docker permission disiplini (0.6.2 dersi).
COPY --chown=www-data:www-data files/ /var/www/html/

# Docker entrypoint: config.php otomatik olustur + izinler
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
