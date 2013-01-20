<?php

// Init owncloud


OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

// Get data
$dir = stripslashes($_POST["dir"]);
$files = isset($_POST["file"]) ? stripslashes($_POST["file"]) : stripslashes($_POST["files"]);

$files = json_decode($files);
$filesWithError = '';

$success = true;

//Now delete
foreach ($files as $file) {
	if (($dir === '' && $file === 'Shared') || !\OC\Files\Filesystem::unlink($dir . '/' . $file)) {
		$filesWithError .= $file . "\n";
		$success = false;
	}
}

// updated max file size after upload
$l=new OC_L10N('files');
$maxUploadFilesize=OCP\Util::maxUploadFilesize($dir);
$maxHumanFilesize=OCP\Util::humanFileSize($maxUploadFilesize);
$maxHumanFilesize=$l->t('Upload') . ' max. '.$maxHumanFilesize;

if($success) {
	OCP\JSON::success(array("data" => array( "dir" => $dir, "files" => $files,
		'uploadMaxFilesize'=>$maxUploadFilesize,
		'maxHumanFilesize'=>$maxHumanFilesize
	)));
} else {
	OCP\JSON::error(array("data" => array( "message" => "Could not delete:\n" . $filesWithError,
		'uploadMaxFilesize'=>$maxUploadFilesize,
		'maxHumanFilesize'=>$maxHumanFilesize
	)));
}
