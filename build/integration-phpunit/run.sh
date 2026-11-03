#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -euo pipefail

PHPUNIT_EXECUTABLE=../../vendor-bin/behat/vendor/bin/phpunit
if [ ! -f "$PHPUNIT_EXECUTABLE" ]; then
    echo "PHPUnit executable not found. Please run 'composer install' in the root directory first." >&2
    exit 1
fi

OC_PATH=../../
OCC=${OC_PATH}occ

INSTALLED=$($OCC status | grep installed: | cut -d " " -f 5 || true)
if [ "$INSTALLED" != "true" ]; then
    echo "Nextcloud instance needs to be installed" >&2
    exit 1
fi

# Disable appstore to avoid spamming from CI
$OCC config:system:set appstoreenabled --value=false --type=boolean
# Disable bruteforce protection because the integration tests do trigger them
$OCC config:system:set auth.bruteforce.protection.enabled --value false --type bool
# Disable rate limit protection, the tests enable it for themselves
$OCC config:system:set ratelimit.protection.enabled --value false --type bool
# Allow creating users with dummy passwords
$OCC app:disable password_policy || true

NC_DATADIR=$($OCC config:system:get datadirectory)

# avoid port collision on jenkins - use $EXECUTOR_NUMBER
PORT=$((8080 + ${EXECUTOR_NUMBER:-0}))
export PORT

echo "" > "${NC_DATADIR}/nextcloud.log"
echo "" > phpserver.log

PHP_CLI_SERVER_WORKERS=2 php -S localhost:$PORT -t ../.. &> phpserver.log &
PHPPID=$!

# Output filtered php server logs
tail -f phpserver.log | grep --line-buffered -v -E ":[0-9]+ (Accepted|Closing)$" &
LOGPID=$!

function cleanup() {
    kill $PHPPID 2>/dev/null || true
    kill $LOGPID 2>/dev/null || true
}
trap cleanup EXIT

export NEXTCLOUD_BASE_URL="http://localhost:$PORT"

set +e
php "$PHPUNIT_EXECUTABLE" --configuration phpunit.xml "$@"
RESULT=$?
set -e

tail "${NC_DATADIR}/nextcloud.log"

echo "run.sh: Exit code: $RESULT"
exit $RESULT
