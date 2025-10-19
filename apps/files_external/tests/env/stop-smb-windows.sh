#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2015 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-smb-windows\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;


# cleanup
rm $thisFolder/config.smb.php
