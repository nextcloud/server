#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../.." &


if [ ! -f CalDAVTester/run.py ]; then
	cd "$SCRIPTPATH"
    git clone https://github.com/DeepDiver1975/CalDAVTester.git
	cd "$SCRIPTPATH/CalDAVTester"
    python run.py -s
	cd "$SCRIPTPATH"
fi

# create test user
cd "$SCRIPTPATH/../../../../"
OC_PASS=user01 php occ user:add --password-from-env user01
php occ dav:create-addressbook user01 addressbook
OC_PASS=user02 php occ user:add --password-from-env user02
php occ dav:create-addressbook user02 addressbook
cd "$SCRIPTPATH/../../../../"

# run the tests
cd "$SCRIPTPATH/CalDAVTester"
PYTHONPATH="$SCRIPTPATH/pycalendar/src" python testcaldav.py --print-details-onfail -s "$SCRIPTPATH/caldavtest/config/serverinfo.xml" -o cdt.txt \
	"$SCRIPTPATH/caldavtest/tests/CardDAV/current-user-principal.xml" \
	"$SCRIPTPATH/caldavtest/tests/CardDAV/sync-report.xml"

