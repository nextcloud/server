#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# This script stops the docker container the files_external tests were run
# against. It will also revert the config changes done in start step.
#

# retrieve current folder to remove the config from the parent folder
thisFolder=`echo $0 | sed 's#env/stop-smb-linux\.sh##'`

if [ -z "$thisFolder" ]; then
    thisFolder="."
fi;

# cleanup
rm $thisFolder/config.smb.php

