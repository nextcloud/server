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

# retrieve current folder to place the config in the parent folder
thisFolder="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# create readiness notification socket
notify_sock=$(readlink -f "$thisFolder"/dockerContainerCeph.$EXECUTOR_NUMBER.swift.sock)
rm -f "$notify_sock" # in case an unfinished test left one behind
mkfifo "$notify_sock"

port=5034

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
    -v "$notify_sock":/run/notifyme.sock \
    --privileged \
    ${docker_image}`

host=$(docker inspect --format="{{.NetworkSettings.IPAddress}}" "$container")


echo "${docker_image} container: $container"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift

echo -n "Waiting for ceph initialization"
ready=$(timeout 600 cat "$notify_sock")
if [[ $ready != 'READY=1' ]]; then
    echo "[ERROR] Waited 600 seconds, no response" >&2
    docker logs $container
    exit 1
fi
if ! "$thisFolder"/wait-for-connection ${host} 80 600; then
    echo "[ERROR] Waited 600 seconds, no response" >&2
    docker logs $container
    exit 1
fi
echo "Waiting another 15 seconds"
sleep 15

cat > $thisFolder/swift.config.php <<DELIM
<?php
\$CONFIG = array (
'objectstore' => array(
	'class' => 'OC\\Files\\ObjectStore\\Swift',
	'arguments' => array(
		'username' => '$user',
		'password' => '$pass',
		'container' => 'owncloud-autotest$EXECUTOR_NUMBER',
		'autocreate' => true,
		'region' => '$region',
		'url' => 'http://$host:$port/v2.0',
		'tenantName' => '$tenant',
		'serviceName' => '$service',
	),
),
);

DELIM

if [ -n "$DEBUG" ]; then
    echo "############## DEBUG info ###############"
    echo "### Docker info"
    docker info
    echo "### Docker images"
    docker images
    echo "### current mountpoints"
    mount
    echo "### contents of $thisFolder/swift.config.php"
    cat $thisFolder/swift.config.php
    echo "### contents of $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift"
    cat $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift
    echo "### docker logs"
    docker logs $container
    echo "############## DEBUG info end ###########"
fi
