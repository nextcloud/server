<?php

\OC::$server->getSession()->close();

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');

// If a directory token is sent along check if public upload is permitted.
// If not, check the login.
// If no token is sent along, rely on login only

$allowedPermissions = \OCP\Constants::PERMISSION_ALL;
$errorCode = null;

$l = \OC::$server->getL10N('files');
if (empty($_POST['dirToken'])) {
	// The standard case, files are uploaded through logged in users :)
	OCP\JSON::checkLoggedIn();
	$dir = isset($_POST['dir']) ? $_POST['dir'] : "";
	if (!$dir || empty($dir) || $dir === false) {
		OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
		die();
	}
} else {
	// TODO: ideally this code should be in files_sharing/ajax/upload.php
	// and the upload/file transfer code needs to be refactored into a utility method
	// that could be used there

	\OC_User::setIncognitoMode(true);

	// return only read permissions for public upload
	$allowedPermissions = \OCP\Constants::PERMISSION_READ;
	$publicDirectory = !empty($_POST['subdir']) ? $_POST['subdir'] : '/';

	$linkItem = OCP\Share::getShareByToken($_POST['dirToken']);
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
			. ini_get('upload_max_filesize'),
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
foreach ($files['size'] as $size) {
	$totalSize += $size;
}
if ($maxUploadFileSize >= 0 and $totalSize > $maxUploadFileSize) {
	OCP\JSON::error(array('data' => array('message' => $l->t('Not enough storage available'),
		'uploadMaxFilesize' => $maxUploadFileSize,
		'maxHumanFilesize' => $maxHumanFileSize)));
	exit();
}

$result = array();
if (strpos($dir, '..') === false) {
	$fileCount = count($files['name']);
	for ($i = 0; $i < $fileCount; $i++) {

		if (isset($_POST['resolution'])) {
			$resolution = $_POST['resolution'];
		} else {
			$resolution = null;
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
						$data['permissions'] = $meta['permissions'] & $allowedPermissions;
						$data['directory'] = $returnedDir;
						$result[] = $data;
					}

				} else {
					$error = $l->t('Upload failed. Could not find uploaded file');
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
				$data['permissions'] = $meta['permissions'] & $allowedPermissions;
				$data['directory'] = $returnedDir;
				$result[] = $data;
			}
		}
	}
} else {
	$error = $l->t('Invalid directory.');
}

if ($error === false) {
	OCP\JSON::encodedPrint($result);
} else {
	OCP\JSON::error(array(array('data' => array_merge(array('message' => $error, 'code' => $errorCode), $storageStats))));
}
