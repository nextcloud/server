#!/usr/bin/env bash
#
# Nextcloud
#
# This script start a docker container to test the files_external tests
# against. It will also change the files_external config to use the docker
# container as testing environment. This is reverted in the stop step.W
#
# Set environment variable DEBUG to print config file
#
# @author Morris Jobke
# @author Robin McCorkell
# @author Vincent Petry
# @copyright 2021 Nextcloud

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker setup"
    exit 0;
fi

echo "Docker executable found - setup docker"

docker_image=minio/minio

echo "Fetch recent ${docker_image} docker image"
docker pull ${docker_image}

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | replace "env/start-amazons3-minio.sh" ""`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# create readiness notification socket
notify_sock=$(readlink -f "$thisFolder"/dockerContainerMinio.$EXECUTOR_NUMBER.amazons3.sock)
rm -f "$notify_sock" # in case an unfinished test left one behind
mkfifo "$notify_sock"

accesskey=minioadmin
secretkey=minioadmin
bucket=nextcloud-dev
port=9000

container=`docker run -d \
	-p 9000:9000 \
    -v "$notify_sock":/run/notifyme.sock \
    ${docker_image} \
	server \
	/data{1...12}`

if test "$?" -ne "0"; then
	echo "Failed to start docker container" >&2
	exit 2
fi

host=`docker inspect --format="{{.NetworkSettings.IPAddress}}" $container`

echo "${docker_image} container: $container host: $host"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerMinio.$EXECUTOR_NUMBER.amazons3

echo -n "Waiting for minio initialization"
sleep 5 # FIXME: use notification / log parsing

cat > $thisFolder/config.amazons3.php <<DELIM
<?php

return array(
    'run'=>true,
    'bucket'=>'$bucket',
    'hostname'=>'$host',
    'port'=>'$port',
    'key'=>'$accesskey',
    'secret'=>'$secretkey',
    'use_ssl'=>false,
    'use_path_style'=>true,
);

DELIM

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.amazons3.php
    cat $thisFolder/dockerContainerMinio.$EXECUTOR_NUMBER.amazons3
fi
