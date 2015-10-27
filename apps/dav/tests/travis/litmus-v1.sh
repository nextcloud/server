#!/usr/bin/env bash

SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../.." &


# compile litmus
if [ ! -f /tmp/litmus/litmus-0.13.tar.gz ]; then
  mkdir -p /tmp/litmus
  wget -O /tmp/litmus/litmus-0.13.tar.gz http://www.webdav.org/neon/litmus/litmus-0.13.tar.gz
  cd /tmp/litmus
  tar -xzf litmus-0.13.tar.gz
  cd /tmp/litmus/litmus-0.13
  ./configure
  make
fi

# run the tests
cd /tmp/litmus/litmus-0.13
make URL=http://127.0.0.1:8888/remote.php/webdav CREDS="admin admin" TESTS="basic copymove props locks" check
