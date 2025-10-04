#!/bin/bash

# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: CC0-1.0

# This is a simple helper to execute npm commands in two directories
# we need this as we cannot use npm workspaces as they break with 2 versions of vue.

if [ "run" = "$1" ]; then
	command=$@
elif [ "install" = "$1" ] || [ "ci" = "$1" ]; then
	command=$@
else
	command="run $@"
fi

echo -e "\e[1;34m>> Running 'npm $command' for Vue 3 based frontend\e[0m"
echo
pushd $(dirname $0)/frontend
npm $command
popd

echo -e "\e[1;34m>> Running 'npm $command' for Vue 2 based frontend\e[0m"
echo
pushd $(dirname $0)/frontend-legacy
npm $command
popd
