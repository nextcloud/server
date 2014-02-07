<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
\OC_Util::checkLoggedIn();

if(!\OC_App::isEnabled('files_versions')){
	exit;
}

$file = array_key_exists('file', $_GET) ? (string) urldecode($_GET['file']) : '';
$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : 44;
$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : 44;
$version = array_key_exists('version', $_GET) ? $_GET['version'] : '';
$scalingUp = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;

if($file === '' && $version === '') {
	\OC_Response::setStatus(400); //400 Bad Request
	\OC_Log::write('versions-preview', 'No file parameter was passed', \OC_Log::DEBUG);
	exit;
}

if($maxX === 0 || $maxY === 0) {
	\OC_Response::setStatus(400); //400 Bad Request
	\OC_Log::write('versions-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
	exit;
}

try {
	list($user, $file) = \OCA\Files_Versions\Storage::getUidAndFilename($file);
	$preview = new \OC\Preview($user, 'files_versions', $file.'.v'.$version);
	$mimetype = \OC_Helper::getFileNameMimeType($file);
	$preview->setMimetype($mimetype);
	$preview->setMaxX($maxX);
	$preview->setMaxY($maxY);
	$preview->setScalingUp($scalingUp);

	$preview->showPreview();
}catch(\Exception $e) {
	\OC_Response::setStatus(500);
	\OC_Log::write('core', $e->getmessage(), \OC_Log::DEBUG);
}
