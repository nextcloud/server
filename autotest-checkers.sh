#!/usr/bin/env bash
#

bash ./build/autoloaderchecker.sh
bash ./build/mergejschecker.sh
php ./build/translation-checker.php
php ./build/htaccess-checker.php


for app in $(find "apps/" -mindepth 1 -maxdepth 1 -type d -printf '%f\n'); do
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
        ./occ app:check-code -c strong-comparison "$app"
    else
        ./occ app:check-code "$app"
    fi
    RESULT=$?
done;

php ./build/signed-off-checker.php
