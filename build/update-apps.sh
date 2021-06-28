#!/bin/bash
# Update Nextcloud apps from latest git stable22
# For local development environment
# Use from Nextcloud server folder with `./build/update-apps.sh`
#
# It automatically:
# - goes through all apps which are not shipped via server
# - shows the app name in bold and uses whitespace for separation
# - changes to stable22 and pulls quietly
# - shows the 3 most recent commits for context
# - removes branches merged into stable22
# - … could even do the build steps if they are consistent for the apps (like `make`)

find apps* -maxdepth 2 -name .git -exec sh -c 'cd {}/../ && printf "\n\033[1m${PWD##*/}\033[0m\n" && git checkout stable22 && git pull --quiet -p && git --no-pager log -3 --pretty=format:"%h %Cblue%ar%x09%an %Creset%s" && printf "\n" && git branch --merged stable22 | grep -v "stable22$" | xargs git branch -d && cd ..' \;
