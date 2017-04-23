FROM php:apache

USER root

RUN apt-get update && apt-get install -y libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd zip

RUN mkdir /nextcloud
COPY . /nextcloud
WORKDIR /nextcloud

RUN chown -R www-data:www-data config
RUN chown -R www-data:www-data apps
RUN mkdir data
RUN chown -R www-data:www-data data

RUN ln -s /nextcloud /var/www/html/nextcloud

