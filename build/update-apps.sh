#!/bin/bash
#
# SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
# Update Nextcloud apps from latest git master
# For local development environment
# Use from Nextcloud server folder with `./build/update-apps.sh`
#
# It automatically:
# - goes through all apps which are not shipped via server
# - shows the app name in bold and uses whitespace for separation
# - changes to master/main and pulls quietly
# - shows the 3 most recent commits for context
# - removes branches merged into master/main
# - … could even do the build steps if they are consistent for the apps (like `make`)

set -euo pipefail

for path in apps*/*/.git; do
	(
		path="$(dirname "$path")"
		cd "$path"
		printf "\n\033[1m${PWD##*/}\033[0m\n"
		branch="$(git remote show origin | sed -n '/HEAD branch/s/.*: //p')"
		git checkout "$branch"
		git pull --quiet -p
		git --no-pager log -3 --pretty=format:"%h %Cblue%ar%x09%an %Creset%s"
		printf "\n"
		git branch --merged "$branch" | grep -v "$branch$" | xargs git branch -d || true
	)
done
