#!/usr/bin/env bash

DC_IP=$1
shift

docker run --rm --name client -v /tmp/shared:/shared --dns $DC_IP --hostname client.domain.test icewind1991/samba-krb-test-client $@
