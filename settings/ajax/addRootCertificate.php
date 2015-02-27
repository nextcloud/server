<?php
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$l = new OC_L10N('core');

if (!isset($_FILES['rootcert_import'])) {
	OCP\JSON::error(array('error' => 'No certificate uploaded'));
	exit;
}

$data = file_get_contents($_FILES['rootcert_import']['tmp_name']);
$filename = basename($_FILES['rootcert_import']['name']);

$certificateManager = \OC::$server->getCertificateManager();

try {
	$cert = $certificateManager->addCertificate($data, $filename);
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
} catch(\Exception $e) {
	OCP\JSON::error(array('error' => 'Couldn\'t import SSL root certificate, allowed formats: PEM and DER'));
}
