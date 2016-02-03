#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

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

tail "$/../../../../../data-autotest/owncloud.log"

exit $RESULT
