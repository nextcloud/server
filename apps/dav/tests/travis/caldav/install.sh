#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


cd "$SCRIPTPATH"
if [ ! -f CalDAVTester/testcaldav.py ]; then
    git clone https://github.com/apple/ccs-caldavtester.git CalDAVTester
fi
if [ ! -f pycalendar/setup.py ]; then
    git clone https://github.com/apple/ccs-pycalendar.git pycalendar
fi

cd "$SCRIPTPATH/../../../../../"

# disable the trashbin, so recurrent deletion of the same object works
php occ config:app:set dav calendarRetentionObligation --value=0

# create test user
OC_PASS=user01 php occ user:add --password-from-env user01
php occ dav:create-calendar user01 calendar
php occ dav:create-calendar user01 shared
OC_PASS=user02 php occ user:add --password-from-env user02
php occ dav:create-calendar user02 calendar
cd "$SCRIPTPATH/../../../../../"
