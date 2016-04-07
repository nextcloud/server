#!/usr/bin/env bash

cd "$(dirname "$0")"

TESTSCRIPTS=`find ./lib/ -name "*.php"`

for SCRIPT in ${TESTSCRIPTS}
do
    CMD="./run-test.sh $SCRIPT"
    echo "$CMD"
    ${CMD} || exit $?
done
