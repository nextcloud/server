#!/usr/bin/env bash

composer install

# TODO: avoid port collision on jenkins - use $EXECUTOR_NUMBER
#if [ -z "$EXECUTOR_NUMBER" ]; then
#    EXECUTOR_NUMBER=0
#fi
#PORT=$((8080 + $EXECUTOR_NUMBER))
PORT=8080
echo $PORT
php -S localhost:$PORT -t ../.. &
PHPPID=$!
echo $PHPPID

#export BEHAT_PARAMS="context[parameters][base_url]=http://localhost:$PORT/ocs"
vendor/bin/behat --profile ci

kill $PHPPID
