#!/bin/sh

# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

if [ -d "dist" ]; then
	missing=''
	for app in apps/*; do
		if git check-ignore "$app" -q ; then
			echo "ℹ️  Ignoring non shipped app: $app"
			continue
		fi

		grep "directory name=\"$app\"" psalm.xml 2>&1 > /dev/null
		if [ $? -ne 0 ]; then
			missing="$missing  - $app\n"
		fi
	done

	if [ "$missing"  = "" ]; then
		echo "✅ All apps will be linted by psalm"
	else
		echo "❌ Following apps are not setup for linting using psalm:"
		echo -e "$missing"
		exit 1
	fi
else
	echo "⚠️ This script needs to be executed from the root of the repository"
	exit 1
fi

