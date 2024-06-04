#!/bin/bash
#
# SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
trigger_notification() {
    which notify-send 1>/dev/null
    if [[ $? == 1 ]] ; then
        return
    fi
    export NOTIFY_USER=$SUDO_USER
    export RESULT_STR=$1
    # does not work. just pipe result into a non-sudo cmd
    su "$NOTIFY_USER" -c "notify-send -u normal -t 43200000 -a Nextcloud -i Nextcloud \"LDAP Integration tests $RESULT_STR\""
}

FILES_ROOT=($(ls -d -p Lib/* | grep -v "/$"))
FILES_USER=($(ls -d -p Lib/User/* | grep -v "/$"))
# TODO: Loop through dirs (and subdirs?) once there are more
TESTFILES=("${FILES_ROOT[@]}" "${FILES_USER[@]}")

TESTCMD="./run-test.sh"

echo "Running " ${#TESTFILES[@]} " tests"
for TESTFILE in "${TESTFILES[@]}" ; do
    echo -n "Test: $TESTFILE… "
	STATE=`$TESTCMD "$TESTFILE" | grep -c "Tests succeeded"`
	if [ "$STATE" -eq 0 ] ; then
		echo "failed!"
		trigger_notification "failed"
		exit 1
	fi
    echo "succeeded"
done

echo -e "\nAll tests succeeded"
trigger_notification "succeeded"
