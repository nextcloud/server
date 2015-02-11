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

echo "Fetch recent morrisjobke/docker-swift-onlyone docker image"
docker pull morrisjobke/docker-swift-onlyone

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | replace "env/start-swift-morrisjobke.sh" ""`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

container=`docker run -d -e SWIFT_SET_PASSWORDS=true morrisjobke/docker-swift-onlyone`

host=`docker inspect $container | grep IPAddress | cut -d '"' -f 4`


echo "swift container: $container"

# put container IDs into a file to drop them after the test run (keep in mind that multiple tests run in parallel on the same host)
echo $container >> $thisFolder/dockerContainerMorrisJobke.$EXECUTOR_NUMBER.swift

# TODO find a way to determine the successful initialization inside the docker container
echo "Waiting 15 seconds for swift initialization ... "
sleep 15

user=test:tester
password=`docker logs $container | grep "user_test_tester " | cut -d " " -f3`

cat > $thisFolder/config.swift.php <<DELIM
<?php

return array(
    'run'=>true,
    'url'=>'http://$host:8080/auth/v1.0',
    'user'=>'$user',
    'key'=>'$password',
    'bucket'=>'swift',
    'region' => 'DFW',
);

DELIM

if [ -n "$DEBUG" ]; then
    cat $thisFolder/config.swift.php
    cat $thisFolder/dockerContainerMorrisJobke.$EXECUTOR_NUMBER.swift
fi
