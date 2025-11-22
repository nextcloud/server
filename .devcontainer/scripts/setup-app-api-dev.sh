#!/bin/bash

# Clone sources
git clone https://github.com/nextcloud/app_api.git /var/www/html/apps/app_api

# Enable the app
php /var/www/html/occ app:enable app_api

# Register a default HaRP daemon
echo "Registering default HaRP daemon"
php occ app_api:daemon:register \
    --set-default \
    --net nextclouddev-network \
    --harp \
    --harp_frp_address "appapi-harp:8782" \
    --harp_shared_key "some_very_secure_password" \
    --harp_docker_socket_port 24000 \
    "harp_proxy_docker" \
    "HaRP Proxy (Docker)" \
    "docker-install" \
    "http" \
    "appapi-harp:8780" \
    "http://nextclouddev"