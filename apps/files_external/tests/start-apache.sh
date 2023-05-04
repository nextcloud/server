#!/usr/bin/env bash

docker rm -f apache 2>/dev/null > /dev/null

docker run -d --name apache -v $2:/var/www/html -v /tmp/shared:/shared --dns $1 --hostname httpd.domain.test icewind1991/samba-krb-test-apache 1>&2
APACHE_IP=$(docker inspect apache --format '{{.NetworkSettings.IPAddress}}')

# add the dns record for apache
docker exec dc samba-tool dns add krb.domain.test domain.test httpd A $APACHE_IP -U administrator --password=passwOrd1 1>&2

echo $APACHE_IP
