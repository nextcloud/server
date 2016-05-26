<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$username = (string)$_POST['username'];
$group = (string)$_POST['group'];

$subAdminManager = \OC::$server->getGroupManager()->getSubAdmin();
$targetUserObject = \OC::$server->getUserManager()->get($username);
$targetGroupObject = \OC::$server->getGroupManager()->get($group);

$isSubAdminOfGroup = false;
if($targetUserObject !== null && $targetUserObject !== null) {
	$isSubAdminOfGroup = $subAdminManager->isSubAdminofGroup($targetUserObject, $targetGroupObject);
}

// Toggle group
if($isSubAdminOfGroup) {
	$subAdminManager->deleteSubAdmin($targetUserObject, $targetGroupObject);
} else {
	$subAdminManager->createSubAdmin($targetUserObject, $targetGroupObject);
}

OC_JSON::success();
