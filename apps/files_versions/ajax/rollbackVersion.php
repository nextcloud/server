<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::callCheck();

$file = $_GET['file'];
$revision=(int)$_GET['revision'];

if(OCA\Files_Versions\Storage::rollback( $file, $revision )) {
	OCP\JSON::success(array("data" => array( "revision" => $revision, "file" => $file )));
}else{
	$l = \OC::$server->getL10N('files_versions');
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not revert: %s", array($file) ))));
}
