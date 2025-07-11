#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2015 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`


# start the server
php -S 127.0.0.1:8888 -t "$SCRIPTPATH/../../../../.." &

sleep 30

# run the tests
cd /tmp/litmus/litmus-0.13
make URL=http://127.0.0.1:8888/remote.php/dav/files/admin CREDS="admin admin" TESTS="basic copymove props largefile" check
