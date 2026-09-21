#!/bin/bash
#
# SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Fail loudly. Without this, a failure here leaves a half-configured codespace
# that looks like it started correctly: Apache serves, but Nextcloud is not
# installed, so the browser shows the database setup wizard instead.
set -euo pipefail

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd "$DIR"

# Nextcloud does not boot without 3rdparty, and Codespaces does not clone
# submodules for us.
git submodule update --init

if [ ! -f 3rdparty/autoload.php ]; then
    echo "error: 3rdparty is empty after 'git submodule update --init'." >&2
    echo "       Nextcloud cannot start without it. Re-run this script once" >&2
    echo "       the network is available." >&2
    exit 1
fi

# Codespace config
cp .devcontainer/codespace.config.php config/codespace.config.php

# VSCode debugger profile
mkdir -p .vscode && cp .devcontainer/launch.json .vscode/launch.json

# The db service has no healthcheck, and compose only guarantees that the
# container was started, not that Postgres finished initialising. On a first
# create that takes a few seconds, and maintenance:install fails if it gets
# there first.
# Checked with PHP rather than pg_isready: the image installs php8.4-pgsql but
# not postgresql-client, so pg_isready is not available here.
wait_for_database() {
    local attempt
    for attempt in $(seq 1 30); do
        if php -r 'exit(@pg_connect("host=127.0.0.1 port=5432 user=postgres password=postgres dbname=postgres connect_timeout=2") ? 0 : 1);'; then
            return 0
        fi
        echo "Waiting for the database to accept connections ($attempt/30)"
        sleep 2
    done
    return 1
}

if ! wait_for_database; then
    echo "error: the database did not become available within 60 seconds." >&2
    exit 1
fi

# Onetime installation setup
if [[ ! $(sudo -u "${APACHE_RUN_USER}" php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "Running NC installation"
    sudo -u "${APACHE_RUN_USER}" php occ maintenance:install \
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

# Confirm the instance is actually usable before handing it over, so a failed
# install is reported here rather than discovered in the browser.
if [[ ! $(sudo -u "${APACHE_RUN_USER}" php occ status) =~ installed:[[:space:]]*true ]]; then
    echo "error: Nextcloud is still not installed after running maintenance:install." >&2
    exit 1
fi

sudo service apache2 restart
