#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/
git submodule update --init

# Codespace config
cp .devcontainer/codespace.config.php config/codespace.config.php

# Set git safe.directory
git config --global --add safe.directory /var/www/html

# Onetime installation setup
if [ ! -f "data/.devcontainer-install-complete" ]; then
    echo "Running NC installation"
    sudo -u www-data php occ maintenance:install \
        --verbose \
        --database=pgsql \
        --database-name=postgres \
        --database-host=127.0.0.1 \
        --database-port=5432 \
        --database-user=postgres \
        --database-pass=postgres \
        --admin-user admin \
        --admin-pass admin && \
    touch "data/.devcontainer-install-complete"
fi

# Clear caches and stuff ...
service apache2 restart
