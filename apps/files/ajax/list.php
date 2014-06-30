<?php

try {
	OCP\JSON::checkLoggedIn();
	\OC::$session->close();

	// Load the files
	$dir = isset($_GET['dir']) ? $_GET['dir'] : '';
	$dir = \OC\Files\Filesystem::normalizePath($dir);
	$dirInfo = \OC\Files\Filesystem::getFileInfo($dir);
	if (!$dirInfo || !$dirInfo->getType() === 'dir') {
		header("HTTP/1.0 404 Not Found");
		exit();
	}

	$data = array();
	$baseUrl = OCP\Util::linkTo('files', 'index.php') . '?dir=';

	$permissions = $dirInfo->getPermissions();

	$sortAttribute = isset($_GET['sort']) ? $_GET['sort'] : 'name';
	$sortDirection = isset($_GET['sortdirection']) ? ($_GET['sortdirection'] === 'desc') : false;

	// make filelist

	$files = \OCA\Files\Helper::getFiles($dir, $sortAttribute, $sortDirection);
	$data['directory'] = $dir;
	$data['files'] = \OCA\Files\Helper::formatFileInfos($files);
	$data['permissions'] = $permissions;

	OCP\JSON::success(array('data' => $data));
} catch (\OCP\Files\StorageNotAvailableException $e) {
	$l = OC_L10N::get('files');
	OCP\JSON::error(array(
		'data' => array(
			'exception' => '\OCP\Files\StorageNotAvailableException',
			'message' => $l->t('Storage not available')
		)
	));
}
