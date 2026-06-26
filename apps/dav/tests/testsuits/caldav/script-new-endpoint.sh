#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

# Move the endpoint to the serverinfo file
cp "$SCRIPTPATH/../caldavtest/serverinfo-new-endpoint.xml" "$SCRIPTPATH/../caldavtest/serverinfo.xml"

# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &

sleep 30

# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail --basedir "$SCRIPTPATH/../caldavtest/" -o cdt.txt \
	"CalDAV/current-user-principal.xml" \
	"CalDAV/sync-report.xml" \
	"CalDAV/sharing-calendars.xml"

RESULT=$?

tail "$/../../../../../data-autotest/nextcloud.log"

exit $RESULT
