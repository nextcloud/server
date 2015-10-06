<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

OCP\JSON::checkAppEnabled('files_sharing');

if(!isset($_GET['t'])){
	\OC_Response::setStatus(\OC_Response::STATUS_BAD_REQUEST);
	\OCP\Util::writeLog('core-preview', 'No token parameter was passed', \OCP\Util::DEBUG);
	exit;
}

$token = $_GET['t'];

$password = null;
if (isset($_POST['password'])) {
	$password = $_POST['password'];
}

$relativePath = null;
if (isset($_GET['dir'])) {
	$relativePath = $_GET['dir'];
}

$sortAttribute = isset( $_GET['sort'] ) ? $_GET['sort'] : 'name';
$sortDirection = isset( $_GET['sortdirection'] ) ? ($_GET['sortdirection'] === 'desc') : false;

$data = \OCA\Files_Sharing\Helper::setupFromToken($token, $relativePath, $password);

$linkItem = $data['linkItem'];
// Load the files
$dir = $data['realPath'];

$dir = \OC\Files\Filesystem::normalizePath($dir);
if (!\OC\Files\Filesystem::is_dir($dir . '/')) {
	\OC_Response::setStatus(\OC_Response::STATUS_NOT_FOUND);
	\OCP\JSON::error(array('success' => false));
	exit();
}

$data = array();

// make filelist
$files = \OCA\Files\Helper::getFiles($dir, $sortAttribute, $sortDirection);

$formattedFiles = array();
foreach ($files as $file) {
	$entry = \OCA\Files\Helper::formatFileInfo($file);
	// for now
	unset($entry['directory']);
	// do not disclose share owner
	unset($entry['shareOwner']);
	// do not disclose if something is a remote shares
	unset($entry['mountType']);
	unset($entry['icon']);
	$entry['permissions'] = \OCP\Constants::PERMISSION_READ;
	$formattedFiles[] = $entry;
}

$data['directory'] = $relativePath;
$data['files'] = $formattedFiles;
$data['dirToken'] = $linkItem['token'];

$permissions = $linkItem['permissions'];

// if globally disabled
if (\OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_public_upload', 'yes') === 'no') {
	// only allow reading
	$permissions = \OCP\Constants::PERMISSION_READ;
}

$data['permissions'] = $permissions;

OCP\JSON::success(array('data' => $data));
