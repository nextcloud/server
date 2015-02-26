<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$name = (string)$_POST['cert'];
$certificateManager = \OC::$server->getCertificateManager();
$certificateManager->removeCertificate($name);
