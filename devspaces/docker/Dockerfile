FROM php:7.2.12-apache

LABEL description="This file generates a docker image containing everything needed to build and run NextCloud application"

ENV DEBIAN_FRONTEND=noninteractive

COPY artifacts/apache2.conf /etc/apache2/apache2.conf
COPY artifacts/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN apt-get update && apt-get install -y libxml2-dev libpng-dev software-properties-common && \
    docker-php-ext-install ctype dom gd iconv posix simplexml xmlwriter zip pdo_mysql && \
    apt-get clean && \
    mkdir /data && \
    chown -R www-data /data

WORKDIR /data

CMD [tail -f /dev/null]
