#!/usr/bin/env bash
#
RESULT=0

bash ./build/autoloaderchecker.sh
RESULT=$(($RESULT+$?))
php ./build/translation-checker.php
RESULT=$(($RESULT+$?))
php ./build/triple-dot-checker.php
RESULT=$(($RESULT+$?))
php ./build/htaccess-checker.php
RESULT=$(($RESULT+$?))
bash ./build/ca-bundle-checker.sh
RESULT=$(($RESULT+$?))
php ./build/OCPSinceChecker.php
RESULT=$(($RESULT+$?))

php ./build/files-checker.php
RESULT=$(($RESULT+$?))

exit $RESULT
