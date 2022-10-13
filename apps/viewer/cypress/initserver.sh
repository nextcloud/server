#!/usr/bin/env bash

echo "APP_NAME: $APP_NAME"
echo "BRANCH: $BRANCH"

chown -R www-data:www-data /var/www/html/data

su www-data -c "
	php occ config:system:set default_language --value 'en'
	php occ config:system:set force_language --value 'en'
	php occ config:system:set default_locale --value 'en_US'
	php occ config:system:set force_locale --value 'en_US'
	php occ config:system:set enforce_theme --value light
	php occ app:enable $APP_NAME
	php occ app:list
"
