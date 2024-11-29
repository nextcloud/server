<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controller
 */
class ExternalSharesController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private \OCA\Files_Sharing\External\Manager $externalManager,
		private IClientService $clientService,
		private IConfig $config,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function index() {
		return new JSONResponse($this->externalManager->getOpenShares());
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function create($id) {
		$this->externalManager->acceptShare($id);
		return new JSONResponse();
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 *
	 * @param integer $id
	 * @return JSONResponse
	 */
	#[NoAdminRequired]
	public function destroy($id) {
		$this->externalManager->declineShare($id);
		return new JSONResponse();
	}

	/**
	 * Test whether the specified remote is accessible
	 *
	 * @param string $remote
	 * @param bool $checkVersion
	 * @return bool
	 */
	protected function testUrl($remote, $checkVersion = false) {
		try {
			$client = $this->clientService->newClient();
			$response = json_decode($client->get(
				$remote,
				[
					'timeout' => 3,
					'connect_timeout' => 3,
					'verify' => !$this->config->getSystemValueBool('sharing.federation.allowSelfSignedCertificates', false),
				]
			)->getBody());

			if ($checkVersion) {
				return !empty($response->version) && version_compare($response->version, '7.0.0', '>=');
			} else {
				return is_object($response);
			}
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @NoOutgoingFederatedSharingRequired
	 * @NoIncomingFederatedSharingRequired
	 *
	 * @param string $remote
	 * @return DataResponse
	 * @AnonRateThrottle(limit=5, period=120)
	 */
	#[PublicPage]
	public function testRemote($remote) {
		if (preg_match('%[!#$&\'()*+,;=?@[\]]%', $remote)) {
			return new DataResponse(false);
		}

		if (
			$this->testUrl('https://' . $remote . '/ocm-provider/') ||
			$this->testUrl('https://' . $remote . '/ocm-provider/index.php') ||
			$this->testUrl('https://' . $remote . '/status.php', true)
		) {
			return new DataResponse('https');
		} elseif (
			$this->testUrl('http://' . $remote . '/ocm-provider/') ||
			$this->testUrl('http://' . $remote . '/ocm-provider/index.php') ||
			$this->testUrl('http://' . $remote . '/status.php', true)
		) {
			return new DataResponse('http');
		} else {
			return new DataResponse(false);
		}
	}
}
