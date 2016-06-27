#!/usr/bin/env bash

set -e

find . -name \*.php -not -path './vendor/*' -exec php -l "{}" \;
