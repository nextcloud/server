#!/usr/bin/env bash

cd ../core
php occ app:enable files_external_ftp


# Test lint
cd apps/files_external_ftp
find . -name \*.php -exec php -l "{}" \;

# Run phpunit tests
cd tests
echo '{"host":"localhost","username":"test","password":"test"}' > config.json
phpunit --configuration phpunit.xml
