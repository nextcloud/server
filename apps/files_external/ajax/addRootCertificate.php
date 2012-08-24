<?php

OCP\JSON::checkAppEnabled('files_external');

$view = \OCP\Files::getStorage("files_external");
$from = $_FILES['rootcert_import']['tmp_name'];
$path = \OCP\Config::getSystemValue('datadirectory').$view->getAbsolutePath("").'uploads/';
if(!file_exists($path)) mkdir($path,0700,true);
$to = $path.$_FILES['rootcert_import']['name'];
move_uploaded_file($from, $to);

//check if it is a PEM certificate, otherwise convert it if possible
$fh = fopen($to, 'r');
$data = fread($fh, filesize($to));
fclose($fh);
if (!strpos($data, 'BEGIN CERTIFICATE')) {
	$pem = chunk_split(base64_encode($data), 64, "\n");
	$pem = "-----BEGIN CERTIFICATE-----\n".$pem."-----END CERTIFICATE-----\n";
	$fh = fopen($to, 'w');
	fwrite($fh, $pem);
	fclose($fh);
}

OC_Mount_Config::createCertificateBundle();

header("Location: settings/personal.php");
exit;
?>