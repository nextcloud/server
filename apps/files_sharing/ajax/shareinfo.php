<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAppEnabled('files_sharing');

if (!isset($_GET['t'])) {
	\OC_Response::setStatus(400); //400 Bad Request
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
		return new \OCA\Files_Sharing\ReadOnlyWrapper(array('storage' => $storage));
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
