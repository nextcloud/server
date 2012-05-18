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

require_once('apps/user_ldap/lib_ldap.php');
require_once('apps/user_ldap/user_ldap.php');
require_once('apps/user_ldap/group_ldap.php');

OCP\App::registerAdmin('user_ldap','settings');

// register user backend
OC_User::useBackend( 'LDAP' );
OC_Group::useBackend( new OC_GROUP_LDAP() );

// add settings page to navigation
$entry = array(
	'id' => 'user_ldap_settings',
	'order'=>1,
	'href' => OCP\Util::linkTo( 'user_ldap', 'settings.php' ),
	'name' => 'LDAP'
);
