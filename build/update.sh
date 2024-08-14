#!/bin/bash
#
# SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Update Nextcloud server and apps from latest git stable30
# For local development environment
# Use from Nextcloud server folder with `./build/update.sh`

# Update server
printf "\n\033[1m${PWD##*/}\033[0m\n"
git checkout stable30
git pull --quiet -p
git --no-pager log -3 --pretty=format:"%h %Cblue%ar%x09%an %Creset%s"
printf "\n"
git branch --merged stable30 | grep -v "stable30$" | xargs git branch -d
git submodule update --init

# Update apps
source ./build/update-apps.sh
