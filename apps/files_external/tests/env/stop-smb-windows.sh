#!/usr/bin/env bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2015 Thomas Müller <deepdiver@owncloud.com>
#

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-smb-windows\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;


# cleanup
rm $thisFolder/config.smb.php
