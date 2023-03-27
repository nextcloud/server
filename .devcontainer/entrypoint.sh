#!/bin/bash

# Set proper permissions and start webserver
chown -R www-data:www-data /var/www/html && service apache2 start

while sleep 1000; do :; done
