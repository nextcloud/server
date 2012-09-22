<?php

// Init owncloud
global $eventSource;

if(!OC_User::isLoggedIn()) {
	exit;
}

session_write_close();

// Get the params
$dir = isset( $_REQUEST['dir'] ) ? stripslashes($_REQUEST['dir']) : '';
$filename = isset( $_REQUEST['filename'] ) ? stripslashes($_REQUEST['filename']) : '';
$content = isset( $_REQUEST['content'] ) ? $_REQUEST['content'] : '';
$source = isset( $_REQUEST['source'] ) ? stripslashes($_REQUEST['source']) : '';

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

	$ctx = stream_context_create(null, array('notification' =>'progress'));
	$sourceStream=fopen($source, 'rb', false, $ctx);
	$target=$dir.'/'.$filename;
	$result=OC_Filesystem::file_put_contents($target, $sourceStream);
	if($result) {
		$mime=OC_Filesystem::getMimetype($target);
		$eventSource->send('success', array('mime'=>$mime, 'size'=>OC_Filesystem::filesize($target)));
	} else {
		$eventSource->send('error', "Error while downloading ".$source. ' to '.$target);
	}
	$eventSource->close();
	exit();
} else {
	if($content) {
		if(OC_Filesystem::file_put_contents($dir.'/'.$filename, $content)) {
			OCP\JSON::success(array("data" => array('content'=>$content)));
			exit();
		}
	}elseif(OC_Files::newFile($dir, $filename, 'file')) {
		OCP\JSON::success(array("data" => array('content'=>$content)));
		exit();
	}
}


OCP\JSON::error(array("data" => array( "message" => "Error when creating the file" )));
