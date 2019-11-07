#!/bin/env bash
URL="https://raw.githubusercontent.com/transifex/transifex/master/transifex/languages/fixtures/all_languages.json"
CMDS="curl jq"
 
for i in $CMDS
do
        # command -v will return >0 when the $i is not found
	command -v $i >/dev/null && continue || { echo "$i command not found."; exit 1; }
done

curl $URL | jq '[.[] | {code: .fields.code, name: .fields.name}]' > locales.json
