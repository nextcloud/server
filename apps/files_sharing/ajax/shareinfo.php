<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
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

OCP\JSON::checkAppEnabled('files_sharing');

if (!isset($_GET['t'])) {
	\OC_Response::setStatus(400); //400 Bad Request
	exit;
}

$federatedSharingApp = new \OCA\FederatedFileSharing\AppInfo\Application();
$federatedShareProvider = $federatedSharingApp->getFederatedShareProvider();

if ($federatedShareProvider->isOutgoingServer2serverShareEnabled() === false) {
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

/** @var \OCP\Share\IShare $share */
$share = $data['share'];
// Load the files
$path = $data['realPath'];

$isWritable = $share->getPermissions() & (\OCP\Constants::PERMISSION_UPDATE | \OCP\Constants::PERMISSION_CREATE);
if (!$isWritable) {
	// FIXME: should not add storage wrappers outside of preSetup, need to find a better way
	$previousLog = \OC\Files\Filesystem::logWarningWhenAddingStorageWrapper(false);
	\OC\Files\Filesystem::addStorageWrapper('readonly', function ($mountPoint, $storage) {
		return new \OC\Files\Storage\Wrapper\PermissionsMask(array('storage' => $storage, 'mask' => \OCP\Constants::PERMISSION_READ + \OCP\Constants::PERMISSION_SHARE));
	});
	\OC\Files\Filesystem::logWarningWhenAddingStorageWrapper($previousLog);
}

$rootInfo = \OC\Files\Filesystem::getFileInfo($path);
$rootView = new \OC\Files\View('');

if($rootInfo === false || !($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
	OCP\JSON::error(array('data' => 'Share is not readable.'));
	exit();
}

/**
 * @param \OCP\Files\FileInfo $dir
 * @param \OC\Files\View $view
 * @return array
 */
function getChildInfo($dir, $view, $sharePermissions) {
	$children = $view->getDirectoryContent($dir->getPath());
	$result = array();
	foreach ($children as $child) {
		$formatted = \OCA\Files\Helper::formatFileInfo($child);
		if ($child->getType() === 'dir') {
			$formatted['children'] = getChildInfo($child, $view, $sharePermissions);
		}
		$formatted['mtime'] = $formatted['mtime'] / 1000;
		$formatted['permissions'] = $sharePermissions & (int)$formatted['permissions'];
		$result[] = $formatted;
	}
	return $result;
}

$result = \OCA\Files\Helper::formatFileInfo($rootInfo);
$result['mtime'] = $result['mtime'] / 1000;
$result['permissions'] = (int)$result['permissions'] & $share->getPermissions();


if ($rootInfo->getType() === 'dir') {
	$result['children'] = getChildInfo($rootInfo, $rootView, $share->getPermissions());
}

OCP\JSON::success(array('data' => $result));
