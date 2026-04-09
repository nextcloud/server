#!/bin/sh
#
# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
if [ $1 ] ; then
  TESTSCRIPT=$1
else
  echo "No test file given"                                                                                                                                                    exit
fi

if [ ! -e "$TESTSCRIPT" ] ; then
    echo "Test file does not exist"
    exit
fi


# sleep is necessary, otherwise the LDAP server cannot be connected to, yet.
setup-scripts/start.sh && sleep 5 && php -f "$TESTSCRIPT"
setup-scripts/stop.sh
