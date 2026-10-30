FROM php:8.5-apache-trixie
ENV DEBIAN_FRONTEND=noninteractive \
    COMPOSER_NO_INTERACTION=1
RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip libfreetype6-dev libgmp-dev libicu-dev libjpeg-dev libldap2-dev libpng-dev libwebp-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture --query DEB_BUILD_MULTIARCH)" \
    && docker-php-ext-install -j "$(nproc)" bcmath exif gd gmp intl ldap pcntl zip \
    && pecl install APCu-5.1.28 && docker-php-ext-enable apcu && rm -rf /tmp/pear
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'memory_limit=512M\nopcache.enable=0\n' > "$PHP_INI_DIR/conf.d/nextcloud.ini" \
    && a2enmod headers rewrite \
    && sed -ri 's!/var/www/html!/src!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /src>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/nextcloud.conf \
    && a2enconf nextcloud \
    && printf '%s\n' '#!/bin/sh' 'exec setpriv --reuid=www-data --regid=www-data --init-groups env HOME=/tmp "$@"' > /usr/local/bin/as-www-data \
    && printf '%s\n' '#!/bin/sh' 'exec as-www-data php /src/occ "$@"' > /usr/local/bin/occ \
    && chmod +x /usr/local/bin/as-www-data /usr/local/bin/occ \
    && git config --system --add safe.directory '*' \
    && install -d -o www-data -g www-data /var/lib/nextcloud/data

COPY --chown=www-data:www-data . /src
WORKDIR /src

RUN as-www-data git submodule update --init --depth 1 \
    && as-www-data composer install

RUN occ maintenance:install --database=sqlite --data-dir=/var/lib/nextcloud/data --admin-user=admin --admin-pass=admin \
    && occ config:system:set appstoreenabled --type=boolean --value=false \
    && occ app:enable admin_audit encryption files_external user_ldap \
    && OC_PASS=alice occ user:add --password-from-env --group=alice alice \
    && OC_PASS=bob occ user:add --password-from-env --group=bob bob \
    && cp -a /var/lib/nextcloud/data /var/lib/nextcloud/data.pristine \
    && cp -a config/config.php /var/lib/nextcloud/config.php.pristine \
    && printf '%s\n' '#!/bin/sh' 'set -e' \
         'rm -rf /var/lib/nextcloud/data' \
         'cp -a /var/lib/nextcloud/data.pristine /var/lib/nextcloud/data' \
         'cp -a /var/lib/nextcloud/config.php.pristine /src/config/config.php' \
         > /usr/local/bin/nextcloud-reset \
    && chmod +x /usr/local/bin/nextcloud-reset

RUN apachectl start && sleep 1 \
    && (occ status --exit-code || echo "WARNING: occ status reported a problem") \
    && (test "$(curl -s -o /dev/null -w '%{http_code}' http://localhost/config/config.php)" != 200 \
          || echo "WARNING: config/config.php is served") \
    && apachectl stop
