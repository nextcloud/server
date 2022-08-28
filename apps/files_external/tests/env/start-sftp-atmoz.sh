#!/usr/bin/env bash
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
# @copyright 2015 Morris Jobke <hey@morrisjobke.de>
#

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker setup"
    exit 0;
fi

echo "Docker executable found - setup docker"

echo "Fetch recent atmoz/sftp docker image"
docker pull atmoz/sftp

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | sed 's#env/start-sftp-atmoz\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

user=test
password=12345

container=`docker run -d atmoz/sftp $user:$password:1001`

host=`docker inspect --format="{{.NetworkSettings.IPAddress}}" $container`

cat > $thisFolder/config.sftp.php <<DELIM
<?php

return array(
    'run'=>true,
    'host'=>'$host',
    'user'=>'$user',
    'password'=>'$password',
    'root'=>'upload',
);

DELIM

echo "sftp container: $container"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerAtmoz.$EXECUTOR_NUMBER.sftp

echo -n "Waiting for sftp initialization"
if ! "$thisFolder"/env/wait-for-connection ${host} 22 60; then
    echo "[ERROR] Waited 60 seconds, no response" >&2
    exit 1
fi
sleep 1

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.sftp.php
    cat $thisFolder/dockerContainerAtmoz.$EXECUTOR_NUMBER.sftp
fi

# create folder "upload" with correct permissions
docker exec $container bash -c "mkdir /home/$user/upload && chown $user:users /home/$user/upload"

