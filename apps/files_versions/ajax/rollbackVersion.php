<?php

OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::callCheck();

$userDirectory = "/".OCP\USER::getUser()."/files";

$file = $_GET['file'];
$revision=(int)$_GET['revision'];

if(OCA_Versions\Storage::rollback( $file, $revision )) {
	OCP\JSON::success(array("data" => array( "revision" => $revision, "file" => $file )));
}else{
	OCP\JSON::error(array("data" => array( "message" => "Could not revert:" . $file )));
}

