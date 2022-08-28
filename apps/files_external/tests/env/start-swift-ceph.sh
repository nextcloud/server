#!/bin/bash
#
# ownCloud
#
# This script start a docker container to test the files_external tests
# against. It will also change the files_external config to use the docker
# container as testing environment. This is reverted in the stop step.W
#
# Set environment variable DEBUG to print config file
#
# @author Morris Jobke
# @author Robin McCorkell
# @copyright 2015 ownCloud

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker setup"
    exit 0;
fi

echo "Docker executable found - setup docker"

docker_image=xenopathic/ceph-keystone

echo "Fetch recent ${docker_image} docker image"
docker pull ${docker_image}

# debian 8 default comes without loaded loop module. please run "sudo modprobe loop" if you get an error here:
lsmod | grep '^loop' || { echo "Error: kernel module loop not loaded. Needed by docker image ${docker_image}"; exit 1; }

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | sed 's#env/start-swift-ceph\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# create readiness notification socket
notify_sock=$(readlink -f "$thisFolder"/dockerContainerCeph.$EXECUTOR_NUMBER.swift.sock)
rm -f "$notify_sock" # in case an unfinished test left one behind
mkfifo "$notify_sock"

port=5001

user=test
pass=testing
tenant=testenant
region=testregion
service=testceph

container=`docker run -d \
    -e KEYSTONE_PUBLIC_PORT=${port} \
    -e KEYSTONE_ADMIN_USER=${user} \
    -e KEYSTONE_ADMIN_PASS=${pass} \
    -e KEYSTONE_ADMIN_TENANT=${tenant} \
    -e KEYSTONE_ENDPOINT_REGION=${region} \
    -e KEYSTONE_SERVICE=${service} \
    -e OSD_SIZE=300 \
    --privileged \
    -v "$notify_sock":/run/notifyme.sock \
    ${docker_image}`

host=`docker inspect --format="{{.NetworkSettings.IPAddress}}" $container`


echo "${docker_image} container: $container"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift

echo "Waiting for ceph initialization"
ready=$(timeout 600 cat "$notify_sock")
if [[ $ready != 'READY=1' ]]; then
    echo "[ERROR] Waited 600 seconds, no response" >&2
    docker logs $container
    exit 1
fi
if ! "$thisFolder"/env/wait-for-connection ${host} 80 600; then
    echo "[ERROR] Waited 600 seconds, no response" >&2
    docker logs $container
    exit 1
fi
echo "Waiting another 15 seconds"
sleep 15 

cat > $thisFolder/config.swift.php <<DELIM
<?php

return array(
    'run'=>true,
    'url'=>'http://$host:$port/v2.0',
    'user'=>'$user',
    'tenant'=>'$tenant',
    'password'=>'$pass',
    'service_name'=>'$service',
    'bucket'=>'swift',
    'region' => '$region',
);

DELIM

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.swift.php
    cat $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift
fi
