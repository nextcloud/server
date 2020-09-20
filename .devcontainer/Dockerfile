FROM nextcloud
ENTRYPOINT ["docker-php-entrypoint"]
CMD ["apache2-foreground"]

RUN curl -sL https://deb.nodesource.com/setup_12.x | bash - && \
    echo '[client]\nhost = mysql\nuser = nextcloud\npassword = nextcloud\ndatabase = nextcloud' > /var/www/.my.cnf && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends nano git openssh-client default-mysql-client nodejs && \
    curl -L https://phar.phpunit.de/phpunit-8.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit

ARG USER_UID=1000
ARG USER_GID=$USER_UID

RUN groupmod --gid $USER_GID www-data && \
    usermod --uid $USER_UID --gid $USER_GID www-data && \
    chown -R $USER_UID:$USER_GID /var/www
