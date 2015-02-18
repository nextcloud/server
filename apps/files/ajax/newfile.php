<?php

// Init owncloud
global $eventSource;

\OCP\JSON::checkLoggedIn();
\OCP\JSON::callCheck();

\OC::$server->getSession()->close();

// Get the params
$dir = isset( $_REQUEST['dir'] ) ? '/'.trim((string)$_REQUEST['dir'], '/\\') : '';
$fileName = isset( $_REQUEST['filename'] ) ? trim((string)$_REQUEST['filename'], '/\\') : '';

$l10n = \OC::$server->getL10N('files');

$result = array(
	'success' 	=> false,
	'data'		=> NULL
);

try {
	\OC\Files\Filesystem::getView()->verifyPath($dir, $fileName);
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

$target = $dir.'/'.$fileName;

if (\OC\Files\Filesystem::file_exists($target)) {
	$result['data'] = array('message' => (string)$l10n->t(
			'The name %s is already used in the folder %s. Please choose a different name.',
			array($fileName, $dir))
		);
	OCP\JSON::error($result);
	exit();
}

$success = false;
$templateManager = OC_Helper::getFileTemplateManager();
$mimeType = OC_Helper::getMimetypeDetector()->detectPath($target);
$content = $templateManager->getTemplate($mimeType);

if($content) {
	$success = \OC\Files\Filesystem::file_put_contents($target, $content);
} else {
	$success = \OC\Files\Filesystem::touch($target);
}

if($success) {
	$meta = \OC\Files\Filesystem::getFileInfo($target);
	OCP\JSON::success(array('data' => \OCA\Files\Helper::formatFileInfo($meta)));
	return;
}

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the file') )));
