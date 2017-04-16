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
# instance from the point of view of a real user, in a Drone step.
#
# The acceptance tests are written in Behat so, besides running the tests, this
# script installs Behat, its dependencies, and some related packages in the
# "vendor" subdirectory of the acceptance tests. The acceptance tests also use
# the Selenium server to control a web browser, and they require a Nextcloud
# server to be available, so this script waits for the Selenium server and the
# Nextcloud server (both provided in their own Drone service) to be ready before
# running the tests.

# Exit immediately on errors.
set -o errexit

# Ensure working directory is script directory, as some actions (like installing
# Behat through Composer or running Behat) expect that.
cd "$(dirname $0)"

SCENARIO_TO_RUN=$1

composer install

# Although Behat documentation states that using the BEHAT_PARAMS environment
# variable "You can set any value for any option that is available in a
# behat.yml file" this is currently not true for the constructor parameters of
# contexts (see https://github.com/Behat/Behat/issues/983). Thus, the default
# "behat.yml" configuration file has to be adjusted to provide the appropriate
# parameters for NextcloudTestServerContext.
ORIGINAL="\
        - NextcloudTestServerContext"
REPLACEMENT="\
        - NextcloudTestServerContext:\n\
            nextcloudTestServerHelper: NextcloudTestServerDroneHelper\n\
            nextcloudTestServerHelperParameters:\n\
              - $NEXTCLOUD_SERVER_CONTROL_PORT"
sed "s/$ORIGINAL/$REPLACEMENT/" config/behat.yml > config/behat-drone.yml

# Both the Selenium server and the Nextcloud server control should be ready by
# now, as Composer typically takes way longer to execute than their startup
# (which is done in parallel in Drone services), but just in case.

echo "Waiting for Selenium"
timeout 60s bash -c "while ! curl 127.0.0.1:4444 >/dev/null 2>&1; do sleep 1; done"

# This just checks if it can connect to the port in which the Nextcloud server
# control should be listening on.
NEXTCLOUD_SERVER_CONTROL_PORT="12345"
PHP_CHECK_NEXTCLOUD_SERVER="\
if ((\\\$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) { exit(1); } \
if (socket_connect(\\\$socket, \\\"127.0.0.1\\\", \\\"$NEXTCLOUD_SERVER_CONTROL_PORT\\\") === false) { exit(1); } \
socket_close(\\\$socket);"

echo "Waiting for Nextcloud server control"
timeout 60s bash -c "while ! php -r \"$PHP_CHECK_NEXTCLOUD_SERVER\" >/dev/null 2>&1; do sleep 1; done"

vendor/bin/behat --config=config/behat-drone.yml $SCENARIO_TO_RUN
