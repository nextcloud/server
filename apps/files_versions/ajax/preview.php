<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
