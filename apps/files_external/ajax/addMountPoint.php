<?php
/**
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

if ($_POST['isPersonal'] == 'true') {
	OCP\JSON::checkLoggedIn();
	$isPersonal = true;
} else {
	OCP\JSON::checkAdminUser();
	$isPersonal = false;
}

$mountPoint = (string)$_POST['mountPoint'];
$oldMountPoint = (string)$_POST['oldMountPoint'];
$class = (string)$_POST['class'];
$options = (array)$_POST['classOptions'];
$type = (string)$_POST['mountType'];
$applicable = (string)$_POST['applicable'];

if ($oldMountPoint and $oldMountPoint !== $mountPoint) {
	OC_Mount_Config::removeMountPoint($oldMountPoint, $type, $applicable, $isPersonal);
}

$status = OC_Mount_Config::addMountPoint($mountPoint, $class, $options, $type, $applicable, $isPersonal);
OCP\JSON::success(array('data' => array('message' => $status)));
