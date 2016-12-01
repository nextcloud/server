<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Florian Pritz <bluewind@xinu.at>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Individual IT Services <info@individual-it.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Luke Policinski <lpolicinski@gmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roman Geber <rgeber@owncloudapps.com>
 * @author TheSFReader <TheSFReader@gmail.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
\OC::$server->getSession()->close();

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');

// If a directory token is sent along check if public upload is permitted.
// If not, check the login.
// If no token is sent along, rely on login only

$errorCode = null;
$errorFileName = null;

$l = \OC::$server->getL10N('files');
if (empty($_POST['dirToken'])) {
	// The standard case, files are uploaded through logged in users :)
	OCP\JSON::checkLoggedIn();
	$dir = isset($_POST['dir']) ? (string)$_POST['dir'] : '';
	if (!$dir || empty($dir) || $dir === false) {
		OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
		die();
	}
} else {
	$shareManager = \OC::$server->getShareManager();
	$share = $shareManager->getShareByToken((string)$_POST['dirToken']);

	// TODO: ideally this code should be in files_sharing/ajax/upload.php
	// and the upload/file transfer code needs to be refactored into a utility method
	// that could be used there

	\OC_User::setIncognitoMode(true);

	// If it is a write-only folder no subdirectory can be specified
	$publicDirectory = '';
	if ($share->getPermissions() & \OCP\Constants::PERMISSION_READ) {
		$publicDirectory = !empty($_POST['subdir']) ? (string)$_POST['subdir'] : '/';
	} else {
		$_POST['file_directory'] = '';
	}

	$linkItem = OCP\Share::getShareByToken((string)$_POST['dirToken']);
	if ($linkItem === false) {
		OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Invalid Token')))));
		die();
	}

	if (!($linkItem['permissions'] & \OCP\Constants::PERMISSION_CREATE)) {
		OCP\JSON::checkLoggedIn();
	} else {
		// resolve reshares
		$rootLinkItem = OCP\Share::resolveReShare($linkItem);

		OCP\JSON::checkUserExists($rootLinkItem['uid_owner']);
		// Setup FS with owner
		OC_Util::tearDownFS();
		OC_Util::setupFS($rootLinkItem['uid_owner']);

		// The token defines the target directory (security reasons)
		$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
		if($path === null) {
			OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
			die();
		}
		$dir = sprintf(
			"/%s/%s",
			$path,
			$publicDirectory
		);

		if (!$dir || empty($dir) || $dir === false) {
			OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
			die();
		}

		$dir = rtrim($dir, '/');
	}
}

OCP\JSON::callCheck();

// get array with current storage stats (e.g. max file size)
$storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);

if (!isset($_FILES['files'])) {
	OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('No file was uploaded. Unknown error')), $storageStats)));
	exit();
}

foreach ($_FILES['files']['error'] as $error) {
	if ($error != 0) {
		$errors = array(
			UPLOAD_ERR_OK => $l->t('There is no error, the file uploaded with success'),
			UPLOAD_ERR_INI_SIZE => $l->t('The uploaded file exceeds the upload_max_filesize directive in php.ini: ')
			. OC::$server->getIniWrapper()->getNumeric('upload_max_filesize'),
			UPLOAD_ERR_FORM_SIZE => $l->t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
			UPLOAD_ERR_PARTIAL => $l->t('The uploaded file was only partially uploaded'),
			UPLOAD_ERR_NO_FILE => $l->t('No file was uploaded'),
			UPLOAD_ERR_NO_TMP_DIR => $l->t('Missing a temporary folder'),
			UPLOAD_ERR_CANT_WRITE => $l->t('Failed to write to disk'),
		);
		$errorMessage = $errors[$error];
		\OC::$server->getLogger()->alert("Upload error: $error - $errorMessage", array('app' => 'files'));
		OCP\JSON::error(array('data' => array_merge(array('message' => $errorMessage), $storageStats)));
		exit();
	}
}
$files = $_FILES['files'];

$error = false;

$maxUploadFileSize = $storageStats['uploadMaxFilesize'];
$maxHumanFileSize = OCP\Util::humanFileSize($maxUploadFileSize);

$totalSize = 0;
$isReceivedShare = \OC::$server->getRequest()->getParam('isReceivedShare', false) === 'true';
// defer quota check for received shares
if (!$isReceivedShare && $storageStats['freeSpace'] >= 0) {
	foreach ($files['size'] as $size) {
		$totalSize += $size;
	}
}
if ($maxUploadFileSize >= 0 and $totalSize > $maxUploadFileSize) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Not enough storage available'),
		'uploadMaxFilesize' => $maxUploadFileSize,
		'maxHumanFilesize' => $maxHumanFileSize)));
	exit();
}

$result = array();
if (\OC\Files\Filesystem::isValidPath($dir) === true) {
	$fileCount = count($files['name']);
	for ($i = 0; $i < $fileCount; $i++) {

		if (isset($_POST['resolution'])) {
			$resolution = $_POST['resolution'];
		} else {
			$resolution = null;
		}

		if(isset($_POST['dirToken'])) {
			// If it is a read only share the resolution will always be autorename
			if (!($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
				$resolution = 'autorename';
			}
		}

		// target directory for when uploading folders
		$relativePath = '';
		if(!empty($_POST['file_directory'])) {
			$relativePath = '/'.$_POST['file_directory'];
		}

		// $path needs to be normalized - this failed within drag'n'drop upload to a sub-folder
		if ($resolution === 'autorename') {
			// append a number in brackets like 'filename (2).ext'
			$target = OCP\Files::buildNotExistingFileName($dir . $relativePath, $files['name'][$i]);
		} else {
			$target = \OC\Files\Filesystem::normalizePath($dir . $relativePath.'/'.$files['name'][$i]);
		}

		// relative dir to return to the client
		if (isset($publicDirectory)) {
			// path relative to the public root
			$returnedDir = $publicDirectory . $relativePath;
		} else {
			// full path
			$returnedDir = $dir . $relativePath;
		}
		$returnedDir = \OC\Files\Filesystem::normalizePath($returnedDir);


		$exists = \OC\Files\Filesystem::file_exists($target);
		if ($exists) {
			$updatable = \OC\Files\Filesystem::isUpdatable($target);
		}
		if ( ! $exists || ($updatable && $resolution === 'replace' ) ) {
			// upload and overwrite file
			try
			{
				if (is_uploaded_file($files['tmp_name'][$i]) and \OC\Files\Filesystem::fromTmpFile($files['tmp_name'][$i], $target)) {

					// updated max file size after upload
					$storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);

					$meta = \OC\Files\Filesystem::getFileInfo($target);
					if ($meta === false) {
						$error = $l->t('The target folder has been moved or deleted.');
						$errorCode = 'targetnotfound';
					} else {
						$data = \OCA\Files\Helper::formatFileInfo($meta);
						$data['status'] = 'success';
						$data['originalname'] = $files['name'][$i];
						$data['uploadMaxFilesize'] = $maxUploadFileSize;
						$data['maxHumanFilesize'] = $maxHumanFileSize;
						$data['permissions'] = $meta['permissions'];
						$data['directory'] = $returnedDir;
						$result[] = $data;
					}

				} else {
					$error = $l->t('Upload failed. Could not find uploaded file');
					$errorFileName = $files['name'][$i];
				}
			} catch(Exception $ex) {
				$error = $ex->getMessage();
			}

		} else {
			// file already exists
			$meta = \OC\Files\Filesystem::getFileInfo($target);
			if ($meta === false) {
				$error = $l->t('Upload failed. Could not get file info.');
			} else {
				$data = \OCA\Files\Helper::formatFileInfo($meta);
				if ($updatable) {
					$data['status'] = 'existserror';
				} else {
					$data['status'] = 'readonly';
				}
				$data['originalname'] = $files['name'][$i];
				$data['uploadMaxFilesize'] = $maxUploadFileSize;
				$data['maxHumanFilesize'] = $maxHumanFileSize;
				$data['permissions'] = $meta['permissions'];
				$data['directory'] = $returnedDir;
				$result[] = $data;
			}
		}
	}
} else {
	$error = $l->t('Invalid directory.');
}

if ($error === false) {
	// Do not leak file information if it is a read-only share
	if(isset($_POST['dirToken'])) {
		$shareManager = \OC::$server->getShareManager();
		$share = $shareManager->getShareByToken((string)$_POST['dirToken']);
		if (!($share->getPermissions() & \OCP\Constants::PERMISSION_READ)) {
			$newResults = [];
			foreach($result as $singleResult) {
				$fileName = $singleResult['originalname'];
				$newResults['filename'] = $fileName;
				$newResults['mimetype'] = \OC::$server->getMimeTypeDetector()->detectPath($fileName);
			}
			$result = $newResults;
		}
	}
	OCP\JSON::encodedPrint($result);
} else {
	OCP\JSON::error(array(array('data' => array_merge(array(
		'message' => $error,
		'code' => $errorCode,
		'filename' => $errorFileName
	), $storageStats))));
}
