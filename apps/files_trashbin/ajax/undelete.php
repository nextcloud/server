<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

$files = $_POST['files'];
$dir = '/';
if (isset($_POST['dir'])) {
	$dir = rtrim((string)$_POST['dir'], '/'). '/';
}
$allFiles = false;
if (isset($_POST['allfiles']) && (string)$_POST['allfiles'] === 'true') {
	$allFiles = true;
	$list = array();
	$dirListing = true;
	if ($dir === '' || $dir === '/') {
		$dirListing = false;
	}
	foreach (OCA\Files_Trashbin\Helper::getTrashFiles($dir, \OCP\User::getUser()) as $file) {
		$fileName = $file['name'];
		if (!$dirListing) {
			$fileName .= '.d' . $file['mtime'];
		}
		$list[] = $fileName;
	}
} else {
	$list = json_decode($files);
}

$error = array();
$success = array();

$i = 0;
foreach ($list as $file) {
	$path = $dir . '/' . $file;
	if ($dir === '/') {
		$file = ltrim($file, '/');
		$delimiter = strrpos($file, '.d');
		$filename = substr($file, 0, $delimiter);
		$timestamp =  substr($file, $delimiter+2);
	} else {
		$path_parts = pathinfo($file);
		$filename = $path_parts['basename'];
		$timestamp = null;
	}

	if ( !OCA\Files_Trashbin\Trashbin::restore($path, $filename, $timestamp) ) {
		$error[] = $filename;
		\OCP\Util::writeLog('trashbin', 'can\'t restore ' . $filename, \OCP\Util::ERROR);
	} else {
		$success[$i]['filename'] = $file;
		$success[$i]['timestamp'] = $timestamp;
		$i++;
	}

}

if ( $error ) {
	$filelist = '';
	foreach ( $error as $e ) {
		$filelist .= $e.', ';
	}
	$l = OC::$server->getL10N('files_trashbin');
	$message = $l->t("Couldn't restore %s", array(rtrim($filelist, ', ')));
	OCP\JSON::error(array("data" => array("message" => $message,
										  "success" => $success, "error" => $error)));
} else {
	OCP\JSON::success(array("data" => array("success" => $success)));
}
