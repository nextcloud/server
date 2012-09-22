<?php

OCP\JSON::checkAppEnabled('files_external');

$fh = fopen($_FILES['rootcert_import']['tmp_name'], 'r');
$data = fread($fh, filesize($_FILES['rootcert_import']['tmp_name']));
fclose($fh);

$view = new \OC_FilesystemView('/'.\OCP\User::getUser().'/files_external/uploads');
if (!$view->file_exists('')) $view->mkdir('');

//check if it is a PEM certificate, otherwise convert it if possible
if (!strpos($data, 'BEGIN CERTIFICATE')) {
	$data = chunk_split(base64_encode($data), 64, "\n");
	$data = "-----BEGIN CERTIFICATE-----\n".$data."-----END CERTIFICATE-----\n";
}

$view->file_put_contents($_FILES['rootcert_import']['name'], $data);

OC_Mount_Config::createCertificateBundle();

header("Location: settings/personal.php");
exit;
