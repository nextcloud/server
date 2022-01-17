#!/bin/bash

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

# Helper script to install and configure the Nextcloud server as expected by the
# acceptance tests.
#
# This script is not meant to be called manually; it is called when needed by
# the acceptance tests launchers.

set -o errexit

NEXTCLOUD_SERVER_DOMAIN=""
if [ "$1" = "--nextcloud-server-domain" ]; then
	NEXTCLOUD_SERVER_DOMAIN=$2

	shift 2
fi

php occ maintenance:install --admin-pass=admin

OC_PASS=123456acb php occ user:add --password-from-env user0
OC_PASS=123456acb php occ user:add --password-from-env user1
OC_PASS=123456acb php occ user:add --password-from-env disabledUser
php occ user:disable disabledUser

php occ app:disable dashboard

if [ "$NEXTCLOUD_SERVER_DOMAIN" != "" ]; then
	# Default first trusted domain is "localhost"; replace it with given domain.
	php occ config:system:set trusted_domains 0 --value="$NEXTCLOUD_SERVER_DOMAIN"
fi
