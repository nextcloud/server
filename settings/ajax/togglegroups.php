<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
OC_JSON::checkSubAdminUser();
OCP\JSON::callCheck();

$success = true;
$username = (string)$_POST['username'];
$group = (string)$_POST['group'];

if($username === OC_User::getUser() && $group === "admin" &&  OC_User::isAdminUser($username)) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Admins can\'t remove themself from the admin group'))));
	exit();
}

$isUserAccessible = false;
$isGroupAccessible = false;
$currentUserObject = \OC::$server->getUserSession()->getUser();
$targetUserObject = \OC::$server->getUserManager()->get($username);
$targetGroupObject = \OC::$server->getGroupManager()->get($group);
if($targetUserObject !== null && $currentUserObject !== null && $targetGroupObject !== null) {
	$isUserAccessible = \OC::$server->getGroupManager()->getSubAdmin()->isUserAccessible($currentUserObject, $targetUserObject);
	$isGroupAccessible = \OC::$server->getGroupManager()->getSubAdmin()->isSubAdminofGroup($currentUserObject, $targetGroupObject);
}

if(!OC_User::isAdminUser(OC_User::getUser())
	&& (!$isUserAccessible
		|| !$isGroupAccessible)) {
	$l = \OC::$server->getL10N('core');
	OC_JSON::error(array( 'data' => array( 'message' => $l->t('Authentication error') )));
	exit();
}

if(!OC_Group::groupExists($group)) {
	OC_Group::createGroup($group);
}

$l = \OC::$server->getL10N('settings');

$error = $l->t("Unable to add user to group %s", $group);
$action = "add";

// Toggle group
if( OC_Group::inGroup( $username, $group )) {
	$action = "remove";
	$error = $l->t("Unable to remove user from group %s", $group);
	$success = OC_Group::removeFromGroup( $username, $group );
	$usersInGroup=OC_Group::usersInGroup($group);
}
else{
	$success = OC_Group::addToGroup( $username, $group );
}

// Return Success story
if( $success ) {
	OC_JSON::success(array("data" => array( "username" => $username, "action" => $action, "groupname" => $group )));
}
else{
	OC_JSON::error(array("data" => array( "message" => $error )));
}
