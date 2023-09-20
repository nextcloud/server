#!/usr/bin/env bash
set -e

SCRIPT_DIR="${0%/*}"

docker rm -f apache 2>/dev/null > /dev/null

docker run -d --name apache -v $2:/var/www/html -v /var/www/html/data -v /var/www/html/config -v /var/www/html/extra-apps -v /tmp/shared:/shared --dns $1 --hostname httpd.domain.test icewind1991/samba-krb-test-apache 1>&2
APACHE_IP=$(docker inspect apache --format '{{.NetworkSettings.IPAddress}}')
docker exec apache chown 33 /var/www/html/config /var/www/html/data /var/www/html/extra-apps
docker cp "$SCRIPT_DIR/apps.config.php" apache:/var/www/html/config/apps.config.php

# add the dns record for apache
docker exec dc samba-tool dns add krb.domain.test domain.test httpd A $APACHE_IP -U administrator --password=passwOrd1 1>&2

echo $APACHE_IP
