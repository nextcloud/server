#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/
git submodule update --init

# VSCode debugger profile
mkdir -p .vscode && cp .devcontainer/launch.json .vscode/launch.json

# Onetime installation setup
if [[ ! $(sudo -u ${APACHE_RUN_USER} php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "Running NC installation"

    # we could be faced with an externalized, empty config dir
    touch config/CAN_INSTALL

    # this produces the plain config file without any overwriting
    sudo -u ${APACHE_RUN_USER} php occ maintenance:install \
        --verbose \
        --database=pgsql \
        --database-name=postgres \
        --database-host=db \
        --database-port=5432 \
        --database-user=postgres \
        --database-pass=postgres \
        --admin-user admin \
        --admin-pass admin
fi

# Codespace config; do it late to avoid duplication into config.php
# (which removed the conditional code!)
# may consider setting 'config_is_read_only' => true,
cp .devcontainer/codespace.config.php config/codespace.config.php

# add MagentaCLOUD convenience setup
source .devcontainer/nmcsetup.sh

sudo service apache2 restart
