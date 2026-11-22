#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#
# This script stops the docker container the files_external tests were run
# against. It will also revert the config changes done in start step.
#

if ! command -v docker >/dev/null 2>&1; then
    echo "No docker executable found - skipped docker stop"
    exit 0;
fi

echo "Docker executable found - stop and remove docker containers"

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-ftp-morrisjobke\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# stopping and removing docker containers
for container in `cat $thisFolder/dockerContainerMorrisJobke.$EXECUTOR_NUMBER.ftp`; do
    echo "Stopping and removing docker container $container"
    # kills running container and removes it
    docker stop $container
    docker rm -f $container
done;

# cleanup
rm $thisFolder/config.ftp.php
rm $thisFolder/dockerContainerMorrisJobke.$EXECUTOR_NUMBER.ftp

