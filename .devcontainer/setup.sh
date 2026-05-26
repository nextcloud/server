#!/bin/bash
#
# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/

git submodule update --init

# Codespace config
cp .devcontainer/codespace.config.php config/codespace.config.php

# Authorization header fix for Apache → PHP
sudo tee /etc/apache2/conf-available/nextcloud-auth-fix.conf > /dev/null <<'EOF'
SetEnvIfNoCase Authorization "(.+)" HTTP_AUTHORIZATION=$1
EOF

sudo a2enconf nextcloud-auth-fix

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
