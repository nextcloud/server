<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$view = \OCP\Files::getStorage("files_external");
$cert = $_POST['cert'];
$file = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("").'uploads/'.$cert;
unlink($file);
OC_Mount_Config::createCertificateBundle();
