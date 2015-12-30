#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &

sleep 30

# run the tests
cd /tmp/litmus/litmus-0.13
make URL=http://127.0.0.1:8888/remote.php/dav/files/admin CREDS="admin admin" TESTS="basic copymove props locks" check
