#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
RESULT=0

bash ./build/autoloaderchecker.sh
RESULT=$(($RESULT+$?))
php ./build/translation-checker.php
RESULT=$(($RESULT+$?))
php ./build/triple-dot-checker.php
RESULT=$(($RESULT+$?))
php ./build/htaccess-checker.php
RESULT=$(($RESULT+$?))
php ./build/files-checker.php
RESULT=$(($RESULT+$?))

exit $RESULT
