<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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
OCP\JSON::checkLoggedIn();
\OC::$server->getSession()->close();

// Load the files
$dir = isset($_GET['dir']) ? (string)$_GET['dir'] : '';
$sortAttribute = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;
$data = array();

// make filelist
try {
	$files = \OCA\Files_Trashbin\Helper::getTrashFiles($dir, \OCP\User::getUser(), $sortAttribute, $sortDirection);
} catch (Exception $e) {
	header("HTTP/1.0 404 Not Found");
	exit();
}

$encodedDir = \OCP\Util::encodePath($dir);

$data['permissions'] = 0;
$data['directory'] = $dir;
$data['files'] = \OCA\Files_Trashbin\Helper::formatFileInfos($files);

OCP\JSON::success(array('data' => $data));

