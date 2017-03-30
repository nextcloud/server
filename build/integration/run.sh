#!/usr/bin/env bash

OC_PATH=../../
OCC=${OC_PATH}occ
SCENARIO_TO_RUN=$1
HIDE_OC_LOGS=$2

INSTALLED=$($OCC status | grep installed: | cut -d " " -f 5)

if [ "$INSTALLED" == "true" ]; then
    # Disable bruteforce protection because the integration tests do trigger them
    $OCC config:system:set auth.bruteforce.protection.enabled --value false --type bool
else
    if [ "$SCENARIO_TO_RUN" != "setup_features/setup.feature" ]; then
        echo "Nextcloud instance needs to be installed" >&2
        exit 1
    fi
fi

composer install

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

if [ "$INSTALLED" == "true" ]; then

    #Enable external storage app
    $OCC app:enable files_external

    mkdir -p work/local_storage
    OUTPUT_CREATE_STORAGE=`$OCC files_external:create local_storage local null::null -c datadir=$PWD/work/local_storage`

    ID_STORAGE=`echo $OUTPUT_CREATE_STORAGE | tr ' ' '\n' | tail -n1`

    $OCC files_external:option $ID_STORAGE enable_sharing true

fi

vendor/bin/behat --strict -f junit -f pretty $SCENARIO_TO_RUN
RESULT=$?

kill $PHPPID
kill $PHPPID_FED

if [ "$INSTALLED" == "true" ]; then

    $OCC files_external:delete -y $ID_STORAGE

    #Disable external storage app
    $OCC app:disable files_external
fi

if [ -z $HIDE_OC_LOGS ]; then
	tail "${OC_PATH}/data/nextcloud.log"
fi

echo "runsh: Exit code: $RESULT"
exit $RESULT
