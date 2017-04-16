#!/usr/bin/env bash

# @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
#
# @license GNU AGPL version 3 or any later version
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

# Helper script to run the acceptance tests, which test a running Nextcloud
# instance from the point of view of a real user.
#
# The acceptance tests are written in Behat so, besides running the tests, this
# script installs Behat, its dependencies, and some related packages in the
# "vendor" subdirectory of the acceptance tests. The acceptance tests also use
# the Selenium server to control a web browser, so the Selenium server is also
# launched before the tests start in its own Docker container (it will be
# stopped automatically once the tests end). Finally, the tests expect that a
# Docker image with the Nextcloud installation to be tested is available, so the
# script creates it based on the Nextcloud code from the grandparent directory.
#
# To perform its job, the script requires the "composer" and "docker" commands
# to be available.
#
# The Docker Command Line Interface (the "docker" command) requires special
# permissions to talk to the Docker daemon, and those permissions are typically
# available only to the root user. However, you should NOT run this script as
# root, but as a regular user instead. Please see the Docker documentation to
# find out how to give access to a regular user to the Docker daemon:
# https://docs.docker.com/engine/installation/linux/linux-postinstall/
#
# Note, however, that being able to communicate with the Docker daemon is the
# same as being able to get root privileges for the system. Therefore, you must
# give access to the Docker daemon (and thus run this script as) ONLY to trusted
# and secure users:
# https://docs.docker.com/engine/security/security/#docker-daemon-attack-surface
#
# Finally, take into account that this script will automatically remove the
# Docker containers named "selenium-nextcloud-local-test-acceptance",
# "nextcloud-local-test-acceptance" and
# "nextcloud-local-test-acceptance-[0-9a-f.]*", and the Docker image tagged as
# "nextcloud-local-test-acceptance:latest", even if the script did not create
# them (probably you will not have containers nor images with those names, but
# just in case).

# Installs Behat and its dependencies.
#
# Behat and its dependencies will be installed in the "vendor" subdirectory of
# the directory of the script.
function prepareBehat() {
	echo "Installing Behat and dependencies"
	composer install
}

# Launches the Selenium server in a Docker container.
#
# The acceptance tests use Firefox by default but, unfortunately, Firefox >= 48
# does not provide yet the same level of support as earlier versions for certain
# features related to automated testing. Therefore, the Docker image used is not
# the latest one, but an older version known to work.
#
# The acceptance tests expect the Selenium server to be accessible at
# "127.0.0.1:4444", so the 4444 port of the container is mapped to the 4444 port
# of the host.
#
# The Nextcloud server has to be accessed at "127.0.0.1" by the Selenium server
# (as that is the only trusted domain by default), so the Nextcloud server
# containers have to be connected to the network of the Selenium server
# container (another option would be to connect the Selenium server to the host
# network, but messing with the host network is better avoided if possible). The
# acceptance tests themselves also need access to the Nextcloud server to ensure
# that it is ready before starting each scenario, so the 80 port of the Selenium
# server is mapped to the 80 port of the host (it is not possible to map the
# port in the container that connects to the network of another container).
#
# Besides the Selenium server, the Docker image also provides a VNC server, so
# the 5900 port of the container is also mapped to the 5900 port of the host.
#
# The Docker container started here will be automatically stopped when the
# script exits (see cleanUp). If the Selenium server can not be started then the
# script will be exited immediately with an error state; the most common cause
# for the Selenium server to fail to start is that another server is already
# running in the default port.
#
# As the web browser is run inside the Docker container it is not visible by
# default. However, it can be viewed using VNC (for example,
# "vncviewer 127.0.0.1:5900"); when asked for the password use "secret".
function prepareSelenium() {
	SELENIUM_CONTAINER=selenium-nextcloud-local-test-acceptance

	echo "Starting Selenium server"
	docker run --detach --name=$SELENIUM_CONTAINER --publish 80:80 --publish 4444:4444 --publish 5900:5900 selenium/standalone-firefox-debug:2.53.1-beryllium

	echo "Waiting for Selenium server to be ready"
	if ! timeout 10s bash -c "while ! curl 127.0.0.1:4444 >/dev/null 2>&1; do sleep 1; done"; then
		echo "Could not start Selenium server; running" \
		     "\"docker run --rm --publish 80:80 --publish 4444:4444 --publish 5900:5900 selenium/standalone-firefox-debug:2.53.1-beryllium\"" \
		     "could give you a hint of the problem"

		exit 1
	fi
}

# Creates a Docker image to be used in Behat by NextcloudTestServerContext based
# on the local Nextcloud directory.
#
# NextcloudTestServerContext creates and destroys a Docker container for each
# acceptance test run, and the image that the container is created from must
# provide an installed copy of Nextcloud with certain configuration (like an
# "admin" user with an "admin" password, or local data storage). This function
# creates that Docker image based on the Nextcloud code from the grandparent
# directory, although ignoring any configuration or data that it may provide
# (for example, if that directory was used directly to deploy a Nextcloud
# instance in a web server). As the Nextcloud code is copied to the image
# instead of referenced the original code can be modified while the acceptance
# tests are running without interfering in them.
#
# Besides the Docker image to be used by the acceptance tests, which is removed
# automatically when the script exits, this function creates another image,
# that the other one will be based on, which is not removed when the script
# exits. Building this parent image could be a slow process, so it is kept built
# instead of removing it every time to speed up the launch of the acceptance
# tests.
function prepareDocker() {
	NEXTCLOUD_LOCAL_IMAGE=nextcloud-local-test-acceptance
	NEXTCLOUD_LOCAL_CONTAINER=nextcloud-local-test-acceptance

	# To create the Docker image to be used by the acceptance tests first a
	# parent image is created. This parent image provides a system in which a
	# Nextcloud server could be installed. Then, that parent image is run in a
	# container in which the relevant code from the grandparent directory is
	# copied; once the code is copied, the Nextcloud server is installed and
	# configured as needed inside the container. Finally, the image to be used
	# by the acceptance tests is generated by persisting the container to a new
	# image.
	#
	# The image to be used by the acceptance tests could have been created just
	# with a Dockerfile by adding the relevant code to the build context before
	# starting the build and then using the ADD command in the Dockerfile (plus
	# running the commands to install and configure the server as needed). In
	# fact, standard Docker practices favor the creation of images through
	# Dockerfiles to get a reproducible build. However, in this case I felt that
	# it would go against that reproducible spirit of Dockerfiles, as an
	# additional .tar file would have to be explicitly created each time before
	# building the image, and that file would probably be different between
	# different builds, thus resulting in a different image each time. Therefore
	# I think that the current approach is better suited for this scenario.

	echo "Building Docker parent image"
	docker build --tag $NEXTCLOUD_LOCAL_IMAGE:parent - < docker/nextcloud-local-parent/Dockerfile

	docker run --detach --name=$NEXTCLOUD_LOCAL_CONTAINER $NEXTCLOUD_LOCAL_IMAGE:parent

	# Use the $TMPDIR or, if not set, fall back to /tmp.
	NEXTCLOUD_LOCAL_TAR="$(mktemp --tmpdir="${TMPDIR:-/tmp}" --suffix=.tar nextcloud-local-XXXXXXXXXX)"

	# Setting the user and group of files in the tar would be superfluous, as
	# "docker cp" does not take them into account (the extracted files are set
	# to root).
	echo "Copying local Git working directory of Nextcloud to the container"
	tar --create --file="$NEXTCLOUD_LOCAL_TAR" --exclude=".git" --exclude="./build" --exclude="./config/config.php" --exclude="./data" --exclude="./tests" --directory=../../ .
	tar --append --file="$NEXTCLOUD_LOCAL_TAR" --directory=../../ build/acceptance/installAndConfigureServer.sh

	docker cp - $NEXTCLOUD_LOCAL_CONTAINER:/var/www/html/ < "$NEXTCLOUD_LOCAL_TAR"
	docker exec $NEXTCLOUD_LOCAL_CONTAINER chown -R www-data:www-data /var/www/html/

	echo "Installing Nextcloud in the container"
	docker exec --user www-data $NEXTCLOUD_LOCAL_CONTAINER build/acceptance/installAndConfigureServer.sh

	echo "Creating Docker image to be used in acceptance tests"
	docker commit --message "Nextcloud installed from the local Git working directory" $NEXTCLOUD_LOCAL_CONTAINER $NEXTCLOUD_LOCAL_IMAGE

	# Once the image to be used by the acceptance tests is created the container
	# is no longer needed, so it can be stopped and removed.
	docker stop $NEXTCLOUD_LOCAL_CONTAINER
	# Although the parent Nextcloud image does not define a volume "--volumes"
	# is used anyway just in case any of its ancestor images does.
	docker rm --volumes $NEXTCLOUD_LOCAL_CONTAINER
}

# Removes/stops temporal elements created/started by this script.
function cleanUp() {
	# Disable (yes, "+" disables) exiting immediately on errors to ensure that
	# all the cleanup commands are executed (well, no errors should occur during
	# the cleanup anyway, but just in case).
	set +o errexit

	echo "Cleaning up"

	if [ -f "$NEXTCLOUD_LOCAL_TAR" ]; then
		echo "Removing $NEXTCLOUD_LOCAL_TAR"
	    rm $NEXTCLOUD_LOCAL_TAR
	fi

	# If the script run successfully the container should have already been
	# removed; this is needed only when an error happened.
	# The name filter must be specified as "^/XXX$" to get an exact match; using
	# just "XXX" would match every name that contained "XXX".
	if [ -n "$(docker ps --all --quiet --filter name="^/$NEXTCLOUD_LOCAL_CONTAINER$")" ]; then
		echo "Removing Docker container $NEXTCLOUD_LOCAL_CONTAINER"
		docker rm --volumes --force $NEXTCLOUD_LOCAL_CONTAINER
	fi

	# In case of failure (like calling a method that does not exist on an
	# object) the tests would be aborted without removing the containers created
	# by NextcloudTestServerContext; if that happens those dangling containers
	# are removed here.
	DANGLING_CONTAINERS_CREATED_BY_ACCEPTANCE_TESTS="$(docker ps --all --quiet --filter name="^/$NEXTCLOUD_LOCAL_CONTAINER-[0-9a-f.]*$" --filter ancestor="$NEXTCLOUD_LOCAL_IMAGE:parent")"
	if [ -n "$DANGLING_CONTAINERS_CREATED_BY_ACCEPTANCE_TESTS" ]; then
		echo "Removing Docker containers matching $NEXTCLOUD_LOCAL_CONTAINER-[0-9a-f.]*"
		docker rm --volumes --force $DANGLING_CONTAINERS_CREATED_BY_ACCEPTANCE_TESTS
	fi

	if [ -n "$(docker images --quiet $NEXTCLOUD_LOCAL_IMAGE:latest)" ]; then
		echo "Removing Docker image $NEXTCLOUD_LOCAL_IMAGE:latest"
		docker rmi $NEXTCLOUD_LOCAL_IMAGE:latest
	fi

	if [ -n "$(docker ps --all --quiet --filter name="^/$SELENIUM_CONTAINER$")" ]; then
		echo "Removing Docker container $SELENIUM_CONTAINER"
		docker rm --volumes --force $SELENIUM_CONTAINER
	fi
}

# Exit immediately on errors.
set -o errexit

# Execute cleanUp when the script exits, either normally or due to an error.
trap cleanUp EXIT

# Ensure working directory is script directory, as some actions (like installing
# Behat through Composer or generating the Nextcloud image for Docker) expect
# that.
cd "$(dirname $0)"

# If no parameter is provided to this script all the acceptance tests are run.
SCENARIO_TO_RUN=$1

prepareBehat
prepareSelenium
prepareDocker

echo "Running tests"
vendor/bin/behat $SCENARIO_TO_RUN
