<?php

/**
 * ownCloud - Core
 *
 * @author Morris Jobke
 * @author Raghu Nayyar 
 * @copyright 2014 Morris Jobke morris.jobke@gmail.com
 * @copyright 2014 Raghu Nayyar raghu.nayyar.007@gmail.com
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

// This file is repsonsible for the Ajax Request for Group list
// Outputs are Names of Groups and IDs of users which are a part of them

OC_JSON::checkSubAdminUser();

$users = array();
$groupname = array();
$useringroup = array();
$userUid = OC_User::getUser();
$isAdmin = OC_User::isAdminUser($userUid);

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

if ($isAdmin) {
	$groups = OC_Group::getGroups();
	$batch = OC_User::getDisplayNames('', $limit, $offset);
	foreach ($batch as $user) {
		$users['users'][] = array( 'user' => $user );
	}
}
else {
	$groups = OC_SubAdmin::getSubAdminsGroups($userUid);
	$batch = OC_Group::usersInGroups($groups, '', $limit, $offset);
	foreach ($batch as $user) {
		$users['users'][] = array( 'user' => $user );
	}
}

// convert them to the needed format
foreach( $groups as $gid ) {
	$groupname[] = array(
		'id' => str_replace(' ','', $gid ),
		'name' => $gid,
		'useringroup' => OC_Group::usersInGroup($gid, '', $limit, $offset),
		'isAdmin' => !OC_User::isAdminUser($gid),
	);
}

OCP\JSON::success(array('result' => $groupname ));

?>