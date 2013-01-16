<?php

/**
* ownCloud - user_ldap
*
* @author Dominik Schmidt
* @copyright 2011 Dominik Schmidt dev@dominik-schmidt.de
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

OCP\App::registerAdmin('user_ldap', 'settings');

$query = \OCP\DB::prepare('
	SELECT DISTINCT `configkey`
	FROM `*PREFIX*appconfig`
	WHERE `configkey` LIKE ?
');
$serverConnections = $query->execute(array('%ldap_login_filter'))->fetchAll();
if(count($serverConnections) == 1) {
	$prefix = substr($serverConnections[0]['configkey'], 0, strlen($serverConnections[0]['configkey'])- strlen('ldap_login_filter'));
	$connector = new OCA\user_ldap\lib\Connection($prefix);
	$userBackend  = new OCA\user_ldap\USER_LDAP();
	$userBackend->setConnector($connector);
	$groupBackend = new OCA\user_ldap\GROUP_LDAP();
	$groupBackend->setConnector($connector);
} else {
	$prefixes = array();
	foreach($serverConnections as $serverConnection) {
		$prefixes[] = substr($serverConnection['configkey'], 0, strlen($serverConnection['configkey'])- strlen('ldap_login_filter'));
	}
	$userBackend  = new OCA\user_ldap\User_Proxy($prefixes);
	$groupBackend  = new OCA\user_ldap\Group_Proxy($prefixes);
}

// register user backend
OC_User::useBackend($userBackend);
OC_Group::useBackend($groupBackend);

// add settings page to navigation
$entry = array(
	'id' => 'user_ldap_settings',
	'order'=>1,
	'href' => OCP\Util::linkTo( 'user_ldap', 'settings.php' ),
	'name' => 'LDAP'
);

OCP\Backgroundjob::addRegularTask('OCA\user_ldap\lib\Jobs', 'updateGroups');
if(OCP\App::isEnabled('user_webdavauth')) {
	OCP\Util::writeLog('user_ldap', 'user_ldap and user_webdavauth are incompatible. You may experience unexpected behaviour', OCP\Util::WARN);
}
