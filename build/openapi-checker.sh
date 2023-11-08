#!/usr/bin/env bash

for path in core/openapi.json apps/*/openapi.json; do
	composer exec generate-spec "$(dirname "$path")" "$path" || exit 1
done

files="$(git diff --name-only)"
changed=false
for file in $files; do
    if [[ $file == *"openapi.json" ]]; then
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
