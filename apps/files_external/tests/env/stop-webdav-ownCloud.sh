#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker stop"
    exit 0;
fi

echo "Docker executable found - stop and remove docker containers"

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-webdav-ownCloud\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# stopping and removing docker containers
for container in `cat $thisFolder/dockerContainerOwnCloud.$EXECUTOR_NUMBER.webdav`; do
    echo "Stopping and removing docker container $container"
    # kills running container and removes it
    docker stop $container
    docker rm -f $container
done;

# cleanup
rm $thisFolder/config.webdav.php
rm $thisFolder/dockerContainerOwnCloud.$EXECUTOR_NUMBER.webdav

