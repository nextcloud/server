#!/bin/sh
set -e

ESLINT=$(which eslint || true)
if [ -z "$ESLINT" ]; then
    echo "Can't find command \"eslint\" in $PATH"
    exit 1
fi

echo Checking scripts with $ESLINT ...
find -name "*.js" -print0 | xargs -0 $ESLINT
