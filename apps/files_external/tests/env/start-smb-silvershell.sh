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

echo "Fetch recent silvershell/samba docker image"
docker pull silvershell/samba

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | sed 's#env/start-smb-silvershell\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

container=`docker run -d -e SMB_USER=test -e SMB_PWD=test silvershell/samba`

host=`docker inspect --format="{{.NetworkSettings.IPAddress}}" $container`

cat > $thisFolder/config.smb.php <<DELIM
<?php

return array(
    'run'=>true,
    'host'=>'$host',
    'user'=>'test',
    'password'=>'test',
    'root'=>'',
    'share'=>'public',
);

DELIM

echo "samba container: $container"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerSilvershell.$EXECUTOR_NUMBER.smb

echo -n "Waiting for samba initialization"
if ! "$thisFolder"/env/wait-for-connection ${host} 445 60; then
    echo "[ERROR] Waited 60 seconds, no response" >&2
    exit 1
fi
sleep 1

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.smb.php
    cat $thisFolder/dockerContainerSilvershell.$EXECUTOR_NUMBER.smb
fi


