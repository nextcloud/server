#!/bin/bash
#
# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/

git submodule update --init

# Ensure Apache modules and vhost for Nextcloud
sudo a2enmod headers rewrite env >/dev/null 2>&1 || true
if ! grep -q "<Directory /var/www/html>" /etc/apache2/sites-available/000-default.conf; then
    sudo bash -lc 'printf "\n<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n" >> /etc/apache2/sites-available/000-default.conf'
fi
sudo apache2ctl configtest || true

# Codespace config
cp .devcontainer/codespace.config.php config/codespace.config.php

# VSCode debugger profile
mkdir -p .vscode && cp .devcontainer/launch.json .vscode/launch.json

# Onetime installation setup
if [[ ! $(sudo -u ${APACHE_RUN_USER} php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "Running NC installation"
    sudo -u ${APACHE_RUN_USER} php occ maintenance:install \
        --verbose \
        --database=pgsql \
        --database-name=postgres \
        --database-host=127.0.0.1 \
        --database-port=5432 \
        --database-user=postgres \
        --database-pass=postgres \
        --admin-user admin \
        --admin-pass admin
fi

sudo service apache2 restart
