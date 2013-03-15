<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$view = \OCP\Files::getStorage("files_external");
$file = 'uploads/'.ltrim($_POST['cert'], "/\\.");

if ( $view->file_exists($file) ) {
	$view->unlink($file);
	OC_Mount_Config::createCertificateBundle();
}
