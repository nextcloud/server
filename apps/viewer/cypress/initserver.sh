#!/usr/bin/env bash

echo "APP_NAME: $APP_NAME"
chown -R www-data:www-data /var/www/html/data

su www-data -c "
	php occ config:system:set force_language --value en
	php occ app:enable $APP_NAME
	php occ app:list
"
