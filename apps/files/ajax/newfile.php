<?php

// Init owncloud
global $eventSource;

if(!OC_User::isLoggedIn()) {
	exit;
}

session_write_close();
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

if(trim($filename) === '') {
	$result['data'] = array('message' => $l10n->t('File name cannot be empty.'));
	OCP\JSON::error($result);
	exit();
}

if(strpos($filename, '/') !== false) {
	$result['data'] = array('message' => $l10n->t('File name must not contain "/". Please choose a different name.'));
	OCP\JSON::error($result);
	exit();
}

//TODO why is stripslashes used on foldername in newfolder.php but not here?
$target = $dir.'/'.$filename;

if (\OC\Files\Filesystem::file_exists($target)) {
	$result['data'] = array('message' => $l10n->t(
			'The name %s is already used in the folder %s. Please choose a different name.',
			array($filename, $dir))
		);
	OCP\JSON::error($result);
	exit();
}

if($source) {
	if(substr($source, 0, 8)!='https://' and substr($source, 0, 7)!='http://') {
		OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Not a valid source') )));
		exit();
	}

	$ctx = stream_context_create(null, array('notification' =>'progress'));
	$sourceStream=fopen($source, 'rb', false, $ctx);
	$result=\OC\Files\Filesystem::file_put_contents($target, $sourceStream);
	if($result) {
		$meta = \OC\Files\Filesystem::getFileInfo($target);
		$mime=$meta['mimetype'];
		$id = $meta['fileid'];
		$eventSource->send('success', array('mime'=>$mime, 'size'=>\OC\Files\Filesystem::filesize($target), 'id' => $id, 'etag' => $meta['etag']));
	} else {
		$eventSource->send('error', $l10n->t('Error while downloading %s to %s', array($source, $target)));
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
		$id = $meta['fileid'];
		$mime = $meta['mimetype'];
		$size = $meta['size'];
		OCP\JSON::success(array('data' => array(
			'id' => $id,
			'mime' => $mime,
			'size' => $size,
			'content' => $content,
			'etag' => $meta['etag'],
		)));
		exit();
	}
}

OCP\JSON::error(array('data' => array( 'message' => $l10n->t('Error when creating the file') )));
