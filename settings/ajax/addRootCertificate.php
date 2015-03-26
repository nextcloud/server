<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
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
