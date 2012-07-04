<?php

OCP\JSON::checkAppEnabled('files_external');

$view = \OCP\Files::getStorage("files_external");
$cert = $_POST['cert'];
$file = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("").$cert;
unlink($file);
?>