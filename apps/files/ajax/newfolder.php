<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

// Get the params
$dir = isset($_POST['dir']) ? $_POST['dir'] : '';
$foldername = isset($_POST['foldername']) ? $_POST['foldername'] : '';

$l10n = \OC::$server->getL10N('files');

$result = array(
	'success' 	=> false,
	'data'		=> NULL
	);

if(trim($foldername) === '') {
	$result['data'] = array('message' => $l10n->t('Folder name cannot be empty.'));
	OCP\JSON::error($result);
	exit();
}

if(!OCP\Util::isValidFileName($foldername)) {
	$result['data'] = array('message' => (string)$l10n->t("Invalid name, '\\', '/', '<', '>', ':', '\"', '|', '?' and '*' are not allowed."));
	OCP\JSON::error($result);
	exit();
}

if (!\OC\Files\Filesystem::file_exists($dir . '/')) {
	$result['data'] = array('message' => (string)$l10n->t(
			'The target folder has been moved or deleted.'),
			'code' => 'targetnotfound'
		);
	OCP\JSON::error($result);
	exit();
}

$target = $dir . '/' . $foldername;
		
if (\OC\Files\Filesystem::file_exists($target)) {
	$result['data'] = array('message' => $l10n->t(
			'The name %s is already used in the folder %s. Please choose a different name.',
			array($foldername, $dir))
		);
	OCP\JSON::error($result);
	exit();
}

if(\OC\Files\Filesystem::mkdir($target)) {
	if ( $dir !== '/') {
		$path = $dir.'/'.$foldername;
	} else {
		$path = '/'.$foldername;
	}
	$meta = \OC\Files\Filesystem::getFileInfo($path);
	$meta['type'] = 'dir'; // missing ?!
	OCP\JSON::success(array('data' => \OCA\Files\Helper::formatFileInfo($meta)));
	exit();
}

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the folder') )));
