<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OAuth2\Controller;

use OCA\OAuth2\Db\ClientMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

class LoginRedirectorController extends Controller {
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ClientMapper */
	private $clientMapper;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ClientMapper $clientMapper
	 */
	public function __construct($appName,
								IRequest $request,
								IURLGenerator $urlGenerator,
								ClientMapper $clientMapper) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->clientMapper = $clientMapper;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $client_id
	 * @param string $redirect_uri
	 * @param string $state
	 * @return RedirectResponse
	 */
	public function authorize($client_id,
							  $redirect_uri,
							  $state) {
		$client = $this->clientMapper->getByIdentifier($client_id);

		if($client->getRedirectUri() !== $redirect_uri) {
			throw new \Exception('Redirect URI does not match');
		}

		$targetUrl = $this->urlGenerator->linkToRouteAbsolute(
			'core.ClientFlowLogin.showAuthPickerPage',
			[
				'clientIdentifier' => $client->getClientIdentifier(),
				'oauthState' => $state,
			]
		);
		return new RedirectResponse($targetUrl);
	}
}
