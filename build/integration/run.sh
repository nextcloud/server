#!/usr/bin/env bash

COMPOSER=$(which composer)

if [ -x "$COMPOSER" ]; then
	echo "Using composer executable $COMPOSER"
else
	echo "Could not find composer executable" >&2
	exit 1
fi

# Disable bruteforce protection because the integration tests do trigger them
../../occ config:system:set auth.bruteforce.protection.enabled --value false --type bool

composer install

SCENARIO_TO_RUN=$1
HIDE_OC_LOGS=$2

# avoid port collision on jenkins - use $EXECUTOR_NUMBER
if [ -z "$EXECUTOR_NUMBER" ]; then
    EXECUTOR_NUMBER=0
fi
PORT=$((8080 + $EXECUTOR_NUMBER))
echo $PORT
php -S localhost:$PORT -t ../.. &
PHPPID=$!
echo $PHPPID

PORT_FED=$((8180 + $EXECUTOR_NUMBER))
echo $PORT_FED
php -S localhost:$PORT_FED -t ../.. &
PHPPID_FED=$!
echo $PHPPID_FED

export TEST_SERVER_URL="http://localhost:$PORT/ocs/"
export TEST_SERVER_FED_URL="http://localhost:$PORT_FED/ocs/"

#Enable external storage app
../../occ app:enable files_external

mkdir -p work/local_storage
OUTPUT_CREATE_STORAGE=`../../occ files_external:create local_storage local null::null -c datadir=./build/integration/work/local_storage` 

ID_STORAGE=`echo $OUTPUT_CREATE_STORAGE | awk {'print $5'}`

../../occ files_external:option $ID_STORAGE enable_sharing true

vendor/bin/behat -f junit -f pretty $SCENARIO_TO_RUN
RESULT=$?

kill $PHPPID
kill $PHPPID_FED

../../occ files_external:delete -y $ID_STORAGE

#Disable external storage app
../../occ app:disable files_external

if [ -z $HIDE_OC_LOGS ]; then
	tail "../../data/nextcloud.log"
fi

exit $RESULT

