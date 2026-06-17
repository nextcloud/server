#!/bin/bash
#
# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Set git safe.directory
git config --global --add safe.directory /var/www/html
git config --global --add safe.directory /var/www/html/3rdparty

# Ensure devcontainer user has access to docker socket
if [ -S /var/run/docker.sock ]; then
    sudo chmod 666 /var/run/docker.sock
fi
