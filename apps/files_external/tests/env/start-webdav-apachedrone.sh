#!/usr/bin/env bash
#
# Nextcloud
#

# retrieve current folder to place the config in the parent folder
thisFolder=`echo $0 | sed 's#env/start-webdav-apachedrone\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

cat > $thisFolder/config.webdav.php <<DELIM
<?php

return array(
    'run'=>true,
    'host'=>'127.0.0.1:80/webdav/',
    'user'=>'test',
    'password'=>'pass',
    'root'=>'',
    'wait'=> 0
);

DELIM

