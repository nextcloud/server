<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lennart Rosam <hello@takuto.de>
 * @author Morris Jobke <hey@morrisjobke.de>
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

$installedVersion = \OC::$server->getConfig()->getAppValue('user_ldap', 'installed_version');


if (
	version_compare($installedVersion, '0.5.2', '<') || // stable8
	(version_compare($installedVersion, '0.5.99', '>') && version_compare($installedVersion, '0.6.1.1', '<')) || // stable8.1
	(version_compare($installedVersion, '0.6.99', '>') && version_compare($installedVersion, '0.7.1', '<')) // stable8.2
) {
	\OC::$server->getConfig()->setAppValue('user_ldap', 'enforce_home_folder_naming_rule', false);
}

if(version_compare($installedVersion, '0.6.2', '<')) {
	// Remove LDAP case insensitive setting from DB as it is no longer beeing used.
	$helper = new \OCA\user_ldap\lib\Helper();
	$prefixes = $helper->getServerConfigurationPrefixes();

	foreach($prefixes as $prefix) {
		\OC::$server->getConfig()->deleteAppValue('user_ldap', $prefix . "ldap_nocase");
	}
}

OCP\Backgroundjob::registerJob('OCA\user_ldap\lib\Jobs');
OCP\Backgroundjob::registerJob('\OCA\User_LDAP\Jobs\CleanUp');
