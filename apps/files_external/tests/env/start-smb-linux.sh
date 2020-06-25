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

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | sed 's#env/start-smb-linux\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

cat > $thisFolder/config.smb.php <<DELIM
<?php

return array(
    'run'=>true,
    'host'=>'127.0.0.1',
    'user'=>'test',
    'password'=>'test',
    'root'=>'',
    'share'=>'public',
);

DELIM

echo -n "Waiting for samba initialization"
if ! "$thisFolder"/env/wait-for-connection 127.0.0.1 445 60; then
    echo "[ERROR] Waited 60 seconds, no response" >&2
    exit 1
fi

sleep 1
