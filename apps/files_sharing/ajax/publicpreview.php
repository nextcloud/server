<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkAppEnabled('files_sharing');

\OC_User::setIncognitoMode(true);

$file = array_key_exists('file', $_GET) ? (string) $_GET['file'] : '';
$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : '36';
$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : '36';
$scalingUp = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;
$token = array_key_exists('t', $_GET) ? (string) $_GET['t'] : '';
$keepAspect = array_key_exists('a', $_GET) ? true : false;

if($token === ''){
	\OC_Response::setStatus(\OC_Response::STATUS_BAD_REQUEST);
	\OC_Log::write('core-preview', 'No token parameter was passed', \OC_Log::DEBUG);
	exit;
}

$linkedItem = \OCP\Share::getShareByToken($token);
if($linkedItem === false || ($linkedItem['item_type'] !== 'file' && $linkedItem['item_type'] !== 'folder')) {
	\OC_Response::setStatus(\OC_Response::STATUS_NOT_FOUND);
	\OC_Log::write('core-preview', 'Passed token parameter is not valid', \OC_Log::DEBUG);
	exit;
}

if(!isset($linkedItem['uid_owner']) || !isset($linkedItem['file_source'])) {
	\OC_Response::setStatus(\OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OC_Log::write('core-preview', 'Passed token seems to be valid, but it does not contain all necessary information . ("' . $token . '")', \OC_Log::WARN);
	exit;
}

$rootLinkItem = OCP\Share::resolveReShare($linkedItem);
$userId = $rootLinkItem['uid_owner'];

OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
\OC_Util::setupFS($userId);
\OC\Files\Filesystem::initMountPoints($userId);
$view = new \OC\Files\View('/' . $userId . '/files');

$pathId = $linkedItem['file_source'];
$path = $view->getPath($pathId);
$pathInfo = $view->getFileInfo($path);
$sharedFile = null;

if($linkedItem['item_type'] === 'folder') {
	$isValid = \OC\Files\Filesystem::isValidPath($file);
	if(!$isValid) {
		\OC_Response::setStatus(\OC_Response::STATUS_BAD_REQUEST);
		\OC_Log::write('core-preview', 'Passed filename is not valid, might be malicious (file:"' . $file . '";ip:"' . $_SERVER['REMOTE_ADDR'] . '")', \OC_Log::WARN);
		exit;
	}
	$sharedFile = \OC\Files\Filesystem::normalizePath($file);
}

if($linkedItem['item_type'] === 'file') {
	$parent = $pathInfo['parent'];
	$path = $view->getPath($parent);
	$sharedFile = $pathInfo['name'];
}

$path = \OC\Files\Filesystem::normalizePath($path, false);
if(substr($path, 0, 1) === '/') {
	$path = substr($path, 1);
}

if($maxX === 0 || $maxY === 0) {
	\OC_Response::setStatus(\OC_Response::STATUS_BAD_REQUEST);
	\OC_Log::write('core-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
	exit;
}

$root = 'files/' . $path;

try{
	$preview = new \OC\Preview($userId, $root);
	$preview->setFile($sharedFile);
	$preview->setMaxX($maxX);
	$preview->setMaxY($maxY);
	$preview->setScalingUp($scalingUp);
	$preview->setKeepAspect($keepAspect);

	$preview->showPreview();
} catch (\Exception $e) {
	\OC_Response::setStatus(\OC_Response::STATUS_INTERNAL_SERVER_ERROR);
	\OC_Log::write('core', $e->getmessage(), \OC_Log::DEBUG);
}
