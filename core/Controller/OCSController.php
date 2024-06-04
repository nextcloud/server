<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OC\Security\IdentityProof\Manager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

class OCSController extends \OCP\AppFramework\OCSController {
	public function __construct(
		string $appName,
		IRequest $request,
		private CapabilitiesManager $capabilitiesManager,
		private IUserSession $userSession,
		private IUserManager $userManager,
		private Manager $keyManager,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @PublicPage
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[ApiRoute(verb: 'GET', url: '/config', root: '')]
	public function getConfig(): DataResponse {
		$data = [
			'version' => '1.7',
			'website' => 'Nextcloud',
			'host' => $this->request->getServerHost(),
			'contact' => '',
			'ssl' => 'false',
		];

		return new DataResponse($data);
	}

	/**
	 * @PublicPage
	 *
	 * Get the capabilities
	 *
	 * @return DataResponse<Http::STATUS_OK, array{version: array{major: int, minor: int, micro: int, string: string, edition: '', extendedSupport: bool}, capabilities: array<string, mixed>}, array{}>
	 *
	 * 200: Capabilities returned
	 */
	#[ApiRoute(verb: 'GET', url: '/capabilities', root: '/cloud')]
	public function getCapabilities(): DataResponse {
		$result = [];
		[$major, $minor, $micro] = \OCP\Util::getVersion();
		$result['version'] = [
			'major' => (int)$major,
			'minor' => (int)$minor,
			'micro' => (int)$micro,
			'string' => \OC_Util::getVersionString(),
			'edition' => '',
			'extendedSupport' => \OCP\Util::hasExtendedSupport()
		];

		if ($this->userSession->isLoggedIn()) {
			$result['capabilities'] = $this->capabilitiesManager->getCapabilities();
		} else {
			$result['capabilities'] = $this->capabilitiesManager->getCapabilities(true);
		}

		$response = new DataResponse($result);
		$response->setETag(md5(json_encode($result)));
		return $response;
	}

	/**
	 * @PublicPage
	 * @BruteForceProtection(action=login)
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[ApiRoute(verb: 'POST', url: '/check', root: '/person')]
	public function personCheck(string $login = '', string $password = ''): DataResponse {
		if ($login !== '' && $password !== '') {
			if ($this->userManager->checkPassword($login, $password)) {
				return new DataResponse([
					'person' => [
						'personid' => $login
					]
				]);
			}

			$response = new DataResponse([], 102);
			$response->throttle();
			return $response;
		}
		return new DataResponse([], 101);
	}

	/**
	 * @PublicPage
	 */
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	#[ApiRoute(verb: 'GET', url: '/key/{cloudId}', root: '/identityproof')]
	public function getIdentityProof(string $cloudId): DataResponse {
		$userObject = $this->userManager->get($cloudId);

		if ($userObject !== null) {
			$key = $this->keyManager->getKey($userObject);
			$data = [
				'public' => $key->getPublic(),
			];
			return new DataResponse($data);
		}

		return new DataResponse(['Account not found'], 404);
	}
}
