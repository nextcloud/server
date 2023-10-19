#!/usr/bin/env bash
set -e

function getContainerHealth {
  docker inspect --format "{{.State.Health.Status}}" "$1"
}

function waitContainer {
  while STATUS=$(getContainerHealth "$1"); [ "$STATUS" != "healthy" ]; do
    if [ "$STATUS" == "unhealthy" ]; then
      echo "Failed!" 1>&2
      exit 1
    fi
    printf . 1>&2
    lf=$'\n'
    sleep 1
  done
  printf "$lf" 1>&2
}

docker rm -f dc 2>/dev/null > /dev/null

mkdir -p /tmp/shared

# start the dc
docker run -dit --name dc -v /tmp/shared:/shared --hostname krb.domain.test --cap-add SYS_ADMIN icewind1991/samba-krb-test-dc 1>&2

waitContainer dc

sleep 5

docker inspect dc --format '{{.NetworkSettings.IPAddress}}'
