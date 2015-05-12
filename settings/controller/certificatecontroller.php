<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\ICertificateManager;
use OCP\IL10N;
use OCP\IRequest;

/**
 * @package OC\Settings\Controller
 */
class CertificateController extends Controller {
	/** @var ICertificateManager */
	private $certificateManager;
	/** @var IL10N */
	private $l10n;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ICertificateManager $certificateManager
	 * @param IL10N $l10n
	 */
	public function __construct($appName,
								IRequest $request,
								ICertificateManager $certificateManager,
								IL10N $l10n) {
		parent::__construct($appName, $request);
		$this->certificateManager = $certificateManager;
		$this->l10n = $l10n;
	}

	/**
	 * Add a new personal root certificate to the users' trust store
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @return array
	 */
	public function addPersonalRootCertificate() {
		$file = $this->request->getUploadedFile('rootcert_import');
		if(empty($file)) {
			return new DataResponse(['message' => 'No file uploaded'], Http::STATUS_UNPROCESSABLE_ENTITY);
		}

		try {
			$certificate = $this->certificateManager->addCertificate(file_get_contents($file['tmp_name']), $file['name']);
			return new DataResponse([
				'name' => $certificate->getName(),
				'commonName' => $certificate->getCommonName(),
				'organization' => $certificate->getOrganization(),
				'validFrom' => $certificate->getIssueDate()->getTimestamp(),
				'validTill' => $certificate->getExpireDate()->getTimestamp(),
				'validFromString' => $this->l10n->l('date', $certificate->getIssueDate()),
				'validTillString' => $this->l10n->l('date', $certificate->getExpireDate()),
				'issuer' => $certificate->getIssuerName(),
				'issuerOrganization' => $certificate->getIssuerOrganization(),
			]);
		} catch (\Exception $e) {
			return new DataResponse('An error occurred.', Http::STATUS_UNPROCESSABLE_ENTITY);
		}
	}

	/**
	 * Removes a personal root certificate from the users' trust store
	 *
	 * @NoAdminRequired
	 * @NoSubadminRequired
	 * @param string $certificateIdentifier
	 * @return DataResponse
	 */
	public function removePersonalRootCertificate($certificateIdentifier) {
		$this->certificateManager->removeCertificate($certificateIdentifier);
		return new DataResponse();
	}

}
