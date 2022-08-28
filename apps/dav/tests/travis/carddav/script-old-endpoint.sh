#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

# Move the endpoint to the serverinfo file
cp "$SCRIPTPATH/../caldavtest/serverinfo-old-carddav-endpoint.xml" "$SCRIPTPATH/../caldavtest/serverinfo.xml"

# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &


# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail --basedir "$SCRIPTPATH/../caldavtest/" -o cdt.txt \
	"CardDAV/current-user-principal.xml" \
	"CardDAV/sync-report.xml"

RESULT=$?

tail "$/../../../../../data-autotest/nextcloud.log"

exit $RESULT
