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

# VSCode debugger profile
mkdir -p .vscode && cp .devcontainer/launch.json .vscode/launch.json

# Onetime installation setup
if [[ ! $(sudo -u ${APACHE_RUN_USER} php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "Running NC installation"
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

# Query Docker daemon API via unix socket and write DOCKER_API_VERSION to .bashrc for CLI use
if [ -S /var/run/docker.sock ]; then
    sudo chmod 666 /var/run/docker.sock
    json="$(curl --silent --unix-socket /var/run/docker.sock http://localhost/version || true)"
    api="$(printf '%s' "$json" | php -r '$d=json_decode(stream_get_contents(STDIN), true); echo isset($d["ApiVersion"])?$d["ApiVersion"]:"";')"
    if [ -n "$api" ]; then
        echo "export DOCKER_API_VERSION=$api" >> ~/.bashrc
        echo "Wrote DOCKER_API_VERSION=$api to ~/.bashrc"
    else
        echo "Warning: could not determine Docker API version after ${max_retries} attempts"
    fi
fi

sudo service apache2 restart
