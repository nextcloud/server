#!/bin/bash
set -e

echo Removing config
rm config/config.php

echo Removing data folder
rm -rf data/

echo Installing Nextcloud
php console.php maintenance:install --admin-user "Admin" --admin-pass "!QAZ1qaz" --database "mysql" --database-name "nextcloud" --database-user "nextcloud" --database-pass "userp@ssword" --database-host "sunet-mdb2"
echo Enabling apps
echo Assuming that you have mounted the user_saml app from the host
php console.php app:enable files_accesscontrol
php console.php app:enable user_saml
#php console.php app:enable mfachecker
php console.php app:enable mfaverifiedzone
echo Editing config
sed -i "8 i\    2 => 'sunet-nc2'," config/config.php
sed -i "8 i\    1 => 'mesh.pondersource.org'," config/config.php
sed -i "3 i\  'allow_local_remote_servers' => true," config/config.php
