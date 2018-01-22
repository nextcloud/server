FROM php:7.0-apache

RUN apt-get update
RUN apt-get install -y \
       libpng-dev \
       libpq-dev \
       libsqlite3-dev \
       libxml2-dev \
       libzip-dev \
    && docker-php-ext-install -j$(nproc) \
       gd \
       pdo_mysql \
       pdo_pgsql \
       posix \
       simplexml \
       zip

WORKDIR /var/www/html
