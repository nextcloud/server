<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$session->close();


// Get data
$dir = stripslashes($_POST["dir"]);
$allFiles = isset($_POST["allfiles"]) ? $_POST["allfiles"] : false;

// delete all files in dir ?
if ($allFiles === 'true') {
	$files = array();
	$fileList = \OC\Files\Filesystem::getDirectoryContent($dir);
	foreach ($fileList as $fileInfo) {
		$files[] = $fileInfo['name'];
	}
} else {
	$files = isset($_POST["file"]) ? $_POST["file"] : $_POST["files"];
	$files = json_decode($files);
}
$filesWithError = '';

$success = true;

//Now delete
foreach ($files as $file) {
	if (!\OC\Files\Filesystem::unlink($dir . '/' . $file)) {
		$filesWithError .= $file . "\n";
		$success = false;
	}
}

// get array with updated storage stats (e.g. max file size) after upload
$storageStats = \OCA\Files\Helper::buildFileStorageStatistics($dir);

if ($success) {
	OCP\JSON::success(array("data" => array_merge(array("dir" => $dir, "files" => $files), $storageStats)));
} else {
	OCP\JSON::error(array("data" => array_merge(array("message" => "Could not delete:\n" . $filesWithError), $storageStats)));
}
