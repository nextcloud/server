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

if($filename == '') {
	OCP\JSON::error(array("data" => array( "message" => "Empty Filename" )));
	exit();
}
if(strpos($filename, '/')!==false) {
	OCP\JSON::error(array("data" => array( "message" => "Invalid Filename" )));
	exit();
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
					if($progress>$lastsize) {//limit the number or messages send
						$eventSource->send('progress', $progress);
					}
					$lastsize=$progress;
				}
			}
			break;
	}
}

if($source) {
	if(substr($source, 0, 8)!='https://' and substr($source, 0, 7)!='http://') {
		OCP\JSON::error(array("data" => array( "message" => "Not a valid source" )));
		exit();
	}

	$contextArray = array(
		'http' => array(
			'timeout' => 10,
			'follow_location' => false, // Do not follow the location since we can't limit the protocol
		),
		'ssl' => array(
			'disable_compression' => true
		)
	);
	$ctx = stream_context_create($contextArray, array('notification' =>'progress'));
	$sourceStream=fopen($source, 'rb', false, $ctx);
	$target=$dir.'/'.$filename;
	$result=\OC\Files\Filesystem::file_put_contents($target, $sourceStream);
	if($result) {
		$meta = \OC\Files\Filesystem::getFileInfo($target);
		$mime=$meta['mimetype'];
		$id = $meta['fileid'];
		$eventSource->send('success', array('mime'=>$mime, 'size'=>\OC\Files\Filesystem::filesize($target), 'id' => $id));
	} else {
		$eventSource->send('error', "Error while downloading ".$source. ' to '.$target);
	}
	$eventSource->close();
	exit();
} else {
	if($content) {
		if(\OC\Files\Filesystem::file_put_contents($dir.'/'.$filename, $content)) {
			$meta = \OC\Files\Filesystem::getFileInfo($dir.'/'.$filename);
			$id = $meta['fileid'];
			$mime = $meta['mimetype'];
			OCP\JSON::success(array("data" => array('mime'=>$mime, 'content'=>$content, 'id' => $id)));
			exit();
		}
	}elseif(\OC\Files\Filesystem::touch($dir . '/' . $filename)) {
		$meta = \OC\Files\Filesystem::getFileInfo($dir.'/'.$filename);
		$id = $meta['fileid'];
		$mime = $meta['mimetype'];
		OCP\JSON::success(array("data" => array('mime'=>$mime, 'content'=>$content, 'id' => $id)));
		exit();
	}
}


OCP\JSON::error(array("data" => array( "message" => "Error when creating the file" )));
