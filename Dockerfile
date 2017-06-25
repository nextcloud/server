FROM php:7-apache

RUN apt-get update \
    && apt-get install -y libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd zip

RUN ln -s /nextcloud /var/www/html/nextcloud
