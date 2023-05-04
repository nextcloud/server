#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/

# Set git safe.directory
git config --global --add safe.directory /var/www/html
git config --global --add safe.directory /var/www/html/3rdparty

git submodule update --init

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
