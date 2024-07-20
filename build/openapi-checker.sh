#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

for path in core apps/*; do
	if [ ! -f "$path/.noopenapi" ] && [[ "$(git check-ignore "$path")" != "$path" ]]; then
		composer exec generate-spec "$path" "$path/openapi.json" || exit 1
	fi
done

files="$(git diff --name-only)"
changed=false
for file in $files; do
    if [[ $file == *"openapi"*".json" ]]; then
        changed=true
        break
    fi
done

if [ $changed = true ]
then
	git --no-pager diff
    echo "The OpenAPI specifications are not up to date"
    echo "Please run: bash build/openapi-checker.sh"
    echo "And commit the result"
    exit 1
else
    echo "OpenAPI specifications up to date. Carry on"
    exit 0
fi
