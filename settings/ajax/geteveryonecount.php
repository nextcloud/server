<?php
/**
 * ownCloud
 *
 * @author Clark Tomlinson
 * @copyright 2014 Clark Tomlinson <clark@owncloud.com>
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

OC_JSON::callCheck();
OC_JSON::checkSubAdminUser();

$userCount = 0;

$currentUser = \OC::$server->getUserSession()->getUser()->getUID();

if (!OC_User::isAdminUser($currentUser)) {
	$groups = OC_SubAdmin::getSubAdminsGroups($currentUser);

	foreach ($groups as $group) {
		$userCount += count(OC_Group::usersInGroup($group));

	}
} else {

	$userCountArray = \OC::$server->getUserManager()->countUsers();

	if (!empty($userCountArray)) {
		foreach ($userCountArray as $classname => $usercount) {
			$userCount += $usercount;
		}
	}
}


OC_JSON::success(array('count' => $userCount));
