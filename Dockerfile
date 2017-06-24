FROM php:7-apache

USER root

RUN apt-get update && apt-get install -y libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd zip

RUN mkdir /var/httpd

RUN mkdir /nextcloud
COPY . /nextcloud
WORKDIR /nextcloud

RUN mkdir -p data && chown -R www-data:www-data apps config data

RUN ln -s /nextcloud /var/www/html/nextcloud
