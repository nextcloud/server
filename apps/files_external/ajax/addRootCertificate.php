<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

if ( ! ($filename = $_FILES['rootcert_import']['name']) ) {
	header("Location: settings/personal.php");
	exit;
}

$fh = fopen($_FILES['rootcert_import']['tmp_name'], 'r');
$data = fread($fh, filesize($_FILES['rootcert_import']['tmp_name']));
fclose($fh);
$filename = $_FILES['rootcert_import']['name'];

$view = new \OC\Files\View('/'.\OCP\User::getUser().'/files_external/uploads');
if (!$view->file_exists('')) {
	$view->mkdir('');
}

$isValid = openssl_pkey_get_public($data);

//maybe it was just the wrong file format, try to convert it...
if ($isValid == false) {
	$data = chunk_split(base64_encode($data), 64, "\n");
	$data = "-----BEGIN CERTIFICATE-----\n".$data."-----END CERTIFICATE-----\n";
	$isValid = openssl_pkey_get_public($data);
}

// add the certificate if it could be verified
if ( $isValid ) {
	$view->file_put_contents($filename, $data);
	OC_Mount_Config::createCertificateBundle();
} else {
	OCP\Util::writeLog('files_external',
			'Couldn\'t import SSL root certificate ('.$filename.'), allowed formats: PEM and DER',
			OCP\Util::WARN);
}

header('Location:' . OCP\Util::linkToRoute( "settings_personal" ));
exit;
