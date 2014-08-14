<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

if (!($filename = $_FILES['rootcert_import']['name'])) {
	header('Location:' . OCP\Util::linkToRoute("settings_personal"));
	exit;
}

$fh = fopen($_FILES['rootcert_import']['tmp_name'], 'r');
$data = fread($fh, filesize($_FILES['rootcert_import']['tmp_name']));
fclose($fh);
$filename = $_FILES['rootcert_import']['name'];

$certificateManager = \OC::$server->getCertificateManager();

if (!$certificateManager->addCertificate($data, $filename)) {
	OCP\Util::writeLog('files_external',
		'Couldn\'t import SSL root certificate (' . $filename . '), allowed formats: PEM and DER',
		OCP\Util::WARN);
}

header('Location:' . OCP\Util::linkToRoute("settings_personal"));
exit;
