#!/bin/bash
for resource in calendar contacts core files media gallery settings
do
tx set --auto-local -r owncloud.$resource "<lang>/$resource.po" --source-lang en --source-file templates/$resource.pot --execute
done
