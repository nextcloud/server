<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
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

if (!isset($_GET['t'])) {
	\OC_Response::setStatus(400); //400 Bad Request
	exit;
}

if (OCA\Files_Sharing\Helper::isOutgoingServer2serverShareEnabled() === false) {
	\OC_Response::setStatus(404); // 404 not found
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

$data = \OCA\Files_Sharing\Helper::setupFromToken($token, $relativePath, $password);

$linkItem = $data['linkItem'];
// Load the files
$path = $data['realPath'];

$isWritable = $linkItem['permissions'] & (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE);
if (!$isWritable) {
	\OC\Files\Filesystem::addStorageWrapper('readonly', function ($mountPoint, $storage) {
		return new \OC\Files\Storage\Wrapper\PermissionsMask(array('storage' => $storage, 'mask' => \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_SHARE));
	});
}

$rootInfo = \OC\Files\Filesystem::getFileInfo($path);
$rootView = new \OC\Files\View('');

/**
 * @param \OCP\Files\FileInfo $dir
 * @param \OC\Files\View $view
 * @return array
 */
function getChildInfo($dir, $view) {
	$children = $view->getDirectoryContent($dir->getPath());
	$result = array();
	foreach ($children as $child) {
		$formated = \OCA\Files\Helper::formatFileInfo($child);
		if ($child->getType() === 'dir') {
			$formated['children'] = getChildInfo($child, $view);
		}
		$formated['mtime'] = $formated['mtime'] / 1000;
		$result[] = $formated;
	}
	return $result;
}

$result = \OCA\Files\Helper::formatFileInfo($rootInfo);
$result['mtime'] = $result['mtime'] / 1000;
if ($rootInfo->getType() === 'dir') {
	$result['children'] = getChildInfo($rootInfo, $rootView);
}

OCP\JSON::success(array('data' => $result));
