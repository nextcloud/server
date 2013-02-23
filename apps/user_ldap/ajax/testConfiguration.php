<?php

/**
 * ownCloud - user_ldap
 *
 * @author Arthur Schiwon
 * @copyright 2012, 2013 Arthur Schiwon blizzz@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$l=OC_L10N::get('user_ldap');

$connection = new \OCA\user_ldap\lib\Connection('', null);
if($connection->setConfiguration($_POST)) {
	//Configuration is okay
	if($connection->bind()) {
		OCP\JSON::success(array('message'
			=> $l->t('The configuration is valid and the connection could be established!')));
	} else {
		OCP\JSON::error(array('message'
			=> $l->t('The configuration is valid, but the Bind failed. Please check the server settings and credentials.')));
	}
} else {
	OCP\JSON::error(array('message'
		=> $l->t('The configuration is invalid. Please look in the ownCloud log for further details.')));
}
