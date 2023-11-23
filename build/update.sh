#!/bin/bash
# Update Nextcloud server and apps from latest git stable28
# For local development environment
# Use from Nextcloud server folder with `./build/update.sh`

# Update server
printf "\n\033[1m${PWD##*/}\033[0m\n"
git checkout stable28
git pull --quiet -p
git --no-pager log -3 --pretty=format:"%h %Cblue%ar%x09%an %Creset%s"
printf "\n"
git branch --merged stable28 | grep -v "stable28$" | xargs git branch -d
git submodule update --init

# Update apps
source ./build/update-apps.sh
