# This Dockerfile builds an image of a system in which a Nextcloud server could
# be installed. It is based on the Dockerfile for Nextcloud 11 on Apache
# (https://github.com/nextcloud/docker/blob/843d309ee62b9d2704e6141d2103f9ded97e35b6/11.0/apache/Dockerfile),
# although without the download and copy of a specific Nextcloud version; there
# is no volume either at "/var/www/html" to make possible to create a new image
# from a container based on this image that includes the installed Nextcloud
# server of the container (as the command to generate a new image from a
# container, "docker commit", does not include in the new image any data stored
# in volumes mounted inside the container).

FROM php:7.1-apache

RUN apt-get update && apt-get install -y \
  bzip2 \
  libcurl4-openssl-dev \
  libfreetype6-dev \
  libicu-dev \
  libjpeg-dev \
  libldap2-dev \
  libmcrypt-dev \
  libmemcached-dev \
  libpng12-dev \
  libpq-dev \
  libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# https://docs.nextcloud.com/server/9/admin_manual/installation/source_installation.html
RUN docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
  && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu \
  && docker-php-ext-install gd exif intl mbstring mcrypt ldap mysqli opcache pdo_mysql pdo_pgsql pgsql zip

# set recommended PHP.ini settings
# see https://secure.php.net/manual/en/opcache.installation.php
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=60'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
  } > /usr/local/etc/php/conf.d/opcache-recommended.ini
RUN a2enmod rewrite

# PECL extensions
RUN set -ex \
 && pecl install APCu-5.1.8 \
 && pecl install memcached-3.0.2 \
 && pecl install redis-3.1.1 \
 && docker-php-ext-enable apcu redis memcached
RUN a2enmod rewrite

CMD ["apache2-foreground"]
