#!/usr/bin/env bash

SETUP_SCRIPTS_DIR="setup-scripts"

cd "$(dirname "$0")"

for SCRIPT in start.sh stop.sh config.php
do
    if [ ! -e "$SETUP_SCRIPTS_DIR/$SCRIPT" ] ; then
        wget "https://raw.githubusercontent.com/owncloud/administration/master/ldap-testing/$SCRIPT" \
            -O "$SETUP_SCRIPTS_DIR/$SCRIPT"
        if [[ "$SCRIPT" == *.sh ]] ; then
            chmod +x "$SETUP_SCRIPTS_DIR/$SCRIPT"
        fi
    fi
done

TESTSCRIPTS=`find ./lib/ -name "*.php"`

for SCRIPT in ${TESTSCRIPTS}
do
    CMD="./run-test.sh $SCRIPT"
    echo "$CMD"
    ${CMD} || exit $?
done
