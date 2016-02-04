#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


if [ ! -f CalDAVTester/run.py ]; then
	cd "$SCRIPTPATH"
    git clone https://github.com/DeepDiver1975/CalDAVTester.git
	cd "$SCRIPTPATH/CalDAVTester"
    python run.py -s
	cd "$SCRIPTPATH"
fi

# create test user
cd "$SCRIPTPATH/../../../../../"
OC_PASS=user01 php occ user:add --password-from-env user01
php occ dav:create-calendar user01 calendar
php occ dav:create-calendar user01 shared
OC_PASS=user02 php occ user:add --password-from-env user02
php occ dav:create-calendar user02 calendar
cd "$SCRIPTPATH/../../../../../"
