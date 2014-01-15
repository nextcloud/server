<?php

// Firefox and Konqueror tries to download application/json for me.  --Arthur
OCP\JSON::setContentTypeHeader('text/plain');

// If a directory token is sent along check if public upload is permitted.
// If not, check the login.
// If no token is sent along, rely on login only

$allowedPermissions = OCP\PERMISSION_ALL;

$l = OC_L10N::get('files');
if (empty($_POST['dirToken'])) {
	// The standard case, files are uploaded through logged in users :)
	OCP\JSON::checkLoggedIn();
	$dir = isset($_POST['dir']) ? $_POST['dir'] : "";
	if (!$dir || empty($dir) || $dir === false) {
		OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
		die();
	}
} else {
	// return only read permissions for public upload
	$allowedPermissions = OCP\PERMISSION_READ;

	$linkItem = OCP\Share::getShareByToken($_POST['dirToken']);
	if ($linkItem === false) {
		OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Invalid Token')))));
		die();
	}

	if (!($linkItem['permissions'] & OCP\PERMISSION_CREATE)) {
		OCP\JSON::checkLoggedIn();
	} else {
		// resolve reshares
		$rootLinkItem = OCP\Share::resolveReShare($linkItem);

		// Setup FS with owner
		OC_Util::tearDownFS();
		OC_Util::setupFS($rootLinkItem['uid_owner']);

		// The token defines the target directory (security reasons)
		$path = \OC\Files\Filesystem::getPath($linkItem['file_source']);
		$dir = sprintf(
			"/%s/%s",
			$path,
			isset($_POST['subdir']) ? $_POST['subdir'] : ''
		);

		if (!$dir || empty($dir) || $dir === false) {
			OCP\JSON::error(array('data' => array_merge(array('message' => $l->t('Unable to set upload directory.')))));
			die();
		}
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
		OCP\JSON::error(array('data' => array_merge(array('message' => $errors[$error]), $storageStats)));
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
		// $path needs to be normalized - this failed within drag'n'drop upload to a sub-folder
		if (isset($_POST['resolution']) && $_POST['resolution']==='autorename') {
			// append a number in brackets like 'filename (2).ext'
			$target = OCP\Files::buildNotExistingFileName(stripslashes($dir), $files['name'][$i]);
		} else {
			$target = \OC\Files\Filesystem::normalizePath(stripslashes($dir).'/'.$files['name'][$i]);
		}
		
		if ( ! \OC\Files\Filesystem::file_exists($target)
			|| (isset($_POST['resolution']) && $_POST['resolution']==='replace')
		) {
			// upload and overwrite file
			try
			{
				if (is_uploaded_file($files['tmp_name'][$i]) and \OC\Files\Filesystem::fromTmpFile($files['tmp_name'][$i], $target)) {

					// updated max file size after upload
					$storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);

					$meta = \OC\Files\Filesystem::getFileInfo($target);
					if ($meta === false) {
						$error = $l->t('Upload failed. Could not get file info.');
					} else {
						$result[] = array('status' => 'success',
							'mime' => $meta['mimetype'],
							'mtime' => $meta['mtime'],
							'size' => $meta['size'],
							'id' => $meta['fileid'],
							'name' => basename($target),
							'etag' => $meta['etag'],
							'originalname' => $files['tmp_name'][$i],
							'uploadMaxFilesize' => $maxUploadFileSize,
							'maxHumanFilesize' => $maxHumanFileSize,
							'permissions' => $meta['permissions'] & $allowedPermissions
						);
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
				$result[] = array('status' => 'existserror',
					'mime' => $meta['mimetype'],
					'mtime' => $meta['mtime'],
					'size' => $meta['size'],
					'id' => $meta['fileid'],
					'name' => basename($target),
					'etag' => $meta['etag'],
					'originalname' => $files['tmp_name'][$i],
					'uploadMaxFilesize' => $maxUploadFileSize,
					'maxHumanFilesize' => $maxHumanFileSize,
					'permissions' => $meta['permissions'] & $allowedPermissions
				);
			}
		}
	}
} else {
	$error = $l->t('Invalid directory.');
}

if ($error === false) {
	OCP\JSON::encodedPrint($result);
	exit();
} else {
	OCP\JSON::error(array(array('data' => array_merge(array('message' => $error), $storageStats))));
}
