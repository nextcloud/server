#!/bin/bash
#
# ownCloud
#
# This script stops the docker container the files_external tests were run
# against. It will also revert the config changes done in start step.
#
# @author Morris Jobke
# @author Robin McCorkell
# @copyright 2015 ownCloud

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker stop"
    exit 0;
fi

echo "Docker executable found - stop and remove docker containers"

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | replace "stop-swift-ceph.sh" ""`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

if [ -e $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift ]; then
    # stopping and removing docker containers
    for container in `cat $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift`; do
        if [ -n "$DEBUG" ]; then
            docker logs $container
        fi
        echo "Stopping and removing docker container $container"
        # kills running container and removes it
        docker stop $container
        docker rm -f $container
    done;
fi;

# cleanup
rm -rf $thisFolder/swift.config.php
rm -rf $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift
rm -rf $thisFolder/dockerContainerCeph.$EXECUTOR_NUMBER.swift.sock
