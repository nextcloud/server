<?php

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

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the folder') )));
