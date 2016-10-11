#!/bin/sh -e
HERE=$(dirname "$0")
PHP_EXE=hhvm "$HERE/autotest.sh" "$@"
