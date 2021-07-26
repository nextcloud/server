#!/usr/bin/env bash
#
# Nextcloud

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-webdav-apachedrone\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# cleanup
rm $thisFolder/config.webdav.php

