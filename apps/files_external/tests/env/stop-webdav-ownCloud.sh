#!/bin/bash
#
# ownCloud
#
# This script stops the docker container the files_external tests were run
# against. It will also revert the config changes done in start step.
#
# @author Morris Jobke
# @copyright 2014 Morris Jobke <hey@morrisjobke.de>
#

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker stop"
    exit 0;
fi

echo "Docker executable found - stop and remove docker containers"

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | replace "env/stop-webdav-ownCloud.sh" ""`

echo "DEBUG"

netstat -tlpen

echo "CONFIG:"

cat $thisFolder/config.webdav.php
cat $thisFolder/dockerContainerOwnCloud.$EXECUTOR_NUMBER.webdav

# stopping and removing docker containers
for container in `cat $thisFolder/dockerContainerOwnCloud.$EXECUTOR_NUMBER.webdav`; do
    echo "Stopping and removing docker container $container"
    # kills running container and removes it
    docker rm -f $container
done;

# cleanup
rm $thisFolder/config.webdav.php
rm $thisFolder/dockerContainerOwnCloud.$EXECUTOR_NUMBER.webdav

