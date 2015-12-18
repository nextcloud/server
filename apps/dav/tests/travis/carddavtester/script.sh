#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &

# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail -s "$SCRIPTPATH/../caldavtest/config/serverinfo.xml" -o cdt.txt \
	"$SCRIPTPATH/../caldavtest/tests/CardDAV/current-user-principal.xml" \
	"$SCRIPTPATH/../caldavtest/tests/CardDAV/sync-report.xml"
RESULT=$?

tail "$/../../../../../data-autotest/owncloud.log"

exit $RESULT
