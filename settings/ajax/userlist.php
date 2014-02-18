<?php
/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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
if (isset($_GET['offset'])) {
	$offset = $_GET['offset'];
} else {
	$offset = 0;
}
if (isset($_GET['limit'])) {
	$limit = $_GET['limit'];
} else {
	$limit = 10;
}
$users = array();
$userManager = \OC_User::getManager();
if (OC_User::isAdminUser(OC_User::getUser())) {
	$batch = OC_User::getDisplayNames('', $limit, $offset);
	foreach ($batch as $uid => $displayname) {
		$user = $userManager->get($uid);
		$users[] = array(
			'name' => $uid,
			'displayname' => $displayname,
			'groups' => join(', ', OC_Group::getUserGroups($uid)),
			'subadmin' => join(', ', OC_SubAdmin::getSubAdminsGroups($uid)),
			'quota' => OC_Preferences::getValue($uid, 'files', 'quota', 'default'),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin(),
		);
	}
} else {
	$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$batch = OC_Group::usersInGroups($groups, '', $limit, $offset);
	foreach ($batch as $uid) {
		$user = $userManager->get($uid);
		$users[] = array(
			'name' => $user,
			'displayname' => $user->getDisplayName(),
			'groups' => join(', ', OC_Group::getUserGroups($uid)),
			'quota' => OC_Preferences::getValue($uid, 'files', 'quota', 'default'),
			'storageLocation' => $user->getHome(),
			'lastLogin' => $user->getLastLogin(),
		);
	}
}
OC_JSON::success(array('data' => $users));
