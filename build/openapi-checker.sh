#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

specs=()
for path in core apps/*; do
	if [ ! -f "$path/.noopenapi" ] && [[ "$(git check-ignore "$path")" != "$path" ]]; then
		composer exec generate-spec "$path" "$path/openapi.json" || exit 1
		if [[ "$(basename "$path")" != "core" ]]; then
			if [ -f "$path/openapi-full.json" ]; then
				specs+=("$path/openapi-full.json")
			else
				specs+=("$path/openapi.json")
			fi;
		fi;
	fi
done

composer exec merge-specs -- \
	--core core/openapi-full.json \
	--merged openapi.json \
	"${specs[@]}"

files="$(git ls-files --exclude-standard --modified --others)"
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
