#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &

sleep 30

# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail --basedir "$SCRIPTPATH/../caldavtest/" -o cdt.txt \
	"CardDAV/current-user-principal.xml" \
	"CardDAV/sync-report.xml" \
	"CardDAV/sharing-addressbooks.xml"


RESULT=$?

tail "$/../../../../../data-autotest/nextcloud.log"

exit $RESULT
