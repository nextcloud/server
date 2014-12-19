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

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\Mapping\GroupMapping;

// Check user and app status
OCP\JSON::checkAdminUser();
OCP\JSON::checkAppEnabled('user_ldap');
OCP\JSON::callCheck();

$subject = $_POST['ldap_clear_mapping'];
$mapping = null;
if($subject === 'user') {
	$mapping = new UserMapping(\OC::$server->getDatabaseConnection());
} else if($subject === 'group') {
	$mapping = new GroupMapping(\OC::$server->getDatabaseConnection());
}
try {
	if(is_null($mapping) || !$mapping->clear()) {
		$l = \OC::$server->getL10N('user_ldap');
		throw new \Exception($l->t('Failed to clear the mappings.'));
	}
	OCP\JSON::success();
} catch (\Exception $e) {
	OCP\JSON::error(array('message' => $e->getMessage()));
}
