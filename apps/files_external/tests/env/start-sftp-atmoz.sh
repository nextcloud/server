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

host=`docker inspect $container | grep IPAddress | cut -d '"' -f 4`

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

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.sftp.php
    cat $thisFolder/dockerContainerAtmoz.$EXECUTOR_NUMBER.sftp
fi

# TODO find a way to determine the successful initialization inside the docker container
echo "Waiting 5 seconds for sftp initialization ... "
sleep 5

# create folder "upload" with correct permissions
docker exec $container bash -c "mkdir /home/$user/upload && chown $user:users /home/$user/upload"

