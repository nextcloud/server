#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: CC0-1.0

# This is a simple helper to execute npm COMMANDs in two directories
# we need this as we cannot use npm workspaces as they break with 2 versions of vue.

COMMAND=""
FRONTEND="$(dirname $0)/frontend"
FRONTEND_LEGACY="$(dirname $0)/frontend-legacy"

build_command() {
	if [ "install" = "$1" ] || [ "ci" = "$1" ]; then
		COMMAND=$@
	elif [ "run" = "$1" ]; then
		COMMAND="run --if-present ${@:2}"
	else
		COMMAND="run --if-present $@"
	fi
}

run_parallel() {
	npx concurrently \
		"cd \"$FRONTEND\" && npm $COMMAND" \
		"cd \"$FRONTEND_LEGACY\" && npm $COMMAND"
}

run_sequentially() {
	echo -e "\e[1;34m>> Running 'npm $COMMAND' for Vue 3 based frontend\e[0m"
	echo
	pushd "$FRONTEND"
	npm $COMMAND
	popd

	echo -e "\e[1;34m>> Running 'npm $COMMAND' for Vue 2 based frontend\e[0m"
	echo
	pushd "$FRONTEND_LEGACY"
	npm $COMMAND
	popd
}


if [ "--parallel" = "$1" ]; then
	build_command ${@:2}
	run_parallel
else
	build_command $@
	run_sequentially
fi
