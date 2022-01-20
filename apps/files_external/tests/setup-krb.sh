#!/bin/bash

function getContainerHealth {
  docker inspect --format "{{.State.Health.Status}}" $1
}

function waitContainer {
  while STATUS=$(getContainerHealth $1); [ $STATUS != "healthy" ]; do
    if [ $STATUS == "unhealthy" ]; then
      echo "Failed!"
      exit -1
    fi
    printf .
    lf=$'\n'
    sleep 1
  done
  printf "$lf"
}

mkdir /tmp/shared

# start the dc
docker run -dit --name dc -v /tmp/shared:/shared --hostname krb.domain.test --cap-add SYS_ADMIN icewind1991/samba-krb-test-dc
DC_IP=$(docker inspect dc --format '{{.NetworkSettings.IPAddress}}')

waitContainer dc

# start apache
docker run -d --name apache -v $PWD:/var/www/html -v /tmp/shared:/shared --dns $DC_IP --hostname httpd.domain.test icewind1991/samba-krb-test-apache
APACHE_IP=$(docker inspect apache --format '{{.NetworkSettings.IPAddress}}')

# add the dns record for apache
docker exec dc samba-tool dns add krb.domain.test domain.test httpd A $APACHE_IP -U administrator --password=passwOrd1
