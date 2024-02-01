<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OC\Security\IdentityProof\Manager;
use OCP\AppFramework\Http;
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
	public function getIdentityProof(string $cloudId): DataResponse {
		$userObject = $this->userManager->get($cloudId);

		if ($userObject !== null) {
			$key = $this->keyManager->getKey($userObject);
			$data = [
				'public' => $key->getPublic(),
			];
			return new DataResponse($data);
		}

		return new DataResponse(['User not found'], 404);
	}
}
