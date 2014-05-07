<?php

// Init owncloud
global $eventSource;

if(!OC_User::isLoggedIn()) {
	exit;
}

\OC::$session->close();

// Get the params
$dir = isset( $_REQUEST['dir'] ) ? '/'.trim($_REQUEST['dir'], '/\\') : '';
$filename = isset( $_REQUEST['filename'] ) ? trim($_REQUEST['filename'], '/\\') : '';
$content = isset( $_REQUEST['content'] ) ? $_REQUEST['content'] : '';
$source = isset( $_REQUEST['source'] ) ? trim($_REQUEST['source'], '/\\') : '';

if($source) {
	$eventSource=new OC_EventSource();
} else {
	OC_JSON::callCheck();
}

function progress($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
	static $filesize = 0;
	static $lastsize = 0;
	global $eventSource;

	switch($notification_code) {
		case STREAM_NOTIFY_FILE_SIZE_IS:
			$filesize = $bytes_max;
			break;

		case STREAM_NOTIFY_PROGRESS:
			if ($bytes_transferred > 0) {
				if (!isset($filesize)) {
				} else {
					$progress = (int)(($bytes_transferred/$filesize)*100);
					if($progress>$lastsize) { //limit the number or messages send
						$eventSource->send('progress', $progress);
					}
					$lastsize=$progress;
				}
			}
			break;
	}
}

$l10n = \OC_L10n::get('files');

$result = array(
	'success' 	=> false,
	'data'		=> NULL
);
$trimmedFileName = trim($filename);

if($trimmedFileName === '') {
	$result['data'] = array('message' => (string)$l10n->t('File name cannot be empty.'));
	OCP\JSON::error($result);
	exit();
}
if($trimmedFileName === '.' || $trimmedFileName === '..') {
	$result['data'] = array('message' => (string)$l10n->t('"%s" is an invalid file name.', $trimmedFileName));
	OCP\JSON::error($result);
	exit();
}

if(!OCP\Util::isValidFileName($filename)) {
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

//TODO why is stripslashes used on foldername in newfolder.php but not here?
$target = $dir.'/'.$filename;

if (\OC\Files\Filesystem::file_exists($target)) {
	$result['data'] = array('message' => (string)$l10n->t(
			'The name %s is already used in the folder %s. Please choose a different name.',
			array($filename, $dir))
		);
	OCP\JSON::error($result);
	exit();
}

if($source) {
	if(substr($source, 0, 8)!='https://' and substr($source, 0, 7)!='http://') {
		OCP\JSON::error(array('data' => array('message' => $l10n->t('Not a valid source'))));
		exit();
	}

	if (!ini_get('allow_url_fopen')) {
		$eventSource->send('error', array('message' => $l10n->t('Server is not allowed to open URLs, please check the server configuration')));
		$eventSource->close();
		exit();
	}

	$ctx = stream_context_create(null, array('notification' =>'progress'));
	$sourceStream=@fopen($source, 'rb', false, $ctx);
	$result = 0;
	if (is_resource($sourceStream)) {
		$result=\OC\Files\Filesystem::file_put_contents($target, $sourceStream);
	}
	if($result) {
		$meta = \OC\Files\Filesystem::getFileInfo($target);
		$data = \OCA\Files\Helper::formatFileInfo($meta);
		$eventSource->send('success', $data);
	} else {
		$eventSource->send('error', array('message' => $l10n->t('Error while downloading %s to %s', array($source, $target))));
	}
	if (is_resource($sourceStream)) {
		fclose($sourceStream);
	}
	$eventSource->close();
	exit();
} else {
	$success = false;
	if (!$content) {
		$templateManager = OC_Helper::getFileTemplateManager();
		$mimeType = OC_Helper::getMimetypeDetector()->detectPath($target);
		$content = $templateManager->getTemplate($mimeType);
	}

	if($content) {
		$success = \OC\Files\Filesystem::file_put_contents($target, $content);
	} else {
		$success = \OC\Files\Filesystem::touch($target);
	}

	if($success) {
		$meta = \OC\Files\Filesystem::getFileInfo($target);
		OCP\JSON::success(array('data' => \OCA\Files\Helper::formatFileInfo($meta)));
		exit();
	}
}

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the file') )));
