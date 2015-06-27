<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
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
// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

// Get the params
$dir = isset($_POST['dir']) ? (string)$_POST['dir'] : '';
$folderName = isset($_POST['foldername']) ?(string) $_POST['foldername'] : '';

$l10n = \OC::$server->getL10N('files');

$result = array(
	'success' 	=> false,
	'data'		=> NULL
	);

try {
	\OC\Files\Filesystem::getView()->verifyPath($dir, $folderName);
} catch (\OCP\Files\InvalidPathException $ex) {
	$result['data'] = [
		'message' => $ex->getMessage()];
	OCP\JSON::error($result);
	return;
}

if (!\OC\Files\Filesystem::file_exists($dir . '/')) {
	$result['data'] = array('message' => (string)$l10n->t(
			'The target folder has been moved or deleted.'),
			'code' => 'targetnotfound'
		);
	OCP\JSON::error($result);
	exit();
}

$target = $dir . '/' . $folderName;
		
if (\OC\Files\Filesystem::file_exists($target)) {
	$result['data'] = array('message' => $l10n->t(
			'The name %s is already used in the folder %s. Please choose a different name.',
			array($folderName, $dir))
		);
	OCP\JSON::error($result);
	exit();
}

try {
	if(\OC\Files\Filesystem::mkdir($target)) {
		if ( $dir !== '/') {
			$path = $dir.'/'.$folderName;
		} else {
			$path = '/'.$folderName;
		}
		$meta = \OC\Files\Filesystem::getFileInfo($path);
		$meta['type'] = 'dir'; // missing ?!
		OCP\JSON::success(array('data' => \OCA\Files\Helper::formatFileInfo($meta)));
		exit();
	}
} catch (\Exception $e) {
	$result = [
		'success' => false,
		'data' => [
			'message' => $e->getMessage()
		]
	];
	OCP\JSON::error($result);
	exit();
}

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the folder') )));
