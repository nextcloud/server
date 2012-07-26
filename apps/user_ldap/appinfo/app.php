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

$connector = new OCA\user_ldap\lib\Connection('user_ldap');
$userBackend  = new OCA\user_ldap\USER_LDAP();
$userBackend->setConnector($connector);
$groupBackend = new OCA\user_ldap\GROUP_LDAP();
$groupBackend->setConnector($connector);

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
