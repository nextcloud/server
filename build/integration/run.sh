#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#
OC_PATH=../../
OCC=${OC_PATH}occ
TAGS=""
if [ "$1" = "--tags" ]; then
	TAGS="--tags=$2"

	shift 2
fi
SCENARIO_TO_RUN=$1
HIDE_OC_LOGS=$2

INSTALLED=$($OCC status | grep installed: | cut -d " " -f 5)

if [ "$INSTALLED" == "true" ]; then
    # Disable appstore to avoid spamming from CI
    $OCC config:system:set appstoreenabled --value=false --type=boolean
    # Disable bruteforce protection because the integration tests do trigger them
    $OCC config:system:set auth.bruteforce.protection.enabled --value false --type bool
    # Disable rate limit protection because the integration tests do trigger them
    $OCC config:system:set ratelimit.protection.enabled --value false --type bool
    # Allow local remote urls otherwise we can not share
    $OCC config:system:set allow_local_remote_servers --value true --type bool
    # Allow self signed certificates
    $OCC config:system:set sharing.federation.allowSelfSignedCertificates --value true --type bool
	# Allow creating users with dummy passwords
	$OCC app:disable password_policy
else
    if [ "$SCENARIO_TO_RUN" != "setup_features/setup.feature" ]; then
        echo "Nextcloud instance needs to be installed" >&2
        exit 1
    fi
fi
NC_DATADIR=$($OCC config:system:get datadirectory)

composer install

# avoid port collision on jenkins - use $EXECUTOR_NUMBER
if [ -z "$EXECUTOR_NUMBER" ]; then
    EXECUTOR_NUMBER=0
fi
PORT=$((8080 + $EXECUTOR_NUMBER))
echo $PORT
export PORT

echo "" > "${NC_DATADIR}/nextcloud.log"
echo "" > phpserver.log

PHP_CLI_SERVER_WORKERS=2 php -S localhost:$PORT -t ../.. &> phpserver.log &
PHPPID=$!
echo $PHPPID

# Output filtered php server logs
tail -f phpserver.log | grep --line-buffered -v -E ":[0-9]+ Accepted$" | grep --line-buffered -v -E ":[0-9]+ Closing$" &
LOGPID=$!
echo $LOGPID

function cleanup() {
    kill $PHPPID
    kill $LOGPID
}
trap cleanup EXIT

# The federated server is started and stopped by the tests themselves
PORT_FED=$((8180 + $EXECUTOR_NUMBER))
echo $PORT_FED
export PORT_FED

export TEST_SERVER_URL="http://localhost:$PORT/ocs/"
export TEST_SERVER_FED_URL="http://localhost:$PORT_FED/ocs/"

if [ "$INSTALLED" == "true" ]; then

    #Enable external storage app
    $OCC app:enable files_external user_ldap

    mkdir -p work/local_storage
    OUTPUT_CREATE_STORAGE=`$OCC files_external:create local_storage local null::null -c datadir=$PWD/work/local_storage`

    ID_STORAGE=`echo $OUTPUT_CREATE_STORAGE | tr ' ' '\n' | tail -n1`

    $OCC files_external:option $ID_STORAGE enable_sharing true

fi

vendor/bin/behat --strict --colors -f junit -f pretty $TAGS $SCENARIO_TO_RUN
RESULT=$?

if [ "$INSTALLED" == "true" ]; then

    $OCC files_external:delete -y $ID_STORAGE

    #Disable external storage app
    $OCC app:disable files_external user_ldap
fi

if [ -z $HIDE_OC_LOGS ]; then
	tail "${NC_DATADIR}/nextcloud.log"
fi

echo "runsh: Exit code: $RESULT"
exit $RESULT
