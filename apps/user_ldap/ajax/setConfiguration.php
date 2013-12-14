<?php

/**
 * ownCloud - user_ldap
 *
 * @author Arthur Schiwon
 * @copyright 2013 Arthur Schiwon blizzz@owncloud.com
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

$prefix = $_POST['ldap_serverconfig_chooser'];

// Checkboxes are not submitted, when they are unchecked. Set them manually.
// only legacy checkboxes (Advanced and Expert tab) need to be handled here,
// the Wizard-like tabs handle it on their own
$chkboxes = array('ldap_configuration_active', 'ldap_override_main_server',
				  'ldap_nocase', 'ldap_turn_off_cert_check');
foreach($chkboxes as $boxid) {
	if(!isset($_POST[$boxid])) {
		$_POST[$boxid] = 0;
	}
}

$ldapWrapper = new OCA\user_ldap\lib\LDAP();
$connection = new \OCA\user_ldap\lib\Connection($ldapWrapper, $prefix);
$connection->setConfiguration($_POST);
$connection->saveConfiguration();
OCP\JSON::success();
