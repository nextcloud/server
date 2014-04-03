<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
\OC_Util::checkLoggedIn();

if(!\OC_App::isEnabled('files_trashbin')){
	exit;
}

$file = array_key_exists('file', $_GET) ? (string) $_GET['file'] : '';
$maxX = array_key_exists('x', $_GET) ? (int) $_GET['x'] : '44';
$maxY = array_key_exists('y', $_GET) ? (int) $_GET['y'] : '44';
$scalingUp = array_key_exists('scalingup', $_GET) ? (bool) $_GET['scalingup'] : true;

if($file === '') {
	\OC_Response::setStatus(400); //400 Bad Request
	\OC_Log::write('core-preview', 'No file parameter was passed', \OC_Log::DEBUG);
	exit;
}

if($maxX === 0 || $maxY === 0) {
	\OC_Response::setStatus(400); //400 Bad Request
	\OC_Log::write('core-preview', 'x and/or y set to 0', \OC_Log::DEBUG);
	exit;
}

try{
	$preview = new \OC\Preview(\OC_User::getUser(), 'files_trashbin/files', $file);
	$view = new \OC\Files\View('/'.\OC_User::getUser(). '/files_trashbin/files');
	if ($view->is_dir($file)) {
		$mimetype = 'httpd/unix-directory';
	} else {
		$pathInfo = pathinfo(ltrim($file, '/'));
		$fileName = $pathInfo['basename'];
		// if in root dir
		if ($pathInfo['dirname'] === '.') {
			// cut off the .d* suffix
			$i = strrpos($fileName, '.');
			if ($i !== false) {
				$fileName = substr($fileName, 0, $i);
			}
		}
		$mimetype = \OC_Helper::getFileNameMimeType($fileName);
	}
	$preview->setMimetype($mimetype);
	$preview->setMaxX($maxX);
	$preview->setMaxY($maxY);
	$preview->setScalingUp($scalingUp);

	$preview->showPreview();
}catch(\Exception $e) {
	\OC_Response::setStatus(500);
	\OC_Log::write('core', $e->getmessage(), \OC_Log::DEBUG);
}
