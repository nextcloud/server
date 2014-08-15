<?php

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::callCheck();

$l = new OC_L10N('core');

if (!($filename = $_FILES['rootcert_import']['name'])) {
	header('Location:' . OCP\Util::linkToRoute("settings_personal"));
	exit;
}

$fh = fopen($_FILES['rootcert_import']['tmp_name'], 'r');
$data = fread($fh, filesize($_FILES['rootcert_import']['tmp_name']));
fclose($fh);
$filename = $_FILES['rootcert_import']['name'];

$certificateManager = \OC::$server->getCertificateManager();

if ($cert = $certificateManager->addCertificate($data, $filename)) {
	OCP\JSON::success(array(
		'name' => $cert->getName(),
		'commonName' => $cert->getCommonName(),
		'organization' => $cert->getOrganization(),
		'validFrom' => $cert->getIssueDate()->getTimestamp(),
		'validTill' => $cert->getExpireDate()->getTimestamp(),
		'validFromString' => $l->l('date', $cert->getIssueDate()),
		'validTillString' => $l->l('date', $cert->getExpireDate()),
		'issuer' => $cert->getIssuerName(),
		'issuerOrganization' => $cert->getIssuerOrganization()
	));
} else {
	OCP\JSON::error(array('error' => 'Couldn\'t import SSL root certificate, allowed formats: PEM and DER'));
}
