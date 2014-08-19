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
if (OC_User::isAdminUser(OC_User::getUser())) {
	$batch = OC_User::getDisplayNames('', $limit, $offset);
	foreach ($batch as $user => $displayname) {
		$users[] = array(
			'name' => $user,
			'displayname' => $displayname,
			'groups' => OC_Group::getUserGroups($user),
			'subadmin' => OC_SubAdmin::getSubAdminsGroups($user),
			'quota' => OC_Preferences::getValue($user, 'files', 'quota', 'default'));
	}
} else {
	$groups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$batch = OC_Group::usersInGroups($groups, '', $limit, $offset);
	foreach ($batch as $user) {
		$users[] = array(
			'name' => $user,
			'displayname' => OC_User::getDisplayName($user),
			'groups' => OC_Group::getUserGroups($user),
			'quota' => OC_Preferences::getValue($user, 'files', 'quota', 'default'));
	}
}
OC_JSON::success(array('data' => $users));
