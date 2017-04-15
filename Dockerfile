FROM php:7.0-apache

USER root

RUN apt-get update && apt-get install -y libpng12-dev \
    && docker-php-ext-install -j$(nproc) gd zip

ARG dest=/var/www/html/

COPY . $dest

RUN chown -R www-data:www-data $dest/config
RUN chown -R www-data:www-data $dest/apps
RUN mkdir $dest/data
RUN chown -R www-data:www-data $dest/data

