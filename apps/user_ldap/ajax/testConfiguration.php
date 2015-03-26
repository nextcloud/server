<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$l = \OC::$server->getL10N('user_ldap');

$ldapWrapper = new OCA\user_ldap\lib\LDAP();
$connection = new \OCA\user_ldap\lib\Connection($ldapWrapper, '', null);
//needs to be true, otherwise it will also fail with an irritating message
$_POST['ldap_configuration_active'] = 1;
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
		=> $l->t('The configuration is invalid. Please have a look at the logs for further details.')));
}
