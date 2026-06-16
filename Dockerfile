FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY web/ /var/www/html/
COPY images/ /var/www/html/images/

RUN sed -i 's/DirectoryIndex .*/DirectoryIndex index.php index.html/' \
        /etc/apache2/mods-enabled/dir.conf \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
