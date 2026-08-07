#!/usr/bin/env bash
set -euxo pipefail

composer psalm
composer psalm:ocp
composer psalm:ncu
composer psalm:strict
composer rector:strict
composer openapi
./build/autoloaderchecker.sh
