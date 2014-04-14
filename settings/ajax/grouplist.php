<?php
/**
 * ownCloud
 *
 * @author Arthur Schiwon
 * @copyright 2014 Arthur Schiwon <blizzz@owncloud.com>
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
if (isset($_GET['pattern']) && !empty($_GET['pattern'])) {
	$pattern = $_GET['pattern'];
} else {
	$pattern = '';
}
$groups = array();
$adminGroups = array();
$groupManager = \OC_Group::getManager();

$accessiblegroups = $groupManager->search($pattern);
if (!OC_User::isAdminUser(OC_User::getUser())) {
	$subadminGroups = OC_SubAdmin::getSubAdminsGroups(OC_User::getUser());
	$accessiblegroups = array_intersect($accessiblegroups, $subadminGroups);
}

$sortGroupsIndex = 0;
$sortGroupsKeys = array();
$sortAdminGroupsIndex = 0;
$sortAdminGroupsKeys = array();

foreach($accessiblegroups as $group) {
	$gid = $group->getGID();
	$usersInGroup = OC_Group::usersInGroup($gid, '');
	if (!OC_User::isAdminUser($gid)) {
		$groups[] = array(
			'id' => str_replace(' ','', $gid ),
			'name' => $gid,
			'usercount' => count($usersInGroup),
		);
		$sortGroupsKeys[$sortGroupsIndex] = count($usersInGroup);
		$sortGroupsIndex++;
	} else {
		$adminGroup[] =  array(
			'id' => str_replace(' ','', $gid ),
			'name' => $gid,
			'usercount' => count($usersInGroup)
		);
		$sortAdminGroupsKeys[$sortAdminGroupsIndex] = count($usersInGroup);
		$sortAdminGroupsIndex++;
	}
}

if(!empty($groups)) {
	array_multisort($sortGroupsKeys, SORT_DESC, $groups);
}
if(!empty($adminGroup)) {
	array_multisort($sortAdminGroupsKeys, SORT_DESC, $adminGroup);
}

OC_JSON::success(
	array('data' => array('adminGroups' => $adminGroups, 'groups' => $groups)));
