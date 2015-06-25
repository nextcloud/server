<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
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
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

// Get data
$dir = isset($_POST['dir']) ? (string)$_POST['dir'] : '';
$file = isset($_POST['file']) ? (string)$_POST['file'] : '';
$target = isset($_POST['target']) ? rawurldecode((string)$_POST['target']) : '';

$l = \OC::$server->getL10N('files');

if(\OC\Files\Filesystem::file_exists($target . '/' . $file)) {
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s - File with this name already exists", array($file)) )));
	exit;
}

if ($target != '' || strtolower($file) != 'shared') {
	$targetFile = \OC\Files\Filesystem::normalizePath($target . '/' . $file);
	$sourceFile = \OC\Files\Filesystem::normalizePath($dir . '/' . $file);
	try {
		if(\OC\Files\Filesystem::rename($sourceFile, $targetFile)) {
			OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $file )));
		} else {
			OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
		}
	} catch (\OCP\Files\NotPermittedException $e) {
		OCP\JSON::error(array("data" => array( "message" => $l->t("Permission denied") )));
	} catch (\Exception $e) {
		OCP\JSON::error(array("data" => array( "message" => $e->getMessage())));
	}
}else{
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not move %s", array($file)) )));
}
