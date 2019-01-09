#!/usr/bin/env bash
#
RESULT=0

bash ./build/autoloaderchecker.sh
RESULT=$(($RESULT+$?))
php ./build/translation-checker.php
RESULT=$(($RESULT+$?))
php ./build/htaccess-checker.php
RESULT=$(($RESULT+$?))


for app in $(find "apps/" -mindepth 1 -maxdepth 1 -type d -exec basename {} \;); do
    echo "Testing $app"
    if
        [ "$app" == "dav" ] || \
        [ "$app" == "encryption" ] || \
        [ "$app" == "federatedfilesharing" ] || \
        [ "$app" == "files" ] || \
        [ "$app" == "files_external" ] || \
        [ "$app" == "files_sharing" ] || \
        [ "$app" == "files_trashbin" ] || \
        [ "$app" == "files_versions" ] || \
        [ "$app" == "lookup_server_connector" ] || \
        [ "$app" == "provisioning_api" ] || \
        [ "$app" == "testing" ] || \
        [ "$app" == "twofactor_backupcodes" ] || \
        [ "$app" == "updatenotification" ] || \
        [ "$app" == "user_ldap" ]
    then
        ./occ app:check-code --skip-checkers "$app"
    else
        ./occ app:check-code "$app"
    fi
    RESULT=$(($RESULT+$?))
done;

php ./build/files-checker.php
RESULT=$(($RESULT+$?))

exit $RESULT
