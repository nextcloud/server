#!/usr/bin/env bash
#
# ownCloud
#
# This script stops the docker container the files_external tests were run
# against. It will also revert the config changes done in start step.
#
# @author Morris Jobke
# @copyright 2015 Morris Jobke <hey@morrisjobke.de>
#

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-smb-linux\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# cleanup
rm $thisFolder/config.smb.php

