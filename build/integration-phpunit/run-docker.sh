#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

# Helper script to run the PHPUnit API integration tests on a fresh Nextcloud
# server through Docker. It is the counterpart of build/integration/run-docker.sh
# for the Behat suite and follows the same approach: the root directory of the
# Nextcloud server is copied into a container, ignoring the configuration and
# data of the local instance, a new installation is performed inside the
# container, and the tests are run against it. The container is removed when the
# script exits.
#
# Only SQLite is supported; see build/integration/run-docker.sh for a variant
# that can start a MySQL or PostgreSQL container as well.
#
# The script requires the "docker" command to be available. Being able to talk
# to the Docker daemon is equivalent to root access on the host, so run this
# only as a trusted user:
# https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface
#
# Any arguments are forwarded to PHPUnit, for example:
#   ./run-docker.sh --filter testUserLimitIsCountedSeparatelyFromAnonymousLimit

set -o errexit

# Switches between mktemp on GNU/Linux and gmktemp on macOS.
function setOperatingSystemAbstractionVariables() {
	case "$OSTYPE" in
		darwin*)
			if [ "$(which gmktemp)" == "" ]; then
				echo "Please install coreutils (brew install coreutils)"
				exit 1
			fi

			MKTEMP=gmktemp
			;;
		linux*)
			MKTEMP=mktemp
			;;
		*)
			echo "Operating system ($OSTYPE) not supported"
			exit 1
			;;
	esac
}

function cleanUp() {
	# Disable (yes, "+" disables) exiting immediately on errors to ensure that
	# all the cleanup commands are executed.
	set +o errexit

	echo "Cleaning up"

	if [ -n "$NEXTCLOUD_LOCAL_TAR" ] && [ -f "$NEXTCLOUD_LOCAL_TAR" ]; then
		rm "$NEXTCLOUD_LOCAL_TAR"
	fi

	# The name filter must be specified as "^/XXX$" to get an exact match.
	if [ -n "$(docker ps --all --quiet --filter name="^/$NEXTCLOUD_LOCAL_CONTAINER$")" ]; then
		echo "Removing Docker container $NEXTCLOUD_LOCAL_CONTAINER"
		docker rm --volumes --force $NEXTCLOUD_LOCAL_CONTAINER
	fi
}

trap cleanUp EXIT

# Ensure working directory is script directory, as copying the Git working
# directory to the container expects that.
cd "$(dirname $0)"

# "--image XXX" option can be provided to set the Docker image to use to run
# the integration tests (one of the "nextcloud/continuous-integration-phpX.Y:latest" images).
NEXTCLOUD_LOCAL_IMAGE="ghcr.io/nextcloud/continuous-integration-php8.3:latest"
if [ "$1" = "--image" ]; then
	NEXTCLOUD_LOCAL_IMAGE=$2

	shift 2
fi

NEXTCLOUD_LOCAL_CONTAINER=nextcloud-local-test-integration-phpunit

setOperatingSystemAbstractionVariables

echo "Starting the Nextcloud container"
# The image exits immediately if no command is given, so a Bash session is
# created to prevent that.
docker run \
	--volume composer_cache:/root/.composer \
	--detach --name=$NEXTCLOUD_LOCAL_CONTAINER --interactive --tty $NEXTCLOUD_LOCAL_IMAGE bash

# Use the $TMPDIR or, if not set, fall back to /tmp.
NEXTCLOUD_LOCAL_TAR="$($MKTEMP --tmpdir="${TMPDIR:-/tmp}" --suffix=.tar nextcloud-local-XXXXXXXXXX)"

echo "Copying local Git working directory of Nextcloud to the container"
tar --create --file="$NEXTCLOUD_LOCAL_TAR" \
	--exclude=".git" \
	--exclude="./config/config.php" \
	--exclude="./config/*.config.php" \
	--exclude="./data" \
	--exclude="./data-autotest" \
	--exclude="./tests" \
	--exclude="node_modules" \
	--directory=../../ \
	.

docker exec $NEXTCLOUD_LOCAL_CONTAINER mkdir /nextcloud
docker cp - $NEXTCLOUD_LOCAL_CONTAINER:/nextcloud/ < "$NEXTCLOUD_LOCAL_TAR"
docker exec $NEXTCLOUD_LOCAL_CONTAINER chown -R www-data:www-data /nextcloud

docker exec -w /nextcloud $NEXTCLOUD_LOCAL_CONTAINER composer install

echo "Installing Nextcloud in the container"
docker exec --user www-data --workdir /nextcloud $NEXTCLOUD_LOCAL_CONTAINER php occ maintenance:install --admin-pass=admin

echo "Running tests"
# --tty is needed to get colourful output.
docker exec --tty --user www-data -w "/nextcloud/build/integration-phpunit" $NEXTCLOUD_LOCAL_CONTAINER bash -c "./run.sh $*"
